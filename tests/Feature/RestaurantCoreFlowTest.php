<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Area;
use App\Models\Table;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\Currency;
use App\Helpers\CurrencyHelper;
use App\Services\TicketPrinterService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\RestaurantSampleSeeder;

class RestaurantCoreFlowTest extends TestCase
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
     * Test 1: Verificar que los usuarios y roles de restaurante están configurados.
     */
    public function test_restaurant_roles_and_users_exist(): void
    {
        $admin = User::where('email', 'admin@jsfood.dev')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('Super Admin'));

        $waiter = User::where('email', 'mesonero1@jsfood.dev')->first();
        $this->assertNotNull($waiter);
        $this->assertTrue($waiter->hasRole('Mesonero'));

        $chef = User::where('email', 'cocina@jsfood.dev')->first();
        $this->assertNotNull($chef);
        $this->assertTrue($chef->hasRole('Cocinero'));
    }

    /**
     * Test 2: Múltiples Cuentas / Separación de Cuentas en la misma Mesa.
     */
    public function test_table_supports_multiple_independent_accounts_and_syncs_status(): void
    {
        $table = Table::where('name', 'Mesa 1')->first();
        $waiter = User::where('email', 'mesonero1@jsfood.dev')->first();
        $burger = Product::where('code', 'PLATO-BURGER-01')->first();

        $this->assertEquals('free', $table->status);

        // Cuenta A: Cliente 1
        $orderA = Order::create([
            'table_id' => $table->id,
            'area_id' => $table->area_id,
            'user_id' => $waiter->id,
            'account_label' => 'Cuenta 1 (Juan)',
            'status' => 'pending',
            'service_type' => 'dine_in',
        ]);

        OrderItem::create([
            'order_id' => $orderA->id,
            'product_id' => $burger->id,
            'quantity' => 1,
            'unit_price_usd' => $burger->price_usd,
            'subtotal_usd' => $burger->price_usd,
            'notes' => 'Sin cebolla',
        ]);
        $orderA->calculateTotals();
        $table->syncStatus();

        // La mesa debe estar ocupada con 1 cuenta activa
        $table->refresh();
        $this->assertEquals('occupied', $table->status);
        $this->assertEquals(1, $table->active_orders_count);

        // Cuenta B: Cliente 2 en la misma mesa (desconocidos o cuenta separada)
        $orderB = Order::create([
            'table_id' => $table->id,
            'area_id' => $table->area_id,
            'user_id' => $waiter->id,
            'account_label' => 'Cuenta 2 (Pedro)',
            'status' => 'pending',
            'service_type' => 'dine_in',
        ]);
        $orderB->calculateTotals();
        $table->syncStatus();

        $table->refresh();
        $this->assertEquals(2, $table->active_orders_count);

        // Se envía a cocina la Cuenta A
        $orderA->sendToKitchen();
        $table->refresh();
        $this->assertEquals('kitchen', $table->status);

        // Cliente 1 paga su Cuenta A
        $orderA->closeAndPay($waiter->id);
        $table->refresh();

        // La mesa DEBE seguir ocupada porque la Cuenta B sigue activa
        $this->assertEquals('occupied', $table->status);
        $this->assertEquals(1, $table->active_orders_count);

        // Cliente 2 paga su Cuenta B
        $orderB->closeAndPay($waiter->id);
        $table->refresh();

        // Ahora que todas las cuentas están pagadas, la mesa pasa a libre
        $this->assertEquals('free', $table->status);
        $this->assertEquals(0, $table->active_orders_count);
    }

    /**
     * Test 3: Modelo Híbrido de Inventario (Descuento automático de insumos por receta de plato).
     */
    public function test_hybrid_inventory_deducts_recipe_supplies_on_sale(): void
    {
        $burger = Product::where('code', 'PLATO-BURGER-01')->first();
        $pan = Product::where('code', 'INS-PAN-01')->first();
        $carne = Product::where('code', 'INS-CARNE-01')->first();
        $queso = Product::where('code', 'INS-QUESO-01')->first();

        $initialPanStock = $pan->current_stock;     // 100
        $initialCarneStock = $carne->current_stock; // 20 kg
        $initialQuesoStock = $queso->current_stock; // 150

        // Se venden 2 Hamburguesas Clásicas
        $waiter = User::where('email', 'mesonero1@jsfood.dev')->first();
        $order = Order::create([
            'user_id' => $waiter->id,
            'service_type' => 'takeout',
            'account_label' => 'Para Llevar',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $burger->id,
            'quantity' => 2,
            'unit_price_usd' => $burger->price_usd,
            'subtotal_usd' => $burger->price_usd * 2,
        ]);
        $order->calculateTotals();

        // Cobrar la orden (dispara deductStockForSale)
        $order->closeAndPay($waiter->id);

        $pan->refresh();
        $carne->refresh();
        $queso->refresh();

        // 2 hamburguesas requieren 2 panes (100 - 2 = 98)
        $this->assertEquals($initialPanStock - 2, $pan->current_stock);
        // 2 hamburguesas requieren 2 * 0.180 kg = 0.360 kg (20 - 0.36 = 19.64)
        $this->assertEquals($initialCarneStock - 0.360, $carne->current_stock);
        // 2 hamburguesas requieren 2 * 2 = 4 lonjas de queso (150 - 4 = 146)
        $this->assertEquals($initialQuesoStock - 4, $queso->current_stock);
    }

    /**
     * Test 4: Validación de Crédito a Clientes.
     */
    public function test_customer_credit_limit_validation(): void
    {
        $customer = Customer::where('identification', 'J-31000999-1')->first();
        $this->assertTrue($customer->allow_credit);
        $this->assertEquals(500.00, $customer->credit_limit);

        // Puede comprar a crédito dentro del límite
        $this->assertTrue($customer->hasAvailableCredit(200.00));

        // Registrar compra de 300 USD
        $customer->adjustBalance(300.00, 'credit_charge', null, null, 'REF-001');
        $customer->refresh();
        $this->assertEquals(300.00, $customer->current_balance);

        // Le quedan 200 de saldo disponible: no puede exceder 500
        $this->assertTrue($customer->hasAvailableCredit(200.00));
        $this->assertFalse($customer->hasAvailableCredit(201.00));
    }

    /**
     * Test 5: Generación de Tickets Térmicos 58mm y 80mm adaptables.
     */
    public function test_ticket_printer_service_generates_tickets_for_58mm_and_80mm(): void
    {
        $order = Order::with('items.product')->first();
        if (!$order) {
            $burger = Product::where('code', 'PLATO-BURGER-01')->first();
            $waiter = User::first();
            $order = Order::create(['user_id' => $waiter->id, 'account_label' => 'Mesa 1 - Cuenta A']);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $burger->id,
                'quantity' => 1,
                'unit_price_usd' => 8.50,
                'subtotal_usd' => 8.50,
                'notes' => 'Término medio',
            ]);
            $order->calculateTotals();
        }

        // Ticket de cocina 80mm y 58mm
        $kitchen80 = TicketPrinterService::buildKitchenTicket($order, 80);
        $kitchen58 = TicketPrinterService::buildKitchenTicket($order, 58);
        $this->assertNotEmpty($kitchen80);
        $this->assertNotEmpty($kitchen58);

        // Pre-cuenta 80mm y 58mm
        $preBill80 = TicketPrinterService::buildPreBillTicket($order, 80);
        $preBill58 = TicketPrinterService::buildPreBillTicket($order, 58);
        $this->assertNotEmpty($preBill80);
        $this->assertNotEmpty($preBill58);

        // Comprobante de pago 80mm
        $receipt80 = TicketPrinterService::buildReceiptTicket($order, 80);
        $this->assertNotEmpty($receipt80);
    }
}
