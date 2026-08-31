<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Package;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_checkout_and_get_snap_token()
    {
        $product = Product::create(['name' => 'Prod', 'slug' => 'prod', 'status' => 'ACTIVE']);
        $package = Package::create([
            'product_id' => $product->id, 'name' => 'Basic', 'price' => 150000, 
            'duration_value' => 1, 'duration_unit' => 'MONTH', 'is_unlimited' => false, 'status' => 'ACTIVE', 'is_recurring' => false
        ]);

        $response = $this->postJson('/api/orders', [
            'package_id' => $package->id,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['order', 'snap_token', 'final_price']);
        
        $this->assertDatabaseHas('orders', ['customer_email' => 'test@example.com']);
        $this->assertDatabaseHas('payments', ['amount' => 150000, 'status' => 'PENDING']);
    }
}
