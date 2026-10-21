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

    // 7. Heartbeat de Verificación de Dispositivo en Tiempo Real
    Route::get('/device/heartbeat', function () {
        return response()->json(['status' => 'authorized']);
    })->name('device.heartbeat');

    // 8. Estado en Tiempo Real de Cocina y Servicio (KDS, globo rojo y alertas sonoras tanto de entrada a cocina como de platos listos para mesonero)
    Route::get('/kitchen/status', function () {
        $activeKitchenOrders = \App\Models\Order::whereIn('status', ['pending', 'in_kitchen'])
            ->whereHas('items.product', fn($q) => $q->where('send_to_kitchen', true))
            ->select('id', 'order_number')
            ->get();

        $latestKitchen = $activeKitchenOrders->sortByDesc('id')->first();

        // Órdenes listas para servir (para que el mesonero/teléfono escuche cuando la comanda esté lista)
        $readyOrders = \App\Models\Order::with('diningTable')
            ->where('status', 'ready')
            ->select('id', 'order_number', 'table_id', 'kitchen_ready_at')
            ->get();

        $latestReady = $readyOrders->sortByDesc('kitchen_ready_at')->first();

        return response()->json([
            'count' => $activeKitchenOrders->count(),
            'latest_id' => $latestKitchen ? $latestKitchen->id : 0,
            'latest_order_number' => $latestKitchen ? $latestKitchen->order_number : null,
            'ready_count' => $readyOrders->count(),
            'latest_ready_id' => $latestReady ? $latestReady->id : 0,
            'latest_ready_order_number' => $latestReady ? $latestReady->order_number : null,
            'latest_ready_table' => $latestReady && $latestReady->diningTable ? $latestReady->diningTable->name : 'Para Llevar',
        ]);
    })->name('kitchen.status');
});

// 9. Enrutamiento determinista y resiliente para Livewire 3 (evita 404 por desincronización de hash o caché de navegador)
Route::post('/livewire/update', [\Livewire\Mechanisms\HandleRequests\HandleRequests::class, 'handleUpdate'])->middleware(['web']);
Route::get('/livewire/livewire.js', [\Livewire\Mechanisms\FrontendAssets\FrontendAssets::class, 'returnJavaScriptAsFile']);
Route::post('/livewire-{hash}/update', [\Livewire\Mechanisms\HandleRequests\HandleRequests::class, 'handleUpdate'])->middleware(['web']);
Route::get('/livewire-{hash}/livewire.js', [\Livewire\Mechanisms\FrontendAssets\FrontendAssets::class, 'returnJavaScriptAsFile']);
