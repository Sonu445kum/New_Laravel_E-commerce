<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class ReviewController extends Controller
{
    public function __construct(){ $this->middleware('auth'); }

    public function store(Request $req, $productId)
    {
        $product = Product::findOrFail($productId);
        $data = $req->validate(['rating'=>'required|integer|min:1|max:5','comment'=>'nullable|string','images.*'=>'nullable|image|max:5120']);
        $review = $product->reviews()->create([
            'user_id'=>Auth::id(),
            'rating'=>$data['rating'],
            'comment'=>$data['comment'] ?? null
        ]);
        if($req->hasFile('images')){
            foreach($req->file('images') as $f){
                $path = $f->store('reviews','public');
                $review->images()->create(['path'=>$path]);
            }
        }
        return back()->with('success','Review submitted');
    }
}
