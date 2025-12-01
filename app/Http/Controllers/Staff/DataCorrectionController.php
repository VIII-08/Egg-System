<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataCorrectionRequest;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


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
            $requestTypes = [ 'Egg Production Log', 'Expense Record', 'Chicken Stock Adjustment' ];
        } elseif ($userRole === 'staff-marketing') {
            $requestTypes = [ 'Sales Transaction', 'Expense Record' ];
        }

        return Inertia::render('Staff/RequestDataCorrection', [
            'recentRequests' => $recentRequests,
            'requestTypes' => $requestTypes,
            'reviewedCorrectionsCount' => $reviewedCorrectionsCount,
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
        ]);

        $imagePath = null;
        // Only process image if it's an expense correction request
        if ($request->hasFile('receipt_image') && $validated['request_type'] === 'Expense Record') {
            $imagePath = $request->file('receipt_image')->store('correction-receipts', 'public');
        }

        DataCorrectionRequest::create([
            'user_id' => $request->user()->id,
            'request_type' => $validated['request_type'],
            'reference_id' => $validated['reference_id'],
            'description_of_error' => $validated['description_of_error'],
            'proposed_correction' => $validated['proposed_correction'],
            'receipt_image_path' => $imagePath,
        ]);

        return to_route('data-correction.create')->with('success', 'Your correction request has been submitted.');
    }
}