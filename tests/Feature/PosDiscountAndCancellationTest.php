<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Cashier\CashierRegister;
use App\Livewire\Tables\TableMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\RestaurantSampleSeeder;

class PosDiscountAndCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(RestaurantSampleSeeder::class);

        Configuration::firstOrCreate(['id' => 1], [
            'business_name' => 'JSFood Test',
            'printer_name' => 'POS-80',
            'printer_width' => 80,
            'kitchen_printer_name' => 'KITCHEN-58',
            'kitchen_printer_width' => 58,
        ]);
    }

    public function test_can_apply_percentage_and_fixed_discount_to_order()
    {
        $user = User::first();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-DISC1',
            'service_type' => 'dine_in',
            'status' => 'pending',
            'subtotal_usd' => 100.00,
            'total_usd' => 100.00,
        ]);

        $product = \App\Models\Product::first();
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 100.00,
            'subtotal_usd' => 100.00,
        ]);
        $order->calculateTotals();

        $order->applyDiscount(15.00);
        $this->assertEquals(15.00, $order->fresh()->discount_usd);
        $this->assertEquals(85.00, $order->fresh()->total_usd);

        // Remove discount
        $order->applyDiscount(0.00);
        $this->assertEquals(0.00, $order->fresh()->discount_usd);
        $this->assertEquals(100.00, $order->fresh()->total_usd);
    }

    public function test_cashier_can_apply_preset_discount_and_cancel_order()
    {
        $user = User::first();
        $this->actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-CASH1',
            'service_type' => 'dine_in',
            'status' => 'pending',
            'subtotal_usd' => 50.00,
            'total_usd' => 50.00,
        ]);

        $product = \App\Models\Product::first();
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 50.00,
            'subtotal_usd' => 50.00,
        ]);
        $order->calculateTotals();

        Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->call('selectOrder', $order->id)
            ->call('applyPresetDiscount', 10)
            ->assertDispatched('notify');

        $this->assertEquals(5.00, $order->fresh()->discount_usd);
        $this->assertEquals(45.00, $order->fresh()->total_usd);

        // Cancel order from cashier
        Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->call('selectOrder', $order->id)
            ->call('cancelSelectedOrder')
            ->assertDispatched('notify');

        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    public function test_table_map_can_create_takeout_order_and_switch_views()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TableMap::class)
            ->set('takeoutLabel', 'Para Llevar - Carlos')
            ->call('createTakeoutOrder')
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'service_type' => 'takeout',
            'account_label' => 'Para Llevar - Carlos',
            'status' => 'pending',
        ]);
    }

    public function test_cashier_can_checkout_and_reprint_ticket()
    {
        $user = User::first();
        $this->actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-PRINT1',
            'service_type' => 'dine_in',
            'status' => 'pending',
            'subtotal_usd' => 20.00,
            'total_usd' => 20.00,
        ]);

        $product = \App\Models\Product::first();
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 20.00,
            'subtotal_usd' => 20.00,
        ]);

        Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->call('selectOrder', $order->id)
            ->set('amountReceived', 20.00)
            ->call('processCheckout')
            ->assertDispatched('notify')
            ->call('reprintTicket')
            ->assertDispatched('notify');

        $this->assertEquals('paid', $order->fresh()->status);
    }

    public function test_cashier_selects_order_with_bank_payment()
    {
        $user = User::first();
        $this->actingAs($user);

        $bank = \App\Models\Bank::create([
            'name' => 'Banesco Pago Movil',
            'type' => 'pago_movil',
            'currency_code' => 'VED',
            'state' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-BANK1',
            'service_type' => 'dine_in',
            'status' => 'pending',
            'subtotal_usd' => 30.00,
            'total_usd' => 30.00,
        ]);

        $product = \App\Models\Product::first();
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 30.00,
            'subtotal_usd' => 30.00,
        ]);

        \App\Models\Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'payment_method' => 'mobile_pay',
            'currency_code' => 'VED',
            'exchange_rate' => 60.00,
            'amount_currency' => 600.00,
            'amount_usd' => 10.00,
            'change_usd' => 0.00,
            'reference' => '123456',
        ]);

        Livewire::test(CashierRegister::class)
            ->call('selectOrder', $order->id)
            ->assertSet('selectedOrderId', $order->id);
    }
}

