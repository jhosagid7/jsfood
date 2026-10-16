<div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-3 sm:py-4">

    <!-- Barra de Contexto de la Mesa y Sub-cuenta -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 sm:p-4 mb-4 flex flex-wrap items-center justify-between gap-3 shadow-md">
        <div class="flex items-center space-x-3">
            <a href="{{ url('/tables') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs flex items-center space-x-1">
                <span>◀</span>
                <span class="hidden sm:inline">Mesas</span>
            </a>

            <div>
                <div class="flex items-center space-x-2">
                    <h2 class="text-lg sm:text-xl font-black text-white">
                        {{ $currentOrder->diningTable ? $currentOrder->diningTable->name : 'Para Llevar / Mostrador' }}
                    </h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                        {{ $currentOrder->account_label ?? 'Cuenta Principal' }}
                    </span>
                </div>
                <div class="text-xs text-slate-400 font-medium">
                    <span>Orden #{{ $currentOrder->order_number ?? '' }}</span>
                    <span class="mx-1">•</span>
                    <span>Cliente: <strong class="text-slate-200">{{ $currentOrder->customer->name ?? 'General' }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Estado de la Orden -->
        @php
            $statusBadge = match($currentOrder->status ?? 'pending') {
                'pending' => 'bg-slate-800 text-slate-300 border-slate-700',
                'in_kitchen' => 'bg-orange-500/20 text-orange-400 border-orange-500/40 animate-pulse',
                'ready' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40 ring-2 ring-emerald-500/30',
                'served' => 'bg-blue-500/20 text-blue-400 border-blue-500/40',
                'bill_requested' => 'bg-red-500/20 text-red-400 border-red-500/40',
                default => 'bg-slate-800 text-slate-300 border-slate-700',
            };
            $statusText = match($currentOrder->status ?? 'pending') {
                'pending' => 'Borrador / Sin Enviar',
                'in_kitchen' => 'En Cocina 🍳',
                'ready' => '¡Listo para Servir! 🔔',
                'served' => 'Platos Servidos 🍽️',
                'bill_requested' => 'Pre-cuenta Solicitada 🧾',
                default => ucfirst($currentOrder->status ?? ''),
            };
        @endphp
        <span class="px-3 py-1 rounded-xl text-xs font-black border uppercase tracking-wider {{ $statusBadge }}">
            {{ $statusText }}
        </span>
    </div>

    <!-- Contenido Dividido: Catálogo a la Izquierda / Comanda a la Derecha -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- COLUMNA IZQUIERDA: Menú y Catálogo de Platos (8 cols) -->
        <div class="lg:col-span-7 xl:col-span-8 flex flex-col space-y-4">
            
            <!-- Barra de Búsqueda y Pestañas de Categoría -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 space-y-3">
                <!-- Buscador Rápido -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-500">🔍</span>
                    <input type="text" wire:model.live.debounce.250ms="search" placeholder="Buscar hamburguesa, bebida, arroz..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>

                <!-- Categorías Táctiles (Horizontal Scroll) -->
                <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-none">
                    <button wire:click="selectCategory(null)"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ is_null($selectedCategoryId) ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        Todos
                    </button>
                    @foreach($categories as $cat)
                        <button wire:click="selectCategory({{ $cat->id }})"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $selectedCategoryId === $cat->id ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Grilla de Platos / Productos Táctiles -->
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                @forelse($products as $product)
                    <div wire:click="addProduct({{ $product->id }})"
                        wire:key="prod-card-{{ $product->id }}"
                        class="bg-slate-900 border border-slate-800 hover:border-amber-500/60 rounded-2xl p-3.5 cursor-pointer transition-all duration-150 transform active:scale-95 flex flex-col justify-between h-36 select-none shadow-md group">
                        
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                    {{ $product->category->name ?? '' }}
                                </span>
                                @if($product->type === 'direct')
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $product->current_stock > 5 ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-red-950 text-red-400 border border-red-800' }}">
                                        {{ (int)$product->current_stock }} disp.
                                    </span>
                                @elseif($product->has_recipe)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-950 text-purple-400 border border-purple-800">
                                        Cocina / Receta
                                    </span>
                                @endif
                            </div>

                            <h4 class="text-sm font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-2 mt-1">
                                {{ $product->name }}
                            </h4>
                        </div>

                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-base font-black text-amber-400">
                                ${{ number_format($product->price_usd, 2) }}
                            </span>
                            <span class="w-7 h-7 rounded-lg bg-slate-800 group-hover:bg-amber-500 group-hover:text-slate-950 flex items-center justify-center text-sm font-black transition-colors">
                                +
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-slate-500">
                        No se encontraron productos para esta búsqueda.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- COLUMNA DERECHA: Comanda / Ticket del Mesonero (4-5 cols) -->
        <div class="lg:col-span-5 xl:col-span-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl sticky top-20 flex flex-col max-h-[85vh]">
                
                <!-- Encabezado de la Comanda -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-base font-black text-white flex items-center space-x-2">
                        <span>🧾</span>
                        <span>Comanda Activa</span>
                    </h3>
                    <span class="text-xs font-bold text-slate-400">
                        {{ $currentOrder->items->count() ?? 0 }} productos
                    </span>
                </div>

                <!-- Lista de Ítems en la Comanda -->
                <div class="flex-1 overflow-y-auto py-3 space-y-2.5 divide-y divide-slate-800/60">
                    @forelse($currentOrder->items ?? [] as $item)
                        <div class="pt-2 first:pt-0 flex flex-col space-y-1" wire:key="item-row-{{ $item->id }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 pr-2">
                                    <h5 class="text-sm font-bold text-white leading-tight">
                                        {{ $item->product->name ?? 'Producto' }}
                                    </h5>
                                    <div class="text-xs text-slate-400">
                                        ${{ number_format($item->unit_price_usd, 2) }} c/u
                                    </div>
                                </div>

                                <div class="text-right">
                                    <span class="text-sm font-black text-amber-400 block">
                                        ${{ number_format($item->subtotal_usd, 2) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Controles de Cantidad y Botón de Nota -->
                            <div class="flex items-center justify-between pt-1">
                                <button wire:click="openNoteModal({{ $item->id }})"
                                    class="text-[11px] font-semibold text-slate-400 hover:text-amber-400 flex items-center space-x-1">
                                    <span>✏️</span>
                                    <span>{{ !empty($item->notes) ? 'Nota: ' . Str::limit($item->notes, 20) : '+ Agregar Nota' }}</span>
                                </button>

                                <div class="flex items-center space-x-1.5 bg-slate-950 p-1 rounded-xl border border-slate-800">
                                    <button wire:click="decrementItem({{ $item->id }})"
                                        class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center text-xs">
                                        -
                                    </button>
                                    <span class="w-6 text-center text-xs font-black text-white">
                                        {{ $item->quantity }}
                                    </span>
                                    <button wire:click="incrementItem({{ $item->id }})"
                                        class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center text-xs">
                                        +
                                    </button>
                                </div>
                            </div>

                            @if(!empty($item->notes))
                                <div class="bg-amber-950/40 border border-amber-500/20 rounded-lg px-2 py-1 text-[11px] font-medium text-amber-300">
                                    💬 {{ $item->notes }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-500 text-xs">
                            <span class="text-3xl block mb-2">🍽️</span>
                            Selecciona platos del menú para agregarlos a la comanda.
                        </div>
                    @endforelse
                </div>

                <!-- Resumen de Totales y Botones de Acción -->
                <div class="pt-3 border-t border-slate-800 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-400 font-semibold">Subtotal:</span>
                        <span class="text-white font-bold">${{ number_format($currentOrder->subtotal_usd ?? 0, 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between text-lg font-black border-t border-slate-800 pt-2">
                        <span class="text-white">TOTAL:</span>
                        <span class="text-amber-400">${{ number_format($currentOrder->total_usd ?? 0, 2) }}</span>
                    </div>

                    <!-- Botones de Acción de Alto Impacto -->
                    <div class="space-y-2 pt-1">
                        <!-- 1. Enviar a Cocina -->
                        <button wire:click="sendOrderToKitchen"
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-slate-950 font-black text-sm flex items-center justify-center space-x-2 shadow-lg shadow-orange-500/25 transition-all">
                            <span>🍳</span>
                            <span>Enviar a Cocina e Imprimir</span>
                        </button>

                        <div class="grid grid-cols-2 gap-2">
                            <!-- 2. Pre-cuenta -->
                            <button wire:click="printPreBill"
                                class="py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs flex items-center justify-center space-x-1 transition-all">
                                <span>🧾</span>
                                <span>Pre-cuenta</span>
                            </button>

                            <!-- 3. Cobrar en Caja -->
                            <a href="{{ url('/cashier?order_id=' . ($currentOrder->id ?? '')) }}"
                                class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center justify-center space-x-1 transition-all shadow-md shadow-emerald-600/20">
                                <span>💳</span>
                                <span>Cobrar</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- MODAL DE NOTAS AL COCINERO PARA EL PLATO -->
    @if($showItemNoteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-sm p-5 shadow-2xl">
                <h4 class="text-base font-black text-white mb-2">Instrucciones para Cocina</h4>
                <p class="text-xs text-slate-400 mb-3">Escribe las preferencias del cliente (ej. sin cebolla, salsa aparte, etc.):</p>

                <textarea wire:model="itemNoteText" rows="3" placeholder="Ej: Bien cocida, sin mayonesa, cambio de guarnición..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none mb-3"></textarea>

                <div class="flex items-center space-x-2">
                    <button wire:click="$set('showItemNoteModal', false)"
                        class="w-1/2 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs">
                        Cancelar
                    </button>
                    <button wire:click="saveItemNote"
                        class="w-1/2 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs">
                        Guardar Nota
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
