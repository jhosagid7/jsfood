<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6" wire:poll.10s>

    <!-- Barra Superior: Filtro por Áreas y Leyenda de Estados -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        
        <!-- Pestañas de Áreas -->
        <div class="flex items-center space-x-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
            <button wire:click="selectArea(null)" 
                class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all shadow-sm {{ is_null($selectedAreaId) ? 'bg-amber-500 text-slate-950 shadow-amber-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                🏢 Todas las Zonas
            </button>
            @foreach($areas as $area)
                <button wire:click="selectArea({{ $area->id }})" 
                    class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all shadow-sm {{ $selectedAreaId === $area->id ? 'bg-amber-500 text-slate-950 shadow-amber-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                    {{ $area->name }}
                </button>
            @endforeach
        </div>

        <!-- Leyenda de Estados -->
        <div class="flex flex-wrap items-center gap-3 text-xs font-semibold bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
            <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span><span class="text-slate-300">Libre</span></span>
            <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span><span class="text-slate-300">Ocupada</span></span>
            <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse"></span><span class="text-slate-300">En Cocina</span></span>
            <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span><span class="text-slate-300">Servida</span></span>
            <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span><span class="text-slate-300">Pre-cuenta</span></span>
        </div>
    </div>

    <!-- Cuadrícula de Mesas (Touch-Friendly Responsive Grid) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
        @forelse($tables as $t)
            @php
                $statusColor = match($t->status) {
                    'free' => 'border-emerald-500/40 bg-emerald-950/20 hover:border-emerald-400 text-emerald-400',
                    'occupied' => 'border-amber-500/40 bg-amber-950/20 hover:border-amber-400 text-amber-400',
                    'kitchen' => 'border-orange-500/60 bg-orange-950/30 hover:border-orange-400 text-orange-400 ring-2 ring-orange-500/30',
                    'ready' => 'border-indigo-500/60 bg-indigo-950/30 hover:border-indigo-400 text-indigo-400 ring-2 ring-indigo-500/30 animate-bounce',
                    'served' => 'border-blue-500/40 bg-blue-950/20 hover:border-blue-400 text-blue-400',
                    'bill_requested' => 'border-red-500/60 bg-red-950/30 hover:border-red-400 text-red-400 ring-2 ring-red-500/30',
                    default => 'border-slate-700 bg-slate-900/40 text-slate-400',
                };

                $statusLabel = match($t->status) {
                    'free' => 'Libre',
                    'occupied' => 'Ocupada',
                    'kitchen' => 'Cocina 🍳',
                    'ready' => '¡Lista! 🔔',
                    'served' => 'Servida',
                    'bill_requested' => 'Cuenta 🧾',
                    default => ucfirst($t->status),
                };

                $totalConsumed = $t->activeOrders->sum('total_usd');
            @endphp

            <div wire:click="openTable({{ $t->id }})"
                wire:key="table-card-{{ $t->id }}"
                class="relative rounded-2xl border p-4 cursor-pointer transition-all duration-200 transform active:scale-95 hover:shadow-xl hover:-translate-y-0.5 flex flex-col justify-between h-36 sm:h-40 select-none {{ $statusColor }}">
                
                <!-- Encabezado de la Mesa -->
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 block">{{ $t->area->name ?? '' }}</span>
                        <h3 class="text-lg sm:text-xl font-black text-white tracking-tight">{{ $t->name }}</h3>
                    </div>

                    <!-- Badge de Cuentas Múltiples -->
                    @if($t->active_orders_count > 1)
                        <span class="bg-purple-900/80 border border-purple-500/60 text-purple-200 text-[10px] font-extrabold px-2 py-0.5 rounded-full shadow-sm">
                            👥 {{ $t->active_orders_count }} Cuentas
                        </span>
                    @elseif($t->active_orders_count === 1)
                        <span class="bg-slate-800 text-slate-300 text-[10px] font-bold px-1.5 py-0.5 rounded-md">
                            1 Cuenta
                        </span>
                    @endif
                </div>

                <!-- Centro: Capacidad e Icono -->
                <div class="my-auto flex items-center justify-between text-xs text-slate-400">
                    <span class="flex items-center space-x-1">
                        <span>👥</span>
                        <span>{{ $t->capacity }} pers.</span>
                    </span>

                    <span class="px-2 py-0.5 rounded-md text-[11px] font-black uppercase tracking-wider bg-slate-900/80 border border-current">
                        {{ $statusLabel }}
                    </span>
                </div>

                <!-- Pie: Total Consumido -->
                <div class="border-t border-slate-800/80 pt-2 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400 font-medium">Consumo:</span>
                    <span class="text-sm font-black {{ $totalConsumed > 0 ? 'text-amber-400' : 'text-slate-500' }}">
                        ${{ number_format($totalConsumed, 2) }}
                    </span>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400">
                <p class="text-lg font-bold">No se encontraron mesas en esta zona.</p>
            </div>
        @endforelse
    </div>

    <!-- MODAL 1: Detalle de Cuentas Activas de la Mesa (Split Bill Hub) -->
    @if($showTableModal && $selectedTable)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-xl p-5 sm:p-6 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                
                <!-- Encabezado Modal -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">🍽️</span>
                        <div>
                            <h3 class="text-xl font-black text-white">{{ $selectedTable->name }}</h3>
                            <span class="text-xs text-slate-400 font-semibold">{{ $selectedTable->area->name ?? '' }} • {{ $selectedTable->active_orders_count }} cuentas activas</span>
                        </div>
                    </div>
                    <button wire:click="$set('showTableModal', false)" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                        ✕
                    </button>
                </div>

                <!-- Lista de Sub-Cuentas Independientes -->
                <div class="flex-1 overflow-y-auto py-4 space-y-3">
                    @forelse($selectedTable->activeOrders as $order)
                        <div class="bg-slate-950/70 rounded-xl p-4 border border-slate-800 hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        {{ $order->account_label }}
                                    </span>
                                    <span class="text-xs text-slate-400 font-mono">#{{ $order->order_number }}</span>
                                </div>
                                <div class="mt-1 text-xs text-slate-300">
                                    <span>Cliente: <strong class="text-white">{{ $order->customer->name ?? 'Consumidor General' }}</strong></span>
                                    <span class="text-slate-600 mx-1">•</span>
                                    <span>{{ $order->items->count() }} ítems</span>
                                </div>
                                <div class="text-base font-black text-amber-400 mt-1">
                                    Total: ${{ number_format($order->total_usd, 2) }}
                                </div>
                            </div>

                            <!-- Botones de Acción por Sub-Cuenta -->
                            <div class="flex items-center space-x-2">
                                <a href="{{ url('/pos/order/' . $selectedTable->id . '?order_id=' . $order->id) }}"
                                    class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition-colors flex items-center space-x-1">
                                    <span>📝</span>
                                    <span>Comanda</span>
                                </a>
                                <button wire:click="printPreBill({{ $order->id }})"
                                    class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-amber-400 rounded-xl text-xs font-bold transition-colors flex items-center space-x-1">
                                    <span>🧾</span>
                                    <span>Pre-cuenta</span>
                                </button>
                                <a href="{{ url('/cashier?order_id=' . $order->id) }}"
                                    class="px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-black transition-colors flex items-center space-x-1 shadow-sm">
                                    <span>💳</span>
                                    <span>Cobrar</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-500">
                            No hay cuentas abiertas en esta mesa.
                        </div>
                    @endforelse
                </div>

                <!-- Pie Modal: Botón para Crear Otra Cuenta en Esta Mesa -->
                <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
                    <button wire:click="$set('showNewAccountModal', true)" 
                        class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-black text-sm flex items-center justify-center space-x-2 shadow-lg shadow-orange-500/20">
                        <span>➕ Abrir Otra Cuenta Independiente en Esta Mesa</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 2: Apertura de Nueva Cuenta en Mesa -->
    @if($showNewAccountModal && $selectedTable)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-md p-5 sm:p-6 shadow-2xl">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                    <h3 class="text-lg font-black text-white">Nueva Comanda en {{ $selectedTable->name }}</h3>
                    <button wire:click="$set('showNewAccountModal', false)" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form wire:submit.prevent="createNewAccount" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre / Identificador de la Cuenta:</label>
                        <input type="text" wire:model="newAccountLabel" placeholder="Ej: Cuenta 1, Amigos de Juan, Pareja..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <span class="text-[11px] text-slate-500">Puedes asignarle el nombre del comensal para separar cuentas fácilmente.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Cliente Asociado (Opcional):</label>
                        <select wire:model="newCustomerId"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="">Consumidor General / Ocasional</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->identification ?? 'S/R' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-2 flex items-center space-x-2">
                        <button type="button" wire:click="$set('showNewAccountModal', false)"
                            class="w-1/2 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="w-1/2 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20">
                            Iniciar Comanda 🚀
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
