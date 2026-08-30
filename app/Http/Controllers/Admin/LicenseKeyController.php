<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LicenseKey;
class LicenseKeyController extends Controller
{
    public function index() { return LicenseKey::with('product', 'package')->latest()->paginate(50); }
    public function import(Request $request) {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'package_id' => 'nullable|exists:packages,id',
            'keys' => 'required|array',
            'keys.*' => 'string|unique:license_keys,license_key'
        ]);
        $inserted = [];
        foreach ($validated['keys'] as $key) {
            $inserted[] = LicenseKey::create([
                'product_id' => $validated['product_id'],
                'package_id' => $validated['package_id'] ?? null,
                'license_key' => $key,
                'status' => 'AVAILABLE'
            ]);
        }
        
        $count = count($inserted);
        \App\Models\AuditLog::create([
            'action' => 'IMPORT_LICENSES',
            'entity' => 'LICENSE',
            'entity_id' => "Batch #{$count}",
            'before' => null,
            'after' => "Imported {$count} keys",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return response()->json(['message' => "Imported {$count} keys successfully"], 201);
    }
    public function show($id) { return LicenseKey::findOrFail($id); }
    public function update(Request $request, $id) {
        $license = LicenseKey::findOrFail($id);
        $license->update($request->only('status'));
        return response()->json($license);
    }
    public function destroy($id) {}
}