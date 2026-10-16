<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tables\TableMap;
use App\Livewire\Pos\OrderTerminal;
use App\Livewire\Kitchen\KitchenDisplay;
use App\Livewire\Cashier\CashierRegister;
use App\Livewire\Settings\PrinterSettings;
use App\Livewire\Settings\DeviceManager;

// Redirigir inicio al mapa visual de mesas
Route::get('/', function () {
    return redirect()->route('tables.index');
});

// 1. Gestión Visual de Mesas y Salones
Route::get('/tables', TableMap::class)->name('tables.index');

// 2. Comandas Táctiles para Mesonero
Route::get('/pos/order/{tableId?}', OrderTerminal::class)->name('pos.order');

// 3. Pantalla de Cocina (KDS)
Route::get('/kds', KitchenDisplay::class)->name('kitchen.kds');

// 4. Caja, Pre-cuenta y Cobro Multimoneda
Route::get('/cashier', CashierRegister::class)->name('cashier.index');

// 5. Ajustes de Impresoras y Dispositivos (Ecosistema jspos-sales)
Route::get('/settings/printers', PrinterSettings::class)->name('settings.printers');
Route::get('/settings/devices', DeviceManager::class)->name('settings.devices');
