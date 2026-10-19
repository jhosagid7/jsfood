<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Encabezado y Acciones Rápidas -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-xl">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-2xl">📂</span>
                <h1 class="text-2xl font-black text-white tracking-tight">Categorías del Menú</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Organiza las secciones del menú para el terminal táctil de mesoneros y caja.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ url('/products') }}" 
                class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition-all flex items-center space-x-1.5 border border-slate-700/60">
                <span>🍔</span>
                <span>Ver Catálogo de Platos</span>
            </a>
            <button wire:click="openCreateModal" 
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 transition-all flex items-center space-x-1.5 cursor-pointer">
                <span>➕</span>
                <span>Nueva Categoría</span>
            </button>
        </div>
    </div>

    <!-- Barra de Búsqueda y Estadísticas -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative w-full sm:w-80">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-sm">🔍</span>
            <input type="text" wire:model.live.debounce.250ms="search" placeholder="Buscar categoría por nombre..."
                class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-amber-500 focus:outline-none">
        </div>

        <div class="text-xs text-slate-400">
            Total categorías registradas: <span class="font-bold text-white">{{ $categories->count() }}</span>
        </div>
    </div>

    <!-- Cuadrícula de Categorías -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($categories as $cat)
            @php
                $colorClasses = match($cat->color) {
                    'amber' => 'border-amber-500/40 bg-amber-500/10 text-amber-400',
                    'emerald' => 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400',
                    'blue' => 'border-blue-500/40 bg-blue-500/10 text-blue-400',
                    'purple' => 'border-purple-500/40 bg-purple-500/10 text-purple-400',
                    'rose' => 'border-rose-500/40 bg-rose-500/10 text-rose-400',
                    'orange' => 'border-orange-500/40 bg-orange-500/10 text-orange-400',
                    'cyan' => 'border-cyan-500/40 bg-cyan-500/10 text-cyan-400',
                    default => 'border-slate-700 bg-slate-800/40 text-slate-300',
                };
            @endphp
            <div class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-4 shadow-lg transition-all flex flex-col justify-between space-y-3 group"
                wire:key="category-{{ $cat->id }}">
                
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl border {{ $colorClasses }}">
                            {{ $cat->icon ?: '🍽️' }}
                        </div>
                        <div>
                            <h3 class="text-base font-black text-white group-hover:text-amber-400 transition-colors">
                                {{ $cat->name }}
                            </h3>
                            <span class="text-[11px] font-mono text-slate-500 block">
                                /{{ $cat->slug }}
                            </span>
                        </div>
                    </div>

                    <button wire:click="toggleStatus({{ $cat->id }})" 
                        title="Clic para cambiar estado"
                        class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border transition-all {{ $cat->is_active ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30' }}">
                        {{ $cat->is_active ? 'Activa' : 'Inactiva' }}
                    </button>
                </div>

                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                    <div class="flex items-center space-x-2">
                        <span>📦 {{ $cat->products_count }} {{ Str::plural('producto', $cat->products_count) }}</span>
                        <span class="text-slate-600">•</span>
                        <span class="font-mono text-[11px] text-slate-500">Orden: {{ $cat->sort_order }}</span>
                    </div>

                    <div class="flex items-center space-x-1">
                        <button wire:click="editCategory({{ $cat->id }})" 
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                            title="Editar categoría">
                            ✏️
                        </button>
                        <button wire:click="deleteCategory({{ $cat->id }})" 
                            wire:confirm="¿Estás seguro de eliminar esta categoría?"
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-red-900/60 text-slate-400 hover:text-red-300 transition-colors"
                            title="Eliminar categoría">
                            🗑️
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-slate-900/50 rounded-2xl border border-slate-800/80 space-y-3">
                <span class="text-4xl block">📂</span>
                <p class="text-sm font-bold text-slate-400">No se encontraron categorías</p>
                <button wire:click="openCreateModal" class="px-4 py-2 rounded-xl bg-amber-500 text-slate-950 font-black text-xs">
                    Crear Primera Categoría
                </button>
            </div>
        @endforelse
    </div>

    <!-- MODAL CREAR / EDITAR CATEGORÍA -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
            x-transition>
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-lg font-black text-white flex items-center space-x-2">
                        <span>{{ $editingCategoryId ? '✏️ Editar Categoría' : '➕ Nueva Categoría' }}</span>
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-white font-bold text-lg">✕</button>
                </div>

                <form wire:submit="saveCategory" class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre de la Categoría:</label>
                        <input type="text" wire:model="name" placeholder="Ej: Hamburguesas, Bebidas, Pizzas..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @error('name') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Icono Emoji Predefinido o Personalizado -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Icono / Emoji:</label>
                        <div class="flex items-center space-x-2 mb-2">
                            <input type="text" wire:model="icon" maxlength="10"
                                class="w-20 text-center text-xl bg-slate-950 border border-slate-800 rounded-xl px-2 py-2 text-white font-black focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <span class="text-[11px] text-slate-400">Elige un emoji o escribe uno.</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 p-2 bg-slate-950/60 rounded-xl border border-slate-800/80">
                            @foreach(['🍔', '🍕', '🥩', '🍟', '🌮', '🥗', '🍰', '🍺', '🥤', '☕', '🍹', '🍣', '🍦', '🍱', '🥪'] as $e)
                                <button type="button" wire:click="$set('icon', '{{ $e }}')"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-base hover:bg-slate-800 transition-colors {{ $icon === $e ? 'bg-amber-500/20 border border-amber-500/40' : '' }}">
                                    {{ $e }}
                                </button>
                            @endforeach
                        </div>
                        @error('icon') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Color de la Tarjeta / Botón en Terminal Táctil -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Color Distintivo:</label>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach([
                                'amber' => 'Ámbar',
                                'emerald' => 'Verde',
                                'blue' => 'Azul',
                                'purple' => 'Morado',
                                'rose' => 'Rosa',
                                'orange' => 'Naranja',
                                'cyan' => 'Cian',
                                'slate' => 'Gris'
                            ] as $colKey => $colLabel)
                                <button type="button" wire:click="$set('color', '{{ $colKey }}')"
                                    class="py-2 px-2 text-xs font-bold rounded-xl border flex items-center justify-center space-x-1 transition-all {{ $color === $colKey ? 'border-amber-400 bg-slate-800 text-white shadow-md' : 'border-slate-800 bg-slate-950 text-slate-400 hover:bg-slate-800' }}">
                                    <span class="w-2.5 h-2.5 rounded-full {{ match($colKey) {
                                        'amber' => 'bg-amber-400',
                                        'emerald' => 'bg-emerald-400',
                                        'blue' => 'bg-blue-400',
                                        'purple' => 'bg-purple-400',
                                        'rose' => 'bg-rose-400',
                                        'orange' => 'bg-orange-400',
                                        'cyan' => 'bg-cyan-400',
                                        default => 'bg-slate-400',
                                    } }}"></span>
                                    <span>{{ $colLabel }}</span>
                                </button>
                            @endforeach
                        </div>
                        @error('color') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Orden de Visualización y Activo -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Posición / Orden:</label>
                            <input type="number" wire:model="sortOrder" min="0"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @error('sortOrder') <span class="text-[11px] text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center pt-6">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" wire:model="isActive"
                                    class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500 h-4 w-4">
                                <span class="text-xs font-bold text-slate-300">Categoría Activa</span>
                            </label>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold">
                            Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 flex items-center space-x-2 cursor-pointer">
                            <span wire:loading.remove wire:target="saveCategory">💾 Guardar Categoría</span>
                            <span wire:loading wire:target="saveCategory" class="flex items-center space-x-1">
                                <span class="animate-spin">⏳</span>
                                <span>Guardando...</span>
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif

</div>
