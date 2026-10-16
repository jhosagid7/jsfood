<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@jsfood.dev'],
            [
                'name' => 'Jhonny Pirela (Super Admin)',
                'password' => Hash::make('admin123'),
            ]
        );
        $superAdmin->assignRole('Super Admin');

        // 2. Administrador / Dueño
        $admin = User::firstOrCreate(
            ['email' => 'gerente@jsfood.dev'],
            [
                'name' => 'Gerente de Restaurante',
                'password' => Hash::make('gerente123'),
            ]
        );
        $admin->assignRole('Administrador');

        // 3. Cajero
        $cashier = User::firstOrCreate(
            ['email' => 'cajero@jsfood.dev'],
            [
                'name' => 'Cajero Principal',
                'password' => Hash::make('cajero123'),
            ]
        );
        $cashier->assignRole('Cajero');

        // 4. Mesonero
        $waiter = User::firstOrCreate(
            ['email' => 'mesonero1@jsfood.dev'],
            [
                'name' => 'Mesonero Carlos',
                'password' => Hash::make('mesonero123'),
            ]
        );
        $waiter->assignRole('Mesonero');

        // 5. Cocinero
        $chef = User::firstOrCreate(
            ['email' => 'cocina@jsfood.dev'],
            [
                'name' => 'Chef Cocina Principal',
                'password' => Hash::make('cocina123'),
            ]
        );
        $chef->assignRole('Cocinero');
    }
}
