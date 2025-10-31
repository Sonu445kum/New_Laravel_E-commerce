<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function __construct() { $this->middleware(['auth','can:access-admin']); }

    public function index() { $products = Product::paginate(20); return view('admin.products.index', compact('products')); }

    public function create() { $categories = Category::all(); return view('admin.products.create', compact('categories')); }

    public function store(Request $req)
    {
        $data = $req->validate(['title'=>'required','slug'=>'required|unique:products,slug','price'=>'required|numeric','stock'=>'required|integer']);
        $product = Product::create($data);
        if ($req->hasFile('images')) {
            foreach ($req->file('images') as $f) {
                $path = $f->store('products','public');
                $product->images()->create(['path'=>$path]);
            }
        }
        return redirect()->route('admin.products.index')->with('success','Created');
    }

    public function edit(Product $product) { $categories = Category::all(); return view('admin.products.edit', compact('product','categories')); }

    public function update(Request $req, Product $product)
    {
        $data = $req->validate(['title'=>'required','slug'=>"required|unique:products,slug,{$product->id}",'price'=>'required|numeric','stock'=>'required|integer']);
        $product->update($data);
        return back()->with('success','Updated');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $img) Storage::disk('public')->delete($img->path);
        $product->delete();
        return back()->with('success','Deleted');
    }
}
