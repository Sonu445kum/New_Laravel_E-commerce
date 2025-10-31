<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class CartController extends Controller
{
    public function index() { $cart = session('cart', []); return view('cart.index', compact('cart')); }

    public function add(Request $req)
    {
        $data = $req->validate(['product_id'=>'required|exists:products,id','quantity'=>'nullable|integer|min:1']);
        $product = Product::findOrFail($data['product_id']);
        $cart = session()->get('cart', []);
        $key = $product->id;
        $qty = $data['quantity'] ?? 1;

        if(isset($cart[$key])) $cart[$key]['quantity'] += $qty;
        else $cart[$key] = ['product_id'=>$product->id,'title'=>$product->title,'price'=>$product->price,'quantity'=>$qty];

        session()->put('cart', $cart);
        return back()->with('success','Added to cart');
    }

    public function update(Request $req)
    {
        $data = $req->validate(['key'=>'required','quantity'=>'required|integer|min:1']);
        $cart = session()->get('cart', []);
        if(isset($cart[$data['key']])){
            $cart[$data['key']]['quantity'] = $data['quantity'];
            session()->put('cart', $cart);
            return back()->with('success','Cart updated');
        }
        return back()->withErrors('Item not found');
    }

    public function remove(Request $req)
    {
        $cart = session()->get('cart', []);
        unset($cart[$req->key]);
        session()->put('cart',$cart);
        return back()->with('success','Removed');
    }

    public function clear()
    {
        session()->forget('cart');
        return back()->with('success','Cart cleared');
    }
}
