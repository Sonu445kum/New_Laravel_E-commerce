<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    /**
     * Display all coupons.
     */
    public function index()
    {
        $coupons = Coupon::latest()->paginate(10);
        return view('admin.coupons.index', compact('coupons'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('admin.coupons.create');
    }

    /**
     * Store a new coupon.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|unique:coupons,code',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        Coupon::create([
            'code' => $request->code ?? strtoupper(Str::random(8)),
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    /**
     * Edit coupon.
     */
    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);
        return view('admin.coupons.edit', compact('coupon'));
    }

    /**
     * Update coupon.
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $request->validate([
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $coupon->update([
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    /**
     * Delete coupon.
     */
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully.');
    }
}
