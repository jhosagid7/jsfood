<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
        <div>
            <h2 class="text-2xl font-black text-white">Gestión de Dispositivos Conectados</h2>
            <p class="text-xs text-slate-400">Aprueba o bloquea las tablets y teléfonos de los mesoneros para conectar al restaurante.</p>
        </div>

        <a href="{{ url('/settings/printers') }}" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold">
            🖨️ Ajustes de Impresoras
        </a>
    </div>

    <!-- Política de Acceso -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
        <h3 class="text-base font-black text-white flex items-center space-x-2">
            <span>🛡️</span>
            <span>Política de Aprobación de Dispositivos</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Modo de Admisión:</label>
                <select wire:model="deviceAccessMode"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500">
                    <option value="open">Abierto (Auto-aprobar nuevos dispositivos hasta el límite)</option>
                    <option value="restricted">Restringido (Requiere aprobación manual del Administrador)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Límite Máximo de Dispositivos:</label>
                <input type="number" wire:model="maxDevices" min="1" max="50"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500">
            </div>
        </div>

        <div class="flex justify-end">
            <button wire:click="savePolicy"
                class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs">
                Guardar Política
            </button>
        </div>
    </div>

    <!-- Lista de Dispositivos Conectados -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-3">
        <h3 class="text-base font-black text-white flex items-center justify-between">
            <span class="flex items-center space-x-2">
                <span>📱</span>
                <span>Dispositivos Registrados ({{ $devices->count() }})</span>
            </span>
        </h3>

        <div class="space-y-2.5 pt-2">
            @forelse($devices as $dev)
                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3" wire:key="device-{{ $dev->id }}">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-sm font-black text-white">{{ $dev->name ?? 'Dispositivo Móvil' }}</h4>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $dev->status === 'approved' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : ($dev->status === 'pending' ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-red-950 text-red-400 border border-red-800') }}">
                                {{ $dev->status }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            <span>IP: <strong class="text-slate-300">{{ $dev->ip_address }}</strong></span>
                            <span class="mx-1">•</span>
                            <span>Último acceso: {{ $dev->last_accessed_at ? $dev->last_accessed_at->diffForHumans() : 'Nunca' }}</span>
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono mt-0.5 truncate max-w-md">
                            {{ $dev->user_agent }}
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center space-x-2">
                        @if($dev->status !== 'approved')
                            <button wire:click="approveDevice({{ $dev->id }})"
                                class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs">
                                ✓ Aprobar
                            </button>
                        @endif

                        @if($dev->status !== 'rejected')
                            <button wire:click="rejectDevice({{ $dev->id }})"
                                class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs">
                                ✕ Bloquear
                            </button>
                        @endif

                        <button wire:click="deleteDevice({{ $dev->id }})"
                            class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-red-900/60 text-slate-400 hover:text-red-300 font-bold text-xs">
                            🗑️
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-500 text-xs">
                    No hay dispositivos móviles registrados todavía.
                </div>
            @endforelse
        </div>
    </div>

</div>
