<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LicenseKey;
class LicenseKeyController extends Controller
{
    public function index() { return LicenseKey::with('product', 'package')->latest()->paginate(50); }
    public function store(Request $request) {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'package_id' => 'nullable|exists:packages,id',
            'license_keys' => 'required|array',
            'license_keys.*' => 'string|unique:license_keys,license_key'
        ]);
        $inserted = [];
        foreach ($validated['license_keys'] as $key) {
            $inserted[] = LicenseKey::create([
                'product_id' => $validated['product_id'],
                'package_id' => $validated['package_id'] ?? null,
                'license_key' => $key,
                'status' => 'AVAILABLE'
            ]);
        }
        return response()->json($inserted, 201);
    }
    public function show($id) { return LicenseKey::findOrFail($id); }
    public function update(Request $request, $id) {
        $license = LicenseKey::findOrFail($id);
        $license->update($request->only('status'));
        return response()->json($license);
    }
    public function destroy($id) {}
}