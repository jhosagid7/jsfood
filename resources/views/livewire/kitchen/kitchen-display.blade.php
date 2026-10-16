<div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-3 sm:py-5" wire:poll.5s="checkNewOrders">

    <!-- Barra Superior de Control KDS -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 bg-slate-900 border border-slate-800 p-3 sm:p-4 rounded-2xl shadow-md">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">🍳</span>
            <div>
                <h2 class="text-xl font-black text-white">Pantalla de Cocina (KDS)</h2>
                <span class="text-xs text-slate-400 font-semibold flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Actualización en tiempo real (cada 5s)</span>
                </span>
            </div>
        </div>

        <!-- Filtros de Estado & Probar Sonido -->
        <div class="flex items-center space-x-2 overflow-x-auto pb-1 sm:pb-0">
            <button type="button" onclick="if(window.JSFoodAudio) window.JSFoodAudio.playKitchenChime()"
                title="Probar timbre de cocina"
                class="px-2.5 py-2 rounded-xl bg-slate-800 hover:bg-amber-500/20 text-amber-400 border border-slate-700 text-xs font-bold transition-all flex items-center space-x-1">
                <span>🔊</span>
                <span class="hidden md:inline">Probar Timbre</span>
            </button>
            <button wire:click="$set('filter', 'active')"
                class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-sm {{ $filter === 'active' ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                🔥 Todas las Activas
            </button>
            <button wire:click="$set('filter', 'in_kitchen')"
                class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-sm {{ $filter === 'in_kitchen' ? 'bg-orange-500 text-slate-950 font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                🍳 En Preparación
            </button>
            <button wire:click="$set('filter', 'ready')"
                class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-sm {{ $filter === 'ready' ? 'bg-emerald-500 text-slate-950 font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                🔔 Listas para Servir
            </button>
        </div>
    </div>

    <!-- Tablero de Comandas / Tarjetas Kanban de Cocina -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($orders as $order)
            @php
                $sentTime = $order->kitchen_sent_at ?? $order->created_at;
                $minutesAgo = $sentTime ? (int) $sentTime->diffInMinutes(now()) : 0;

                // Color según demora
                if ($minutesAgo < 10) {
                    $timerClass = 'bg-emerald-950/80 text-emerald-400 border-emerald-700/60';
                    $cardBorder = 'border-slate-800 hover:border-slate-700';
                } elseif ($minutesAgo <= 20) {
                    $timerClass = 'bg-amber-950/80 text-amber-400 border-amber-700/60';
                    $cardBorder = 'border-amber-500/50 ring-1 ring-amber-500/20';
                } else {
                    $timerClass = 'bg-red-950/90 text-red-300 border-red-600 animate-pulse';
                    $cardBorder = 'border-red-500/80 ring-2 ring-red-500/30';
                }

                $isReady = $order->status === 'ready';
                $isPreparing = $order->status === 'in_kitchen';
            @endphp

            <div wire:key="kitchen-order-{{ $order->id }}"
                class="bg-slate-900 rounded-2xl border flex flex-col justify-between shadow-xl overflow-hidden transition-all {{ $cardBorder }} {{ $isReady ? 'bg-slate-900/60 border-emerald-500/50' : '' }}">
                
                <!-- Encabezado de la Tarjeta -->
                <div class="p-4 bg-slate-950/70 border-b border-slate-800 flex items-start justify-between">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-lg font-black text-white leading-tight">
                                {{ $order->diningTable ? $order->diningTable->name : 'Para Llevar' }}
                            </h3>
                            @if(!empty($order->account_label) && $order->account_label !== 'Cuenta Principal')
                                <span class="bg-purple-950 text-purple-300 border border-purple-800 text-[10px] font-black px-1.5 py-0.5 rounded">
                                    {{ $order->account_label }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs text-slate-400 font-medium block mt-0.5">
                            Mesonero: <strong class="text-slate-300">{{ $order->user->name ?? 'N/A' }}</strong>
                        </span>
                    </div>

                    <!-- Cronómetro de Demora -->
                    <div class="flex flex-col items-end">
                        <span class="px-2 py-0.5 rounded-lg border text-xs font-black tracking-wider {{ $timerClass }}">
                            ⏱️ {{ $minutesAgo }}m
                        </span>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5">#{{ $order->order_number }}</span>
                    </div>
                </div>

                <!-- Detalle de Platos a Preparar -->
                <div class="p-4 flex-1 space-y-3 overflow-y-auto max-h-72">
                    @foreach($order->items as $item)
                        <div class="bg-slate-950/40 p-2.5 rounded-xl border border-slate-800/80 space-y-1">
                            <div class="flex items-start space-x-2">
                                <span class="w-7 h-7 rounded-lg bg-amber-500 text-slate-950 font-black text-sm flex items-center justify-center shrink-0">
                                    {{ $item->quantity }}
                                </span>
                                <div class="flex-1">
                                    <h5 class="text-sm font-bold text-white leading-tight">
                                        {{ $item->product->name ?? 'Plato' }}
                                    </h5>
                                </div>
                            </div>

                            <!-- Notas Especiales del Cliente al Cocinero -->
                            @if(!empty($item->notes))
                                <div class="bg-amber-950/80 border border-amber-500/40 rounded-lg p-2 text-xs font-black text-amber-300 flex items-start space-x-1.5 mt-1">
                                    <span class="text-sm">⚠️</span>
                                    <span>NOTA: {{ $item->notes }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if(!empty($order->notes))
                        <div class="bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-300">
                            <strong class="text-amber-400 block mb-0.5">Nota General:</strong>
                            {{ $order->notes }}
                        </div>
                    @endif
                </div>

                <!-- Botones de Acción para el Cocinero -->
                <div class="p-3 bg-slate-950/90 border-t border-slate-800 flex items-center space-x-2">
                    @if($order->status === 'pending')
                        <button wire:click="markAsPreparing({{ $order->id }})"
                            class="flex-1 py-2.5 px-3 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-black text-xs flex items-center justify-center space-x-1 shadow-md">
                            <span>🍳</span>
                            <span>Empezar</span>
                        </button>
                    @elseif($order->status === 'in_kitchen')
                        <button wire:click="markAsReady({{ $order->id }})"
                            class="flex-1 py-2.5 px-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-xs flex items-center justify-center space-x-1 shadow-lg shadow-emerald-500/25">
                            <span>🔔</span>
                            <span>¡Listo para Servir!</span>
                        </button>
                    @elseif($order->status === 'ready')
                        <button wire:click="markAsServed({{ $order->id }})"
                            class="flex-1 py-2.5 px-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs flex items-center justify-center space-x-1">
                            <span>🍽️</span>
                            <span>Entregado</span>
                        </button>
                    @endif

                    <!-- Reimprimir Comanda -->
                    <button wire:click="reprintKitchenTicket({{ $order->id }})" title="Reimprimir Comanda en Tiquera de Cocina"
                        class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs flex items-center justify-center">
                        🖨️
                    </button>
                </div>

            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-500">
                <span class="text-5xl block mb-3">👨‍🍳</span>
                <p class="text-xl font-black text-slate-400">Todo limpio en cocina</p>
                <p class="text-sm text-slate-500 mt-1">No hay comandas pendientes de preparación en este momento.</p>
            </div>
        @endforelse
    </div>

</div>
