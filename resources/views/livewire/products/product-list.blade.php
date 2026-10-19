<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Encabezado y Acciones Principales -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-xl">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-2xl">🍔</span>
                <h1 class="text-2xl font-black text-white tracking-tight">Catálogo de Productos & Menú</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Administra los platos de "Donde Cholao Grill", bebidas directas, insumos y configuración de recetas y cocina.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ url('/categories') }}" 
                class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition-all flex items-center space-x-1.5 border border-slate-700/60">
                <span>📂</span>
                <span>Gestionar Categorías</span>
            </a>
            <button wire:click="openCreateModal" 
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 transition-all flex items-center space-x-1.5 cursor-pointer">
                <span>➕</span>
                <span>Nuevo Producto / Plato</span>
            </button>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="bg-slate-900/80 border border-slate-800 p-4 rounded-2xl shadow-md space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Buscador -->
            <div class="relative sm:col-span-2 lg:col-span-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-sm">🔍</span>
                <input type="text" wire:model.live.debounce.250ms="search" placeholder="Buscar por nombre, código..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <!-- Filtro de Categoría -->
            <div>
                <select wire:model.live="filterCategory"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">-- Todas las Categorías --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro de Tipo -->
            <div>
                <select wire:model.live="filterType"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">-- Todos los Tipos --</option>
                    <option value="dish">🍽️ Platos Preparados (Cocina)</option>
                    <option value="direct">🥤 Productos Directos (Bebidas/Snacks)</option>
                    <option value="supply">🥩 Insumos Crudos (Materia Prima)</option>
                </select>
            </div>

            <!-- Filtro de Destino / Cocina -->
            <div>
                <select wire:model.live="filterSendToKitchen"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">-- Todos los Destinos --</option>
                    <option value="1">🍳 Pasa a Cocina (Requiere Chef)</option>
                    <option value="0">🥤 Entrega Directa (Sin Cocina)</option>
                </select>
            </div>

            <!-- Filtro de Stock Bajo -->
            <div class="flex items-center justify-between sm:justify-start space-x-2 px-2 py-1">
                <label class="flex items-center space-x-2 text-xs font-bold text-slate-300 cursor-pointer">
                    <input type="checkbox" wire:model.live="filterLowStock"
                        class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500 h-4 w-4">
                    <span>⚠️ Stock Bajo</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Cuadrícula de Productos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($products as $prod)
            <div class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl overflow-hidden shadow-lg transition-all flex flex-col justify-between group"
                wire:key="product-{{ $prod->id }}">
                
                <!-- Imagen Destacada o Icono de Cabecera -->
                <div class="relative h-44 bg-slate-950 flex items-center justify-center overflow-hidden border-b border-slate-800/80">
                    @if($prod->image)
                        <img src="{{ asset('storage/' . $prod->image) }}" alt="{{ $prod->name }}" 
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                        <div class="flex flex-col items-center justify-center text-slate-600">
                            <span class="text-5xl">{{ $prod->category?->icon ?: ($prod->type === 'supply' ? '🥩' : '🍽️') }}</span>
                            <span class="text-[10px] text-slate-600 mt-1 font-semibold">Sin imagen destacada</span>
                        </div>
                    @endif

                    <!-- Badges Superiores Izquierdos -->
                    <div class="absolute top-2 left-2 flex flex-col space-y-1">
                        @if($prod->type === 'dish')
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-purple-600/95 text-white shadow">
                                🍳 Plato
                            </span>
                        @elseif($prod->type === 'direct')
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-cyan-600/95 text-white shadow">
                                🥤 Directo
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-rose-600/95 text-white shadow">
                                🥩 Insumo
                            </span>
                        @endif

                        @if($prod->type !== 'supply')
                            <button wire:click="toggleSendToKitchen({{ $prod->id }})" 
                                title="Clic para alternar si va a cocina o entrega directa"
                                class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider shadow backdrop-blur-md transition-all text-left {{ $prod->send_to_kitchen ? 'bg-orange-500/90 hover:bg-orange-400 text-slate-950' : 'bg-slate-800/90 hover:bg-slate-700 text-slate-300' }}">
                                {{ $prod->send_to_kitchen ? '🍳 A Cocina' : '🚫 No Cocina' }}
                            </button>
                        @endif
                    </div>

                    <!-- Badge de Estado (Activo / Inactivo) -->
                    <button wire:click="toggleStatus({{ $prod->id }})" 
                        title="Clic para cambiar estado"
                        class="absolute top-2 right-2 text-[10px] font-black uppercase px-2 py-0.5 rounded-md shadow backdrop-blur-md transition-all {{ $prod->is_active ? 'bg-emerald-500/90 text-white' : 'bg-red-500/90 text-white' }}">
                        {{ $prod->is_active ? 'Activo' : 'Pausado' }}
                    </button>
                </div>

                <!-- Contenido y Datos del Producto -->
                <div class="p-4 space-y-2 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1">
                            <span class="font-bold text-amber-400">
                                {{ $prod->category?->name ?? 'Sin categoría' }}
                            </span>
                            @if($prod->code)
                                <span class="font-mono text-slate-500">#{{ $prod->code }}</span>
                            @endif
                        </div>

                        <h3 class="text-base font-black text-white group-hover:text-amber-400 transition-colors line-clamp-1">
                            {{ $prod->name }}
                        </h3>

                        @if($prod->description)
                            <p class="text-[11px] text-slate-400 line-clamp-2 mt-0.5">
                                {{ $prod->description }}
                            </p>
                        @endif

                        @if($prod->has_recipe && $prod->recipeItems->isNotEmpty())
                            <div class="mt-2 text-[10px] bg-slate-950 p-1.5 rounded-lg border border-slate-800 text-slate-400">
                                <span class="font-bold text-purple-400">📜 Receta:</span>
                                {{ $prod->recipeItems->count() }} insumos configurados
                            </div>
                        @endif
                    </div>

                    <!-- Precios y Stock -->
                    <div class="pt-3 border-t border-slate-800/80 space-y-1">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-black text-amber-400">
                                ${{ number_format($prod->price_usd, 2) }}
                            </span>
                            @if($prod->track_stock)
                                <span class="text-[11px] font-mono font-bold {{ $prod->current_stock <= $prod->min_stock ? 'text-red-400 animate-pulse' : 'text-slate-400' }}">
                                    Stock: {{ (float)$prod->current_stock }} {{ $prod->unit }}
                                </span>
                            @else
                                <span class="text-[10px] text-slate-500 italic">Sin inventario</span>
                            @endif
                        </div>

                        <!-- Moneda Dual (Bs / COP) -->
                        @if($config && $config->currency_mode === 'multi')
                            <div class="flex items-center justify-between text-[10px] text-slate-500">
                                <span>Bs {{ number_format($prod->price_usd * $config->bcv_rate, 2) }}</span>
                                <span>COP$ {{ number_format($prod->price_usd * $config->cop_rate, 0) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="p-3 bg-slate-950/70 border-t border-slate-800/80 flex items-center space-x-2">
                    <button wire:click="editProduct({{ $prod->id }})" 
                        class="flex-1 py-1.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition-all flex items-center justify-center space-x-1">
                        <span>✏️</span>
                        <span>Editar</span>
                    </button>
                    <button wire:click="deleteProduct({{ $prod->id }})"
                        wire:confirm="¿Seguro que deseas eliminar este producto? Si tiene ventas previas o insumos asignados no podrá borrarse."
                        class="p-1.5 px-2.5 rounded-xl bg-red-950/60 hover:bg-red-800/80 text-red-300 text-xs font-bold transition-all border border-red-800/50">
                        <span>🗑️</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-slate-900 border border-slate-800 rounded-2xl">
                <span class="text-4xl block mb-2">🍽️</span>
                <h4 class="text-base font-bold text-white">No se encontraron productos</h4>
                <p class="text-xs text-slate-400 mt-1">Prueba cambiando los filtros o registra nuevos platos en el menú.</p>
                <button wire:click="openCreateModal" class="mt-4 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl">
                    Crear mi primer producto
                </button>
            </div>
        @endforelse
    </div>

    <!-- Paginación -->
    @if($products->hasPages())
        <div class="pt-4">
            {{ $products->links() }}
        </div>
    @endif

    <!-- MODAL CREAR / EDITAR PRODUCTO O PLATO -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-3xl w-full p-6 shadow-2xl space-y-5 my-8 max-h-[90vh] overflow-y-auto">
                
                <!-- Encabezado del Modal -->
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">{{ $editingProductId ? '✏️' : '✨' }}</span>
                        <h2 class="text-lg font-black text-white">
                            {{ $editingProductId ? 'Editar Plato / Producto' : 'Nuevo Plato o Producto' }}
                        </h2>
                    </div>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <!-- Formulario -->
                <form wire:submit="saveProduct" class="space-y-4">

                    <!-- Fila 1: Nombre y Código -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre del Plato / Producto *</label>
                            <input type="text" wire:model="name" placeholder="Ej: Hamburguesa Clásica, Perro Especial..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @error('name') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Código / Referencia:</label>
                            <input type="text" wire:model="code" placeholder="Ej: HAMB-01"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @error('code') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Fila 2: Tipo de Producto y Categoría -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tipo de Producto *</label>
                            <select wire:model.live="type"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="dish">🍽️ Plato Preparado (Comida de restaurante, puede llevar receta)</option>
                                <option value="direct">🥤 Producto Directo (Bebidas embotelladas, chucherías de venta directa)</option>
                                <option value="supply">🥩 Insumo Crudo (Materia prima para recetas, carne, queso, pan)</option>
                            </select>
                            @error('type') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Categoría del Menú:</label>
                            <select wire:model="categoryId"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="">-- Sin categoría asignada --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('categoryId') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Fila 3: ¿Pasa a Cocina? (Configuración Clave para Tiquera KDS) -->
                    @if($type !== 'supply')
                        <div class="p-3.5 bg-slate-950 rounded-2xl border border-slate-800 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-white flex items-center space-x-2 cursor-pointer">
                                    <span class="text-lg">🍳</span>
                                    <span>¿Enviar a Cocina / Requiere Preparación?</span>
                                </label>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model.live="sendToKitchen" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                                </label>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                @if($sendToKitchen)
                                    <span class="text-emerald-400 font-bold">✅ SÍ PASA A COCINA:</span> Se imprimirá en el ticket de cocina y aparecerá en la pantalla KDS del cocinero (Hamburguesas, Perros, Carne en Vara, Merengadas).
                                @else
                                    <span class="text-amber-400 font-bold">🚫 NO PASA A COCINA (ENTREGA DIRECTA):</span> No hará bulto en el ticket de cocina ni molestará al cocinero (Coca-Cola, Pepsi, Agua Mineral, Chucherías).
                                @endif
                            </p>
                        </div>
                    @endif

                    <!-- Fila 4: Fotografía Destacada del Producto (Para Menú / Catálogo) -->
                    <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-300 uppercase">
                                📸 Imagen Destacada del Producto (Para Catálogos y Menú):
                            </label>
                            @if($photo || $existingImage)
                                <button type="button" wire:click="removeImage"
                                    class="text-[11px] text-red-400 hover:text-red-300 font-bold flex items-center space-x-1 cursor-pointer">
                                    <span>🗑️</span>
                                    <span>Quitar Imagen</span>
                                </button>
                            @endif
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <!-- Visualizador de Imagen -->
                            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-900 border-2 border-dashed border-slate-700 flex items-center justify-center shrink-0">
                                @if($photo)
                                    <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                                @elseif($existingImage)
                                    <img src="{{ asset('storage/' . $existingImage) }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-3xl text-slate-600">🖼️</span>
                                @endif
                            </div>

                            <!-- Selector de Archivo -->
                            <div class="flex-1 w-full">
                                <input type="file" wire:model="photo" accept="image/*"
                                    class="w-full text-xs text-slate-400 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-amber-400 hover:file:bg-slate-700 cursor-pointer">
                                <span class="text-[10px] text-slate-500 block mt-1">
                                    Formatos permitidos: JPG, PNG, WEBP (Máx. 2MB). Esta imagen se mostrará en los catálogos y cartas digitales.
                                </span>
                                <div wire:loading wire:target="photo" class="text-[11px] text-amber-400 font-bold mt-1">
                                    Cargando imagen... ⏳
                                </div>
                            </div>
                        </div>
                        @error('photo') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Fila 5: Descripción / Ingredientes del Plato -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Descripción / Ingredientes (Visible en Menú):</label>
                        <textarea wire:model="description" rows="2" placeholder="Ej: Pan artesanal, 100% carne de res, ensalada tradicional, papas, jamón, queso, tocineta y salsas..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                        @error('description') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Fila 6: Precios USD y Cálculo en Bolívares / Pesos -->
                    <div class="p-3.5 bg-slate-950 rounded-2xl border border-slate-800 space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                            <div>
                                <label class="block text-xs font-bold text-amber-400 uppercase mb-1">Precio de Venta ($ USD): *</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-black text-amber-400">$</span>
                                    <input type="number" step="0.01" wire:model.live="priceUsd"
                                        class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-7 pr-3 py-2 text-base font-black text-amber-400 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                </div>
                                @error('priceUsd') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            @if($config && $config->currency_mode === 'multi')
                                <div class="p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 text-xs">
                                    <span class="text-slate-400 block text-[10px]">Equivalente en Bolívares (BCV):</span>
                                    <span class="font-black text-white text-sm">
                                        Bs {{ number_format(((float)$priceUsd) * $config->bcv_rate, 2) }}
                                    </span>
                                </div>

                                <div class="p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 text-xs">
                                    <span class="text-slate-400 block text-[10px]">Equivalente en Pesos (COP):</span>
                                    <span class="font-black text-emerald-400 text-sm">
                                        COP$ {{ number_format(((float)$priceUsd) * $config->cop_rate, 0) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Fila 7: Control de Stock e Inventario -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Stock Actual:</label>
                            <input type="number" step="0.001" wire:model="currentStock"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @error('currentStock') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Stock Mínimo (Alerta):</label>
                            <input type="number" step="0.001" wire:model="minStock"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @error('minStock') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Unidad de Medida:</label>
                            <select wire:model="unit"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="unit">Unidad (ud)</option>
                                <option value="portion">Porción / Ración</option>
                                <option value="kg">Kilogramos (Kg)</option>
                                <option value="liter">Litros (L)</option>
                            </select>
                            @error('unit') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- SECCIÓN RECETA TÉCNICA (SOLO SI ES PLATO PREPARADO) -->
                    @if($type === 'dish')
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="flex items-center space-x-2 text-xs font-bold text-purple-400 uppercase cursor-pointer">
                                    <input type="checkbox" wire:model.live="hasRecipe"
                                        class="rounded bg-slate-900 border-slate-700 text-purple-600 focus:ring-purple-500 h-4 w-4">
                                    <span>📜 Ficha Técnica / Receta (Descuento automático de insumos en cocina)</span>
                                </label>

                                @if($hasRecipe)
                                    <button type="button" wire:click="addRecipeItem"
                                        class="px-2.5 py-1 rounded-lg bg-purple-950 border border-purple-800 text-purple-300 text-xs font-bold hover:bg-purple-900 transition-all flex items-center space-x-1">
                                        <span>➕</span>
                                        <span>Añadir Insumo</span>
                                    </button>
                                @endif
                            </div>

                            @if($hasRecipe)
                                <p class="text-[11px] text-slate-400">
                                    Al vender este plato en caja o comanda, se descontarán automáticamente los insumos seleccionados de tu inventario.
                                </p>

                                @if(empty($recipeItems))
                                    <div class="text-xs text-slate-500 text-center py-2">
                                        No has añadido insumos a esta receta. Haz clic en "Añadir Insumo".
                                    </div>
                                @else
                                    <div class="space-y-2 mt-2">
                                        @foreach($recipeItems as $index => $item)
                                            <div class="flex items-center space-x-2 bg-slate-900 p-2 rounded-xl border border-slate-800" wire:key="recipe-row-{{ $index }}">
                                                <!-- Insumo Selector -->
                                                <div class="flex-1">
                                                    <select wire:model="recipeItems.{{ $index }}.supply_id"
                                                        class="w-full bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none">
                                                        <option value="">-- Seleccionar Insumo --</option>
                                                        @foreach($supplies as $sup)
                                                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->unit }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- Cantidad Necesaria -->
                                                <div class="w-28">
                                                    <input type="number" step="0.001" placeholder="Cant."
                                                        wire:model="recipeItems.{{ $index }}.quantity_needed"
                                                        class="w-full bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-white font-mono focus:outline-none">
                                                </div>

                                                <!-- Eliminar Fila -->
                                                <button type="button" wire:click="removeRecipeItem({{ $index }})"
                                                    class="p-1.5 text-red-400 hover:text-red-300 text-xs">
                                                    ✕
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Estado Activo / Inactivo -->
                    <div class="flex items-center space-x-2 pt-2">
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-300 cursor-pointer">
                            <input type="checkbox" wire:model="isActive"
                                class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500 h-4 w-4">
                            <span>✅ Producto Activo en el Menú</span>
                        </label>
                    </div>

                    <!-- Botones de Acción del Modal -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-all">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 transition-all">
                            {{ $editingProductId ? 'Actualizar Producto' : 'Guardar Producto' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif

</div>
