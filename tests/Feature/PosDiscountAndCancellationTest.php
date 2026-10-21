<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Cashier\CashierRegister;
use App\Livewire\Tables\TableMap;
use App\Livewire\Pos\OrderTerminal;
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

    public function test_paid_order_is_not_selected_in_cashier()
    {
        $user = User::first();
        $this->actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-PAID-TEST',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'subtotal_usd' => 10.00,
            'total_usd' => 10.00,
        ]);

        Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->assertSet('selectedOrderId', null)
            ->assertSet('lastPaidOrderId', $order->id);
    }

    public function test_table_map_can_merge_multiple_accounts()
    {
        $user = User::first();
        $this->actingAs($user);

        $table = \App\Models\Table::first();
        $burger = \App\Models\Product::first();

        // Cuenta 1
        $o1 = Order::create([
            'table_id' => $table->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-SPLIT-1',
            'service_type' => 'dine_in',
            'account_label' => 'Cuenta 1',
            'status' => 'pending',
            'subtotal_usd' => 10.00,
            'total_usd' => 10.00,
        ]);
        $o1->items()->create([
            'product_id' => $burger->id,
            'quantity' => 1,
            'unit_price_usd' => 10.00,
            'subtotal_usd' => 10.00,
        ]);

        // Cuenta 2
        $o2 = Order::create([
            'table_id' => $table->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-SPLIT-2',
            'service_type' => 'dine_in',
            'account_label' => 'Cuenta 2',
            'status' => 'pending',
            'subtotal_usd' => 15.00,
            'total_usd' => 15.00,
        ]);
        $o2->items()->create([
            'product_id' => $burger->id,
            'quantity' => 1,
            'unit_price_usd' => 15.00,
            'subtotal_usd' => 15.00,
        ]);

        $table->syncStatus();
        $this->assertEquals(2, $table->activeOrders()->count());

        // Unificar cuentas
        Livewire::test(TableMap::class)
            ->call('mergeTableAccounts', $table->id)
            ->assertDispatched('notify');

        $this->assertEquals(1, $table->activeOrders()->count());
        $mergedOrder = $table->activeOrders()->first();
        $this->assertEquals('Cuenta Unificada', $mergedOrder->account_label);
        $this->assertEquals(2, $mergedOrder->items()->count());
        $this->assertEquals(25.00, $mergedOrder->total_usd);
    }

    public function test_cashier_can_split_bill_equally()
    {
        $user = User::first();
        $this->actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-SPLIT-EQ',
            'service_type' => 'dine_in',
            'status' => 'pending',
            'subtotal_usd' => 60.00,
            'total_usd' => 60.00,
        ]);

        Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->call('selectOrder', $order->id)
            ->call('splitEqually', 3)
            ->assertSet('amountReceived', 20.00)
            ->assertDispatched('notify');
    }

    public function test_order_terminal_can_split_order_by_items()
    {
        $user = User::first();
        $this->actingAs($user);

        $table = \App\Models\Table::first();
        $burger = \App\Models\Product::first();

        $order = Order::create([
            'table_id' => $table->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-SPLIT-ITEMS',
            'service_type' => 'dine_in',
            'account_label' => 'Cuenta Principal',
            'status' => 'pending',
            'subtotal_usd' => 20.00,
            'total_usd' => 20.00,
        ]);

        $item = $order->items()->create([
            'product_id' => $burger->id,
            'quantity' => 2,
            'unit_price_usd' => 10.00,
            'subtotal_usd' => 20.00,
        ]);

        Livewire::test(OrderTerminal::class, ['tableId' => $table->id])
            ->set('orderId', $order->id)
            ->call('openSplitModal')
            ->set('splitNewAccountLabel', 'Cuenta Amigo')
            ->set('splitQuantities.' . $item->id, 1)
            ->call('executeSplitOrder')
            ->assertDispatched('notify');

        // La orden original ahora tiene 1 ítem y total $10
        $this->assertEquals(1, $order->fresh()->items()->first()->quantity);
        $this->assertEquals(10.00, $order->fresh()->total_usd);

        // La nueva sub-cuenta tiene 1 ítem y total $10
        $newOrder = Order::where('table_id', $table->id)->where('account_label', 'Cuenta Amigo')->first();
        $this->assertNotNull($newOrder);
        $this->assertEquals(1, $newOrder->items()->count());
        $this->assertEquals(10.00, $newOrder->total_usd);
    }

    public function test_cashier_handles_partial_payment_abono_gracefully()
    {
        $user = User::first();
        $this->actingAs($user);

        $burger = \App\Models\Product::first();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-ABONO-TEST',
            'service_type' => 'dine_in',
            'status' => 'pending',
            'subtotal_usd' => 40.00,
            'total_usd' => 40.00,
        ]);
        $order->items()->create([
            'product_id' => $burger->id,
            'quantity' => 2,
            'unit_price_usd' => 20.00,
            'subtotal_usd' => 40.00,
        ]);

        $test = Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->call('selectOrder', $order->id)
            ->call('splitEqually', 2)
            ->assertSet('amountReceived', 20.00)
            ->call('processCheckout')
            ->assertDispatched('notify');

        // La orden aún debe estar pendiente con 1 pago de $20
        $this->assertEquals('pending', $order->fresh()->status);
        $this->assertEquals(1, $order->fresh()->payments()->count());
        $this->assertEquals(20.00, $order->fresh()->payments()->first()->amount_usd);

        // El segundo pago completa la cuenta
        $test->set('amountReceived', 20.00)
            ->call('processCheckout')
            ->assertDispatched('notify');

        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertEquals(2, $order->fresh()->payments()->count());
        $this->assertEquals(40.00, $order->fresh()->payments()->sum('amount_usd'));
    }

    public function test_cashier_can_view_order_items_and_toggle_detail()
    {
        $user = User::first();
        $this->actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-DETAIL-TEST',
            'service_type' => 'dine_in',
            'status' => 'pending',
            'subtotal_usd' => 25.00,
            'total_usd' => 25.00,
        ]);

        $product = \App\Models\Product::first();
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_usd' => 12.50,
            'subtotal_usd' => 25.00,
            'notes' => 'Sin cebolla por favor',
        ]);

        Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->call('selectOrder', $order->id)
            ->assertSee($product->name)
            ->assertSee('Sin cebolla por favor')
            ->assertSee('$25.00')
            ->assertSet('showItemsDetail', true)
            ->call('toggleItemsDetail')
            ->assertSet('showItemsDetail', false);
    }

    public function test_cashier_can_split_order_by_items()
    {
        $user = User::first();
        $this->actingAs($user);

        $table = \App\Models\Table::first();
        $products = \App\Models\Product::take(2)->get();
        $prod1 = $products[0];
        $prod2 = $products[1];

        $order = Order::create([
            'table_id' => $table->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-SPLIT-ITEMS-TEST',
            'service_type' => 'dine_in',
            'account_label' => 'Cuenta Principal',
            'status' => 'pending',
            'subtotal_usd' => 30.00,
            'total_usd' => 30.00,
        ]);

        $item1 = $order->items()->create([
            'product_id' => $prod1->id,
            'quantity' => 2,
            'unit_price_usd' => 10.00,
            'subtotal_usd' => 20.00,
        ]);

        $item2 = $order->items()->create([
            'product_id' => $prod2->id,
            'quantity' => 1,
            'unit_price_usd' => 10.00,
            'subtotal_usd' => 10.00,
        ]);

        Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->call('selectOrder', $order->id)
            ->call('openSplitModal')
            ->assertSet('showSplitModal', true)
            ->assertSet('splitNewAccountLabel', 'Cuenta 2')
            ->call('incrementSplitQty', $item1->id)
            ->call('executeSplitOrder')
            ->assertDispatched('notify')
            ->assertSet('showSplitModal', false);

        // Cuenta original debe tener 1x prod1 ($10) y 1x prod2 ($10) = $20
        $this->assertEquals(20.00, $order->fresh()->total_usd);
        $this->assertEquals(1, $order->fresh()->items()->where('product_id', $prod1->id)->first()->quantity);

        // Nueva cuenta creada en la mesa con 1x prod1 = $10
        $newOrder = Order::where('table_id', $table->id)->where('account_label', 'Cuenta 2')->first();
        $this->assertNotNull($newOrder);
        $this->assertEquals(10.00, $newOrder->total_usd);
        $this->assertEquals(1, $newOrder->items()->count());
    }
}



