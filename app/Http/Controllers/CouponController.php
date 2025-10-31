<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use Illuminate\Support\Facades\Session;

class CouponController extends Controller
{
    /**
     * Apply a coupon code to the current cart.
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:coupons,code',
        ], [
            'code.exists' => 'Invalid coupon code. Please try again.',
        ]);

        $coupon = Coupon::where('code', $request->code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now());
            })
            ->first();

        if (!$coupon) {
            return back()->with('error', 'Coupon is invalid or expired.');
        }

        // Store coupon data in session
        Session::put('coupon', [
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
        ]);

        return back()->with('success', 'Coupon applied successfully!');
    }

    /**
     * Remove the applied coupon.
     */
    public function removeCoupon()
    {
        if (Session::has('coupon')) {
            Session::forget('coupon');
            return back()->with('success', 'Coupon removed successfully.');
        }

        return back()->with('error', 'No coupon applied.');
    }

    /**
     * Helper: calculate discount based on cart total.
     * You can use this in checkout or order calculation logic.
     */
    public static function calculateDiscount($cartTotal)
    {
        if (!Session::has('coupon')) {
            return 0;
        }

        $coupon = Session::get('coupon');
        $discount = 0;

        if ($coupon['discount_type'] === 'fixed') {
            $discount = $coupon['discount_value'];
        } elseif ($coupon['discount_type'] === 'percent') {
            $discount = ($cartTotal * $coupon['discount_value']) / 100;
        }

        return $discount;
    }
}
