<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
class PackageController extends Controller
{
    public function index() { return Package::with('features', 'product')->get(); }
    public function store(Request $request) {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string',
            'price' => 'required|numeric',
            'duration_value' => 'nullable|integer',
            'duration_unit' => 'nullable|in:MONTH,YEAR',
            'is_unlimited' => 'boolean',
            'status' => 'required|in:ACTIVE,INACTIVE,ARCHIVED'
        ]);
        $package = Package::create($validated);
        if ($request->has('features')) {
            foreach ($request->features as $f) {
                $package->features()->create(['feature_name' => $f]);
            }
        }
        \App\Models\AuditLog::create([
            'action' => 'CREATE_PACKAGE',
            'entity' => 'PACKAGE',
            'entity_id' => $package->id,
            'before_data' => null,
            'after_data' => json_encode($package),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        return response()->json($package->load('features'), 201);
    }
    public function show($id) { return Package::with('features')->findOrFail($id); }
    public function update(Request $request, $id) {
        $package = Package::findOrFail($id);
        $package->update($request->all());
        return response()->json($package);
    }
    public function destroy($id) {
        Package::findOrFail($id)->update(['status' => 'ARCHIVED']);
        return response()->json(['message' => 'Archived']);
    }
}