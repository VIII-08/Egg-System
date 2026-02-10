<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Collectible;
use Illuminate\Support\Facades\Auth;

class CollectibleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Only marketing staff can access
        if ($user->role !== 'staff-marketing') {
            abort(403, 'Only marketing staff can access collectibles.');
        }

        $search = $request->input('search', '');
        $status = $request->input('status', 'all'); // all, unpaid, partial, paid

        // Base query: collectibles from sales transactions created by this user
        $query = Collectible::query()
            ->whereHas('salesTransaction', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['salesTransaction', 'payments.recordedBy']);

        // Apply search filter (customer name)
        if ($search) {
            $query->where('customer_name', 'like', '%' . $search . '%');
        }

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $collectibles = $query->latest()->paginate(20)->withQueryString();

        // Get unique customer names for autocomplete/search suggestions
        $customers = Collectible::query()
            ->whereHas('salesTransaction', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->distinct()
            ->pluck('customer_name')
            ->filter()
            ->sort()
            ->values();

        return Inertia::render('Staff/Collectibles', [
            'collectibles' => $collectibles,
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }
}











