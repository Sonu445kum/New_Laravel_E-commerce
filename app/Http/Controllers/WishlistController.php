<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;
use App\Models\Product;

class WishlistController extends Controller
{
    /**
     * Apply authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the user's wishlist items.
     */
    public function index()
    {
        $user = Auth::user();

        // Ensure relation works fine and eager load product details
        $items = $user->wishlist()
            ->with('product')
            ->latest()
            ->paginate(20);

        return view('wishlist.index', compact('items'));
    }

    /**
     * Add a product to the user's wishlist.
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $userId = Auth::id();

        // Prevent duplicates using firstOrCreate
        $wishlistItem = Wishlist::firstOrCreate([
            'user_id' => $userId,
            'product_id' => $validated['product_id'],
        ]);

        if ($wishlistItem->wasRecentlyCreated) {
            return back()->with('success', 'Product added to your wishlist.');
        }

        return back()->with('info', 'This product is already in your wishlist.');
    }

    /**
     * Remove a product from the user's wishlist.
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $deleted = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->delete();

        if ($deleted) {
            return back()->with('success', 'Product removed from your wishlist.');
        }

        return back()->with('error', 'Item not found in wishlist.');
    }
}
