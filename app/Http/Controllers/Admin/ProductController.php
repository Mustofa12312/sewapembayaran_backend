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
        return \App\Models\Product::with('packages')->latest()->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:products',
                'description' => 'nullable|string',
                'thumbnail' => 'nullable|string',
                'category' => 'nullable|string',
                'status' => 'required|in:ACTIVE,INACTIVE,ARCHIVED'
            ]);

            $product = \App\Models\Product::create($validated);
            
            \App\Models\AuditLog::create([
                'action' => 'CREATE_PRODUCT',
                'entity' => 'PRODUCT',
                'entity_id' => $product->id,
                'before_data' => null,
                'after_data' => json_encode($product),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent(), 0, 255)
            ]);

            return response()->json($product, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    public function show(string $id)
    {
        return \App\Models\Product::with('packages.features')->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $product = \App\Models\Product::findOrFail($id);
        
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
        $product = \App\Models\Product::findOrFail($id);
        $product->update(['status' => 'ARCHIVED']);
        return response()->json(['message' => 'Product archived']);
    }
}
