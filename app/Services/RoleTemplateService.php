<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleTemplateService
{
    /**
     * Obtener todas las plantillas de roles con metadatos y permisos para Restaurantes.
     */
    public static function getTemplates(): array
    {
        return [
            'super_admin' => [
                'name' => 'Super Admin',
                'badge' => 'Control Total',
                'color' => 'danger',
                'icon' => 'shield-lock',
                'description' => 'Acceso absoluto e ilimitado al sistema, bases de datos, licencias y hardware.',
                'permissions' => ['*'],
            ],
            'admin' => [
                'name' => 'Administrador',
                'badge' => 'Gestión General',
                'color' => 'primary',
                'icon' => 'person-gear',
                'description' => 'Gestión de menú, insumos, compras, precios, reportes de ventas, mesas y usuarios.',
                'permissions' => self::getAdminPermissions(),
            ],
            'cashier' => [
                'name' => 'Cajero',
                'badge' => 'Caja & Cobranza',
                'color' => 'success',
                'icon' => 'cash-coin',
                'description' => 'Cobro de cuentas de mesas, notas de entrega, emisión de pre-cuentas, apertura/cierre de caja.',
                'permissions' => self::getCashierPermissions(),
            ],
            'waiter' => [
                'name' => 'Mesonero',
                'badge' => 'Comandas & Mesas',
                'color' => 'info',
                'icon' => 'person-walking',
                'description' => 'Toma de pedidos en mesas, notas de cocina, solicitud de pre-cuenta y entrega de platos.',
                'permissions' => self::getWaiterPermissions(),
            ],
            'chef' => [
                'name' => 'Cocinero',
                'badge' => 'Cocina / KDS',
                'color' => 'warning',
                'icon' => 'fire',
                'description' => 'Visualización de pedidos en pantalla de cocina KDS, cambio de estados a preparando/listo.',
                'permissions' => self::getChefPermissions(),
            ],
            'delivery' => [
                'name' => 'Delivery',
                'badge' => 'Reparto & Despacho',
                'color' => 'secondary',
                'icon' => 'bicycle',
                'description' => 'Control de pedidos para llevar y despacho a domicilio, confirmación de entregas.',
                'permissions' => self::getDeliveryPermissions(),
            ],
        ];
    }

    /**
     * Lista completa de permisos del sistema JSFood.
     */
    public static function getAllPermissions(): array
    {
        return [
            // Mesas y Salón
            'tables.view', 'tables.manage', 'tables.change_status',
            // Comandas y Órdenes
            'orders.create', 'orders.view', 'orders.edit', 'orders.cancel', 'orders.split_bill', 'orders.print_pre_bill',
            // Cocina / KDS
            'kitchen.kds_view', 'kitchen.update_status', 'kitchen.reprint_ticket',
            // Caja y Facturación
            'pos.checkout', 'pos.open_cash_register', 'pos.close_cash_register', 'pos.discounts',
            // Clientes y Créditos
            'customers.view', 'customers.manage', 'credits.view', 'credits.manage',
            // Menú, Stock e Insumos
            'menu.view', 'menu.manage', 'stock.view', 'stock.adjust', 'recipes.manage',
            // Hardware, Impresoras y Dispositivos
            'devices.manage', 'printers.manage', 'printers.discover',
            // Licencias, Backups y Configuración
            'licenses.view', 'licenses.manage', 'backups.manage', 'settings.manage', 'users.manage',
            // Reportes
            'reports.sales', 'reports.waiters', 'reports.kitchen', 'reports.stock',
        ];
    }

    public static function getAdminPermissions(): array
    {
        return [
            'tables.view', 'tables.manage', 'tables.change_status',
            'orders.create', 'orders.view', 'orders.edit', 'orders.cancel', 'orders.split_bill', 'orders.print_pre_bill',
            'kitchen.kds_view', 'kitchen.update_status', 'kitchen.reprint_ticket',
            'pos.checkout', 'pos.open_cash_register', 'pos.close_cash_register', 'pos.discounts',
            'customers.view', 'customers.manage', 'credits.view', 'credits.manage',
            'menu.view', 'menu.manage', 'stock.view', 'stock.adjust', 'recipes.manage',
            'devices.manage', 'printers.manage', 'printers.discover',
            'licenses.view', 'backups.manage', 'settings.manage', 'users.manage',
            'reports.sales', 'reports.waiters', 'reports.kitchen', 'reports.stock',
        ];
    }

    public static function getCashierPermissions(): array
    {
        return [
            'tables.view',
            'orders.view', 'orders.create', 'orders.split_bill', 'orders.print_pre_bill',
            'pos.checkout', 'pos.open_cash_register', 'pos.close_cash_register',
            'customers.view', 'customers.manage', 'credits.view', 'credits.manage',
            'reports.sales',
        ];
    }

    public static function getWaiterPermissions(): array
    {
        return [
            'tables.view', 'tables.change_status',
            'orders.create', 'orders.view', 'orders.edit', 'orders.split_bill', 'orders.print_pre_bill',
            'menu.view',
        ];
    }

    public static function getChefPermissions(): array
    {
        return [
            'kitchen.kds_view', 'kitchen.update_status', 'kitchen.reprint_ticket',
            'menu.view',
        ];
    }

    public static function getDeliveryPermissions(): array
    {
        return [
            'orders.view',
        ];
    }

    /**
     * Aplica la plantilla de permisos al rol especificado.
     */
    public static function applyTemplateToRole(Role $role, string $templateKey): void
    {
        $templates = self::getTemplates();
        if (!isset($templates[$templateKey])) {
            return;
        }

        $allPerms = self::getAllPermissions();
        foreach ($allPerms as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $perms = $templates[$templateKey]['permissions'];
        if ($perms === ['*']) {
            $role->syncPermissions(Permission::all());
        } else {
            $role->syncPermissions($perms);
        }
    }
}
