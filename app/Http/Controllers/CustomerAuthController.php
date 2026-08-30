<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Customer;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:customers',
            'password' => 'required|min:6',
            'phone' => 'nullable|string',
            'referrer_code' => 'nullable|string'
        ]);

        $referrerId = null;
        if (!empty($validated['referrer_code'])) {
            $referrer = Customer::where('referral_code', $validated['referrer_code'])->first();
            if ($referrer) {
                $referrerId = $referrer->id;
            }
        }

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'referral_code' => strtoupper(\Illuminate\Support\Str::random(6)),
            'referrer_id' => $referrerId
        ]);

        $token = $customer->createToken('customer-token')->plainTextToken;
        return response()->json(['customer' => $customer, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $customer->createToken('customer-token')->plainTextToken;
        return response()->json(['customer' => $customer, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}