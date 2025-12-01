<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\DataCorrectionRequest;
use App\Models\FinancialReport;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
   // In HandleInertiaRequests.php

public function share(Request $request): array
{

     // Calculate the total pending approvals if the user is an admin
     $pendingApprovalsCount = 0;
     if ($request->user() && $request->user()->role === 'admin') {
         $correctionRequests = DataCorrectionRequest::where('status', 'pending')->count();
         $financialReports = FinancialReport::where('status', 'submitted')->count();
         $pendingApprovalsCount = $correctionRequests + $financialReports;
     }

     // Calculate reviewed financial reports count for treasurer (approved/rejected reports)
     $reviewedReportsCount = 0;
     if ($request->user() && $request->user()->role === 'treasurer') {
         $reviewedReportsCount = FinancialReport::where('generated_by', $request->user()->id)
             ->whereIn('status', ['approved', 'rejected'])
             ->whereNotNull('reviewed_at')
             ->count();
     }

     // Calculate reviewed correction requests count for staff (approved/rejected requests)
     $reviewedCorrectionsCount = 0;
     if ($request->user() && in_array($request->user()->role, ['staff-production', 'staff-marketing'])) {
         $reviewedCorrectionsCount = DataCorrectionRequest::where('user_id', $request->user()->id)
             ->whereIn('status', ['approved', 'rejected'])
             ->whereNotNull('reviewed_at')
             ->count();
     }

    return array_merge(parent::share($request), [
        // This logic explicitly checks for a user and packages their data.
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'profile_picture' => $request->user()->profile_picture,
            ] : null,
        ],
        'pendingApprovalsCount' => $pendingApprovalsCount,
        'reviewedReportsCount' => $reviewedReportsCount,
        'reviewedCorrectionsCount' => $reviewedCorrectionsCount,
    ]);
}
}
