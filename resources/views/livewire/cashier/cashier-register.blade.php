<div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-3 sm:py-5">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- COLUMNA IZQUIERDA: Cuentas Pendientes por Cobrar (4 cols) -->
        <div class="lg:col-span-4 flex flex-col space-y-3">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
                <h3 class="text-sm font-black text-white flex items-center justify-between">
                    <span class="flex items-center space-x-2">
                        <span>🧾</span>
                        <span>Cuentas por Cobrar</span>
                    </span>
                    <span class="bg-amber-500/20 text-amber-400 text-xs px-2 py-0.5 rounded-full font-bold">
                        {{ $pendingOrders->count() }} activas
                    </span>
                </h3>
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
                            <span class="text-base font-black text-white">
                                ${{ number_format($ord->total_usd, 2) }}
                            </span>
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
                            <span class="text-xs text-slate-400 font-mono">Factura/Orden: #{{ $selectedOrder->order_number }}</span>
                        </div>

                        <!-- Total en Grande -->
                        <div class="text-right">
                            <span class="text-xs text-slate-400 font-bold uppercase block">Total a Cobrar</span>
                            <span class="text-3xl font-black text-amber-400">
                                ${{ number_format($selectedOrder->total_usd, 2) }}
                            </span>
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
                            <button wire:click="$set('selectedPaymentMethod', 'binance_usdt')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'binance_usdt' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                🟡 Binance USDT
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'zelle')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'zelle' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                🇺🇸 Zelle
                            </button>
                            <button wire:click="$set('selectedPaymentMethod', 'credit')"
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'credit' ? 'bg-purple-600 text-white font-black border-purple-500 shadow-md' : 'bg-slate-950 border-slate-800 text-purple-400 hover:bg-slate-800' }}">
                                👤 Crédito Cliente
                            </button>
                        </div>
                    </div>

                    <!-- Campos de Monto Recibido y Referencia -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-950/60 p-4 rounded-xl border border-slate-800">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Monto a Registrar:</label>
                            <input type="number" step="0.01" wire:model="amountReceived"
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-base font-black text-amber-400 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <span class="text-[11px] text-slate-500">Monto total o parcial si es pago mixto.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Referencia / Comprobante (Opcional):</label>
                            <input type="text" wire:model="paymentReference" placeholder="Ej: Últimos 4 dígitos o ref bancaria"
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Botón para Agregar al Desglose si es Pago Mixto -->
                    <div class="flex justify-end">
                        <button wire:click="addPayment"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs flex items-center space-x-1.5 transition-colors">
                            <span>➕</span>
                            <span>Agregar como Pago Parcial / Mixto</span>
                        </button>
                    </div>

                    <!-- Lista de Pagos Parciales Aplicados -->
                    @if(!empty($appliedPayments))
                        <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 space-y-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase">Pagos Registrados en esta Transacción:</h4>
                            @foreach($appliedPayments as $idx => $ap)
                                <div class="flex items-center justify-between text-xs py-1 border-b border-slate-800/60 last:border-0">
                                    <span class="font-bold text-slate-300 uppercase">{{ $ap['method'] }} ({{ $ap['currency'] }})</span>
                                    <div class="flex items-center space-x-3">
                                        <span class="font-black text-emerald-400">${{ number_format($ap['amount_usd'], 2) }}</span>
                                        <button wire:click="removePayment({{ $idx }})" class="text-red-400 hover:text-red-300 font-bold">✕</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- BOTÓN PRINCIPAL DE COBRO Y EMISIÓN DE TICKET -->
                    <div class="pt-4 border-t border-slate-800">
                        <button wire:click="processCheckout"
                            class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-lg flex items-center justify-center space-x-3 shadow-xl shadow-emerald-600/30 transition-all transform active:scale-95">
                            <span>🖨️</span>
                            <span>COBRAR Y EMITIR FACTURA TÉRMICA ({{ $config->printer_width ?? 80 }}mm)</span>
                        </button>
                        <span class="block text-center text-xs text-slate-500 mt-2">
                            Abre automáticamente la gaveta de dinero RJ11 y descuenta los insumos del inventario.
                        </span>
                    </div>

                </div>
            @else
                <div class="p-16 text-center text-slate-500 bg-slate-900 rounded-2xl border border-slate-800">
                    <span class="text-5xl block mb-3">👈</span>
                    <p class="text-xl font-black text-slate-300">Selecciona una comanda</p>
                    <p class="text-sm text-slate-500 mt-1">Elige una cuenta de la lista izquierda para proceder al cobro.</p>
                </div>
            @endif
        </div>

    </div>

</div>
