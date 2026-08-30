<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return App\Models\Product::with('packages')->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'category' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE,ARCHIVED'
        ]);

        $product = App\Models\Product::create($validated);
        return response()->json($product, 201);
    }

    public function show(string $id)
    {
        return App\Models\Product::with('packages.features')->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $product = App\Models\Product::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|unique:products,slug,' . $id,
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'category' => 'nullable|string',
            'status' => 'in:ACTIVE,INACTIVE,ARCHIVED'
        ]);

        $product->update($validated);
        return response()->json($product);
    }

    public function destroy(string $id)
    {
        // Avoid hard delete if related to orders, just change status. 
        // For MVP, we'll just allow it if no packages, otherwise we should archive.
        $product = App\Models\Product::findOrFail($id);
        $product->update(['status' => 'ARCHIVED']);
        return response()->json(['message' => 'Product archived']);
    }
}
