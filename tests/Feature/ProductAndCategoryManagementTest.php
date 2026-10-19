<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\Area;
use App\Models\Configuration;
use App\Services\TicketPrinterService;
use App\Livewire\Products\ProductList;
use App\Livewire\Products\CategoryManager;
use App\Livewire\Kitchen\KitchenDisplay;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class ProductAndCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::updateOrCreate(['id' => 1], [
            'business_name' => 'Donde Cholao Grill',
            'printer_name' => 'POS-80',
            'kitchen_printer_name' => 'KITCHEN-58',
            'bcv_rate' => 36.5,
            'cop_rate' => 4000.0,
            'currency_mode' => 'multi',
        ]);

        $role = Role::firstOrCreate(['name' => 'Admin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);
    }

    public function test_products_and_categories_pages_render_for_authenticated_user(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/products')
            ->assertStatus(200)
            ->assertSee('Catálogo de Productos');

        $this->actingAs($this->adminUser)
            ->get('/categories')
            ->assertStatus(200)
            ->assertSee('Categorías del Menú');
    }

    public function test_can_create_category_via_livewire(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CategoryManager::class)
            ->set('name', 'Pizzas Artesanales')
            ->set('icon', '🍕')
            ->set('sortOrder', 5)
            ->call('saveCategory')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('categories', [
            'name' => 'Pizzas Artesanales',
            'slug' => 'pizzas-artesanales',
            'icon' => '🍕',
        ]);
    }

    public function test_can_create_dish_that_goes_to_kitchen(): void
    {
        $this->actingAs($this->adminUser);
        $cat = Category::create(['name' => 'Hamburguesas', 'slug' => 'hamburguesas', 'icon' => '🍔', 'sort_order' => 1]);

        Livewire::test(ProductList::class)
            ->call('openCreateModal')
            ->set('name', 'Hamburguesa Doble Queso')
            ->set('code', 'HAMB-DQ')
            ->set('categoryId', $cat->id)
            ->set('priceUsd', 7.50)
            ->set('type', 'dish')
            ->set('sendToKitchen', true)
            ->set('currentStock', 50)
            ->set('minStock', 5)
            ->set('unit', 'unit')
            ->call('saveProduct')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('products', [
            'name' => 'Hamburguesa Doble Queso',
            'code' => 'HAMB-DQ',
            'price_usd' => 7.50,
            'type' => 'dish',
            'send_to_kitchen' => true,
        ]);
    }

    public function test_can_create_direct_drink_that_does_not_go_to_kitchen(): void
    {
        $this->actingAs($this->adminUser);
        $cat = Category::create(['name' => 'Bebidas', 'slug' => 'bebidas', 'icon' => '🍾', 'sort_order' => 2]);

        Livewire::test(ProductList::class)
            ->call('openCreateModal')
            ->set('name', 'Coca-Cola 350ml')
            ->set('code', 'BEB-COCA350')
            ->set('categoryId', $cat->id)
            ->set('priceUsd', 1.00)
            ->set('type', 'direct')
            ->set('sendToKitchen', false)
            ->set('currentStock', 100)
            ->set('minStock', 12)
            ->set('unit', 'unit')
            ->call('saveProduct')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('products', [
            'name' => 'Coca-Cola 350ml',
            'type' => 'direct',
            'send_to_kitchen' => false,
        ]);
    }

    public function test_toggle_send_to_kitchen_switches_destination(): void
    {
        $this->actingAs($this->adminUser);
        $prod = Product::create([
            'name' => 'Té Frío',
            'code' => 'TE-FRIO',
            'price_usd' => 1.50,
            'type' => 'direct',
            'send_to_kitchen' => false,
            'unit' => 'unit',
            'current_stock' => 20,
            'min_stock' => 5,
        ]);

        Livewire::test(ProductList::class)
            ->call('toggleSendToKitchen', $prod->id)
            ->assertDispatched('notify');

        $this->assertTrue($prod->fresh()->send_to_kitchen);

        Livewire::test(ProductList::class)
            ->call('toggleSendToKitchen', $prod->id);

        $this->assertFalse($prod->fresh()->send_to_kitchen);
    }

    public function test_kitchen_ticket_only_prints_products_marked_for_kitchen(): void
    {
        $area = Area::create(['name' => 'Salón Principal', 'color' => '#f59e0b']);
        $table = Table::create(['area_id' => $area->id, 'name' => 'Mesa 1', 'table_number' => 1, 'capacity' => 4, 'status' => 'busy']);

        $order = Order::create([
            'order_number' => 'ORD-1001',
            'dining_table_id' => $table->id,
            'user_id' => $this->adminUser->id,
            'status' => 'pending',
            'total_usd' => 11.50,
        ]);

        $burger = Product::create([
            'name' => 'Hamburguesa Especial',
            'price_usd' => 6.00,
            'type' => 'dish',
            'send_to_kitchen' => true,
            'unit' => 'unit',
        ]);

        $soda = Product::create([
            'name' => 'Pepsi 1L',
            'price_usd' => 1.75,
            'type' => 'direct',
            'send_to_kitchen' => false,
            'unit' => 'unit',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $burger->id,
            'quantity' => 1,
            'unit_price_usd' => 6.00,
            'subtotal_usd' => 6.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $soda->id,
            'quantity' => 2,
            'unit_price_usd' => 1.75,
            'subtotal_usd' => 3.50,
        ]);

        $order->load('items.product', 'diningTable', 'user');

        // El ticket de cocina SOLO debe contener la Hamburguesa Especial y NO la Pepsi
        $kitchenTicket = TicketPrinterService::buildKitchenTicket($order, 58);

        $this->assertStringContainsString('Hamburguesa Especial', $kitchenTicket);
        $this->assertStringNotContainsString('Pepsi 1L', $kitchenTicket);

        // Si la orden tuviera SOLO refrescos directos, el ticket de cocina retorna vacío (no gasta papel)
        $drinkOrder = Order::create([
            'order_number' => 'ORD-1002',
            'dining_table_id' => $table->id,
            'user_id' => $this->adminUser->id,
            'status' => 'pending',
            'total_usd' => 3.50,
        ]);

        OrderItem::create([
            'order_id' => $drinkOrder->id,
            'product_id' => $soda->id,
            'quantity' => 2,
            'unit_price_usd' => 1.75,
            'subtotal_usd' => 3.50,
        ]);

        $drinkOrder->load('items.product', 'diningTable', 'user');
        $emptyTicket = TicketPrinterService::buildKitchenTicket($drinkOrder, 58);
        $this->assertEmpty($emptyTicket);
    }

    public function test_kitchen_display_only_shows_orders_with_kitchen_items(): void
    {
        $this->actingAs($this->adminUser);
        $area = Area::create(['name' => 'Terraza', 'color' => '#10b981']);
        $table = Table::create(['area_id' => $area->id, 'name' => 'Mesa 2', 'table_number' => 2, 'capacity' => 4, 'status' => 'busy']);

        $burger = Product::create([
            'name' => 'Perro Especial',
            'price_usd' => 4.50,
            'type' => 'dish',
            'send_to_kitchen' => true,
            'unit' => 'unit',
        ]);

        $water = Product::create([
            'name' => 'Agua Nevada',
            'price_usd' => 1.00,
            'type' => 'direct',
            'send_to_kitchen' => false,
            'unit' => 'unit',
        ]);

        // Orden 1: Tiene Perro Especial (Cocina) y Agua (Directo)
        $orderWithKitchen = Order::create([
            'order_number' => 'ORD-2001',
            'dining_table_id' => $table->id,
            'user_id' => $this->adminUser->id,
            'status' => 'pending',
            'total_usd' => 5.50,
        ]);
        OrderItem::create(['order_id' => $orderWithKitchen->id, 'product_id' => $burger->id, 'quantity' => 1, 'unit_price_usd' => 4.50, 'subtotal_usd' => 4.50]);
        OrderItem::create(['order_id' => $orderWithKitchen->id, 'product_id' => $water->id, 'quantity' => 1, 'unit_price_usd' => 1.00, 'subtotal_usd' => 1.00]);

        // Orden 2: Solo Agua Nevada (No Cocina)
        $orderOnlyWater = Order::create([
            'order_number' => 'ORD-2002',
            'dining_table_id' => $table->id,
            'user_id' => $this->adminUser->id,
            'status' => 'pending',
            'total_usd' => 2.00,
        ]);
        OrderItem::create(['order_id' => $orderOnlyWater->id, 'product_id' => $water->id, 'quantity' => 2, 'unit_price_usd' => 1.00, 'subtotal_usd' => 2.00]);

        Livewire::test(KitchenDisplay::class)
            ->assertSee('ORD-2001')
            ->assertSee('Perro Especial')
            ->assertDontSee('ORD-2002')
            ->assertDontSee('Agua Nevada');
    }
}
