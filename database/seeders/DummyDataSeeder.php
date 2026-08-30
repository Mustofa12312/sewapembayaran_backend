<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\LicenseKey;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Customer
        $customer = Customer::create([
            'name' => 'Demo Customer',
            'email' => 'customer@example.com',
            'phone' => '081234567890',
            'password' => Hash::make('password')
        ]);

        // 2. Create Products
        $vpnProduct = Product::create([
            'name' => 'VPN Premium',
            'slug' => 'vpn-premium',
            'description' => 'Fast and secure premium VPN service.',
            'category' => 'Network',
            'status' => 'ACTIVE'
        ]);

        $antivirusProduct = Product::create([
            'name' => 'Antivirus Pro',
            'slug' => 'antivirus-pro',
            'description' => 'Protect your devices from malware.',
            'category' => 'Security',
            'status' => 'ACTIVE'
        ]);

        // 3. Create Packages
        $vpnMonthly = Package::create([
            'product_id' => $vpnProduct->id,
            'name' => 'Monthly Subscription',
            'price' => 50000.00,
            'is_recurring' => true,
            'status' => 'ACTIVE'
        ]);

        PackageFeature::create(['package_id' => $vpnMonthly->id, 'feature_name' => 'Unlimited Bandwidth']);
        PackageFeature::create(['package_id' => $vpnMonthly->id, 'feature_name' => 'No Logs Policy']);
        
        $antivirusYearly = Package::create([
            'product_id' => $antivirusProduct->id,
            'name' => 'Yearly License',
            'price' => 350000.00,
            'is_recurring' => false,
            'status' => 'ACTIVE'
        ]);
        
        PackageFeature::create(['package_id' => $antivirusYearly->id, 'feature_name' => 'Real-time protection']);
        PackageFeature::create(['package_id' => $antivirusYearly->id, 'feature_name' => 'Ransomware shield']);

        // 4. Create Licenses
        for ($i = 0; $i < 5; $i++) {
            LicenseKey::create([
                'product_id' => $vpnProduct->id,
                'package_id' => $vpnMonthly->id,
                'license_key' => 'VPN-'.strtoupper(Str::random(12)),
                'status' => 'AVAILABLE',
                'expires_at' => Carbon::now()->addYear()
            ]);
        }
        for ($i = 0; $i < 3; $i++) {
            LicenseKey::create([
                'product_id' => $antivirusProduct->id,
                'package_id' => $antivirusYearly->id,
                'license_key' => 'AV-'.strtoupper(Str::random(12)),
                'status' => 'AVAILABLE',
                'expires_at' => Carbon::now()->addYear()
            ]);
        }

        // 5. Create some Orders & Subscriptions
        $assignedLicense = LicenseKey::where('status', 'AVAILABLE')->first();
        $assignedLicense->update(['status' => 'ASSIGNED']);

        $order = Order::create([
            'order_number' => 'ORD-'.time(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'package_id' => $vpnMonthly->id,
            'product_id' => $vpnProduct->id,
            'snapshot_price' => 50000.00,
            'status' => 'PAID',
            'secure_token' => Str::random(32)
        ]);

        \Illuminate\Support\Facades\DB::table('order_license_keys')->insert([
            'order_id' => $order->id,
            'license_key_id' => $assignedLicense->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        Subscription::create([
            'customer_id' => $customer->id,
            'package_id' => $vpnMonthly->id,
            'status' => 'ACTIVE',
            'next_billing_date' => Carbon::now()->addDays(30)
        ]);
        
        // Another Pending Order
        Order::create([
            'order_number' => 'ORD-'.(time() + 1),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'package_id' => $antivirusYearly->id,
            'product_id' => $antivirusProduct->id,
            'snapshot_price' => 350000.00,
            'status' => 'PENDING_PAYMENT',
            'secure_token' => Str::random(32)
        ]);

        echo "Dummy data seeded successfully!\n";
    }
}
