<div class="w-full max-w-md mx-auto">
    <!-- Brand Card -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 shadow-xl shadow-orange-500/20 text-white font-black text-3xl mb-4">
            JF
        </div>
        <h1 class="text-2xl font-black tracking-tight text-white">
            JS<span class="text-amber-500">FOOD</span> POS
        </h1>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-1">
            Gestión de Restaurante & Comandas
        </p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <!-- Error Alerts -->
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-red-950/80 border border-red-500/40 text-red-200 text-xs font-semibold flex items-center space-x-3">
                <span class="text-lg">⚠️</span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Formulario Tradicional -->
        <form wire:submit.prevent="login" class="space-y-4">
            <div>
                <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Correo Electrónico
                </label>
                <div class="relative">
                    <input wire:model.defer="email" type="email" id="email" required autofocus
                        placeholder="ejemplo@jsfood.dev"
                        class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-600 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition-all">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Contraseña
                </label>
                <input wire:model.defer="password" type="password" id="password" required
                    placeholder="••••••••"
                    class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-600 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition-all">
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center space-x-2 text-slate-400 hover:text-slate-200 cursor-pointer select-none">
                    <input wire:model="remember" type="checkbox" class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500">
                    <span>Recordar sesión</span>
                </label>
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full py-3.5 px-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black rounded-xl text-sm shadow-lg shadow-amber-500/25 active:scale-[0.98] transition-all flex items-center justify-center space-x-2">
                <span wire:loading.remove wire:target="login">Entrar al Sistema</span>
                <span wire:loading wire:target="login" class="flex items-center space-x-2">
                    <svg class="animate-spin h-4 w-4 text-slate-950" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Autenticando...</span>
                </span>
            </button>
        </form>

        <!-- Divisor -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-800"></div>
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="px-3 bg-slate-900 text-slate-500 font-bold uppercase tracking-widest text-[10px]">
                    Acceso Rápido por Perfil
                </span>
            </div>
        </div>

        <!-- Botones de Cambio Rápido de Rol / Usuario -->
        <div class="grid grid-cols-2 gap-2 text-left">
            <button type="button" wire:click="quickLogin('admin@jsfood.dev')" wire:loading.attr="disabled"
                class="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800/80 border border-slate-800/80 hover:border-amber-500/50 transition-all group flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition-transform">
                    👑
                </div>
                <div class="overflow-hidden">
                    <span class="block text-xs font-bold text-slate-200 group-hover:text-amber-400 transition-colors truncate">Super Admin</span>
                    <span class="block text-[10px] text-slate-500 font-mono truncate">admin@jsfood.dev</span>
                </div>
            </button>

            <button type="button" wire:click="quickLogin('gerente@jsfood.dev')" wire:loading.attr="disabled"
                class="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800/80 border border-slate-800/80 hover:border-blue-500/50 transition-all group flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition-transform">
                    🏢
                </div>
                <div class="overflow-hidden">
                    <span class="block text-xs font-bold text-slate-200 group-hover:text-blue-400 transition-colors truncate">Gerente</span>
                    <span class="block text-[10px] text-slate-500 font-mono truncate">gerente@jsfood.dev</span>
                </div>
            </button>

            <button type="button" wire:click="quickLogin('mesonero1@jsfood.dev')" wire:loading.attr="disabled"
                class="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800/80 border border-slate-800/80 hover:border-emerald-500/50 transition-all group flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition-transform">
                    🍽️
                </div>
                <div class="overflow-hidden">
                    <span class="block text-xs font-bold text-slate-200 group-hover:text-emerald-400 transition-colors truncate">Mesonero</span>
                    <span class="block text-[10px] text-slate-500 font-mono truncate">mesonero1@jsfood.dev</span>
                </div>
            </button>

            <button type="button" wire:click="quickLogin('cocina@jsfood.dev')" wire:loading.attr="disabled"
                class="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800/80 border border-slate-800/80 hover:border-red-500/50 transition-all group flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition-transform">
                    🍳
                </div>
                <div class="overflow-hidden">
                    <span class="block text-xs font-bold text-slate-200 group-hover:text-red-400 transition-colors truncate">Cocinero (KDS)</span>
                    <span class="block text-[10px] text-slate-500 font-mono truncate">cocina@jsfood.dev</span>
                </div>
            </button>

            <button type="button" wire:click="quickLogin('cajero@jsfood.dev')" wire:loading.attr="disabled"
                class="col-span-2 p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800/80 border border-slate-800/80 hover:border-purple-500/50 transition-all group flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition-transform">
                    💳
                </div>
                <div class="overflow-hidden">
                    <span class="block text-xs font-bold text-slate-200 group-hover:text-purple-400 transition-colors truncate">Cajero Principal (Caja)</span>
                    <span class="block text-[10px] text-slate-500 font-mono truncate">cajero@jsfood.dev</span>
                </div>
            </button>
        </div>

        <div class="mt-4 p-3 rounded-xl bg-slate-950/60 border border-slate-800 text-[11px] text-slate-400 text-center font-medium">
            💡 Contraseña para todas las cuentas de prueba: <span class="font-mono text-amber-400 font-bold">admin123 / gerente123 / mesonero123 / etc.</span>
        </div>
    </div>
</div>
