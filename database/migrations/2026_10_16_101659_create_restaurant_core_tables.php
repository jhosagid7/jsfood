<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Configuraciones del Sistema y Licencia
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->default('Mi Restaurante');
            $table->string('taxpayer_id')->nullable(); // RIF / NIT / Cédula fiscal
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('currency_mode')->default('multi'); // 'multi' o 'single'
            $table->string('primary_currency_code')->default('USD');
            $table->decimal('bcv_rate', 15, 4)->default(1.0000);
            $table->decimal('binance_rate', 15, 4)->default(1.0000);
            $table->decimal('cop_rate', 15, 4)->default(4000.0000);
            $table->string('printer_name')->nullable();
            $table->integer('printer_width')->default(80); // 58 o 80 mm
            $table->string('kitchen_printer_name')->nullable();
            $table->integer('kitchen_printer_width')->default(80);
            $table->string('device_access_mode')->default('open'); // 'open' o 'restricted'
            $table->integer('max_devices')->default(10);
            $table->string('license_server_ip')->default('licencias.jhonnypirela.dev');
            $table->string('plan_type')->default('pro');
            $table->timestamps();
        });

        // 2. Monedas
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('label')->nullable();
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 15, 4)->default(1.0000);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // 3. Dispositivos Autorizados (Tablets mesoneros, PCs de caja)
        Schema::create('device_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status')->default('approved'); // 'pending', 'approved', 'rejected'
            $table->string('assigned_printer')->nullable();
            $table->integer('printer_width')->default(80);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
        });

        // 4. Áreas / Salones del Restaurante
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ej. "Salón Principal", "Terraza", "VIP", "Barra"
            $table->string('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Mesas
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->string('name'); // ej. "Mesa 1", "Mesa 2", "Barra 3"
            $table->integer('capacity')->default(4); // Capacidad de personas
            $table->string('status')->default('free'); // 'free', 'occupied', 'kitchen', 'served', 'bill_requested', 'cleaning'
            $table->integer('active_orders_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Categorías de Menú
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Hamburguesas, Arroces, Entradas, Bebidas, Postres
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('color', 20)->default('blue');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. Productos, Platos e Insumos
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('price_usd', 12, 2)->default(0.00);
            $table->string('image')->nullable();
            // direct: refrescos/cervezas (descuento 1 a 1)
            // dish: plato elaborado (hamburguesa, arroz) que puede tener receta
            // supply: insumo crudo (pan, carne, arroz crudo, queso, verduras)
            $table->string('type')->default('dish'); // 'direct', 'dish', 'supply'
            $table->boolean('has_recipe')->default(false); // Si descuenta insumos de product_recipes
            $table->boolean('track_stock')->default(true);
            $table->decimal('current_stock', 12, 3)->default(0.000);
            $table->decimal('min_stock', 12, 3)->default(0.000);
            $table->string('unit', 20)->default('unit'); // 'unit', 'kg', 'portion', 'liter'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 8. Ficha Técnica / Recetas (Descuento de insumos medibles por plato)
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // El plato preparado
            $table->foreignId('supply_id')->constrained('products')->onDelete('cascade');  // El insumo crudo/medible
            $table->decimal('quantity_needed', 12, 3); // ej. 1 pan, 0.200 kg de carne, 1 lonja queso
            $table->string('unit', 20)->default('unit');
            $table->timestamps();
        });

        // 9. Movimientos de Stock (Compras, Descuento por venta, Mise en place a cocina, Mermas)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('type'); // 'purchase', 'dish_sale', 'kitchen_dispatch', 'waste_spoilage', 'adjustment'
            $table->decimal('quantity', 12, 3);
            $table->decimal('previous_stock', 12, 3);
            $table->decimal('new_stock', 12, 3);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 10. Clientes y Cuentas por Cobrar (Crédito)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identification')->nullable(); // Cédula o RIF
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('allow_credit')->default(false);
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->decimal('current_balance', 12, 2)->default(0.00); // Saldo deudor acumulado
            $table->timestamps();
        });

        // 11. Órdenes y Comandas (Soporte Multi-Cuenta / Split Bill por Mesa)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Mesonero o usuario que abrió la comanda
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('service_type')->default('dine_in'); // 'dine_in', 'takeout', 'delivery'
            $table->string('account_label')->default('Cuenta Principal'); // 'Cuenta 1', 'Cuenta 2', 'Asiento A'
            $table->string('status')->default('pending'); // 'pending', 'in_kitchen', 'ready', 'served', 'paid', 'cancelled'
            $table->string('payment_status')->default('unpaid'); // 'unpaid', 'partially_paid', 'paid'
            $table->decimal('subtotal_usd', 12, 2)->default(0.00);
            $table->decimal('discount_usd', 12, 2)->default(0.00);
            $table->decimal('tax_usd', 12, 2)->default(0.00);
            $table->decimal('total_usd', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamp('kitchen_sent_at')->nullable();
            $table->timestamp('kitchen_ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // 12. Ítems de la Comanda
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price_usd', 12, 2);
            $table->decimal('subtotal_usd', 12, 2);
            $table->string('kitchen_status')->default('pending'); // 'pending', 'preparing', 'ready', 'served', 'cancelled'
            $table->text('notes')->nullable(); // ej. "Sin cebolla, término 3/4"
            $table->json('modifiers_data')->nullable(); // extras elegidos con costo o especificaciones
            $table->boolean('printed_to_kitchen')->default(false);
            $table->timestamps();
        });

        // 13. Pagos y Cobros Multimoneda (Caja)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Cajero
            $table->string('payment_method'); // 'cash_usd', 'cash_ved', 'cash_cop', 'card', 'mobile_pay', 'zelle', 'binance_usdt', 'credit'
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 4)->default(1.0000);
            $table->decimal('amount_currency', 15, 2); // Monto pagado en la moneda original (ej. 50000 COP)
            $table->decimal('amount_usd', 12, 2);      // Equivalente en USD recibido
            $table->decimal('change_usd', 12, 2)->default(0.00); // Vuelto entregado en USD
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 14. Transacciones de Crédito de Clientes
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // 'credit_charge' (compra a crédito), 'payment_abono' (pago de deuda)
            $table->decimal('amount_usd', 12, 2);
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('amount_currency', 15, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_recipes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('device_authorizations');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('configurations');
    }
};
