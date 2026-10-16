<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
        <div>
            <h2 class="text-2xl font-black text-white">Ajustes de Impresoras y Monedas</h2>
            <p class="text-xs text-slate-400">Configura las tiqueras térmicas de caja y cocina (58mm/80mm) y las tasas de cambio.</p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ url('/settings/devices') }}" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold">
                📱 Dispositivos Autorizados
            </a>
        </div>
    </div>

    <!-- SECCIÓN 1: Configuración de Impresora de Caja (Facturación) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
        <h3 class="text-base font-black text-white flex items-center space-x-2">
            <span>💳</span>
            <span>Impresora de Caja (Facturación y Pre-cuenta)</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre de la Impresora / Recurso Compartido:</label>
                <input type="text" wire:model="cashierPrinter" placeholder="Ej: POS-80 o \\SERVIDOR\POS80"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500">
                <span class="text-[11px] text-slate-500">Nombre exacto de la impresora en Windows o recurso compartido.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Ancho de Papel:</label>
                <select wire:model="cashierWidth"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500">
                    <option value="80">80 mm (48 caracteres por línea - Estándar)</option>
                    <option value="58">58 mm (32 caracteres por línea - Mini)</option>
                </select>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <button wire:click="testPrint('cashier')"
                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs flex items-center space-x-1.5 transition-colors">
                <span>🖨️</span>
                <span>Probar Impresión de Caja</span>
            </button>
        </div>
    </div>

    <!-- SECCIÓN 2: Configuración de Impresora de Cocina (Comandas) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
        <h3 class="text-base font-black text-white flex items-center space-x-2">
            <span>🍳</span>
            <span>Impresora Térmica de Cocina (Comandas)</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre de la Impresora de Cocina:</label>
                <input type="text" wire:model="kitchenPrinter" placeholder="Ej: KITCHEN-58 o \\COCINA\POS58"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Ancho de Papel Cocina:</label>
                <select wire:model="kitchenWidth"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500">
                    <option value="58">58 mm (32 caracteres por línea - Recomendado Cocina)</option>
                    <option value="80">80 mm (48 caracteres por línea)</option>
                </select>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <button wire:click="testPrint('kitchen')"
                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs flex items-center space-x-1.5 transition-colors">
                <span>🖨️</span>
                <span>Probar Impresión de Cocina</span>
            </button>
        </div>
    </div>

    <!-- SECCIÓN 3: Buscador de Impresoras en Red (PrinterDiscoveryService) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-black text-white flex items-center space-x-2">
                <span>🔍</span>
                <span>Impresoras Detectadas en Windows y Red Local</span>
            </h3>

            <button wire:click="scanPrinters"
                class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs flex items-center space-x-1">
                <span>🔄</span>
                <span>Escanear Red</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-2">
            @forelse($discoveredPrinters as $p)
                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black text-white block">{{ $p['name'] ?? 'Impresora' }}</span>
                        <span class="text-[10px] text-slate-400">{{ $p['host'] ?? 'Local' }}</span>
                    </div>
                    <button wire:click="$set('cashierPrinter', '{{ $p['name'] }}')"
                        class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-amber-400 text-[10px] font-bold rounded-lg">
                        Usar en Caja
                    </button>
                </div>
            @empty
                <div class="col-span-full py-4 text-center text-slate-500 text-xs">
                    Haz clic en "Escanear Red" para buscar colas de impresión activas.
                </div>
            @endforelse
        </div>
    </div>

    <!-- SECCIÓN 4: Sistema de Multi-Moneda vs. Moneda Única -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
        <h3 class="text-base font-black text-white flex items-center space-x-2">
            <span>💵</span>
            <span>Sistema de Monedas y Tasas</span>
        </h3>

        <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Modo de Operación:</label>
            <div class="flex items-center space-x-4">
                <label class="flex items-center space-x-2 text-sm text-slate-200 cursor-pointer">
                    <input type="radio" wire:model.live="currencyMode" value="multi" class="text-amber-500 focus:ring-amber-500">
                    <span><strong>Multi-moneda</strong> (Dólares USD + Bolívares BCV + Pesos COP + USDT)</span>
                </label>
                <label class="flex items-center space-x-2 text-sm text-slate-200 cursor-pointer">
                    <input type="radio" wire:model.live="currencyMode" value="single" class="text-amber-500 focus:ring-amber-500">
                    <span><strong>Moneda Única</strong> (Solo una divisa sin conversiones)</span>
                </label>
            </div>
        </div>

        @if($currencyMode === 'multi')
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tasa BCV (Bolívares):</label>
                    <input type="number" step="0.01" wire:model="bcvRate"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-amber-400 font-bold focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tasa COP (Pesos Colombianos):</label>
                    <input type="number" step="1" wire:model="copRate"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-emerald-400 font-bold focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tasa Binance USDT:</label>
                    <input type="number" step="0.01" wire:model="binanceRate"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-blue-400 font-bold focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
        @endif
    </div>

    <!-- Botón Guardar Cambios -->
    <div class="flex justify-end pt-2">
        <button wire:click="saveSettings"
            class="px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-sm shadow-xl shadow-amber-500/20 transition-all">
            💾 Guardar Todos los Ajustes
        </button>
    </div>

</div>
