<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Collectible;
use App\Models\CollectiblePayment;
use Illuminate\Support\Facades\DB;

class CollectibleController extends Controller
{
    public function storePayment(Request $request, Collectible $collectible)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'staff-marketing'])) {
            abort(403, 'Not authorized to record payments.');
        }

        // Validate that payment amount doesn't exceed remaining balance
        $paymentAmount = (float) $data['amount'];
        $remainingBalance = (float) $collectible->balance;
        
        if ($paymentAmount > $remainingBalance) {
            return back()->withErrors([
                'amount' => "Payment amount (₱" . number_format($paymentAmount, 2) . ") cannot exceed remaining balance (₱" . number_format($remainingBalance, 2) . ")."
            ])->withInput();
        }

        DB::transaction(function () use ($collectible, $data, $user) {
            CollectiblePayment::create([
                'collectible_id' => $collectible->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'recorded_by_user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $collectible->amount_paid = $collectible->amount_paid + $data['amount'];
            $collectible->balance = max(0, $collectible->total_amount - $collectible->amount_paid);
            $collectible->last_payment_date = $data['payment_date'];

            if ($collectible->balance <= 0) {
                $collectible->status = 'paid';
                $collectible->balance = 0;
                $collectible->fully_paid_date = $data['payment_date'];
            } else {
                $collectible->status = $collectible->amount_paid > 0 ? 'partial' : 'unpaid';
            }

            $collectible->save();
        });

        return back()->with('success', 'Payment recorded successfully.');
    }
}

