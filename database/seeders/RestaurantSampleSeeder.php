<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuration;
use App\Models\Currency;
use App\Models\Area;
use App\Models\Table;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Customer;

class RestaurantSampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Configuración del Restaurante
        Configuration::firstOrCreate(['id' => 1], [
            'business_name' => 'JSFood Gourmet & Grill',
            'taxpayer_id' => 'J-12345678-0',
            'phone' => '+58 412 1234567',
            'address' => 'Av. Principal Gastronómica #100',
            'currency_mode' => 'multi',
            'primary_currency_code' => 'USD',
            'bcv_rate' => 60.0000,
            'binance_rate' => 62.0000,
            'cop_rate' => 4100.0000,
            'printer_name' => 'POS-80',
            'printer_width' => 80,
            'kitchen_printer_name' => 'KITCHEN-58',
            'kitchen_printer_width' => 58,
            'device_access_mode' => 'open',
            'max_devices' => 20,
            'license_server_ip' => 'licencias.jhonnypirela.dev',
            'plan_type' => 'pro',
        ]);

        // 2. Monedas
        Currency::firstOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar',
            'label' => 'Dólar Americano',
            'symbol' => '$',
            'exchange_rate' => 1.0000,
            'is_primary' => true,
        ]);

        Currency::firstOrCreate(['code' => 'VED'], [
            'name' => 'Bolivar',
            'label' => 'Bolívar Digital (BCV)',
            'symbol' => 'Bs',
            'exchange_rate' => 60.0000,
            'is_primary' => false,
        ]);

        Currency::firstOrCreate(['code' => 'COP'], [
            'name' => 'Peso Colombiano',
            'label' => 'Peso COP',
            'symbol' => 'COP$',
            'exchange_rate' => 4100.0000,
            'is_primary' => false,
        ]);

        Currency::firstOrCreate(['code' => 'USDT'], [
            'name' => 'Tether USDT',
            'label' => 'Binance Pay / USDT',
            'symbol' => '₮',
            'exchange_rate' => 1.0000,
            'is_primary' => false,
        ]);

        // 3. Áreas y Mesas
        $salon = Area::firstOrCreate(['name' => 'Salón Principal'], [
            'description' => 'Área interna con aire acondicionado',
            'sort_order' => 1,
        ]);

        $terraza = Area::firstOrCreate(['name' => 'Terraza al Aire Libre'], [
            'description' => 'Área exterior',
            'sort_order' => 2,
        ]);

        $barra = Area::firstOrCreate(['name' => 'Barra / Tragos'], [
            'description' => 'Mesones altos y servicio rápido',
            'sort_order' => 3,
        ]);

        // Crear Mesas para Salón
        for ($i = 1; $i <= 8; $i++) {
            Table::firstOrCreate(
                ['area_id' => $salon->id, 'name' => "Mesa {$i}"],
                ['capacity' => 4, 'status' => 'free', 'active_orders_count' => 0]
            );
        }

        // Crear Mesas para Terraza
        for ($i = 1; $i <= 4; $i++) {
            Table::firstOrCreate(
                ['area_id' => $terraza->id, 'name' => "Terraza {$i}"],
                ['capacity' => 6, 'status' => 'free', 'active_orders_count' => 0]
            );
        }

        // Crear Puestos de Barra
        for ($i = 1; $i <= 4; $i++) {
            Table::firstOrCreate(
                ['area_id' => $barra->id, 'name' => "Barra {$i}"],
                ['capacity' => 2, 'status' => 'free', 'active_orders_count' => 0]
            );
        }

        // 4. Categorías de Menú
        $catBurgers = Category::firstOrCreate(['slug' => 'hamburguesas'], [
            'name' => 'Hamburguesas',
            'icon' => 'burger',
            'color' => 'amber',
            'sort_order' => 1,
        ]);

        $catArroces = Category::firstOrCreate(['slug' => 'arroces-y-wok'], [
            'name' => 'Arroces & Wok',
            'icon' => 'bowl-rice',
            'color' => 'red',
            'sort_order' => 2,
        ]);

        $catBebidas = Category::firstOrCreate(['slug' => 'bebidas'], [
            'name' => 'Bebidas & Refrescos',
            'icon' => 'cup-straw',
            'color' => 'blue',
            'sort_order' => 3,
        ]);

        $catInsumos = Category::firstOrCreate(['slug' => 'insumos-cocina'], [
            'name' => 'Insumos y Materia Prima',
            'icon' => 'boxes',
            'color' => 'gray',
            'sort_order' => 99,
            'is_active' => false, // Oculto de la vista del mesonero
        ]);

        // 5. Insumos Crudos / Materia Prima (type: supply)
        $panBurger = Product::firstOrCreate(['code' => 'INS-PAN-01'], [
            'category_id' => $catInsumos->id,
            'name' => 'Pan Brioche de Hamburguesa',
            'type' => 'supply',
            'track_stock' => true,
            'current_stock' => 100.000, // 100 panes
            'unit' => 'unit',
            'price_usd' => 0.00,
        ]);

        $carneBurger = Product::firstOrCreate(['code' => 'INS-CARNE-01'], [
            'category_id' => $catInsumos->id,
            'name' => 'Carne Molida Especial para Hamburguesa',
            'type' => 'supply',
            'track_stock' => true,
            'current_stock' => 20.000, // 20 kilos de carne
            'unit' => 'kg',
            'price_usd' => 0.00,
        ]);

        $quesoAmarillo = Product::firstOrCreate(['code' => 'INS-QUESO-01'], [
            'category_id' => $catInsumos->id,
            'name' => 'Queso Amarillo en Lonjas',
            'type' => 'supply',
            'track_stock' => true,
            'current_stock' => 150.000, // 150 lonjas
            'unit' => 'unit',
            'price_usd' => 0.00,
        ]);

        $arrozCrudo = Product::firstOrCreate(['code' => 'INS-ARROZ-01'], [
            'category_id' => $catInsumos->id,
            'name' => 'Arroz Blanco Grano Entero',
            'type' => 'supply',
            'track_stock' => true,
            'current_stock' => 50.000, // 50 kilos de arroz
            'unit' => 'kg',
            'price_usd' => 0.00,
        ]);

        $polloPechuga = Product::firstOrCreate(['code' => 'INS-POLLO-01'], [
            'category_id' => $catInsumos->id,
            'name' => 'Pechuga de Pollo Deshuesada',
            'type' => 'supply',
            'track_stock' => true,
            'current_stock' => 25.000, // 25 kilos
            'unit' => 'kg',
            'price_usd' => 0.00,
        ]);

        // 6. Productos Terminados (Platos con Receta y Bebidas Directas)
        // A) Hamburguesa Clásica con Ficha Técnica
        $burgerClasica = Product::firstOrCreate(['code' => 'PLATO-BURGER-01'], [
            'category_id' => $catBurgers->id,
            'name' => 'Hamburguesa Clásica Americana',
            'description' => 'Pan brioche, 180g de carne angus, queso cheddar, vegetales frescos y salsa de la casa.',
            'type' => 'dish',
            'has_recipe' => true,
            'track_stock' => false, // Su stock se rige por sus insumos
            'price_usd' => 8.50,
            'unit' => 'unit',
        ]);

        // Receta de Hamburguesa Clásica
        ProductRecipe::firstOrCreate(
            ['product_id' => $burgerClasica->id, 'supply_id' => $panBurger->id],
            ['quantity_needed' => 1.000, 'unit' => 'unit']
        );
        ProductRecipe::firstOrCreate(
            ['product_id' => $burgerClasica->id, 'supply_id' => $carneBurger->id],
            ['quantity_needed' => 0.180, 'unit' => 'kg'] // 180 gramos por plato (de 1 kg salen ~5.5 porciones)
        );
        ProductRecipe::firstOrCreate(
            ['product_id' => $burgerClasica->id, 'supply_id' => $quesoAmarillo->id],
            ['quantity_needed' => 2.000, 'unit' => 'unit'] // 2 lonjas de queso
        );

        // B) Arroz Chino Especial con Ficha Técnica
        $arrozChino = Product::firstOrCreate(['code' => 'PLATO-ARROZ-01'], [
            'category_id' => $catArroces->id,
            'name' => 'Arroz Chino Especial al Wok',
            'description' => 'Arroz frito al wok con pechuga de pollo, jamón, raíces chinas, cebollín y soya.',
            'type' => 'dish',
            'has_recipe' => true,
            'track_stock' => false,
            'price_usd' => 7.00,
            'unit' => 'portion',
        ]);

        // Receta de Arroz Chino
        ProductRecipe::firstOrCreate(
            ['product_id' => $arrozChino->id, 'supply_id' => $arrozCrudo->id],
            ['quantity_needed' => 0.200, 'unit' => 'kg'] // 200g de arroz por plato (de 1 kg salen 5 platos)
        );
        ProductRecipe::firstOrCreate(
            ['product_id' => $arrozChino->id, 'supply_id' => $polloPechuga->id],
            ['quantity_needed' => 0.150, 'unit' => 'kg'] // 150g de pollo por plato
        );

        // C) Bebidas Directas (Sin receta, stock directo 1 a 1)
        Product::firstOrCreate(['code' => 'BEB-COCA-355'], [
            'category_id' => $catBebidas->id,
            'name' => 'Coca-Cola 355ml Lata',
            'description' => 'Refresco frío en lata 355ml',
            'type' => 'direct',
            'has_recipe' => false,
            'track_stock' => true,
            'current_stock' => 48.000, // 48 latas en nevera
            'price_usd' => 1.50,
            'unit' => 'unit',
        ]);

        Product::firstOrCreate(['code' => 'BEB-AGUA-600'], [
            'category_id' => $catBebidas->id,
            'name' => 'Agua Mineral 600ml',
            'description' => 'Botella de agua purificada sin gas',
            'type' => 'direct',
            'has_recipe' => false,
            'track_stock' => true,
            'current_stock' => 60.000,
            'price_usd' => 1.00,
            'unit' => 'unit',
        ]);

        // 7. Cliente Frecuente con Crédito Activo
        Customer::firstOrCreate(['name' => 'Cliente General / Mostrador'], [
            'identification' => '00000000',
            'phone' => '000-0000000',
            'allow_credit' => false,
            'credit_limit' => 0.00,
            'current_balance' => 0.00,
        ]);

        Customer::firstOrCreate(['name' => 'Empresa Corporativa M&F Steel'], [
            'identification' => 'J-31000999-1',
            'phone' => '+58 424 9998877',
            'allow_credit' => true,
            'credit_limit' => 500.00,
            'current_balance' => 0.00,
        ]);
    }
}
