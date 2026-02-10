<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataCorrectionRequest;
use App\Models\FinancialReport;
use App\Models\ProductionLog;
use App\Models\EggProduct;
use App\Models\Expense;
use App\Models\SalesTransaction;
use App\Models\Collectible;
use App\Models\FeedUsageLog;
use App\Models\FarmStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApprovalController extends Controller
{
    // The `index()` method remains unchanged.
    public function index()
    {
        $correctionRequests = DataCorrectionRequest::with('user')
            ->where('status', 'pending')
            ->get()
            ->map(function ($request) {
                // Load related data for expense corrections
                if ($request->request_type === 'Expense Record') {
                    $expense = Expense::find($request->reference_id);
                    $request->related_data = $expense ? [
                        'amount' => $expense->amount,
                        'receipt_number' => $expense->description, // description stores receipt number
                        'category' => $expense->category,
                        'expense_date' => $expense->expense_date,
                        'receipt_image_url' => $expense->receipt_image_url,
                    ] : null;
                    
                    // Add the uploaded receipt image URL if available
                    if ($request->receipt_image_path) {
                        $request->uploaded_receipt_image_url = Storage::url($request->receipt_image_path);
                    }
                }
                
                // Load related data for sales transaction corrections
                if ($request->request_type === 'Sales Transaction') {
                    $salesTransaction = SalesTransaction::with('items.product')->find($request->reference_id);
                    $request->related_data = $salesTransaction ? [
                        'total_amount' => $salesTransaction->total_amount,
                        'customer_name' => $salesTransaction->customer_name,
                        'created_at' => $salesTransaction->created_at,
                        'items' => $salesTransaction->items->map(function ($item) {
                            return [
                                'product_name' => $item->product->name ?? 'N/A',
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                                'subtotal' => $item->quantity * $item->price,
                            ];
                        }),
                    ] : null;
                }
                
                // Load related data for collectibles corrections
                if ($request->request_type === 'Collectibles') {
                    $collectible = Collectible::with('salesTransaction')->find($request->reference_id);
                    $request->related_data = $collectible ? [
                        'customer_name' => $collectible->customer_name,
                        'total_amount' => $collectible->total_amount,
                        'amount_paid' => $collectible->amount_paid,
                        'balance' => $collectible->balance,
                        'status' => $collectible->status,
                        'created_at' => $collectible->created_at,
                    ] : null;
                }

                // Load related data for feed usage corrections
                if ($request->request_type === 'Feed Usage Record') {
                    $feedLog = FeedUsageLog::with('user')->find($request->reference_id);
                    $request->related_data = $feedLog ? [
                        'quantity_kg' => $feedLog->quantity_kg,
                        'notes' => $feedLog->notes,
                        'created_at' => $feedLog->created_at,
                        'recorded_by' => $feedLog->user?->name ?? 'N/A',
                    ] : null;
                }
                
                return $request;
            });
        
        $financialReports = FinancialReport::with('generatedBy')->where('status', 'submitted')->get();
        return inertia('Admin/Approvals', [
            'correctionRequests' => $correctionRequests ?: [],
            'financialReports' => $financialReports ?: [],
        ]);
    }

    public function update(Request $request, $id)
    {
        // Security: Explicit admin check (defense in depth)
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Only administrators can approve or reject requests.');
        }
        
        $request->validate([
            'type' => ['required', 'in:correction,financial'],
            'action' => ['required', 'in:approve,reject'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($request, $id) {
            if ($request->type === 'correction') {
                $correctionRequest = DataCorrectionRequest::findOrFail($id);

                $correctionRequest->reviewed_by = Auth::id();
                $correctionRequest->reviewed_at = now();
                
                if ($request->action === 'approve') {
                    // We now check if the correction was successfully applied
                    if ($this->applyCorrection($correctionRequest)) {
                        $correctionRequest->status = 'approved';
                        // Only add admin notes if provided (for approved requests, notes are optional feedback)
                        if ($request->filled('admin_notes')) {
                            $correctionRequest->admin_notes = $request->admin_notes;
                        }
                    } else {
                        // If it fails, reject the request and leave a note
                        $correctionRequest->status = 'rejected';
                        $correctionRequest->admin_notes = "System could not automatically apply this correction. Please apply manually.";
                    }
                } else {
                    // Rejection - always add admin notes if provided
                    $correctionRequest->status = 'rejected';
                    if ($request->filled('admin_notes')) {
                        $correctionRequest->admin_notes = $request->admin_notes;
                    }
                    // If rejected, delete the uploaded receipt image if it exists
                    if ($correctionRequest->receipt_image_path) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($correctionRequest->receipt_image_path);
                    }
                }
                
                $correctionRequest->save();

            } elseif ($request->type === 'financial') {
                $financialReport = FinancialReport::findOrFail($id);
                
                $financialReport->reviewed_by = Auth::id();
                $financialReport->reviewed_at = now();
                
                if ($request->action === 'approve') {
                    $financialReport->status = 'approved';
                    if ($request->filled('admin_notes')) {
                        $financialReport->admin_notes = $request->admin_notes;
                    }
                } else {
                    $financialReport->status = 'rejected';
                    if ($request->filled('admin_notes')) {
                        $financialReport->admin_notes = $request->admin_notes;
                    }
                }
                
                $financialReport->save();
            }
            
            return true;
        });

        return to_route('admin.approvals.index')->with('success', 'The request has been processed.');
    }

    /**
     * This is the new, fully functional method that performs the actual data update.
     */
    private function applyCorrection(DataCorrectionRequest $request): bool
    {
        switch ($request->request_type) {
            case 'Egg Production Log':
                // Find the original log entry that needs to be changed
                $log = ProductionLog::find($request->reference_id);
                if (!$log) return false; // The original log was deleted

                // Very simple parser: Find the first number in the "proposed correction" string.
                // This assumes the user writes "change quantity to 100".
                preg_match('/(\d+)/', $request->proposed_correction, $matches);
                if (!isset($matches[1])) return false; // Could not find a number in the proposal
                
                $newQuantity = (int) $matches[1];
                $originalQuantity = $log->quantity;
                $quantityDifference = $newQuantity - $originalQuantity;

                // IMPORTANT: Adjust the main inventory in the egg_products table
                $product = EggProduct::find($log->egg_product_id);
                if ($product) {
                    // `increment` can handle negative numbers, so it works for decreases too.
                    $product->increment('stock_quantity', $quantityDifference);
                }

                // Now, update the actual log record
                $log->quantity = $newQuantity;
                $log->save();

                return true; // Success!
            
            case 'Expense Record':
                // Find the original expense entry that needs to be changed
                $expense = Expense::find($request->reference_id);
                if (!$expense) return false; // The original expense was deleted

                // Parse the proposed correction to extract field updates
                // Examples: "change amount to 500", "change receipt number to OR#12345", "change category to Feed"
                $proposal = strtolower($request->proposed_correction);
                
                // Update amount if mentioned
                if (preg_match('/amount\s+to\s+(\d+\.?\d*)/i', $request->proposed_correction, $matches)) {
                    $expense->amount = (float) $matches[1];
                }
                
                // Update receipt number (stored in description field) if mentioned
                // Support variations: "receipt number", "receipt #", "receipt no", "description"
                if (preg_match('/receipt\s*(?:number|#|no\.?)\s+to\s+["\']?([^"\']+)["\']?/i', $request->proposed_correction, $matches)) {
                    $expense->description = trim($matches[1]);
                } elseif (preg_match('/description\s+to\s+["\']?([^"\']+)["\']?/i', $request->proposed_correction, $matches)) {
                    // Also support generic "description" for backward compatibility
                    $expense->description = trim($matches[1]);
                }
                
                // Update category if mentioned
                if (preg_match('/category\s+to\s+["\']?([^"\']+)["\']?/i', $request->proposed_correction, $matches)) {
                    $expense->category = trim($matches[1]);
                }
                
                // Update date if mentioned
                if (preg_match('/date\s+to\s+(\d{4}-\d{2}-\d{2})/i', $request->proposed_correction, $matches)) {
                    $expense->expense_date = $matches[1];
                }
                
                // Update receipt image if a new image was uploaded in the correction request
                if ($request->receipt_image_path) {
                    // Delete old receipt image if it exists
                    if ($expense->receipt_image_path) {
                        Storage::disk('public')->delete($expense->receipt_image_path);
                    }
                    
                    // Move the image from correction-receipts to receipts folder to match normal expense storage
                    $oldPath = $request->receipt_image_path;
                    $newPath = str_replace('correction-receipts/', 'receipts/', $oldPath);
                    
                    // Ensure the receipts directory exists
                    if (Storage::disk('public')->exists($oldPath)) {
                        // Move the file
                        Storage::disk('public')->move($oldPath, $newPath);
                        $expense->receipt_image_path = $newPath;
                    } else {
                        // If file doesn't exist, just use the path as-is (fallback)
                        $expense->receipt_image_path = $request->receipt_image_path;
                    }
                } elseif (preg_match('/receipt\s+image|image\s+to/i', $request->proposed_correction)) {
                    // If user mentioned receipt image in text but didn't upload, the admin should handle manually
                    // This is just a note - the image path won't be updated automatically
                }
                
                $expense->save();
                
                return true; // Success!
            
            case 'Sales Transaction':
                // Find the original sales transaction that needs to be changed
                $salesTransaction = SalesTransaction::with('items.product')->find($request->reference_id);
                if (!$salesTransaction) return false; // The original transaction was deleted

                // Parse the proposed correction format: "sale_item_id:quantity"
                if (!preg_match('/^(\d+):(\d+)$/', $request->proposed_correction, $matches)) {
                    return false; // Invalid format
                }
                
                $saleItemId = (int) $matches[1];
                $newQuantity = (int) $matches[2];
                
                // Find the sale item to update
                $saleItem = $salesTransaction->items->firstWhere('id', $saleItemId);
                if (!$saleItem) return false; // Sale item not found
                
                // Calculate the difference in quantity
                $quantityDifference = $newQuantity - $saleItem->quantity;
                
                // Calculate the old subtotal for this item
                $oldSubtotal = $saleItem->price * $saleItem->quantity;
                
                // Update the sale item quantity
                $saleItem->quantity = $newQuantity;
                $saleItem->save();
                
                // Calculate the new subtotal for this item
                $newSubtotal = $saleItem->price * $newQuantity;
                
                // Recalculate the total amount: subtract old subtotal, add new subtotal
                $newTotalAmount = $salesTransaction->total_amount - $oldSubtotal + $newSubtotal;
                
                // Update the total amount
                $salesTransaction->total_amount = $newTotalAmount;
                $salesTransaction->save();
                
                // Adjust inventory: add back the old quantity, subtract the new quantity
                $product = $saleItem->product;
                if ($product) {
                    $product->increment('stock_quantity', -$quantityDifference);
                }

                return true; // Success!
            
            case 'Chicken Stock Adjustment':
                // For now, chicken stock adjustments would need manual handling
                // as they involve complex logic with inventory adjustments
                // This can be implemented later if needed
                return false;

            case 'Feed Usage Record':
                $feedLog = FeedUsageLog::find($request->reference_id);
                if (!$feedLog) return false;

                // Parse the proposed correction to extract the correct quantity (supports decimals)
                if (!preg_match('/(\d+\.?\d*)/', $request->proposed_correction, $matches)) {
                    return false;
                }
                $newQuantityKg = (float) $matches[1];
                $originalQuantityKg = (float) $feedLog->quantity_kg;

                if ($newQuantityKg < 0.01) {
                    return false; // Invalid - minimum quantity
                }

                // Update the feed usage log
                $feedLog->quantity_kg = $newQuantityKg;
                $feedLog->save();

                // Adjust feed stock: stock was reduced by original_quantity, correct reduction is new_quantity
                // Add back the difference to feed stock
                $stockAdjustment = $originalQuantityKg - $newQuantityKg;
                $feedStat = FarmStat::firstWhere('stat_key', 'current_feed_stock_kg');
                if ($feedStat) {
                    $feedStat->increment('stat_value', $stockAdjustment);
                }

                return true;
            
            case 'Collectibles':
                // Find the original collectible that needs to be changed
                $collectible = Collectible::find($request->reference_id);
                if (!$collectible) return false; // The original collectible was deleted

                // Parse the proposed correction to extract the amount
                // The proposed correction should be a number representing the correct amount_paid
                preg_match('/(\d+\.?\d*)/', $request->proposed_correction, $matches);
                if (!isset($matches[1])) return false; // Could not find a number in the proposal
                
                $newAmountPaid = (float) $matches[1];
                $totalAmount = $collectible->total_amount;
                $originalAmountPaid = $collectible->amount_paid;
                
                // Ensure the new amount paid doesn't exceed total amount
                if ($newAmountPaid > $totalAmount) {
                    return false; // Invalid correction - amount paid cannot exceed total
                }
                
                // Calculate new balance
                $newBalance = $totalAmount - $newAmountPaid;
                
                // Update the collectible
                $collectible->amount_paid = $newAmountPaid;
                $collectible->balance = max(0, $newBalance);
                
                // Update status based on new values
                if ($collectible->balance <= 0) {
                    $collectible->status = 'paid';
                    $collectible->balance = 0;
                    if (!$collectible->fully_paid_date) {
                        $collectible->fully_paid_date = now()->toDateString();
                    }
                } else {
                    $collectible->status = $newAmountPaid > 0 ? 'partial' : 'unpaid';
                }
                
                // Update last payment date if amount paid increased
                if ($newAmountPaid > $originalAmountPaid) {
                    $collectible->last_payment_date = now()->toDateString();
                }
                
                $collectible->save();

                return true; // Success!
        }

        return false; // Default to fail if the type is unknown
    }
}