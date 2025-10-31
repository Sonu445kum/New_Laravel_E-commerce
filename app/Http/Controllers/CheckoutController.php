<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Coupon;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class CheckoutController extends Controller
{
    public function show()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) return redirect()->route('cart.index')->withErrors('Cart empty');
        $coupon = session()->get('coupon');
        return view('checkout.index', compact('cart','coupon'));
    }

    public function applyCoupon(Request $req)
    {
        $req->validate(['code'=>'required|string']);
        $coupon = Coupon::where('code',$req->code)->where('is_active',true)
            ->where(function($q){$q->whereNull('expires_at')->orWhere('expires_at','>',now());})
            ->first();
        if(!$coupon) return back()->withErrors('Invalid/expired coupon');
        session()->put('coupon',$coupon->only(['id','code','discount_type','value']));
        return back()->with('success','Coupon applied');
    }

    public function process(Request $req)
    {
        // same as original – will include full code later if needed
    }

    public function stripeSuccess(Request $req)
    {
        // same as original – will include full code later if needed
    }
}
