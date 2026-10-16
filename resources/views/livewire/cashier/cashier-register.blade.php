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
                            <div class="flex items-center space-x-3 mt-1 text-xs text-slate-400">
                                <span class="font-mono">Factura/Orden: #{{ $selectedOrder->order_number }}</span>
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
                            <div class="flex items-baseline justify-end space-x-1">
                                <span class="text-xs text-slate-400 font-bold uppercase mr-1">Total:</span>
                                <span class="text-3xl font-black text-amber-400">
                                    ${{ number_format($selectedOrder->total_usd, 2) }}
                                </span>
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
                                class="p-2.5 rounded-xl text-xs font-bold border transition-all {{ $selectedPaymentMethod === 'credit' ? 'bg-blue-600 text-white font-black border-blue-500 shadow-md' : 'bg-slate-950 border-slate-800 text-blue-400 hover:bg-slate-800' }}">
                                👤 Crédito Cliente
                            </button>
                        </div>
                    </div>

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
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs flex items-center space-x-1.5 transition-colors">
                            <span wire:loading.remove wire:target="addPayment">➕</span>
                            <span wire:loading wire:target="addPayment" class="animate-spin">⏳</span>
                            <span>Guardar Abono / Pago Parcial</span>
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

</div>
