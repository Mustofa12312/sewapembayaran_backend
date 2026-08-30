<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\Product;
class ProductController extends Controller
{
    public function index() {
        return Product::where('status', 'ACTIVE')->with(['packages' => function($q) {
            $q->where('status', 'ACTIVE')->with('features');
        }])->get();
    }
    public function show($slug) {
        return Product::where('slug', $slug)->where('status', 'ACTIVE')->with(['packages' => function($q) {
            $q->where('status', 'ACTIVE')->with('features');
        }])->firstOrFail();
    }
    public function getPackage($id) {
        return \App\Models\Package::with('product')->where('status', 'ACTIVE')->findOrFail($id);
    }
}