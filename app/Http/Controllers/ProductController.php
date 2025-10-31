<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $req)
    {
        $query = Product::with('images','variants','categories')->where('is_active', true);

        if ($req->filled('q')) {
            $q = $req->q;
            $query->where(function($sub) use ($q){
                $sub->where('title','like',"%$q%")
                     ->orWhere('description','like',"%$q%");
            });
        }

        if ($req->filled('category')) {
            $query->whereHas('categories', fn($q)=>$q->where('slug',$req->category));
        }

        if ($req->filled('min_price')) $query->where('price','>=',$req->min_price);
        if ($req->filled('max_price')) $query->where('price','<=',$req->max_price);

        $products = $query->paginate(12)->withQueryString();
        return view('products.index', compact('products'));
    }

    public function show($slug)
    {
        $product = Product::with('images','variants','reviews.user')->where('slug',$slug)->firstOrFail();
        return view('products.show', compact('product'));
    }
}
