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
                        {{ $currentOrder?->diningTable ? $currentOrder->diningTable->name : ($tableId ? 'Mesa' : 'Para Llevar / Mostrador') }}
                    </h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                        {{ $currentOrder?->account_label ?? 'Nueva Comanda' }}
                    </span>
                </div>
                <div class="text-xs text-slate-400 font-medium">
                    <span>Orden #{{ $currentOrder?->order_number ?? 'Borrador' }}</span>
                    <span class="mx-1">•</span>
                    <span>Cliente: <strong class="text-slate-200">{{ $currentOrder?->customer?->name ?? 'General' }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Estado de la Orden -->
        @php
            $orderStatus = $currentOrder?->status ?? 'pending';
            $statusBadge = match($orderStatus) {
                'pending' => 'bg-slate-800 text-slate-300 border-slate-700',
                'in_kitchen' => 'bg-orange-500/20 text-orange-400 border-orange-500/40 animate-pulse',
                'ready' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40 ring-2 ring-emerald-500/30',
                'served' => 'bg-blue-500/20 text-blue-400 border-blue-500/40',
                'bill_requested' => 'bg-red-500/20 text-red-400 border-red-500/40',
                default => 'bg-slate-800 text-slate-300 border-slate-700',
            };
            $statusText = match($orderStatus) {
                'pending' => 'Borrador / Sin Enviar',
                'in_kitchen' => 'En Cocina 🍳',
                'ready' => '¡Listo para Servir! 🔔',
                'served' => 'Platos Servidos 🍽️',
                'bill_requested' => 'Pre-cuenta Solicitada 🧾',
                default => ucfirst($orderStatus),
            };
        @endphp
        <span class="px-3 py-1 rounded-xl text-xs font-black border uppercase tracking-wider {{ $statusBadge }}">
            {{ $statusText }}
        </span>
    </div>

    <!-- Barra de Selección de Sub-cuentas y Unificación (cuando hay más de 1 cuenta en la mesa) -->
    @if($currentOrder?->diningTable && $currentOrder->diningTable->activeOrders()->count() > 1)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 mb-4 flex flex-wrap items-center justify-between gap-2 shadow-md">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-slate-400">Cuentas en esta Mesa:</span>
                @foreach($currentOrder->diningTable->activeOrders as $subAcc)
                    <button type="button" wire:click="switchAccount({{ $subAcc->id }})"
                        class="px-3 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer {{ $subAcc->id === $orderId ? 'bg-amber-500 text-slate-950 shadow-md ring-2 ring-amber-500/50' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        {{ $subAcc->account_label }} (${{ number_format($subAcc->total_usd, 2) }})
                    </button>
                @endforeach
            </div>
            <button type="button" wire:click="mergeTableAccounts"
                wire:confirm="¿Deseas unir todas las sub-cuentas de esta mesa en una sola cuenta unificada?"
                class="px-3 py-1.5 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-700/60 font-bold text-xs flex items-center space-x-1.5 transition-all cursor-pointer">
                <span>🔗</span>
                <span>Unificar Cuentas (Volver a 1 Sola)</span>
            </button>
        </div>
    @endif

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
                            class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all flex items-center space-x-1.5 {{ $selectedCategoryId === $cat->id ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                            @if($cat->icon)
                                <span>{{ $cat->icon }}</span>
                            @endif
                            <span>{{ $cat->name }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Grilla de Platos / Productos Táctiles -->
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                @forelse($products as $product)
                    <div wire:click="addProduct({{ $product->id }})"
                        wire:key="prod-card-{{ $product->id }}"
                        class="bg-slate-900 border border-slate-800 hover:border-amber-500/60 rounded-2xl p-3 cursor-pointer transition-all duration-150 transform active:scale-95 flex flex-col justify-between select-none shadow-md group overflow-hidden">
                        
                        <div>
                            @if($product->image)
                                <div class="h-20 w-full rounded-xl overflow-hidden mb-2 bg-slate-950 border border-slate-800/80">
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                </div>
                            @elseif($product->category && $product->category->image && file_exists(public_path('storage/' . $product->category->image)))
                                <div class="h-20 w-full rounded-xl overflow-hidden mb-2 bg-slate-950 border border-slate-800/80">
                                    <img src="{{ asset('storage/' . $product->category->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200 opacity-90">
                                </div>
                            @endif

                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                    {{ $product->category->name ?? '' }}
                                </span>
                                @if($product->type === 'direct')
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $product->current_stock > 5 ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-red-950 text-red-400 border border-red-800' }}">
                                        {{ (int)$product->current_stock }} disp.
                                    </span>
                                @elseif($product->send_to_kitchen)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-950 text-purple-400 border border-purple-800">
                                        🍳 Cocina
                                    </span>
                                @endif
                            </div>

                            <h4 class="text-sm font-bold text-white group-hover:text-amber-400 transition-colors line-clamp-2 mt-1">
                                {{ $product->name }}
                            </h4>
                        </div>

                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between mt-2">
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
                        {{ $currentOrder?->items?->count() ?? 0 }} productos
                    </span>
                </div>

                <!-- Lista de Ítems en la Comanda -->
                <div class="flex-1 overflow-y-auto py-3 space-y-2.5 divide-y divide-slate-800/60">
                    @forelse($currentOrder?->items ?? [] as $item)
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
                <div class="pt-3 border-t border-slate-800 space-y-2.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-400 font-semibold">Subtotal:</span>
                        <span class="text-white font-bold">${{ number_format($currentOrder?->subtotal_usd ?? 0, 2) }}</span>
                    </div>

                    <!-- Fila de Descuento -->
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center space-x-1">
                            <span class="text-slate-400 font-semibold">Descuento:</span>
                            @if(($currentOrder?->discount_usd ?? 0) > 0)
                                <button wire:click="removeDiscount" class="text-red-400 hover:text-red-300 text-xs font-bold" title="Remover descuento">✕</button>
                            @endif
                        </div>
                        <div>
                            @if(($currentOrder?->discount_usd ?? 0) > 0)
                                <button wire:click="openDiscountModal" class="text-emerald-400 font-bold hover:underline">
                                    -${{ number_format($currentOrder->discount_usd, 2) }}
                                </button>
                            @else
                                <button wire:click="openDiscountModal" class="text-xs text-amber-400 hover:text-amber-300 font-bold flex items-center space-x-1">
                                    <span>🏷️</span>
                                    <span>+ Descuento</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-lg font-black border-t border-slate-800 pt-2">
                        <span class="text-white">TOTAL:</span>
                        <span class="text-amber-400">${{ number_format($currentOrder?->total_usd ?? 0, 2) }}</span>
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
                            <a href="{{ url('/cashier?order_id=' . ($currentOrder?->id ?? '')) }}"
                                class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center justify-center space-x-1 transition-all shadow-md shadow-emerald-600/20">
                                <span>💳</span>
                                <span>Cobrar</span>
                            </a>
                        </div>

                        <!-- 4. Dividir / Separar Ítems a Nueva Cuenta -->
                        @if($currentOrder && $currentOrder->items->count() > 0)
                            <button type="button" wire:click="openSplitModal"
                                class="w-full py-2.5 px-3 rounded-xl bg-purple-950/70 hover:bg-purple-900 border border-purple-700/60 text-purple-200 font-bold text-xs flex items-center justify-center space-x-1.5 transition-all shadow-sm cursor-pointer">
                                <span>✂️</span>
                                <span>Dividir Cuenta (Separar Platos a Otra Cuenta)</span>
                            </button>
                        @endif

                        @if($currentOrder?->diningTable && $currentOrder->diningTable->activeOrders()->count() > 1)
                            <button type="button" wire:click="mergeTableAccounts"
                                wire:confirm="¿Deseas unir todas las sub-cuentas de esta mesa en una sola cuenta unificada?"
                                class="w-full py-2.5 px-3 rounded-xl bg-indigo-950/70 hover:bg-indigo-900 border border-indigo-700/60 text-indigo-200 font-bold text-xs flex items-center justify-center space-x-1.5 transition-all shadow-sm cursor-pointer">
                                <span>🔗</span>
                                <span>Unificar Cuentas de la Mesa (Volver a 1 Sola)</span>
                            </button>
                        @endif

                        <!-- 5. Cancelar / Descartar Orden -->
                        <button wire:click="cancelOrder"
                            wire:confirm="¿Estás seguro de cancelar o descartar esta orden? Esta acción no se puede deshacer."
                            class="w-full py-2 px-3 rounded-xl bg-slate-950 hover:bg-red-950/60 border border-slate-800 hover:border-red-800 text-slate-400 hover:text-red-300 font-bold text-xs flex items-center justify-center space-x-1.5 transition-all">
                            <span>🗑️</span>
                            <span>Cancelar / Descartar Orden</span>
                        </button>
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

    <!-- MODAL DE DESCUENTO EN COMANDA -->
    @if($showDiscountModal && $currentOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-md p-5 sm:p-6 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">🏷️</span>
                        <div>
                            <h3 class="text-lg font-black text-white">Aplicar Descuento a la Comanda</h3>
                            <span class="text-xs text-slate-400">Subtotal: ${{ number_format($currentOrder->subtotal_usd, 2) }}</span>
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
                        @if(($currentOrder->discount_usd ?? 0) > 0)
                            <button type="button" wire:click="removeDiscount"
                                class="w-1/3 py-2.5 rounded-xl bg-red-950/60 hover:bg-red-900 border border-red-800/80 text-red-300 font-bold text-xs">
                                Quitar
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

    <!-- MODAL DE DIVISIÓN DE CUENTA POR ÍTEMS (SPLIT BILL) -->
    @if($showSplitModal && $currentOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-lg p-5 sm:p-6 shadow-2xl flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">✂️</span>
                        <div>
                            <h3 class="text-lg font-black text-white">Dividir Cuenta / Separar Platos</h3>
                            <span class="text-xs text-slate-400">Selecciona los platos que deseas mover a una nueva sub-cuenta</span>
                        </div>
                    </div>
                    <button wire:click="$set('showSplitModal', false)" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                        ✕
                    </button>
                </div>

                <!-- Nombre de la Nueva Cuenta Destino -->
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre o Etiqueta de la Nueva Sub-Cuenta:</label>
                    <input type="text" wire:model="splitNewAccountLabel" placeholder="Ej: Cuenta 2, Amigo Carlos, etc."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <!-- Lista de Ítems a Mover -->
                <div class="flex-1 overflow-y-auto space-y-2.5 divide-y divide-slate-800/60 pr-1 mb-4">
                    @foreach($currentOrder->items as $item)
                        @php
                            $qtyToMove = $splitQuantities[$item->id] ?? 0;
                        @endphp
                        <div class="pt-2 first:pt-0 flex items-center justify-between" wire:key="split-item-{{ $item->id }}">
                            <div class="flex-1 pr-3">
                                <h5 class="text-sm font-bold text-white leading-tight">
                                    {{ $item->product->name ?? 'Producto' }}
                                </h5>
                                <div class="text-xs text-slate-400">
                                    Disponibles: <strong class="text-amber-400">{{ $item->quantity }}x</strong> • (${{ number_format($item->unit_price_usd, 2) }} c/u)
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-slate-400 font-semibold mr-1">Mover:</span>
                                <div class="flex items-center space-x-1.5 bg-slate-950 p-1 rounded-xl border border-slate-800">
                                    <button type="button" wire:click="decrementSplitQty({{ $item->id }})"
                                        class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center text-xs">
                                        -
                                    </button>
                                    <span class="w-6 text-center text-xs font-black {{ $qtyToMove > 0 ? 'text-purple-300' : 'text-slate-500' }}">
                                        {{ $qtyToMove }}
                                    </span>
                                    <button type="button" wire:click="incrementSplitQty({{ $item->id }})"
                                        class="w-6 h-6 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center text-xs">
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Botones de Acción -->
                <div class="pt-3 border-t border-slate-800 flex items-center space-x-2">
                    <button type="button" wire:click="$set('showSplitModal', false)"
                        class="w-1/3 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs">
                        Cancelar
                    </button>
                    <button type="button" wire:click="executeSplitOrder"
                        class="w-2/3 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black text-xs shadow-lg shadow-purple-600/25 flex items-center justify-center space-x-2 cursor-pointer">
                        <span>✂️</span>
                        <span>Confirmar División de Cuenta</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
