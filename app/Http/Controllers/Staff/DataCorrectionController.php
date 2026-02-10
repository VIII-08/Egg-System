<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataCorrectionRequest;
use App\Models\ProductionLog;
use App\Models\Expense;
use App\Models\SalesTransaction;
use App\Models\ChickenStockLog;
use App\Models\Collectible;
use App\Models\FeedUsageLog;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;


class DataCorrectionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Get the authenticated user's role
        $userRole = $request->user()->role;

        // Fetch this user's recent correction requests
        $recentRequests = DataCorrectionRequest::where('user_id', Auth::id())
                                            ->latest()
                                            ->limit(5)
                                            ->get();

        // Count reviewed correction requests (approved/rejected) for notification badge
        $reviewedCorrectionsCount = DataCorrectionRequest::where('user_id', Auth::id())
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->count();

        $requestTypes = [];

        // Filter the request types based on the user's role
        if ($userRole === 'staff-production') {
            $requestTypes = [ 'Egg Production Log', 'Expense Record', 'Chicken Stock Adjustment', 'Feed Usage Record' ];
        } elseif ($userRole === 'staff-marketing') {
            $requestTypes = [ 'Sales Transaction', 'Expense Record', 'Collectibles' ];
        }

        return Inertia::render('Staff/RequestDataCorrection', [
            'recentRequests' => $recentRequests,
            'requestTypes' => $requestTypes,
            'reviewedCorrectionsCount' => $reviewedCorrectionsCount,
        ]);
    }

    /**
     * Fetch sales transaction details for correction form
     */
    public function getSalesTransaction($id)
    {
        $salesTransaction = SalesTransaction::with(['items.product'])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$salesTransaction) {
            return response()->json(['error' => 'Sales transaction not found'], 404);
        }

        return response()->json([
            'id' => $salesTransaction->id,
            'total_amount' => $salesTransaction->total_amount,
            'items' => $salesTransaction->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'egg_product_id' => $item->egg_product_id,
                    'product_name' => $item->product->name ?? 'N/A',
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * THIS IS THE MISSING METHOD.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => ['required', 'string'],
            'reference_id' => ['required', 'integer', 'min:1'],
            'description_of_error' => ['required', 'string', 'max:1000'],
            'proposed_correction' => ['required', 'string', 'max:1000'],
            'receipt_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif', 'max:2048'], // Only for expense corrections
            'selected_egg_size_id' => ['nullable', 'integer'], // For sales transaction corrections
        ]);

        $userId = $request->user()->id;
        $referenceId = $validated['reference_id'];
        $requestType = $validated['request_type'];

        // Validate that the reference ID exists and belongs to the user
        $recordExists = false;
        $recordBelongsToUser = false;

        switch ($requestType) {
            case 'Egg Production Log':
                $record = ProductionLog::find($referenceId);
                if ($record) {
                    $recordExists = true;
                    $recordBelongsToUser = $record->user_id === $userId;
                }
                break;

            case 'Expense Record':
                $record = Expense::find($referenceId);
                if ($record) {
                    $recordExists = true;
                    $recordBelongsToUser = $record->user_id === $userId;
                }
                break;

            case 'Sales Transaction':
                $record = SalesTransaction::find($referenceId);
                if ($record) {
                    $recordExists = true;
                    $recordBelongsToUser = $record->user_id === $userId;
                }
                break;

            case 'Chicken Stock Adjustment':
                $record = ChickenStockLog::find($referenceId);
                if ($record) {
                    $recordExists = true;
                    $recordBelongsToUser = $record->user_id === $userId;
                }
                break;

            case 'Collectibles':
                $record = Collectible::with('salesTransaction')->find($referenceId);
                if ($record) {
                    $recordExists = true;
                    // Check if the collectible's sales transaction belongs to the user
                    $recordBelongsToUser = $record->salesTransaction && $record->salesTransaction->user_id === $userId;
                }
                break;

            case 'Feed Usage Record':
                $record = FeedUsageLog::find($referenceId);
                if ($record) {
                    $recordExists = true;
                    $recordBelongsToUser = $record->user_id === $userId;
                }
                break;

            default:
                throw ValidationException::withMessages([
                    'request_type' => ['Invalid request type selected.'],
                ]);
        }

        // Check if record exists
        if (!$recordExists) {
            throw ValidationException::withMessages([
                'reference_id' => ['The reference ID does not exist. Please check the ID and try again.'],
            ]);
        }

        // Check if record belongs to user
        if (!$recordBelongsToUser) {
            throw ValidationException::withMessages([
                'reference_id' => ['This reference ID does not belong to you. You can only request corrections for your own records.'],
            ]);
        }

        $imagePath = null;
        // Only process image if it's an expense correction request
        if ($request->hasFile('receipt_image') && $validated['request_type'] === 'Expense Record') {
            $imagePath = $request->file('receipt_image')->store('correction-receipts', 'public');
        }

        // For sales transactions, store the selected_egg_size_id in the proposed_correction
        // Format: "sale_item_id:quantity" (already formatted in frontend)
        $proposedCorrection = $validated['proposed_correction'];
        
        DataCorrectionRequest::create([
            'user_id' => $userId,
            'request_type' => $validated['request_type'],
            'reference_id' => $validated['reference_id'],
            'description_of_error' => $validated['description_of_error'],
            'proposed_correction' => $proposedCorrection,
            'receipt_image_path' => $imagePath,
        ]);

        return to_route('data-correction.create')->with('success', 'Your correction request has been submitted.');
    }
}