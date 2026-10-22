<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    <!-- Encabezado con Pestañas de Navegación de Ajustes -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h2 class="text-2xl font-black text-white flex items-center space-x-2">
                <span>🏦</span>
                <span>Cuentas Bancarias, Monedas y Tasas</span>
            </h2>
            <p class="text-xs text-slate-400">Configura la moneda principal del negocio, las tasas de cambio (BCV, Binance P2P, COP) y tus cuentas bancarias receptoras.</p>
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

    <!-- SECCIÓN 1: Moneda Principal del Sistema y Modo de Moneda (Estilo JSPOS-SALES) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800/80">
            <div>
                <h3 class="text-base font-black text-white flex items-center space-x-2">
                    <span>⚙️</span>
                    <span>Moneda Principal del Negocio & Modo de Operación</span>
                </h3>
                <p class="text-xs text-slate-400">Define con qué moneda se fijan los precios base y si el restaurante opera en multimoneda o moneda única.</p>
            </div>
            <div>
                <button type="button" wire:click="toggleCurrencyMode"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition-all flex items-center space-x-2 {{ $currencyMode === 'multi' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/30 hover:bg-blue-500/20' }}">
                    <span>{{ $currencyMode === 'multi' ? '🌐 Modo Multimoneda Activo' : '🔒 Modo Moneda Única Activo' }}</span>
                </button>
            </div>
        </div>

        <!-- Selector de Moneda Principal -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end bg-slate-950/60 p-4 rounded-xl border border-slate-800">
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">
                    Seleccionar Moneda Principal:
                </label>
                <select wire:model="primaryCurrency"
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    @foreach($currencies as $c)
                        <option value="{{ $c->code }}">
                            {{ $c->code }} - {{ $c->label ?? $c->name }} ({{ $c->symbol }})
                        </option>
                    @endforeach
                </select>
                <span class="text-[11px] text-slate-500 block mt-1">Los precios y reportes se calcularán y presentarán con base en esta moneda.</span>
            </div>

            <div class="flex items-center space-x-3">
                <button type="button" wire:click="setPrimaryCurrency"
                    class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-md cursor-pointer flex items-center space-x-1.5">
                    <span>💾</span>
                    <span>Guardar Moneda Principal</span>
                </button>
                <span class="text-xs text-slate-400">
                    Actual: <strong class="text-amber-400 font-mono">{{ $primaryCurrency }}</strong>
                </span>
            </div>
        </div>

        <!-- Tabla de Monedas Configuradas -->
        <div>
            <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Monedas Habilitadas en el Sistema:</h4>
            <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Nombre / Etiqueta</th>
                            <th class="px-4 py-3">Símbolo</th>
                            <th class="px-4 py-3">Tasa Base (vs USD)</th>
                            <th class="px-4 py-3 text-center">Estado Principal</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @foreach($currencies as $curr)
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-white">{{ $curr->code }}</td>
                                <td class="px-4 py-3">{{ $curr->label ?? $curr->name }}</td>
                                <td class="px-4 py-3 font-mono font-black text-amber-400">{{ $curr->symbol }}</td>
                                <td class="px-4 py-3 font-mono text-emerald-400">
                                    {{ $curr->code === 'COP' ? number_format($curr->exchange_rate, 0) : number_format($curr->exchange_rate, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($curr->is_primary || $curr->code === $primaryCurrency)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            ★ Moneda Principal
                                        </span>
                                    @else
                                        <span class="text-slate-500 text-[11px]">Secundaria</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if(!$curr->is_primary && $curr->code !== $primaryCurrency)
                                        <button type="button" wire:click="setPrimaryCurrency('{{ $curr->code }}')"
                                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-bold border border-slate-700 transition-colors">
                                            Hacer Principal
                                        </button>
                                    @else
                                        <span class="text-slate-600 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: Configuración de Tasas de Cambio de Referencia (Multimoneda) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-black text-white flex items-center space-x-2">
                    <span>💱</span>
                    <span>Tasas de Cambio Oficiales de Referencia</span>
                </h3>
                <p class="text-xs text-slate-400">Tasas cambiarias aplicadas al recibir pagos en Bolívares (BCV o Binance P2P) y Pesos (COP).</p>
            </div>
            
            @php
                $gapReal = $bcvRate > 0 ? (($binanceRate - $bcvRate) / $bcvRate) * 100 : 0;
            @endphp
            @if($bcvRate > 0)
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/10 text-blue-400 border border-blue-500/30 font-mono">
                        📊 Brecha Binance vs BCV: {{ $gapReal >= 0 ? '+' : '' }}{{ number_format($gapReal, 2) }}%
                    </span>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Tasa BCV Oficial (Bolívares) -->
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-amber-400 uppercase">🇻🇪 Tasa BCV Oficial (Bs / USD):</label>
                    <span class="text-[10px] bg-amber-500/10 text-amber-300 px-2 py-0.5 rounded font-mono font-bold">VED (BCV)</span>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold text-sm">Bs</span>
                    <input type="number" step="0.01" min="0.01" wire:model.live.debounce.300ms="bcvRate"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-base font-black text-amber-400 focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                </div>
                <span class="text-[11px] text-slate-500 block">Tasa legal oficial del Banco Central de Venezuela.</span>
            </div>

            <!-- Tasa Binance P2P / Paralela (Bolívares) -->
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-purple-400 uppercase">🇻🇪 Tasa Binance P2P (Bs / USD):</label>
                    <span class="text-[10px] bg-purple-500/10 text-purple-300 px-2 py-0.5 rounded font-mono font-bold">VED (Paralelo)</span>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold text-sm">Bs</span>
                    <input type="number" step="0.01" min="0.01" wire:model.live.debounce.300ms="binanceRate"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-base font-black text-purple-400 focus:ring-2 focus:ring-purple-500 focus:outline-none font-mono">
                </div>
                <span class="text-[11px] text-slate-500 block">Cotización en Bolívares del dólar P2P / Paralelo.</span>
            </div>

            <!-- Tasa Pesos Colombianos (COP) -->
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-emerald-400 uppercase">🇨🇴 Tasa Pesos (COP / USD):</label>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-300 px-2 py-0.5 rounded font-mono font-bold">COP</span>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold text-sm">$</span>
                    <input type="number" step="1" min="1" wire:model.live.debounce.300ms="copRate"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-8 pr-3 py-2 text-base font-black text-emerald-400 focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                </div>
                <span class="text-[11px] text-slate-500 block">Aplica a Bancolombia, Nequi y efectivo COP.</span>
            </div>
        </div>

        <!-- Tarjeta Informativa de Binance Pay Cripto -->
        <div class="p-3 bg-slate-950/70 rounded-xl border border-slate-800 flex items-center justify-between text-xs text-slate-400">
            <div class="flex items-center space-x-2">
                <span class="text-base">🟡</span>
                <span>
                    <strong>Cripto USDT (Binance Pay):</strong> Paridad directa <strong>1 USDT = $1.00 USD</strong>. Los pagos en cripto se cobran directamente en USDT por Pay ID o código QR.
                </span>
            </div>
            <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 font-mono font-bold">1:1 USD</span>
        </div>

        <!-- Barra de Conversión Dinámica de Ejemplo -->
        <div class="p-3.5 bg-slate-950 rounded-xl border border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
            <span class="text-slate-400">💡 Ejemplo de conversión exacta para una cuenta de <strong>$10.00 USD</strong>:</span>
            <div class="flex flex-wrap items-center gap-3 font-mono font-bold">
                <span class="text-amber-400">🇻🇪 BCV: Bs {{ number_format(10 * $bcvRate, 2) }}</span>
                <span class="text-slate-600">|</span>
                <span class="text-purple-400">🇻🇪 Binance: Bs {{ number_format(10 * $binanceRate, 2) }}</span>
                <span class="text-slate-600">|</span>
                <span class="text-emerald-400">🇨🇴 COP$ {{ number_format(10 * $copRate, 0) }}</span>
                <span class="text-slate-600">|</span>
                <span class="text-blue-400">🟡 Cripto: ₮ 10.00 USDT</span>
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

    <!-- SECCIÓN 3: Directorio de Cuentas Bancarias / Métodos de Pago -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-white flex items-center space-x-2">
                    <span>💳</span>
                    <span>Cuentas Bancarias y Métodos de Cobro Registrados</span>
                </h3>
                <p class="text-xs text-slate-400">Cuentas donde tus clientes transferirán o pagarán en Caja y abonos.</p>
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
                    $currUpper = strtoupper($b->currency_code);
                    $isVed = in_array($currUpper, ['VED', 'BS', 'VES']);
                    $isCop = $currUpper === 'COP';
                    $isUsd = $currUpper === 'USD';
                    $isUsdt = in_array($currUpper, ['USDT', 'BINANCE']);
                @endphp
                <div class="bg-slate-950 rounded-2xl border {{ $b->state ? 'border-slate-800' : 'border-slate-800/40 opacity-60' }} p-4 space-y-3 relative overflow-hidden transition-all hover:border-slate-700 shadow-md">
                    <!-- Encabezado de la Tarjeta con Badges -->
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-black text-white text-sm flex items-center space-x-1.5">
                                <span>{{ $b->name }}</span>
                            </h4>
                            <span class="text-[11px] text-slate-400 uppercase font-mono">{{ str_replace('_', ' ', $b->type) }}</span>
                        </div>

                        <!-- Badge de Moneda Asignada al Banco -->
                        @if($isVed)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center space-x-1">
                                <span>🇻🇪</span>
                                <span>VED (Bs)</span>
                            </span>
                        @elseif($isCop)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center space-x-1">
                                <span>🇨🇴</span>
                                <span>COP (Pesos)</span>
                            </span>
                        @elseif($isUsdt)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 flex items-center space-x-1">
                                <span>🟡</span>
                                <span>USDT (Cripto)</span>
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-500/20 text-blue-300 border border-blue-500/30 flex items-center space-x-1">
                                <span>🇺🇸</span>
                                <span>USD ($)</span>
                            </span>
                        @endif
                    </div>

                    <!-- Datos del Banco -->
                    <div class="space-y-1 text-xs text-slate-300 pt-1 border-t border-slate-900">
                        @if($b->account_holder)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Titular:</span>
                                <span class="font-semibold text-slate-200 truncate max-w-[180px]">{{ $b->account_holder }}</span>
                            </div>
                        @endif
                        @if($b->cedula)
                            <div class="flex justify-between">
                                <span class="text-slate-500">C.I. / RIF / NIT:</span>
                                <span class="font-mono text-amber-400">{{ $b->cedula }}</span>
                            </div>
                        @endif
                        @if($b->account_number)
                            <div class="flex justify-between">
                                <span class="text-slate-500">N° Cuenta / Email / ID:</span>
                                <span class="font-mono text-emerald-400 select-all">{{ $b->account_number }}</span>
                            </div>
                        @endif
                        @if($b->phone)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Teléfono:</span>
                                <span class="font-mono text-blue-400">{{ $b->phone }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Acciones y Estado -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-900 text-xs">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" wire:click="toggleBankState({{ $b->id }})" {{ $b->state ? 'checked' : '' }}
                                class="rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-0">
                            <span class="{{ $b->state ? 'text-emerald-400 font-bold' : 'text-slate-500' }}">
                                {{ $b->state ? 'Activo en Caja' : 'Inactivo' }}
                            </span>
                        </label>

                        <div class="flex items-center space-x-1.5">
                            <button wire:click="openEditModal({{ $b->id }})"
                                class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-[11px] transition-colors cursor-pointer">
                                ✏️ Editar
                            </button>
                            <button wire:click="deleteBank({{ $b->id }})" wire:confirm="¿Seguro que deseas eliminar esta cuenta bancaria?"
                                class="p-1 text-slate-500 hover:text-red-400 transition-colors cursor-pointer" title="Eliminar cuenta">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center bg-slate-950 rounded-2xl border border-slate-800 text-slate-500 text-xs">
                    No hay cuentas bancarias registradas. Haz clic en "Nueva Cuenta Bancaria" para agregar una.
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL CREAR / EDITAR CUENTA BANCARIA -->
    @if($showBankModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-base font-black text-white flex items-center space-x-2">
                        <span>{{ $bankId ? '✏️ Editar Cuenta Bancaria' : '➕ Registrar Cuenta Bancaria' }}</span>
                    </h3>
                    <button wire:click="$set('showBankModal', false)" class="text-slate-400 hover:text-white font-bold text-sm">✕</button>
                </div>

                <form wire:submit="saveBank" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-300 uppercase mb-1">Nombre Identificador del Banco / Cuenta: *</label>
                        <input type="text" wire:model="name" placeholder="Ej: Bancolombia, Banesco, Zelle Chase, Binance Pay"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @error('name') <span class="text-red-400 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Moneda de la Cuenta: *</label>
                            <select wire:model="currency_code"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="VED">🇻🇪 VED (Bolívares - Bs)</option>
                                <option value="COP">🇨🇴 COP (Pesos Colombianos)</option>
                                <option value="USD">🇺🇸 USD (Dólares - $)</option>
                                <option value="USDT">🟡 USDT (Binance Cripto - ₮)</option>
                            </select>
                            @error('currency_code') <span class="text-red-400 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Tipo de Método: *</label>
                            <select wire:model="type"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="bank_transfer">Transferencia Bancaria</option>
                                <option value="pago_movil">Pago Móvil</option>
                                <option value="card">Tarjeta / Punto de Venta (POS)</option>
                                <option value="zelle">Zelle</option>
                                <option value="nequi">Nequi</option>
                                <option value="binance">Binance Pay (Cripto)</option>
                                <option value="cash">Efectivo</option>
                            </select>
                            @error('type') <span class="text-red-400 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Titular de la Cuenta:</label>
                            <input type="text" wire:model="account_holder" placeholder="Ej: JS Food C.A."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Cédula / RIF / NIT:</label>
                            <input type="text" wire:model="cedula" placeholder="Ej: J-12345678-9"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">N° de Cuenta / Correo / Pay ID:</label>
                            <input type="text" wire:model="account_number" placeholder="Ej: 0134... / email@zelle.com / ID"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Teléfono (Para Pago Móvil):</label>
                            <input type="text" wire:model="phone" placeholder="Ej: 0414-1234567"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-between border-t border-slate-800">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" wire:model="state" class="rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-0">
                            <span class="text-slate-300 font-bold">Activo para Cobros en Caja</span>
                        </label>

                        <div class="flex items-center space-x-2">
                            <button type="button" wire:click="$set('showBankModal', false)"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors cursor-pointer">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black transition-all shadow-md cursor-pointer">
                                Guardar Cuenta
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
