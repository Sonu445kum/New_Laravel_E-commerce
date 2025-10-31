<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user()?->orders()->latest()->paginate(20) ?? collect();
        return view('orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        if (Auth::id() !== $order->user_id && !Auth::user()?->is_admin) abort(403);
        return view('orders.show', compact('order'));
    }
}
