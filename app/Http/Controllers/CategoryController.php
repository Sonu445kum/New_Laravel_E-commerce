<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index(Request $req, $slug = null)
    {
        if ($slug) {
            $category = Category::where('slug',$slug)->firstOrFail();
            $products = $category->products()->paginate(12);
            return view('categories.show', compact('category','products'));
        }

        $categories = Category::with('children')->get();
        return view('categories.index', compact('categories'));
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'=>'required',
            'slug'=>'required|unique:categories,slug',
            'parent_id'=>'nullable|exists:categories,id'
        ]);
        Category::create($data);
        return back()->with('success','Category created');
    }

    public function update(Request $req, Category $category)
    {
        $data = $req->validate([
            'name'=>'required',
            'slug'=>"required|unique:categories,slug,{$category->id}",
            'parent_id'=>'nullable|exists:categories,id'
        ]);
        $category->update($data);
        return back()->with('success','Category updated');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('success','Deleted');
    }
}
