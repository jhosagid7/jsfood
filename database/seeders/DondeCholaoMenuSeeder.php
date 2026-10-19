<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Configuration;

class DondeCholaoMenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Actualizar Nombre del Negocio
        $config = Configuration::first();
        if ($config) {
            $config->update(['business_name' => 'Donde Cholao Grill']);
        }

        // 2. Crear o Actualizar Categorías
        $categoriesData = [
            ['name' => 'Perros Calientes', 'slug' => 'perros-calientes', 'icon' => '🌭', 'sort_order' => 1],
            ['name' => 'Hamburguesas', 'slug' => 'hamburguesas', 'icon' => '🍔', 'sort_order' => 2],
            ['name' => 'Carne en Vara', 'slug' => 'carne-en-vara', 'icon' => '🥩', 'sort_order' => 3],
            ['name' => 'Raciones & Papas', 'slug' => 'raciones-papas', 'icon' => '🍟', 'sort_order' => 4],
            ['name' => 'Merengadas & Jugos', 'slug' => 'merengadas-jugos', 'icon' => '🥤', 'sort_order' => 5],
            ['name' => 'Bebidas & Refrescos', 'slug' => 'bebidas-refrescos', 'icon' => '🍾', 'sort_order' => 6],
            ['name' => 'Insumos de Cocina', 'slug' => 'insumos-cocina', 'icon' => '📦', 'sort_order' => 99],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::updateOrCreate(
                ['slug' => $c['slug']],
                ['name' => $c['name'], 'icon' => $c['icon'], 'sort_order' => $c['sort_order'], 'is_active' => true]
            );
        }

        // 3. Productos y Platos Reales
        $products = [
            // PERROS CALIENTES (A Cocina: SÍ)
            [
                'category_slug' => 'perros-calientes',
                'name' => 'Promo 2x Perros Caliente Sencillo',
                'code' => 'PERRO-PROMO',
                'price_usd' => 1.50,
                'description' => 'Promo 2x Perros Caliente Sencillo con pan artesanal, ensalada y salsas.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'perros-calientes',
                'name' => 'Perro Caliente Normal',
                'code' => 'PERRO-NORM',
                'price_usd' => 2.00,
                'description' => 'Pan 100% artesanal, salchicha, ensalada tradicional, huevos, papas, queso de año y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'perros-calientes',
                'name' => 'Perro Caliente Especial (Carne Asada)',
                'code' => 'PERRO-ASADA',
                'price_usd' => 4.50,
                'description' => 'Pan 100% artesanal, carne de res asada, salchicha, huevo, ensalada tradicional, papas, jamón, queso, tocineta y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'perros-calientes',
                'name' => 'Perro Caliente Especial (Carne Mechada)',
                'code' => 'PERRO-MECHADA',
                'price_usd' => 4.50,
                'description' => 'Pan 100% artesanal, carne mechada, salchicha, huevo, ensalada tradicional, papas, jamón, queso, tocineta y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],

            // HAMBURGUESAS (A Cocina: SÍ)
            [
                'category_slug' => 'hamburguesas',
                'name' => 'Hamburguesa Clásica',
                'code' => 'HAMB-CLASICA',
                'price_usd' => 4.50,
                'description' => 'Hamburguesa con carne 100% res, papas, lechuga, tomate, cebolla, jamón, queso, huevo y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'hamburguesas',
                'name' => 'Hamburguesa (Carne Asada)',
                'code' => 'HAMB-ASADA',
                'price_usd' => 6.00,
                'description' => 'Hamburguesa con carne 100% res asada, papas, lechuga, tomate, cebolla, jamón, queso, huevo y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'hamburguesas',
                'name' => 'Hamburguesa (Carne Mechada)',
                'code' => 'HAMB-MECHADA',
                'price_usd' => 5.00,
                'description' => 'Hamburguesa con carne mechada, papas, lechuga, tomate, cebolla, jamón, queso, huevo y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'hamburguesas',
                'name' => 'Hamburguesa de Pollo',
                'code' => 'HAMB-POLLO',
                'price_usd' => 6.00,
                'description' => 'Hamburguesa con pollo, papas, lechuga, tomate, cebolla, jamón, queso, huevo y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'hamburguesas',
                'name' => 'Hamburguesa Lomito de Cerdo',
                'code' => 'HAMB-CERDO',
                'price_usd' => 6.00,
                'description' => 'Hamburguesa 100% lomito de cochino, papas, lechuga, tomate, cebolla, jamón, queso, huevo y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'hamburguesas',
                'name' => 'Hamburguesa Mixta (Croqueta, Chuleta)',
                'code' => 'HAMB-MIXTA',
                'price_usd' => 9.00,
                'description' => 'Proteínas 100% de res y cochino a tu gusto, papas, lechuga, tomate, cebolla, jamón, queso, huevo y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'hamburguesas',
                'name' => 'Hamburguesa Triple (Croqueta, Pollo, Chuleta)',
                'code' => 'HAMB-TRIPLE',
                'price_usd' => 11.00,
                'description' => 'Proteínas de res, pollo y cochino a tu gusto, papas, lechuga, tomate, cebolla, jamón, queso, huevo y salsa.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],

            // CARNE EN VARA (A Cocina: SÍ)
            [
                'category_slug' => 'carne-en-vara',
                'name' => 'Carne en Vara 1/4 (250 grs)',
                'code' => 'CARNE-250',
                'price_usd' => 10.00,
                'description' => '250 grs Carne en Vara + chorizo + huevo + queso, ensalada y yuca.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 50,
                'min_stock' => 5,
                'unit' => 'portion',
            ],
            [
                'category_slug' => 'carne-en-vara',
                'name' => 'Carne en Vara 1/2 (500 grs)',
                'code' => 'CARNE-500',
                'price_usd' => 22.50,
                'description' => '250 grs Carne + 250 grs Cerdo, huevos + queso + chorizo + ensalada y yuca.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 50,
                'min_stock' => 5,
                'unit' => 'portion',
            ],
            [
                'category_slug' => 'carne-en-vara',
                'name' => 'Carne en Vara 3/4 (750 grs)',
                'code' => 'CARNE-750',
                'price_usd' => 34.00,
                'description' => '400 grs Carne + 350 grs Cerdo, huevos + queso + chorizo + ensalada y yuca.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 50,
                'min_stock' => 5,
                'unit' => 'portion',
            ],
            [
                'category_slug' => 'carne-en-vara',
                'name' => 'Carne en Vara 1 Kg (1000 grs)',
                'code' => 'CARNE-1K',
                'price_usd' => 45.00,
                'description' => '500 grs Carne + 500 grs Cerdo, chorizo + huevo + queso, ensalada y yuca.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 50,
                'min_stock' => 5,
                'unit' => 'portion',
            ],

            // RACIONES & PAPAS (A Cocina: SÍ)
            [
                'category_slug' => 'raciones-papas',
                'name' => 'Servicio de Papas a la Francesa',
                'code' => 'PAPAS-FRANC',
                'price_usd' => 3.50,
                'description' => 'Ración de papas crocantes a la francesa con salsas.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 80,
                'min_stock' => 10,
                'unit' => 'portion',
            ],

            // MERENGADAS (A Cocina / Barra de Preparación: SÍ)
            [
                'category_slug' => 'merengadas-jugos',
                'name' => 'Merengada con Leche',
                'code' => 'MERENG-LECHE',
                'price_usd' => 1.50,
                'description' => 'Sabores: Oreo, Toddy, Cerelac, Fresa, Parchita, Guanábana, Mora, Cambur, Zapote Rojo.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'merengadas-jugos',
                'name' => 'Jugo Natural / Merengada sin Leche',
                'code' => 'JUGO-NATURAL',
                'price_usd' => 2.00,
                'description' => 'Sabores: Parchita, Mora, Fresa, Guanábana, Lechosa, Melón, Durazno.',
                'type' => 'dish',
                'send_to_kitchen' => true,
                'current_stock' => 100,
                'min_stock' => 10,
                'unit' => 'unit',
            ],

            // BEBIDAS & REFRESCOS EMBOTELLADOS (A Cocina: NO -> ¡Entrega Directa!)
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Coca-Cola Botella 350ml',
                'code' => 'BEB-COCA350',
                'price_usd' => 1.00,
                'description' => 'Refresco Coca-Cola en botella de vidrio 350ml fría.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 60,
                'min_stock' => 12,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Coca-Cola 1 Litro',
                'code' => 'BEB-COCA1L',
                'price_usd' => 1.75,
                'description' => 'Refresco Coca-Cola 1 Litro.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 40,
                'min_stock' => 6,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Coca-Cola 1.5 Litros',
                'code' => 'BEB-COCA15L',
                'price_usd' => 2.50,
                'description' => 'Refresco Coca-Cola 1.5 Litros.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 30,
                'min_stock' => 6,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Coca-Cola 2 Litros',
                'code' => 'BEB-COCA2L',
                'price_usd' => 3.00,
                'description' => 'Refresco Coca-Cola 2 Litros familiar.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 30,
                'min_stock' => 6,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Pepsi Botella 350ml',
                'code' => 'BEB-PEP350',
                'price_usd' => 1.00,
                'description' => 'Refresco Pepsi en botella 350ml fría.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 50,
                'min_stock' => 12,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Pepsi 1 Litro',
                'code' => 'BEB-PEP1L',
                'price_usd' => 1.75,
                'description' => 'Refresco Pepsi 1 Litro.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 40,
                'min_stock' => 6,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Pepsi 1.5 Litros',
                'code' => 'BEB-PEP15L',
                'price_usd' => 2.50,
                'description' => 'Refresco Pepsi 1.5 Litros.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 30,
                'min_stock' => 6,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Pepsi 2 Litros',
                'code' => 'BEB-PEP2L',
                'price_usd' => 3.00,
                'description' => 'Refresco Pepsi 2 Litros familiar.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 30,
                'min_stock' => 6,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Agua Nevada 600ml',
                'code' => 'BEB-AGUA600',
                'price_usd' => 1.00,
                'description' => 'Agua mineral Nevada 600ml.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 60,
                'min_stock' => 12,
                'unit' => 'unit',
            ],
            [
                'category_slug' => 'bebidas-refrescos',
                'name' => 'Agua Nevada 1.5 Litros',
                'code' => 'BEB-AGUA15L',
                'price_usd' => 2.00,
                'description' => 'Agua mineral Nevada 1.5 Litros.',
                'type' => 'direct',
                'send_to_kitchen' => false,
                'current_stock' => 40,
                'min_stock' => 8,
                'unit' => 'unit',
            ],
        ];

        foreach ($products as $p) {
            $cat = $categories[$p['category_slug']] ?? null;
            Product::updateOrCreate(
                ['code' => $p['code']],
                [
                    'name' => $p['name'],
                    'category_id' => $cat?->id,
                    'price_usd' => $p['price_usd'],
                    'description' => $p['description'],
                    'type' => $p['type'],
                    'send_to_kitchen' => $p['send_to_kitchen'],
                    'has_recipe' => false,
                    'track_stock' => true,
                    'current_stock' => $p['current_stock'],
                    'min_stock' => $p['min_stock'],
                    'unit' => $p['unit'],
                    'is_active' => true,
                ]
            );
        }
    }
}
