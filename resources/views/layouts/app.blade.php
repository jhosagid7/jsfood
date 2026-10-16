<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'JSFood POS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full flex flex-col font-sans antialiased bg-slate-950 text-slate-100 selection:bg-amber-500 selection:text-white">

    <!-- Header / Navbar Principal -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40 shadow-md">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="{{ url('/tables') }}" class="flex items-center space-x-2">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-500/20 text-white font-black text-xl">
                            JF
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-lg font-black tracking-tight text-white">JS<span class="text-amber-500">FOOD</span></span>
                            <span class="text-[10px] block font-semibold text-slate-400 tracking-wider uppercase">Restaurante & POS</span>
                        </div>
                    </a>

                    <!-- Badge de Moneda / Tasa BCV & COP -->
                    @php
                        $config = \App\Models\Configuration::first();
                    @endphp
                    @if($config && $config->currency_mode === 'multi')
                        <div class="hidden md:flex items-center space-x-2 bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-700/60 text-xs">
                            <span class="text-slate-400 font-medium">Tasas:</span>
                            <span class="text-amber-400 font-bold">BCV: Bs {{ number_format($config->bcv_rate, 2) }}</span>
                            <span class="text-slate-600">•</span>
                            <span class="text-emerald-400 font-bold">COP: ${{ number_format($config->cop_rate, 0) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Enlaces de Navegación Desktop -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="{{ url('/tables') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('tables*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        🍽️ Mesas & Salón
                    </a>
                    <a href="{{ url('/pos/order') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('pos*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        📝 Comanda Rápida
                    </a>
                    <a href="{{ url('/kds') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors relative {{ request()->is('kds*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        🍳 Cocina (KDS)
                        @php
                            $inKitchenCount = \App\Models\Order::where('status', 'in_kitchen')->count();
                        @endphp
                        @if($inKitchenCount > 0)
                            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full animate-pulse">{{ $inKitchenCount }}</span>
                        @endif
                    </a>
                    <a href="{{ url('/cashier') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('cashier*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        💳 Caja & Cobro
                    </a>
                    <a href="{{ url('/settings/printers') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('settings*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        ⚙️ Ajustes
                    </a>
                </nav>

                <!-- Usuario y Cambio Rápido de Rol -->
                <div class="flex items-center space-x-2">
                    @php
                        $user = auth()->user() ?? \App\Models\User::first();
                        $roleName = $user && $user->roles->first() ? $user->roles->first()->name : 'Admin';
                    @endphp
                    <div class="flex items-center space-x-2 bg-slate-800/80 px-2.5 py-1.5 rounded-xl border border-slate-700/60">
                        <div class="w-7 h-7 rounded-lg bg-slate-700 flex items-center justify-center font-bold text-xs text-amber-400">
                            {{ substr($user ? $user->name : 'U', 0, 1) }}
                        </div>
                        <div class="text-left text-xs leading-tight">
                            <span class="font-bold text-slate-200 block truncate max-w-[100px]">{{ $user ? $user->name : 'Usuario' }}</span>
                            <span class="text-[10px] font-medium text-amber-400">{{ $roleName }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" title="Cerrar sesión / Cambiar usuario" class="p-2 rounded-xl bg-slate-800/80 hover:bg-red-500/20 text-slate-400 hover:text-red-400 border border-slate-700/60 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 pb-20 md:pb-6 overflow-y-auto">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Barra de Navegación Táctil Móvil / Tablet (Sticky Bottom) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 z-50 px-2 py-1.5 flex justify-around items-center">
        <a href="{{ url('/tables') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('tables*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">🍽️</span>
            <span>Mesas</span>
        </a>
        <a href="{{ url('/pos/order') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('pos*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">📝</span>
            <span>Comanda</span>
        </a>
        <a href="{{ url('/kds') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs relative {{ request()->is('kds*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">🍳</span>
            <span>Cocina</span>
            @if(isset($inKitchenCount) && $inKitchenCount > 0)
                <span class="absolute top-0 right-1 bg-red-600 text-white text-[9px] font-bold px-1 rounded-full">{{ $inKitchenCount }}</span>
            @endif
        </a>
        <a href="{{ url('/cashier') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('cashier*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">💳</span>
            <span>Caja</span>
        </a>
        <a href="{{ url('/settings/printers') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('settings*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">⚙️</span>
            <span>Ajustes</span>
        </a>
    </nav>

    <!-- Sistema de Notificaciones Toast (Alpine.js) -->
    <div x-data="{ 
            notifications: [],
            add(msg, type = 'success') {
                const id = Date.now();
                this.notifications.push({ id, msg, type });
                setTimeout(() => this.remove(id), 3500);
            },
            remove(id) {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }
        }"
        x-on:notify.window="add($event.detail.message, $event.detail.type)"
        class="fixed bottom-20 md:bottom-6 right-4 z-50 flex flex-col space-y-2 pointer-events-none">
        <template x-for="n in notifications" :key="n.id">
            <div class="pointer-events-auto px-4 py-3 rounded-xl shadow-2xl border text-sm font-semibold flex items-center space-x-2 transition-all transform duration-300"
                :class="{
                    'bg-emerald-950/95 border-emerald-500/50 text-emerald-200': n.type === 'success',
                    'bg-red-950/95 border-red-500/50 text-red-200': n.type === 'error',
                    'bg-amber-950/95 border-amber-500/50 text-amber-200': n.type === 'warning',
                    'bg-blue-950/95 border-blue-500/50 text-blue-200': n.type === 'info'
                }">
                <span x-text="n.type === 'success' ? '✅' : (n.type === 'error' ? '❌' : '⚠️')"></span>
                <span x-text="n.msg"></span>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
