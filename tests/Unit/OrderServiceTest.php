<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Package;
use App\Models\Coupon;
use App\Services\OrderService;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_order_without_coupon()
    {
        $product = Product::create(['name' => 'Prod 1', 'slug' => 'prod-1', 'status' => 'ACTIVE']);
        $package = Package::create([
            'product_id' => $product->id, 'name' => 'Basic', 'price' => 100000, 
            'duration_value' => 1, 'duration_unit' => 'MONTH', 'is_unlimited' => false, 'status' => 'ACTIVE', 'is_recurring' => false
        ]);

        $service = new OrderService();
        $order = $service->createOrder([
            'package_id' => $package->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com'
        ], null);

        $this->assertEquals(100000, $order->snapshot_price);
        $this->assertEquals(0, $order->discount_amount);
        $this->assertEquals('PENDING_PAYMENT', $order->status);
    }

    public function test_creates_order_with_percentage_coupon()
    {
        $product = Product::create(['name' => 'Prod 2', 'slug' => 'prod-2', 'status' => 'ACTIVE']);
        $package = Package::create([
            'product_id' => $product->id, 'name' => 'Pro', 'price' => 200000, 
            'duration_value' => 1, 'duration_unit' => 'MONTH', 'is_unlimited' => false, 'status' => 'ACTIVE', 'is_recurring' => false
        ]);
        
        $coupon = Coupon::create([
            'code' => 'DISC50', 'type' => 'percent', 'value' => 50, 'status' => 'ACTIVE'
        ]);

        $service = new OrderService();
        $order = $service->createOrder([
            'package_id' => $package->id,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'coupon_code' => 'DISC50'
        ], null);

        $this->assertEquals(200000, $order->snapshot_price);
        $this->assertEquals(100000, $order->discount_amount);
        $this->assertEquals($coupon->id, $order->coupon_id);
    }
}
