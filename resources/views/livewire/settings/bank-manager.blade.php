<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    <!-- Encabezado con Pestañas de Navegación de Ajustes -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h2 class="text-2xl font-black text-white flex items-center space-x-2">
                <span>🏦</span>
                <span>Cuentas Bancarias, Monedas y Tasas</span>
            </h2>
            <p class="text-xs text-slate-400">Configura tus bancos receptores (Bancolombia, Banesco, Zelle), sus monedas (COP, VED, USD) y las tasas de cambio.</p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ url('/settings/printers') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-colors">
                🖨️ Impresoras Térmicas
            </a>
            <a href="{{ url('/settings/banks') }}" class="px-3.5 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-black shadow-md">
                🏦 Bancos y Monedas
            </a>
            <a href="{{ url('/settings/devices') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-colors">
                📱 Dispositivos
            </a>
        </div>
    </div>

    <!-- SECCIÓN 1: Configuración de Tasas de Cambio Oficiales -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-black text-white flex items-center space-x-2">
                <span>💱</span>
                <span>Tasas de Cambio Oficiales (Multimoneda)</span>
            </h3>
            <span class="text-xs text-slate-400">Moneda base del sistema: <strong class="text-emerald-400">$ USD</strong></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Tasa BCV (Bolívares) -->
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-amber-400 uppercase">🇻🇪 Tasa BCV (Bolívares / USD):</label>
                    <span class="text-[10px] bg-amber-500/10 text-amber-300 px-2 py-0.5 rounded font-mono font-bold">VED</span>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold text-sm">Bs</span>
                    <input type="number" step="0.01" min="0.01" wire:model="bcvRate"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-base font-black text-amber-400 focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                </div>
                <span class="text-[11px] text-slate-500 block">Aplica a Banesco, Pago Móvil y efectivo Bs.</span>
            </div>

            <!-- Tasa Pesos Colombianos (COP) -->
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-emerald-400 uppercase">🇨🇴 Tasa Pesos (COP / USD):</label>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-300 px-2 py-0.5 rounded font-mono font-bold">COP</span>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold text-sm">$</span>
                    <input type="number" step="1" min="1" wire:model="copRate"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-8 pr-3 py-2 text-base font-black text-emerald-400 focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                </div>
                <span class="text-[11px] text-slate-500 block">Aplica a Bancolombia, Nequi y efectivo COP.</span>
            </div>

            <!-- Tasa Binance USDT -->
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-blue-400 uppercase">🟡 Tasa Binance Pay / USDT:</label>
                    <span class="text-[10px] bg-blue-500/10 text-blue-300 px-2 py-0.5 rounded font-mono font-bold">USDT</span>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold text-sm">₮</span>
                    <input type="number" step="0.01" min="0.01" wire:model="binanceRate"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-8 pr-3 py-2 text-base font-black text-blue-400 focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono">
                </div>
                <span class="text-[11px] text-slate-500 block">Aplica a transferencias cripto en Binance.</span>
            </div>
        </div>

        <!-- Barra de Conversión Dinámica de Ejemplo -->
        <div class="p-3 bg-slate-950/80 rounded-xl border border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
            <span class="text-slate-400">💡 Ejemplo de conversión para una cuenta de <strong>$10.00 USD</strong>:</span>
            <div class="flex items-center space-x-3 font-mono font-bold">
                <span class="text-amber-400">🇻🇪 Bs {{ number_format(10 * $bcvRate, 2) }}</span>
                <span class="text-slate-600">|</span>
                <span class="text-emerald-400">🇨🇴 COP$ {{ number_format(10 * $copRate, 0) }}</span>
                <span class="text-slate-600">|</span>
                <span class="text-blue-400">🟡 ₮ {{ number_format(10 * $binanceRate, 2) }}</span>
            </div>
        </div>

        <div class="flex justify-end pt-1">
            <button wire:click="saveRates"
                class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-md cursor-pointer flex items-center space-x-2">
                <span>💾</span>
                <span>Guardar Tasas de Cambio</span>
            </button>
        </div>
    </div>

    <!-- SECCIÓN 2: Directorio de Cuentas Bancarias / Métodos de Pago -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-white flex items-center space-x-2">
                    <span>💳</span>
                    <span>Cuentas Bancarias y Métodos de Cobro Registrados</span>
                </h3>
                <p class="text-xs text-slate-400">Estas son las cuentas donde tus clientes pagarán en Caja o al hacer abonos.</p>
            </div>

            <button wire:click="openCreateModal"
                class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs transition-all shadow-md cursor-pointer flex items-center space-x-1.5 shrink-0">
                <span>➕</span>
                <span>Nueva Cuenta Bancaria</span>
            </button>
        </div>

        <!-- Listado de Cuentas Bancarias -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
            @forelse($banks as $b)
                @php
                    $isVed = in_array(strtoupper($b->currency_code), ['VED', 'BS', 'VES']);
                    $isCop = strtoupper($b->currency_code) === 'COP';
                    $isUsd = strtoupper($b->currency_code) === 'USD';
                    $isUsdt = in_array(strtoupper($b->currency_code), ['USDT', 'BINANCE']);
                @endphp
                <div class="bg-slate-950 rounded-2xl border {{ $b->state ? 'border-slate-800' : 'border-slate-800/40 opacity-60' }} p-4 space-y-3 relative overflow-hidden transition-all hover:border-slate-700 shadow-md">
                    <!-- Encabezado de la Tarjeta con Badges -->
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-base font-black text-white block">{{ $b->name }}</span>
                            <span class="text-[11px] text-slate-400 uppercase font-semibold">
                                {{ match($b->type) {
                                    'pago_movil' => '📲 Pago Móvil',
                                    'bank_transfer' => '🏦 Transferencia',
                                    'zelle' => '🇺🇸 Zelle',
                                    'binance' => '🟡 Binance Pay',
                                    'nequi' => '🟣 Nequi',
                                    'card' => '💳 Punto de Venta',
                                    default => '💵 Efectivo / Cuenta',
                                } }}
                            </span>
                        </div>

                        <!-- Badge de Moneda Destacado -->
                        @if($isVed)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                🇻🇪 VED (Bs)
                            </span>
                        @elseif($isCop)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                🇨🇴 COP (Pesos)
                            </span>
                        @elseif($isUsd)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-purple-500/20 text-purple-300 border border-purple-500/40">
                                🇺🇸 USD ($)
                            </span>
                        @elseif($isUsdt)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-blue-500/20 text-blue-300 border border-blue-500/40">
                                🟡 USDT (₮)
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-800 text-slate-300">
                                {{ $b->currency_code }}
                            </span>
                        @endif
                    </div>

                    <!-- Datos Bancarios / Titular -->
                    <div class="space-y-1 text-xs bg-slate-900/80 p-2.5 rounded-xl border border-slate-800/80">
                        @if($b->account_holder)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Titular:</span>
                                <span class="font-bold text-slate-200 truncate max-w-[160px]">{{ $b->account_holder }}</span>
                            </div>
                        @endif
                        @if($b->cedula)
                            <div class="flex justify-between">
                                <span class="text-slate-500">C.I. / RIF:</span>
                                <span class="font-mono text-amber-400 font-bold">{{ $b->cedula }}</span>
                            </div>
                        @endif
                        @if($b->phone)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Teléfono:</span>
                                <span class="font-mono text-blue-400 font-bold">{{ $b->phone }}</span>
                            </div>
                        @endif
                        @if($b->account_number)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nº Cuenta / Email:</span>
                                <span class="font-mono text-emerald-400 font-bold truncate max-w-[150px]">{{ $b->account_number }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Pie de la Tarjeta: Switch y Acciones -->
                    <div class="flex items-center justify-between pt-1 border-t border-slate-800/60">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" wire:click="toggleBankState({{ $b->id }})" {{ $b->state ? 'checked' : '' }}
                                class="rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-emerald-500">
                            <span class="text-xs font-bold {{ $b->state ? 'text-emerald-400' : 'text-slate-500' }}">
                                {{ $b->state ? 'Activo en Caja' : 'Inactivo' }}
                            </span>
                        </label>

                        <div class="flex items-center space-x-1.5">
                            <button type="button" wire:click="openEditModal({{ $b->id }})"
                                class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-colors cursor-pointer">
                                ✏️ Editar
                            </button>
                            <button type="button" wire:click="deleteBank({{ $b->id }})"
                                class="p-1 text-slate-500 hover:text-red-400 hover:bg-red-950/30 rounded-lg transition-colors cursor-pointer" title="Eliminar">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-10 text-center text-slate-500 text-xs">
                    No hay cuentas bancarias registradas. Haz clic en "Nueva Cuenta Bancaria" para agregar una.
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL: Crear o Editar Cuenta Bancaria -->
    @if($showBankModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-5 sm:p-6 shadow-2xl space-y-4 max-h-[95vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-lg font-black text-white flex items-center space-x-2">
                        <span>🏦</span>
                        <span>{{ $bankId ? 'Editar Cuenta Bancaria' : 'Nueva Cuenta Bancaria / Destino de Pago' }}</span>
                    </h3>
                    <button type="button" wire:click="$set('showBankModal', false)"
                        class="text-slate-400 hover:text-white text-xl font-bold p-1 cursor-pointer">
                        ✕
                    </button>
                </div>

                <form wire:submit.prevent="saveBank" class="space-y-4">
                    <!-- Nombre de la Cuenta -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre de la Cuenta o Banco *</label>
                        <input type="text" wire:model="name" placeholder="Ej: Bancolombia Ahorros, Banesco Pago Móvil, Zelle Chase..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @error('name') <span class="text-red-400 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Moneda y Tipo de Cuenta -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-amber-400 uppercase mb-1">Moneda de la Cuenta *</label>
                            <select wire:model="currency_code"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="VED">🇻🇪 Bolívares (VED / Bs)</option>
                                <option value="COP">🇨🇴 Pesos Colombianos (COP)</option>
                                <option value="USD">🇺🇸 Dólares Americanos (USD)</option>
                                <option value="USDT">🟡 Binance USDT (₮)</option>
                            </select>
                            @error('currency_code') <span class="text-red-400 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tipo de Operación *</label>
                            <select wire:model="type"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="pago_movil">📲 Pago Móvil</option>
                                <option value="bank_transfer">🏦 Transferencia Bancaria</option>
                                <option value="card">💳 Tarjeta / Punto de Venta</option>
                                <option value="zelle">🇺🇸 Zelle</option>
                                <option value="nequi">🟣 Nequi</option>
                                <option value="binance">🟡 Binance Pay</option>
                                <option value="cash">💵 Efectivo / Caja</option>
                            </select>
                            @error('type') <span class="text-red-400 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Datos del Titular -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre del Titular:</label>
                            <input type="text" wire:model="account_holder" placeholder="Ej: Restaurante JSFood C.A."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Cédula / RIF / NIT:</label>
                            <input type="text" wire:model="cedula" placeholder="Ej: J-12345678-9 o C.C."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Teléfono y Número de Cuenta / Correo -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Teléfono Asociado:</label>
                            <input type="text" wire:model="phone" placeholder="Ej: 0414-1234567 o 300-1234567"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nº Cuenta / Email / ID:</label>
                            <input type="text" wire:model="account_number" placeholder="Ej: pagos@jsfood.dev o 0102-..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Estado y Orden -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center pt-2">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="state" class="rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-white">Activar cuenta para recibir pagos</span>
                        </label>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Orden de Prioridad:</label>
                            <input type="number" min="0" wire:model="sort"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-800">
                        <button type="button" wire:click="$set('showBankModal', false)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-md cursor-pointer">
                            Guardar Cuenta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
