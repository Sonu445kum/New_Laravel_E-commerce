<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;

class DashboardController extends Controller
{
    /**
     * Display dashboard summary.
     */
    public function index()
    {
        $totalOrders = Order::count();
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalRevenue = Order::where('status', 'completed')->sum('total_amount');

        $recentOrders = Order::latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'totalOrders',
            'totalUsers',
            'totalProducts',
            'totalCategories',
            'totalRevenue',
            'recentOrders'
        ));
    }
}
