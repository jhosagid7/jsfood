<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Services\RoleTemplateService;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear o encontrar roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $cashier = Role::firstOrCreate(['name' => 'Cajero', 'guard_name' => 'web']);
        $waiter = Role::firstOrCreate(['name' => 'Mesonero', 'guard_name' => 'web']);
        $chef = Role::firstOrCreate(['name' => 'Cocinero', 'guard_name' => 'web']);
        $delivery = Role::firstOrCreate(['name' => 'Delivery', 'guard_name' => 'web']);

        // 2. Aplicar plantillas de permisos
        RoleTemplateService::applyTemplateToRole($superAdmin, 'super_admin');
        RoleTemplateService::applyTemplateToRole($admin, 'admin');
        RoleTemplateService::applyTemplateToRole($cashier, 'cashier');
        RoleTemplateService::applyTemplateToRole($waiter, 'waiter');
        RoleTemplateService::applyTemplateToRole($chef, 'chef');
        RoleTemplateService::applyTemplateToRole($delivery, 'delivery');
    }
}
