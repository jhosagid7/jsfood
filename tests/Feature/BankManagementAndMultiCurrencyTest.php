<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Bank;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Livewire\Settings\BankManager;
use App\Livewire\Cashier\CashierRegister;

class BankManagementAndMultiCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin_banks@jsfood.dev',
        ]);

        Configuration::updateOrCreate(['id' => 1], [
            'restaurant_name' => 'JS Food Gourmet',
            'bcv_rate' => 60.00,
            'cop_rate' => 4100.00,
            'binance_rate' => 70.00,
            'currency_mode' => 'multi',
            'primary_currency_code' => 'USD',
        ]);

        \App\Models\Currency::create(['code' => 'USD', 'name' => 'Dólar Americano', 'symbol' => '$', 'exchange_rate' => 1.0, 'is_primary' => true]);
        \App\Models\Currency::create(['code' => 'VED', 'name' => 'Bolívar Digital', 'symbol' => 'Bs', 'exchange_rate' => 60.0, 'is_primary' => false]);
        \App\Models\Currency::create(['code' => 'COP', 'name' => 'Peso Colombiano', 'symbol' => 'COP$', 'exchange_rate' => 4100.0, 'is_primary' => false]);
        \App\Models\Currency::create(['code' => 'USDT', 'name' => 'Binance USDT', 'symbol' => '₮', 'exchange_rate' => 1.0, 'is_primary' => false]);
    }

    public function test_bank_manager_screen_renders_for_admin(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('settings.banks'))
            ->assertStatus(200)
            ->assertSee('Bancos y Monedas');
    }

    public function test_can_change_primary_currency(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(BankManager::class)
            ->set('primaryCurrency', 'VED')
            ->call('setPrimaryCurrency')
            ->assertDispatched('notify');

        $config = Configuration::first();
        $this->assertEquals('VED', $config->primary_currency_code);
        $this->assertTrue(\App\Models\Currency::where('code', 'VED')->value('is_primary'));
        $this->assertFalse(\App\Models\Currency::where('code', 'USD')->value('is_primary'));
    }

    public function test_binance_pay_usdt_has_1_to_1_usd_parity_in_cashier(): void
    {
        $bankUsdt = Bank::create([
            'name' => 'Binance Pay USDT',
            'type' => 'binance',
            'currency_code' => 'USDT',
            'account_holder' => 'JS Food Cripto',
            'account_number' => '123456789',
            'state' => true,
        ]);

        $category = Category::create([
            'name' => 'Postres',
            'slug' => 'postres',
            'color' => '#ec4899',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cheesecake',
            'price_usd' => 15.00,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-USDT',
            'user_id' => $this->adminUser->id,
            'type' => 'takeout',
            'status' => 'pending',
            'subtotal_usd' => 15.00,
            'total_usd' => 15.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 15.00,
            'subtotal_usd' => 15.00,
            'total_price_usd' => 15.00,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(CashierRegister::class)
            ->call('selectOrder', $order->id)
            ->call('selectBank', $bankUsdt->id);

        $details = $component->instance()->getActiveCurrencyDetails();
        $this->assertEquals('USDT', $details['code']);
        $this->assertEquals('₮', $details['symbol']);
        $this->assertEquals(1.0000, $details['rate']);

        // Remaining amount should be $15 USD * 1 = 15.00 USDT (NOT 15 * 1006.2!)
        $this->assertEquals(15.00, (float) $component->get('amountReceived'));
    }

    public function test_can_update_global_exchange_rates(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(BankManager::class)
            ->set('bcvRate', 65.50)
            ->set('copRate', 4250.00)
            ->call('saveRates')
            ->assertDispatched('notify');

        $config = Configuration::first();
        $this->assertEquals(65.50, (float) $config->bcv_rate);
        $this->assertEquals(4250.00, (float) $config->cop_rate);
    }

    public function test_can_create_and_update_bank_account(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(BankManager::class)
            ->call('openCreateModal')
            ->set('name', 'Bancolombia Ahorros')
            ->set('currency_code', 'COP')
            ->set('type', 'bank_transfer')
            ->set('account_holder', 'JS Food SAS')
            ->set('account_number', '9876543210')
            ->set('cedula', '900123456-7')
            ->set('phone', '+57 300 123 4567')
            ->call('saveBank')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('banks', [
            'name' => 'Bancolombia Ahorros',
            'currency_code' => 'COP',
            'account_holder' => 'JS Food SAS',
        ]);

        $bank = Bank::where('name', 'Bancolombia Ahorros')->first();

        // Edit
        Livewire::actingAs($this->adminUser)
            ->test(BankManager::class)
            ->call('openEditModal', $bank->id)
            ->set('account_holder', 'JS Food Gourmet SAS')
            ->call('saveBank');

        $this->assertDatabaseHas('banks', [
            'id' => $bank->id,
            'account_holder' => 'JS Food Gourmet SAS',
        ]);
    }

    public function test_cashier_detects_currency_and_calculates_correct_amount_for_cop(): void
    {
        $bankCop = Bank::create([
            'name' => 'Bancolombia POS',
            'type' => 'card',
            'currency_code' => 'COP',
            'account_holder' => 'JS Food SAS',
            'account_number' => '12345',
            'state' => true,
        ]);

        $category = Category::create([
            'name' => 'Platos',
            'slug' => 'platos',
            'color' => '#f59e0b',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bandeja Paisa',
            'price_usd' => 10.00,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-COP',
            'user_id' => $this->adminUser->id,
            'type' => 'takeout',
            'status' => 'pending',
            'subtotal_usd' => 10.00,
            'total_usd' => 10.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 10.00,
            'subtotal_usd' => 10.00,
            'total_price_usd' => 10.00,
        ]);

        // When cashier selects Bancolombia COP
        $component = Livewire::actingAs($this->adminUser)
            ->test(CashierRegister::class)
            ->call('selectOrder', $order->id)
            ->call('selectBank', $bankCop->id);

        $details = $component->instance()->getActiveCurrencyDetails();
        $this->assertEquals('COP', $details['code']);
        $this->assertEquals('COP$', $details['symbol']);
        $this->assertEquals(4100.00, $details['rate']);

        // Remaining amount should be $10 USD * 4,100 = 41,000 COP
        $this->assertEquals(41000.00, (float) $component->get('amountReceived'));
    }

    public function test_cashier_detects_currency_and_calculates_correct_amount_for_ved(): void
    {
        $bankBs = Bank::create([
            'name' => 'Banesco Pago Móvil',
            'type' => 'pago_movil',
            'currency_code' => 'VED',
            'account_holder' => 'Juan Silva',
            'cedula' => 'V-12345678',
            'phone' => '04141234567',
            'state' => true,
        ]);

        $category = Category::create([
            'name' => 'Bebidas',
            'slug' => 'bebidas',
            'color' => '#10b981',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Jugo Natural',
            'price_usd' => 2.00,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-VED',
            'user_id' => $this->adminUser->id,
            'type' => 'takeout',
            'status' => 'pending',
            'subtotal_usd' => 2.00,
            'total_usd' => 2.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 2.00,
            'subtotal_usd' => 2.00,
            'total_price_usd' => 2.00,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(CashierRegister::class)
            ->call('selectOrder', $order->id)
            ->call('selectBank', $bankBs->id);

        $details = $component->instance()->getActiveCurrencyDetails();
        $this->assertEquals('VED', $details['code']);
        $this->assertEquals('Bs', $details['symbol']);
        $this->assertEquals(60.00, $details['rate']);

        // Remaining amount should be $2 USD * 60 = 120.00 Bs
        $this->assertEquals(120.00, (float) $component->get('amountReceived'));
    }
}
