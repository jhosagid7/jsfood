<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Table;
use App\Models\Product;
use App\Models\Order;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Tables\TableMap;
use App\Livewire\Pos\OrderTerminal;
use App\Livewire\Kitchen\KitchenDisplay;
use App\Livewire\Cashier\CashierRegister;
use App\Livewire\Settings\PrinterSettings;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\RestaurantSampleSeeder;

class LivewireFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(RestaurantSampleSeeder::class);
    }

    /**
     * Test 1: Verificar que el plano de mesas carga y abre nuevas cuentas.
     */
    public function test_table_map_component_renders_and_opens_new_account(): void
    {
        $table = Table::where('name', 'Mesa 1')->first();

        Livewire::test(TableMap::class)
            ->assertStatus(200)
            ->assertSee('Salón Principal')
            ->assertSee('Mesa 1')
            ->call('openTable', $table->id)
            ->set('newAccountLabel', 'Cuenta Familia Pérez')
            ->call('createNewAccount')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('orders', [
            'table_id' => $table->id,
            'account_label' => 'Cuenta Familia Pérez',
        ]);
    }

    /**
     * Test 2: Terminal de Comandas para el Mesonero (agregar producto, nota y enviar a cocina).
     */
    public function test_order_terminal_adds_product_and_sends_to_kitchen(): void
    {
        $table = Table::where('name', 'Mesa 2')->first();
        $burger = Product::where('code', 'PLATO-BURGER-01')->first();

        Livewire::test(OrderTerminal::class, ['tableId' => $table->id])
            ->assertStatus(200)
            ->assertSee('Mesa 2')
            ->call('addProduct', $burger->id)
            ->assertDispatched('notify');

        $order = Order::where('table_id', $table->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(8.50, $order->total_usd);

        // Agregar nota especial para el cocinero
        $item = $order->items()->first();
        Livewire::test(OrderTerminal::class, ['tableId' => $table->id])
            ->set('orderId', $order->id)
            ->call('openNoteModal', $item->id)
            ->set('itemNoteText', 'Sin cebolla, término medio')
            ->call('saveItemNote')
            ->call('sendOrderToKitchen')
            ->assertDispatched('notify');

        $item->refresh();
        $this->assertEquals('Sin cebolla, término medio', $item->notes);

        $order->refresh();
        $this->assertEquals('in_kitchen', $order->status);
    }

    public function test_order_terminal_renders_cleanly_without_table_or_order(): void
    {
        Livewire::test(OrderTerminal::class)
            ->assertStatus(200)
            ->assertSee('Para Llevar / Mostrador')
            ->assertSee('Comanda Activa');
    }

    /**
     * Test 3: Pantalla de Cocina KDS (avanzar pedido a listo para servir).
     */
    public function test_kitchen_display_handles_order_lifecycle(): void
    {
        $waiter = User::where('email', 'mesonero1@jsfood.dev')->first();
        $burger = Product::where('code', 'PLATO-BURGER-01')->first();

        $order = Order::create([
            'user_id' => $waiter->id,
            'account_label' => 'Mesa 3 - Cuenta Rápida',
            'status' => 'in_kitchen',
        ]);
        $order->items()->create([
            'product_id' => $burger->id,
            'quantity' => 1,
            'unit_price_usd' => 8.50,
            'subtotal_usd' => 8.50,
            'notes' => 'Extra queso',
        ]);

        Livewire::test(KitchenDisplay::class)
            ->assertStatus(200)
            ->assertSee('Mesa 3 - Cuenta Rápida')
            ->assertSee('Extra queso')
            ->call('markAsReady', $order->id)
            ->assertDispatched('notify');

        $order->refresh();
        $this->assertEquals('ready', $order->status);
    }

    /**
     * Test 4: Caja Registradora y Cobro Multimoneda con Ticket Térmico.
     */
    public function test_cashier_register_processes_payment(): void
    {
        $waiter = User::where('email', 'mesonero1@jsfood.dev')->first();
        $burger = Product::where('code', 'PLATO-BURGER-01')->first();

        $order = Order::create([
            'user_id' => $waiter->id,
            'account_label' => 'Mesa 4 - Pago',
            'status' => 'ready',
        ]);
        $order->items()->create([
            'product_id' => $burger->id,
            'quantity' => 1,
            'unit_price_usd' => 8.50,
            'subtotal_usd' => 8.50,
        ]);
        $order->calculateTotals();

        Livewire::test(CashierRegister::class, ['order_id' => $order->id])
            ->assertStatus(200)
            ->assertSee('Mesa 4 - Pago')
            ->set('selectedPaymentMethod', 'cash_usd')
            ->set('amountReceived', 8.50)
            ->call('processCheckout')
            ->assertDispatched('notify');

        $order->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'cash_usd',
            'amount_usd' => 8.50,
        ]);
    }

    /**
     * Test 5: Configuración de Impresoras (Ajuste 58mm y 80mm).
     */
    public function test_printer_settings_updates_hardware_configuration(): void
    {
        Livewire::test(PrinterSettings::class)
            ->assertStatus(200)
            ->set('cashierPrinter', 'EPSON-TM-T20')
            ->set('cashierWidth', 80)
            ->set('kitchenPrinter', 'BIXOLON-58')
            ->set('kitchenWidth', 58)
            ->set('currencyMode', 'multi')
            ->call('saveSettings')
            ->assertDispatched('notify');

        $config = Configuration::first();
        $this->assertEquals('EPSON-TM-T20', $config->printer_name);
        $this->assertEquals(80, $config->printer_width);
        $this->assertEquals('BIXOLON-58', $config->kitchen_printer_name);
        $this->assertEquals(58, $config->kitchen_printer_width);
    }
}
