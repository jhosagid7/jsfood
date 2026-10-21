<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Customer;
use App\Models\Bank;
use App\Models\Configuration;
use App\Livewire\Customers\CustomerManager;
use App\Livewire\Cashier\CashierRegister;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Notifications\CreditPaymentReceivedNotification;
use App\Notifications\CreditPaymentReminderNotification;

class CustomerCreditManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin_test@jsfood.dev',
        ]);
    }

    public function test_customer_manager_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('customers.index'))
            ->assertStatus(200)
            ->assertSee('Cuentas por Cobrar');
    }

    public function test_can_create_customer_with_credit_limit_and_days(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(CustomerManager::class)
            ->call('openCreateModal')
            ->set('name', 'Corporación Los Andes')
            ->set('identification', 'J-12345678-9')
            ->set('phone', '04141234567')
            ->set('email', 'contacto@losandes.com')
            ->set('allow_credit', true)
            ->set('credit_limit', 250.00)
            ->set('credit_days', 30)
            ->call('saveCustomer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'name' => 'Corporación Los Andes',
            'identification' => 'J-12345678-9',
            'email' => 'contacto@losandes.com',
            'allow_credit' => true,
            'credit_limit' => 250.00,
            'credit_days' => 30,
        ]);
    }

    public function test_can_edit_existing_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente Original',
            'allow_credit' => false,
            'credit_limit' => 0,
            'credit_days' => 15,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(CustomerManager::class)
            ->call('editCustomer', $customer->id)
            ->set('name', 'Cliente Actualizado')
            ->set('allow_credit', true)
            ->set('credit_limit', 150.00)
            ->set('credit_days', 7)
            ->call('saveCustomer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Cliente Actualizado',
            'allow_credit' => true,
            'credit_limit' => 150.00,
            'credit_days' => 7,
        ]);
    }

    public function test_customer_overdue_calculation_and_status(): void
    {
        // 1. Cliente al día sin deuda
        $customerClean = Customer::create([
            'name' => 'Cliente Solvente',
            'allow_credit' => true,
            'credit_limit' => 200.00,
            'current_balance' => 0.00,
            'credit_days' => 15,
        ]);
        $this->assertEquals('no_debt', $customerClean->credit_status);

        // 2. Cliente con crédito vencido (hace más de 15 días)
        $customerOverdue = Customer::create([
            'name' => 'Cliente Moroso',
            'allow_credit' => true,
            'credit_limit' => 200.00,
            'current_balance' => 50.00,
            'credit_days' => 10,
        ]);

        // Simular compra a crédito ocurrida hace 20 días
        $customerOverdue->transactions()->create([
            'type' => 'credit_charge',
            'amount_usd' => 50.00,
            'currency_code' => 'USD',
            'amount_currency' => 50.00,
            'created_at' => now()->subDays(20),
        ]);

        $this->assertEquals('overdue', $customerOverdue->credit_status);
        $this->assertTrue(now()->greaterThan($customerOverdue->credit_due_date));
    }

    public function test_can_process_abono_and_decrease_balance(): void
    {
        Notification::fake();
        Storage::fake('public');

        Configuration::create([
            'business_name' => 'Test Rest',
            'bcv_rate' => 60.00,
            'cop_rate' => 4100.00,
        ]);

        $bank = Bank::create([
            'name' => 'Banesco Banco Universal',
            'type' => 'pago_movil',
            'currency_code' => 'VED',
            'state' => true,
            'sort' => 1,
        ]);

        $customer = Customer::create([
            'name' => 'Pedro Deudor',
            'email' => 'pedro@correo.com',
            'allow_credit' => true,
            'credit_limit' => 100.00,
            'current_balance' => 60.00,
            'credit_days' => 15,
        ]);

        $voucher = UploadedFile::fake()->image('pago_movil.jpg');

        Livewire::actingAs($this->adminUser)
            ->test(CustomerManager::class)
            ->call('openAbonoModal', $customer->id)
            ->set('abonoAmountUsd', 25.00)
            ->call('setAbonoMethod', 'pago_movil')
            ->set('abonoBankId', $bank->id)
            // Verificar conversión automática a Bolívares: $25 * 60 = 1500 Bs
            ->assertSet('abonoCurrency', 'VED')
            ->assertSet('abonoAmountCurrency', 1500.00)
            ->set('abonoProof', $voucher)
            ->set('abonoReference', 'REF-998877')
            ->call('processAbono')
            ->assertHasNoErrors();

        $customer->refresh();
        $this->assertEquals(35.00, $customer->current_balance);

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'type' => 'payment_abono',
            'amount_usd' => 25.00,
            'currency_code' => 'VED',
            'amount_currency' => 1500.00,
            'reference' => 'REF-998877',
        ]);

        Notification::assertSentTo($customer, CreditPaymentReceivedNotification::class);
    }

    public function test_abono_requires_proof_image_for_digital_payments_but_not_cash(): void
    {
        Configuration::create([
            'business_name' => 'Test Rest',
            'bcv_rate' => 60.00,
        ]);

        $customer = Customer::create([
            'name' => 'Ana Morosa',
            'allow_credit' => true,
            'credit_limit' => 100.00,
            'current_balance' => 40.00,
        ]);

        // 1. Pago móvil sin capture debe fallar validación
        Livewire::actingAs($this->adminUser)
            ->test(CustomerManager::class)
            ->call('openAbonoModal', $customer->id)
            ->call('setAbonoMethod', 'pago_movil')
            ->set('abonoAmountUsd', 10.00)
            ->call('processAbono')
            ->assertHasErrors(['abonoProof']);

        // 2. Efectivo USD no requiere capture
        Livewire::actingAs($this->adminUser)
            ->test(CustomerManager::class)
            ->call('openAbonoModal', $customer->id)
            ->call('setAbonoMethod', 'cash_usd')
            ->set('abonoAmountUsd', 10.00)
            ->call('processAbono')
            ->assertHasNoErrors();

        $customer->refresh();
        $this->assertEquals(30.00, $customer->current_balance);
    }

    public function test_cannot_delete_customer_with_active_debt(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente con Saldo',
            'allow_credit' => true,
            'credit_limit' => 100.00,
            'current_balance' => 20.00,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(CustomerManager::class)
            ->call('deleteCustomer', $customer->id)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_can_send_reminder_email_notification(): void
    {
        Notification::fake();

        $customer = Customer::create([
            'name' => 'Carlos Por Cobrar',
            'email' => 'carlos@empresa.com',
            'allow_credit' => true,
            'credit_limit' => 300.00,
            'current_balance' => 80.00,
            'credit_days' => 7,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(CustomerManager::class)
            ->call('sendReminderEmail', $customer->id)
            ->assertDispatched('notify');

        Notification::assertSentTo($customer, CreditPaymentReminderNotification::class);
    }

    public function test_cashier_defaults_to_contado_and_can_choose_contado_for_credit_customer(): void
    {
        $category = Category::create(['name' => 'Comida', 'slug' => 'comida', 'sort' => 1]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Hamburguesa Especial',
            'slug' => 'hamburguesa-especial',
            'price_usd' => 10.00,
            'cost_usd' => 4.00,
            'type' => 'prepared',
            'state' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Cliente VIP con Credito',
            'allow_credit' => true,
            'credit_limit' => 500.00,
            'current_balance' => 0.00,
        ]);

        $order = Order::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $customer->id,
            'service_type' => 'dine_in',
            'status' => 'pending',
            'total_usd' => 10.00,
            'subtotal_usd' => 10.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_usd' => 10.00,
            'subtotal_usd' => 10.00,
        ]);

        // Al seleccionar la orden, debe estar en Contado (cash_usd) por defecto
        $test = Livewire::actingAs($this->adminUser)
            ->test(CashierRegister::class)
            ->call('selectOrder', $order->id)
            ->assertSet('selectedPaymentMethod', 'cash_usd');

        // El cajero puede cobrar al contado aunque el cliente tenga crédito aprobado
        $test->set('amountReceived', 10.00)
            ->call('processCheckout')
            ->assertDispatched('notify');

        $order->refresh();
        $customer->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertEquals(0.00, $customer->current_balance); // No se cargó a crédito, fue de contado
    }

    public function test_cashier_blocks_credit_for_customer_without_credit_permission(): void
    {
        $customerWithoutCredit = Customer::create([
            'name' => 'Cliente Sin Credito',
            'allow_credit' => false,
            'credit_limit' => 0.00,
            'current_balance' => 0.00,
        ]);

        $order = Order::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $customerWithoutCredit->id,
            'service_type' => 'dine_in',
            'status' => 'pending',
            'total_usd' => 15.00,
            'subtotal_usd' => 15.00,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(CashierRegister::class)
            ->call('selectOrder', $order->id)
            ->assertSet('selectedPaymentMethod', 'cash_usd')
            // Intentar seleccionar crédito debe ser rechazado y volver a cash_usd
            ->set('selectedPaymentMethod', 'credit')
            ->assertSet('selectedPaymentMethod', 'cash_usd')
            ->assertDispatched('notify');
    }
}
