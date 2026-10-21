<div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-3 sm:py-5">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- COLUMNA IZQUIERDA: Cuentas Pendientes por Cobrar (4 cols) -->
        <div class="lg:col-span-4 flex flex-col space-y-3">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-white flex items-center space-x-2">
                        <span>🧾</span>
                        <span>Cuentas por Cobrar</span>
                    </h3>
                    <span class="bg-amber-500/20 text-amber-400 text-xs px-2 py-0.5 rounded-full font-bold">
                        {{ $pendingOrders->count() }} activas
                    </span>
                </div>
                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                    <span class="truncate flex items-center space-x-1" title="{{ $config->printer_name ?? 'POS-80' }}">
                        <span>🖨️</span>
                        <span>Caja: <strong class="text-slate-300 font-mono">{{ Str::limit($config->printer_name ?? 'POS-80', 20) }}</strong></span>
                    </span>
                    @if($lastPaidOrderId)
                        <button type="button" wire:click="reprintTicket" class="text-emerald-400 hover:text-emerald-300 font-bold underline cursor-pointer shrink-0 ml-1">
                            Re-imprimir
                        </button>
                    @endif
                </div>
            </div>

            <!-- Lista de Cuentas -->
            <div class="space-y-2.5 overflow-y-auto max-h-[75vh]">
                @forelse($pendingOrders as $ord)
                    @php
                        $isSelected = $selectedOrderId === $ord->id;
                    @endphp
                    <div wire:click="selectOrder({{ $ord->id }})"
                        wire:key="cashier-order-{{ $ord->id }}"
                        class="p-3.5 rounded-2xl border cursor-pointer transition-all select-none {{ $isSelected ? 'bg-amber-500/10 border-amber-500 ring-2 ring-amber-500/30' : 'bg-slate-900 border-slate-800 hover:border-slate-700' }}">
                        
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-base font-black text-white">
                                    {{ $ord->diningTable ? $ord->diningTable->name : 'Para Llevar' }}
                                </h4>
                                <span class="text-xs font-bold text-amber-400">
                                    {{ $ord->account_label }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-base font-black text-white">
                                    ${{ number_format($ord->total_usd, 2) }}
                                </span>
                                @php
                                    $paidSum = $ord->payments->sum('amount_usd');
                                    $pendingSum = max(0, $ord->total_usd - $paidSum);
                                @endphp
                                @if($paidSum > 0 && $pendingSum > 0)
                                    <span class="block text-[10px] font-black text-amber-400">
                                        Resta: ${{ number_format($pendingSum, 2) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-2 pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                            <span>Cliente: {{ $ord->customer->name ?? 'General' }}</span>
                            <span class="font-mono">#{{ $ord->order_number }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-500 bg-slate-900/40 rounded-2xl border border-slate-800/60">
                        <span class="text-3xl block mb-2">🎉</span>
                        <p class="font-bold text-slate-400 text-sm">No hay cuentas pendientes por cobrar.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- COLUMNA DERECHA: Estación de Cobro y Facturación (8 cols) -->
        <div class="lg:col-span-8">
            @if($selectedOrder)
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-5">
                    
                    <!-- Encabezado de la Orden Seleccionada -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-800">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-2xl font-black text-white">
                                    {{ $selectedOrder->diningTable ? $selectedOrder->diningTable->name : 'Mostrador / Llevar' }}
                                </h2>
                                <span class="bg-amber-500 text-slate-950 text-xs font-black px-2.5 py-0.5 rounded-full">
                                    {{ $selectedOrder->account_label }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-3 mt-1 text-xs text-slate-400">
                                <span class="font-mono">Factura/Orden: #{{ $selectedOrder->order_number }}</span>
                                <span>•</span>
                                <button type="button" wire:click="openSplitModal" class="text-purple-400 hover:text-purple-300 font-bold hover:underline flex items-center space-x-1 cursor-pointer">
                                    <span>🍽️</span>
                                    <span>Dividir por Ítems</span>
                                </button>
                                <span>•</span>
                                <button wire:click="cancelSelectedOrder" 
                                    wire:confirm="¿Estás seguro de cancelar o descartar esta orden? Esta acción no se puede deshacer."
                                    class="text-red-400 hover:text-red-300 font-bold hover:underline flex items-center space-x-1">
                                    <span>🗑️</span>
                                    <span>Cancelar Orden</span>
                                </button>
                            </div>
                        </div>

                        <!-- Subtotal, Descuento y Total en Grande -->
                        <div class="text-right">
                            <div class="flex items-center justify-end space-x-2 mb-1">
                                <span class="text-xs text-slate-400 font-semibold">Subtotal: ${{ number_format($selectedOrder->subtotal_usd, 2) }}</span>
                                @if($selectedOrder->discount_usd > 0)
                                    <span class="text-xs font-black text-emerald-400 bg-emerald-950/60 border border-emerald-800/60 px-2 py-0.5 rounded-full">
                                        Desc: -${{ number_format($selectedOrder->discount_usd, 2) }}
                                    </span>
                                @endif
                                <button wire:click="openDiscountModal"
                                    class="text-xs px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-400 hover:bg-amber-500/30 border border-amber-500/40 font-bold flex items-center space-x-1 transition-all">
                                    <span>🏷️</span>
                                    <span>{{ $selectedOrder->discount_usd > 0 ? 'Modificar Desc.' : '+ Descuento' }}</span>
                                </button>
                            </div>
                            @php
                                $totalPaidSoFar = collect($appliedPayments)->sum('amount_usd');
                                $remainingToPay = max(0, $selectedOrder->total_usd - $totalPaidSoFar);
                            @endphp

                            <div class="flex items-baseline justify-end space-x-3">
                                @if($totalPaidSoFar > 0)
                                    <div class="text-right">
                                        <span class="text-[11px] text-emerald-400 font-bold uppercase block">Abonado:</span>
                                        <span class="text-sm font-black text-emerald-400">
                                            -${{ number_format($totalPaidSoFar, 2) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="text-right">
                                    <span class="text-xs text-slate-400 font-bold uppercase block">
                                        {{ $totalPaidSoFar > 0 ? 'Resta por Cobrar:' : 'Total:' }}
                                    </span>
                                    <span class="text-3xl font-black text-amber-400">
                                        ${{ number_format($totalPaidSoFar > 0 ? $remainingToPay : $selectedOrder->total_usd, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cuadro de Equivalencias Multimoneda -->
                    @if($config && $config->currency_mode === 'multi')
                        <div class="grid grid-cols-3 gap-2 bg-slate-950/80 p-3 rounded-xl border border-slate-800/80 text-center text-xs">
                            <div class="p-2 rounded-lg bg-slate-900 border border-slate-800">
                                <span class="text-slate-400 font-semibold block text-[11px]">En Bolívares (BCV):</span>
                                <span class="text-amber-400 font-black text-sm">
                                    Bs {{ number_format($selectedOrder->total_usd * $config->bcv_rate, 2) }}
                                </span>
                            </div>
                            <div class="p-2 rounded-lg bg-slate-900 border border-slate-800">
                                <span class="text-slate-400 font-semibold block text-[11px]">En Pesos (COP):</span>
                                <span class="text-emerald-400 font-black text-sm">
                                    COP$ {{ number_format($selectedOrder->total_usd * $config->cop_rate, 0) }}
                                </span>
                            </div>
                            <div class="p-2 rounded-lg bg-slate-900 border border-slate-800">
                                <span class="text-slate-400 font-semibold block text-[11px]">En Binance USDT:</span>
                                <span class="text-blue-400 font-black text-sm">
                                    ₮ {{ number_format($selectedOrder->total_usd, 2) }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <!-- Detalle de la Comanda / Consumo del Cliente -->
                    <div class="bg-slate-950/80 rounded-2xl border border-slate-800 p-4 space-y-3 shadow-inner">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="text-base">📋</span>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-200">
                                    Consumo de la Mesa / Comanda
                                </h4>
                                <span class="bg-slate-800 text-amber-400 text-[10px] font-black px-2 py-0.5 rounded-full border border-slate-700">
                                    {{ $selectedOrder->items->count() }} ítems ({{ (int) $selectedOrder->items->sum('quantity') }} platos/bebidas)
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" wire:click="openSplitModal"
                                    class="px-2.5 py-1 rounded-xl bg-purple-500/20 text-purple-300 hover:bg-purple-500/30 border border-purple-500/40 text-xs font-bold flex items-center space-x-1.5 transition-all shadow-sm cursor-pointer">
                                    <span>🍽️</span>
                                    <span>Dividir por Ítems / Separar</span>
                                </button>
                                <button type="button" wire:click="toggleItemsDetail"
                                    class="px-2.5 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-all cursor-pointer">
                                    {{ $showItemsDetail ? '▲ Ocultar' : '▼ Ver Platos' }}
                                </button>
                            </div>
                        </div>

                        @if($showItemsDetail)
                            <div class="divide-y divide-slate-800/80 max-h-52 overflow-y-auto pr-1">
                                @forelse($selectedOrder->items as $item)
                                    <div class="py-2.5 flex items-center justify-between text-xs" wire:key="cashier-item-{{ $item->id }}">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="w-6 h-6 rounded-lg bg-slate-900 text-amber-400 font-black text-[11px] flex items-center justify-center border border-slate-800 shadow-sm shrink-0">
                                                {{ (int) $item->quantity }}x
                                            </span>
                                            <div>
                                                <span class="font-bold text-white text-sm">{{ $item->product->name ?? 'Producto' }}</span>
                                                @if($item->notes)
                                                    <span class="block text-[11px] text-amber-400/90 italic mt-0.5">📝 {{ $item->notes }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-bold text-slate-200 text-sm">${{ number_format($item->subtotal_usd, 2) }}</span>
                                            <span class="block text-[10px] text-slate-500 font-mono">(${{ number_format($item->unit_price_usd, 2) }} c/u)</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-3 text-center text-xs text-slate-500">
                                        No hay productos registrados en esta orden.
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    <!-- Selección de Cliente y Estado de Crédito -->
                    <div class="bg-slate-950/50 p-3.5 rounded-xl border border-slate-800 space-y-2">
                        <label class="block text-xs font-bold text-slate-300 uppercase">Cliente a Facturar:</label>
                        <select wire:model.live="selectedCustomerId"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="">Consumidor Final</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}">
                                    {{ $cust->name }} ({{ $cust->identification ?? 'S/R' }})
                                    @if($cust->allow_credit)
                                        [Crédito Disp: ${{ number_format(max(0, $cust->credit_limit - $cust->current_balance), 2) }}]
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Métodos de Pago Disponibles (Botones Táctiles) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Método de Pago:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button wire:click="$set('selectedPaymentMethod', 'cash_usd')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'cash_usd' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                💵 Efectivo USD ($)
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'cash_ved')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'cash_ved' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                🇻🇪 Efectivo Bs
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'mobile_pay')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'mobile_pay' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                📲 Pago Móvil
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'card')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'card' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                💳 Tarjeta / Punto
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'cash_cop')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'cash_cop' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                🇨🇴 Pesos (COP)
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'nequi')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'nequi' ? 'bg-pink-600 text-white font-black border-pink-500 shadow-md' : 'bg-slate-950 border-slate-800 text-pink-400 hover:bg-slate-800' }}">
                                🟣 Nequi
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'binance_usdt')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'binance_usdt' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                🟡 Binance USDT
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'zelle')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'zelle' ? 'bg-purple-600 text-white font-black border-purple-500 shadow-md' : 'bg-slate-950 border-slate-800 text-purple-400 hover:bg-slate-800' }}">
                                🇺🇸 Zelle
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'credit')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all col-span-2 sm:col-span-4 {{ $selectedPaymentMethod === 'credit' ? 'bg-blue-600 text-white font-black border-blue-400 shadow-lg ring-2 ring-blue-500/30' : 'bg-slate-950 border-slate-800 text-blue-400 hover:bg-slate-800' }}">
                                👤 Cargar a Crédito del Cliente (Cuenta Abierta)
                            </button>
                        </div>
                    </div>

                    <!-- Panel Informativo de Crédito cuando se selecciona Crédito Cliente -->
                    @if($selectedPaymentMethod === 'credit')
                        <div class="p-4 rounded-xl border {{ !$selectedCustomerId ? 'bg-amber-950/50 border-amber-500/60 text-amber-300' : 'bg-blue-950/60 border-blue-500/60 text-blue-200' }} space-y-2 animate-fade-in shadow-inner">
                            @if(!$selectedCustomerId)
                                <div class="flex items-start space-x-2.5">
                                    <span class="text-xl">⚠️</span>
                                    <div>
                                        <span class="text-white block font-black text-sm">Paso Requerido: Selecciona un Cliente arriba</span>
                                        <span class="text-amber-200 text-xs">
                                            Actualmente está seleccionado <strong>Consumidor Final</strong>. Para cargar a crédito debes elegir arriba al cliente que asumirá esta deuda.
                                        </span>
                                    </div>
                                </div>
                            @else
                                @php
                                    $selCust = $customers->firstWhere('id', $selectedCustomerId);
                                @endphp
                                @if($selCust)
                                    <div class="flex items-center justify-between text-xs pb-2 border-b border-blue-800/60">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Cliente a quien se le cargará:</span>
                                            <strong class="text-white text-base">{{ $selCust->name }}</strong>
                                            <span class="text-slate-400 font-mono text-[11px] block">{{ $selCust->identification ?? 'Sin Cédula' }}</span>
                                        </div>
                                        <div class="text-right">
                                            @if($selCust->allow_credit)
                                                <span class="text-[10px] uppercase font-bold text-emerald-400 block">Crédito Disponible:</span>
                                                <span class="text-emerald-300 font-black text-base font-mono">${{ number_format($selCust->available_credit, 2) }}</span>
                                                <span class="text-[10px] text-slate-400 block">Límite: ${{ number_format($selCust->credit_limit, 2) }}</span>
                                            @else
                                                <span class="text-red-400 font-bold block text-xs bg-red-950/80 px-2 py-0.5 rounded border border-red-800">
                                                    ❌ Este cliente no tiene crédito autorizado
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-xs flex items-center justify-between pt-1">
                                        <span class="text-slate-300">
                                            Monto de esta comanda: <strong class="text-amber-400 font-mono text-sm">${{ number_format($amountReceived, 2) }}</strong>
                                        </span>
                                        <span class="text-slate-300">
                                            Plazo acordado: <strong class="text-white">{{ $selCust->credit_days ?? 15 }} días</strong>
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Selector de Cuenta Bancaria / Billetera Destino si aplica -->
                    @if(in_array($selectedPaymentMethod, ['mobile_pay', 'zelle', 'binance_usdt', 'nequi', 'card']))
                        <div class="bg-slate-950/70 p-3.5 rounded-xl border border-slate-800 space-y-2">
                            <label class="block text-xs font-bold text-slate-300 uppercase">
                                🏦 Cuenta Bancaria / Destino del Pago:
                            </label>
                            <select wire:model.live="selectedBankId"
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="">-- Seleccionar cuenta de recepción --</option>
                                @foreach($banks as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->currency_code }}) - {{ $b->account_holder }}</option>
                                @endforeach
                            </select>

                            @php
                                $currentBank = $selectedBankId ? $banks->firstWhere('id', $selectedBankId) : null;
                            @endphp
                            @if($currentBank)
                                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-xs space-y-1 text-slate-300">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Titular:</span>
                                        <span class="font-bold text-white">{{ $currentBank->account_holder }}</span>
                                    </div>
                                    @if($currentBank->cedula)
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">C.I. / RIF / NIT:</span>
                                            <span class="font-mono text-amber-400">{{ $currentBank->cedula }}</span>
                                        </div>
                                    @endif
                                    @if($currentBank->account_number)
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Cuenta / Correo / ID:</span>
                                            <span class="font-mono text-emerald-400">{{ $currentBank->account_number }}</span>
                                        </div>
                                    @endif
                                    @if($currentBank->phone)
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Teléfono:</span>
                                            <span class="font-mono text-blue-400">{{ $currentBank->phone }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- División Rápida de Cuenta en Partes Iguales (Split Bill) -->
                    <div class="bg-slate-950/80 p-3 rounded-xl border border-slate-800 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-base">👥</span>
                            <div>
                                <span class="text-xs font-bold text-white block">Dividir Cuenta en Partes Iguales:</span>
                                <span class="text-[11px] text-slate-400">Calcula la cuota por persona y precarga el monto</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            @foreach([2, 3, 4, 5] as $n)
                                <button type="button" wire:click="splitEqually({{ $n }})" 
                                    wire:loading.attr="disabled"
                                    class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-amber-500 hover:text-slate-950 disabled:opacity-50 text-xs font-black text-slate-300 border border-slate-700 transition-all cursor-pointer">
                                    ÷ {{ $n }} (${{ number_format((($totalPaidSoFar > 0 ? $remainingToPay : ($selectedOrder->total_usd ?? 0))) / $n, 2) }})
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Campos de Monto Recibido, Referencia y Comprobante de Pago -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-950/60 p-4 rounded-xl border border-slate-800">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Monto a Cobrar:</label>
                            <input type="number" step="0.01" wire:model="amountReceived"
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-base font-black text-amber-400 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <span class="text-[11px] text-slate-500">Monto total o parcial (abono).</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Número de Referencia:</label>
                            <input type="text" wire:model="paymentReference" placeholder="Ej: #123456 o últimos 4"
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Capture / Comprobante:</label>
                            <input type="file" wire:model="proofImage" accept="image/*"
                                class="w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-amber-400 hover:file:bg-slate-700 cursor-pointer">
                            @if ($proofImage)
                                <div class="mt-1 flex items-center space-x-2 text-[10px] text-emerald-400 font-bold">
                                    <span>📸 Capture listo para guardar</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Botón para Agregar al Desglose si es Pago Mixto -->
                    <div class="flex justify-end">
                        <button wire:click="addPayment" wire:loading.attr="disabled"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-50 text-slate-200 font-bold text-xs flex items-center space-x-1.5 transition-colors cursor-pointer">
                            <span wire:loading.remove wire:target="addPayment">➕</span>
                            <span wire:loading wire:target="addPayment" class="animate-spin">⏳</span>
                            <span wire:loading.remove wire:target="addPayment">Guardar Abono / Pago Parcial</span>
                            <span wire:loading wire:target="addPayment">Guardando abono...</span>
                        </button>
                    </div>

                    <!-- Lista de Pagos Parciales Aplicados -->
                    @if(!empty($appliedPayments))
                        <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 space-y-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase">Pagos Registrados en esta Orden:</h4>
                            @foreach($appliedPayments as $idx => $ap)
                                <div class="flex items-center justify-between text-xs py-2 border-b border-slate-800/60 last:border-0" wire:key="applied-{{ $idx }}">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-black text-slate-200 uppercase">{{ $ap['method'] }} ({{ $ap['currency'] }})</span>
                                            @if(!empty($ap['bank_name']))
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 font-medium">{{ $ap['bank_name'] }}</span>
                                            @endif
                                            @if(!empty($ap['reference']))
                                                <span class="font-mono text-amber-400 text-[11px]">Ref: {{ $ap['reference'] }}</span>
                                            @endif
                                        </div>
                                        @if(!empty($ap['proof_image']))
                                            <a href="{{ asset('storage/' . $ap['proof_image']) }}" target="_blank" class="text-[10px] text-blue-400 hover:underline flex items-center space-x-1">
                                                <span>📎</span>
                                                <span>Ver Comprobante Adjunto</span>
                                            </a>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="font-black text-emerald-400 text-sm">${{ number_format($ap['amount_usd'], 2) }}</span>
                                        <button wire:click="removePayment({{ $idx }})" class="p-1 text-red-400 hover:text-red-300 font-bold rounded hover:bg-red-950/40" title="Eliminar abono">✕</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- BOTÓN PRINCIPAL DE COBRO Y EMISIÓN DE TICKET -->
                    <div class="pt-4 border-t border-slate-800">
                        @php
                            $rate = 1.0;
                            if (in_array($selectedPaymentMethod, ['cash_ved', 'mobile_pay'])) {
                                $rate = $config->bcv_rate ?? 60.00;
                            } elseif (in_array($selectedPaymentMethod, ['cash_cop', 'nequi'])) {
                                $rate = $config->cop_rate ?? 4100.00;
                            }
                            $amountUsdInput = (in_array($selectedPaymentMethod, ['cash_ved', 'mobile_pay', 'cash_cop', 'nequi']) && $rate > 0)
                                ? ((float)$amountReceived / $rate)
                                : (float)$amountReceived;
                            $willCoverTotal = ($totalPaidSoFar + $amountUsdInput) >= ($selectedOrder->total_usd - 0.01);
                            $isCredit = $selectedPaymentMethod === 'credit';
                            $btnClass = $isCredit 
                                ? 'bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-500 hover:from-blue-500 hover:to-indigo-400 shadow-blue-600/30'
                                : 'bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 shadow-emerald-600/30';
                        @endphp
                        <button wire:click="processCheckout" wire:loading.attr="disabled"
                            class="w-full py-4 px-6 rounded-2xl {{ $btnClass }} disabled:opacity-60 disabled:cursor-not-allowed text-white font-black text-lg flex items-center justify-center space-x-3 shadow-xl transition-all transform active:scale-95 cursor-pointer">
                            <span wire:loading.remove wire:target="processCheckout">{{ $isCredit ? '👤' : '🖨️' }}</span>
                            <span wire:loading wire:target="processCheckout" class="animate-spin">⏳</span>
                            <span wire:loading.remove wire:target="processCheckout">
                                @if($isCredit)
                                    CONFIRMAR Y CARGAR A CRÉDITO DEL CLIENTE (${{ number_format($amountReceived, 2) }})
                                @else
                                    {{ $willCoverTotal ? "COBRAR TOTAL Y EMITIR FACTURA TÉRMICA (" . ($config->printer_width ?? 80) . "mm)" : "REGISTRAR ABONO Y ACTUALIZAR CUENTA" }}
                                @endif
                            </span>
                            <span wire:loading wire:target="processCheckout">
                                {{ $isCredit ? 'Cargando comanda a crédito...' : 'Procesando cobro e imprimiendo ticket...' }}
                            </span>
                        </button>
                        <span class="block text-center text-xs text-slate-500 mt-2">
                            {{ $isCredit ? 'Carga la deuda a la cuenta corriente del cliente, descuenta los insumos del inventario y cierra la orden de la mesa.' : 'Abre automáticamente la gaveta de dinero RJ11 y descuenta los insumos del inventario.' }}
                        </span>
                    </div>

                </div>
            @else
                <div class="p-12 text-center text-slate-500 bg-slate-900 rounded-2xl border border-slate-800 space-y-4">
                    <span class="text-5xl block mb-2">👈</span>
                    <h3 class="text-xl font-black text-slate-300">Selecciona una comanda</h3>
                    <p class="text-sm text-slate-500">Elige una cuenta de la lista izquierda para proceder al cobro.</p>

                    @if($lastPaidOrderId)
                        <div class="mt-6 inline-block bg-slate-950/80 border border-emerald-500/40 rounded-2xl p-5 max-w-sm text-left shadow-xl">
                            <div class="flex items-center space-x-2 text-emerald-400 font-bold text-sm mb-1">
                                <span>✅</span>
                                <span>Último cobro completado</span>
                            </div>
                            <p class="text-xs text-slate-400 mb-3">Impresora destino: <span class="font-mono text-slate-200">{{ $config->printer_name ?? 'POS-80' }}</span></p>
                            <button type="button" wire:click="reprintTicket" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center space-x-2 cursor-pointer">
                                <span>🖨️</span>
                                <span>Re-imprimir Ticket de Pago</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

    </div>

    <!-- MODAL DE DESCUENTO -->
    @if($showDiscountModal && $selectedOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-md p-5 sm:p-6 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">🏷️</span>
                        <div>
                            <h3 class="text-lg font-black text-white">Aplicar Descuento</h3>
                            <span class="text-xs text-slate-400">Orden #{{ $selectedOrder->order_number }} • Subtotal: ${{ number_format($selectedOrder->subtotal_usd, 2) }}</span>
                        </div>
                    </div>
                    <button wire:click="$set('showDiscountModal', false)" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                        ✕
                    </button>
                </div>

                <!-- Botones de Descuento Rápido en Porcentaje -->
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Descuentos Rápidos (%):</label>
                    <div class="grid grid-cols-5 gap-2">
                        <button type="button" wire:click="applyPresetDiscount(5)" class="py-2 rounded-xl bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-xs font-black text-slate-300 transition-all border border-slate-700">5%</button>
                        <button type="button" wire:click="applyPresetDiscount(10)" class="py-2 rounded-xl bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-xs font-black text-slate-300 transition-all border border-slate-700">10%</button>
                        <button type="button" wire:click="applyPresetDiscount(15)" class="py-2 rounded-xl bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-xs font-black text-slate-300 transition-all border border-slate-700">15%</button>
                        <button type="button" wire:click="applyPresetDiscount(20)" class="py-2 rounded-xl bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-xs font-black text-slate-300 transition-all border border-slate-700">20%</button>
                        <button type="button" wire:click="applyPresetDiscount(50)" class="py-2 rounded-xl bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-xs font-black text-slate-300 transition-all border border-slate-700">50%</button>
                    </div>
                </div>

                <!-- Tipo de Descuento Personalizado -->
                <form wire:submit.prevent="saveDiscount" class="space-y-4">
                    <div class="grid grid-cols-2 gap-2 bg-slate-950 p-1 rounded-xl border border-slate-800">
                        <button type="button" wire:click="$set('discountType', 'percent')"
                            class="py-2 rounded-lg text-xs font-bold transition-all {{ $discountType === 'percent' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white' }}">
                            Porcentaje (%)
                        </button>
                        <button type="button" wire:click="$set('discountType', 'fixed')"
                            class="py-2 rounded-lg text-xs font-bold transition-all {{ $discountType === 'fixed' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white' }}">
                            Monto Fijo ($ USD)
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">
                            {{ $discountType === 'percent' ? 'Porcentaje de Descuento (%):' : 'Monto de Descuento ($ USD):' }}
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-slate-400 font-bold">
                                {{ $discountType === 'percent' ? '%' : '$' }}
                            </span>
                            <input type="number" step="0.01" min="0" wire:model="discountValue"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-8 pr-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                        </div>
                    </div>

                    <div class="pt-2 flex items-center space-x-2">
                        @if($selectedOrder->discount_usd > 0)
                            <button type="button" wire:click="removeDiscount"
                                class="w-1/3 py-2.5 rounded-xl bg-red-950/60 hover:bg-red-900 border border-red-800/80 text-red-300 font-bold text-xs">
                                Quitar Descuento
                            </button>
                        @endif
                        <button type="button" wire:click="$set('showDiscountModal', false)"
                            class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs">
                            Cerrar
                        </button>
                        <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20">
                            Aplicar Descuento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL DE DIVISIÓN DE CUENTA POR ÍTEMS (DESDE CAJA) -->
    @if($showSplitModal && $selectedOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-lg p-5 sm:p-6 shadow-2xl flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">🍽️</span>
                        <div>
                            <h3 class="text-lg font-black text-white">Dividir Cuenta / Separar Platos</h3>
                            <span class="text-xs text-slate-400">Selecciona qué consumió cada persona para cobrar por separado</span>
                        </div>
                    </div>
                    <button wire:click="$set('showSplitModal', false)" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 text-base">
                        ✕
                    </button>
                </div>

                <!-- Nombre de la Nueva Cuenta Destino -->
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre o Etiqueta de la Nueva Cuenta:</label>
                    <input type="text" wire:model="splitNewAccountLabel" placeholder="Ej: Cuenta 2, Amigo Carlos, etc."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <!-- Lista de Ítems a Mover -->
                <div class="flex-1 overflow-y-auto space-y-2.5 divide-y divide-slate-800/60 pr-1 mb-4">
                    @foreach($selectedOrder->items as $item)
                        @php
                            $qtyToMove = $splitQuantities[$item->id] ?? 0;
                        @endphp
                        <div class="pt-2 first:pt-0 flex items-center justify-between" wire:key="cashier-split-item-{{ $item->id }}">
                            <div class="flex-1 pr-3">
                                <h5 class="text-sm font-bold text-white leading-tight">
                                    {{ $item->product->name ?? 'Producto' }}
                                </h5>
                                <div class="text-xs text-slate-400">
                                    En la comanda: <strong class="text-amber-400">{{ (int) $item->quantity }}x</strong> • (${{ number_format($item->unit_price_usd, 2) }} c/u)
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-slate-400 font-semibold mr-1">Mover:</span>
                                <div class="flex items-center space-x-1.5 bg-slate-950 p-1 rounded-xl border border-slate-800">
                                    <button type="button" wire:click="decrementSplitQty({{ $item->id }})"
                                        class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center text-sm transition-colors cursor-pointer">
                                        -
                                    </button>
                                    <span class="w-6 text-center text-xs font-black {{ $qtyToMove > 0 ? 'text-purple-300' : 'text-slate-500' }}">
                                        {{ $qtyToMove }}
                                    </span>
                                    <button type="button" wire:click="incrementSplitQty({{ $item->id }})"
                                        class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center text-sm transition-colors cursor-pointer">
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Totales Estimados -->
                @php
                    $newTotal = 0;
                    foreach($selectedOrder->items as $item) {
                        $qty = $splitQuantities[$item->id] ?? 0;
                        $newTotal += ($qty * $item->unit_price_usd);
                    }
                    $remainingTotal = max(0, $selectedOrder->total_usd - $newTotal);
                @endphp
                <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800 mb-4 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-400 block">Nueva cuenta ({{ $splitNewAccountLabel ?: 'Sub-cuenta' }}):</span>
                        <strong class="text-purple-400 text-sm">${{ number_format($newTotal, 2) }}</strong>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block">Restante en cuenta actual:</span>
                        <strong class="text-amber-400 text-sm">${{ number_format($remainingTotal, 2) }}</strong>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <button type="button" wire:click="$set('showSplitModal', false)"
                        class="flex-1 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-colors cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" wire:click="executeSplitOrder"
                        class="flex-1 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black text-xs shadow-lg shadow-purple-600/30 transition-all cursor-pointer">
                        Confirmar División de Cuenta
                    </button>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        $wire.on('clear-order-query', () => {
            const url = new URL(window.location);
            url.searchParams.delete('order_id');
            window.history.replaceState({}, '', url.pathname);
        });
    </script>
    @endscript

</div>

