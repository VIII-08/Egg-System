<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataCorrectionRequest;
use App\Models\FinancialReport;
use App\Models\ProductionLog;
use App\Models\EggProduct;
use App\Models\Expense;
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
        }

        return false; // Default to fail if the type is unknown
    }
}