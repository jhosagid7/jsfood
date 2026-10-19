<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Auth\Login;
use App\Livewire\Tables\TableMap;
use App\Livewire\Pos\OrderTerminal;
use App\Livewire\Kitchen\KitchenDisplay;
use App\Livewire\Cashier\CashierRegister;
use App\Livewire\Settings\PrinterSettings;
use App\Livewire\Settings\DeviceManager;
use App\Livewire\Products\ProductList;
use App\Livewire\Products\CategoryManager;

// 0. Autenticación y Salida
Route::get('/login', Login::class)->name('login')->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

Route::get('/access-denied', function () {
    return view('errors.access-denied');
})->name('access.denied');

// Rutas protegidas bajo autenticación
Route::middleware(['auth'])->group(function () {
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

    // 5. Catálogo de Productos, Menú y Categorías
    Route::get('/products', ProductList::class)->name('products.index');
    Route::get('/categories', CategoryManager::class)->name('categories.index');

    // 6. Ajustes de Impresoras y Dispositivos (Ecosistema jspos-sales)
    Route::get('/settings/printers', PrinterSettings::class)->name('settings.printers');
    Route::get('/settings/devices', DeviceManager::class)->name('settings.devices');
});
