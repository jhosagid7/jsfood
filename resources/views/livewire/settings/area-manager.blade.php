<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    <!-- Encabezado con Pestañas de Navegación de Ajustes -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h2 class="text-2xl font-black text-white flex items-center space-x-2">
                <span>🏢</span>
                <span>Configuración de Áreas, Salones y Mesas</span>
            </h2>
            <p class="text-xs text-slate-400">Personaliza los nombres de tus zonas (Salón, Terraza, Barra), agrega nuevas áreas y gestiona tus mesas.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ url('/settings/printers') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-colors">
                🖨️ Impresoras
            </a>
            <a href="{{ url('/settings/areas') }}" class="px-3.5 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-black shadow-md">
                🏢 Áreas y Mesas
            </a>
            <a href="{{ url('/settings/banks') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-colors">
                🏦 Bancos y Monedas
            </a>
            <a href="{{ url('/settings/devices') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-colors">
                📱 Dispositivos
            </a>
        </div>
    </div>

    <!-- SECCIÓN 1: Directorio de Áreas / Zonas del Restaurante -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-white flex items-center space-x-2">
                    <span>📍</span>
                    <span>Zonas y Salones Registrados</span>
                </h3>
                <p class="text-xs text-slate-400">Selecciona una zona para ver y configurar sus mesas, o agrega una nueva área.</p>
            </div>

            <button type="button" wire:click="openCreateAreaModal"
                class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-md cursor-pointer flex items-center space-x-1.5 shrink-0">
                <span>➕</span>
                <span>Nueva Área / Zona</span>
            </button>
        </div>

        <!-- Tarjetas de Selección de Área -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 pt-1">
            @forelse($areas as $area)
                @php
                    $isSelected = ($selectedAreaId === $area->id);
                @endphp
                <div wire:click="selectArea({{ $area->id }})"
                    class="p-4 rounded-xl border transition-all cursor-pointer relative overflow-hidden {{ $isSelected ? 'bg-slate-950 border-amber-500 ring-2 ring-amber-400/30 shadow-lg' : 'bg-slate-950/70 border-slate-800 hover:border-slate-700 hover:bg-slate-950' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-black text-sm text-white flex items-center space-x-1.5">
                                <span>{{ $area->name }}</span>
                            </h4>
                            <span class="text-[11px] text-slate-400 block mt-0.5">
                                {{ $area->tables_count }} {{ $area->tables_count === 1 ? 'mesa' : 'mesas' }}
                            </span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $area->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-500' }}">
                            {{ $area->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>

                    @if($area->description)
                        <p class="text-[11px] text-slate-400 mt-2 line-clamp-1 italic">
                            {{ $area->description }}
                        </p>
                    @endif

                    <div class="flex items-center justify-between pt-3 mt-3 border-t border-slate-900 text-xs">
                        <span class="text-[10px] font-mono text-slate-500">Orden: #{{ $area->sort_order }}</span>
                        <div class="flex items-center space-x-2" onclick="event.stopPropagation()">
                            <button type="button" wire:click="openEditAreaModal({{ $area->id }})"
                                class="text-slate-400 hover:text-amber-400 font-bold transition-colors" title="Editar nombre de zona">
                                ✏️
                            </button>
                            <button type="button" wire:click="deleteArea({{ $area->id }})"
                                wire:confirm="¿Seguro que deseas eliminar el área '{{ $area->name }}' y todas sus mesas asociadas?"
                                class="text-slate-500 hover:text-red-400 font-bold transition-colors" title="Eliminar área">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center bg-slate-950 rounded-2xl border border-slate-800 text-slate-500 text-xs">
                    No hay áreas registradas. Crea tu primera zona con el botón "Nueva Área / Zona".
                </div>
            @endforelse
        </div>
    </div>

    <!-- SECCIÓN 2: Mesas de la Zona Seleccionada -->
    @if($activeArea)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-black text-white flex items-center space-x-2">
                        <span>🪑</span>
                        <span>Mesas en: <span class="text-amber-400">{{ $activeArea->name }}</span></span>
                    </h3>
                    <p class="text-xs text-slate-400">Total: {{ $tables->count() }} mesas configuradas en esta zona.</p>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="button" wire:click="openBatchModal({{ $activeArea->id }})"
                        class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-colors flex items-center space-x-1.5 cursor-pointer">
                        <span>⚡</span>
                        <span>Generar en Lote</span>
                    </button>

                    <button type="button" wire:click="openCreateTableModal({{ $activeArea->id }})"
                        class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs transition-all shadow-md flex items-center space-x-1.5 cursor-pointer">
                        <span>➕</span>
                        <span>Nueva Mesa</span>
                    </button>
                </div>
            </div>

            <!-- Grilla de Mesas -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 pt-2">
                @forelse($tables as $table)
                    <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 space-y-2 hover:border-slate-700 transition-all flex flex-col justify-between">
                        <div class="flex items-start justify-between">
                            <span class="text-base font-black text-white">{{ $table->name }}</span>
                            <button type="button" wire:click="toggleTableActive({{ $table->id }})"
                                class="w-2.5 h-2.5 rounded-full {{ $table->is_active ? 'bg-emerald-500' : 'bg-slate-600' }}"
                                title="{{ $table->is_active ? 'Mesa activa' : 'Mesa inactiva' }}">
                            </button>
                        </div>

                        <div class="text-[11px] text-slate-400 space-y-0.5">
                            <div class="flex items-center justify-between">
                                <span>Capacidad:</span>
                                <span class="font-bold text-slate-200">👥 {{ $table->capacity }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Estado:</span>
                                <span class="font-mono text-emerald-400 uppercase text-[10px]">{{ $table->status }}</span>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-900 flex items-center justify-between text-xs">
                            <button type="button" wire:click="openEditTableModal({{ $table->id }})"
                                class="text-slate-400 hover:text-amber-400 font-bold" title="Editar mesa">
                                ✏️
                            </button>
                            <button type="button" wire:click="deleteTable({{ $table->id }})"
                                wire:confirm="¿Seguro que deseas eliminar {{ $table->name }}?"
                                class="text-slate-500 hover:text-red-400 font-bold" title="Eliminar mesa">
                                🗑️
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-slate-500 text-xs">
                        Esta zona aún no tiene mesas creadas. Haz clic en <strong>"Nueva Mesa"</strong> o <strong>"Generar en Lote"</strong> para agregarlas.
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- MODAL CREAR / EDITAR ÁREA -->
    @if($showAreaModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-base font-black text-white flex items-center space-x-2">
                        <span>{{ $areaId ? '✏️ Modificar Área / Salón' : '➕ Nueva Área / Salón' }}</span>
                    </h3>
                    <button wire:click="$set('showAreaModal', false)" class="text-slate-400 hover:text-white font-bold text-sm">✕</button>
                </div>

                <form wire:submit="saveArea" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-300 uppercase mb-1">Nombre del Área: *</label>
                        <input type="text" wire:model="areaName" placeholder="Ej: Salón Principal, Terraza VIP, Barra, Balcón..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @error('areaName') <span class="text-red-400 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 uppercase mb-1">Descripción (Opcional):</label>
                        <input type="text" wire:model="areaDescription" placeholder="Ej: Zona con aire acondicionado y música suave"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3 items-center">
                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Orden de Presentación:</label>
                            <input type="number" min="0" wire:model="areaSortOrder"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div class="pt-4">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="checkbox" wire:model="areaIsActive" class="rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-0">
                                <span class="text-slate-300 font-bold">Área Habilitada</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 flex items-center justify-end space-x-2 border-t border-slate-800">
                        <button type="button" wire:click="$set('showAreaModal', false)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black transition-all shadow-md cursor-pointer">
                            Guardar Área
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL CREAR / EDITAR MESA INDIVIDUAL -->
    @if($showTableModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-base font-black text-white flex items-center space-x-2">
                        <span>{{ $tableId ? '✏️ Modificar Mesa' : '➕ Registrar Mesa' }}</span>
                    </h3>
                    <button wire:click="$set('showTableModal', false)" class="text-slate-400 hover:text-white font-bold text-sm">✕</button>
                </div>

                <form wire:submit="saveTable" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-300 uppercase mb-1">Área / Zona Asignada: *</label>
                        <select wire:model="tableAreaId"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @foreach($areas as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                        @error('tableAreaId') <span class="text-red-400 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 uppercase mb-1">Nombre o Número de la Mesa: *</label>
                        <input type="text" wire:model="tableName" placeholder="Ej: Mesa 1, Mesa VIP, Barra 4..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @error('tableName') <span class="text-red-400 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3 items-center">
                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Capacidad (Personas): *</label>
                            <input type="number" min="1" max="50" wire:model="tableCapacity"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @error('tableCapacity') <span class="text-red-400 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="pt-4">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="checkbox" wire:model="tableIsActive" class="rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-0">
                                <span class="text-slate-300 font-bold">Mesa Habilitada</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 flex items-center justify-end space-x-2 border-t border-slate-800">
                        <button type="button" wire:click="$set('showTableModal', false)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black transition-all shadow-md cursor-pointer">
                            Guardar Mesa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL GENERAR MESAS EN LOTE -->
    @if($showBatchModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-base font-black text-white flex items-center space-x-2">
                        <span>⚡ Generar Mesas en Lote</span>
                    </h3>
                    <button wire:click="$set('showBatchModal', false)" class="text-slate-400 hover:text-white font-bold text-sm">✕</button>
                </div>

                <form wire:submit="generateBatchTables" class="space-y-3.5 text-xs">
                    <p class="text-slate-400 text-[11px]">Crea rápidamente una secuencia de mesas con prefijo y capacidad uniforme.</p>

                    <div>
                        <label class="block font-bold text-slate-300 uppercase mb-1">Prefijo de la Mesa: *</label>
                        <input type="text" wire:model="batchPrefix" placeholder="Ej: Mesa , Terraza , Barra "
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Desde el Nº: *</label>
                            <input type="number" min="1" wire:model="batchFrom"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 uppercase mb-1">Hasta el Nº: *</label>
                            <input type="number" min="1" wire:model="batchTo"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 uppercase mb-1">Capacidad por Mesa (Personas): *</label>
                        <input type="number" min="1" max="50" wire:model="batchCapacity"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div class="pt-3 flex items-center justify-end space-x-2 border-t border-slate-800">
                        <button type="button" wire:click="$set('showBatchModal', false)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black transition-all shadow-md cursor-pointer">
                            Generar Mesas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
