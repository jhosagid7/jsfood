<div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-3 sm:py-5">

    <!-- Encabezado y Botón Crear Cliente -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 bg-slate-900 border border-slate-800 p-4 sm:p-5 rounded-2xl shadow-md">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">👥</span>
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-white leading-tight">
                    Clientes & Cuentas por Cobrar
                </h2>
                <span class="text-xs text-slate-400 font-semibold">
                    Control de límites de crédito, plazos de vencimiento y cobranza
                </span>
            </div>
        </div>

        <button type="button" wire:click="openCreateModal"
            class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-sm flex items-center justify-center space-x-2 transition-all shadow-md cursor-pointer">
            <span>➕</span>
            <span>Nuevo Cliente</span>
        </button>
    </div>

    <!-- Tarjetas de Métricas Globales (KPIs) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- Total Clientes -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase">Total Clientes</span>
                <span class="text-xl">👥</span>
            </div>
            <div class="text-2xl font-black text-white mt-1">{{ $totalCustomersCount }}</div>
            <span class="text-[11px] text-slate-500 font-medium">Registrados en el sistema</span>
        </div>

        <!-- Cuentas por Cobrar Total -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase">Por Cobrar Total</span>
                <span class="text-xl">💳</span>
            </div>
            <div class="text-2xl font-black text-amber-400 mt-1">${{ number_format($totalDebtUsd, 2) }}</div>
            @if($config && $config->bcv_exchange_rate > 0)
                <span class="text-[11px] text-slate-400 font-medium">
                    ≈ Bs {{ number_format($totalDebtUsd * $config->bcv_exchange_rate, 2) }}
                </span>
            @endif
        </div>

        <!-- Clientes con Deuda -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase">Con Saldo Deudor</span>
                <span class="text-xl">⚠️</span>
            </div>
            <div class="text-2xl font-black text-orange-400 mt-1">{{ $customersWithDebtCount }}</div>
            <span class="text-[11px] text-slate-500 font-medium">Cuentas activas pendientes</span>
        </div>

        <!-- Créditos Vencidos -->
        <div class="bg-slate-900 border {{ $overdueCount > 0 ? 'border-red-500/60 ring-2 ring-red-500/20 bg-red-950/20' : 'border-slate-800' }} rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase">Créditos Vencidos</span>
                <span class="text-xl {{ $overdueCount > 0 ? 'animate-bounce' : '' }}">🚨</span>
            </div>
            <div class="text-2xl font-black {{ $overdueCount > 0 ? 'text-red-400 animate-pulse' : 'text-slate-300' }} mt-1">
                {{ $overdueCount }}
            </div>
            <span class="text-[11px] {{ $overdueCount > 0 ? 'text-red-400 font-bold' : 'text-slate-500' }}">
                {{ $overdueCount > 0 ? '¡Requiere gestión de cobro urgente!' : 'Sin créditos vencidos' }}
            </span>
        </div>
    </div>

    <!-- Barra de Búsqueda y Pestañas de Filtro -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 sm:p-4 mb-6 flex flex-col md:flex-row gap-3 items-center justify-between shadow-sm">
        <!-- Input de Búsqueda -->
        <div class="relative w-full md:w-80">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-500">🔍</span>
            <input type="text" wire:model.live.debounce.250ms="search" placeholder="Buscar por nombre, cédula, teléfono..."
                class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>

        <!-- Botones de Filtro -->
        <div class="flex items-center space-x-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none">
            <button type="button" wire:click="$set('filter', 'all')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ $filter === 'all' ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                Todos ({{ $totalCustomersCount }})
            </button>
            <button type="button" wire:click="$set('filter', 'with_debt')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1 {{ $filter === 'with_debt' ? 'bg-orange-500 text-slate-950 font-black shadow-md' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                <span>💳</span>
                <span>Con Saldo ({{ $customersWithDebtCount }})</span>
            </button>
            <button type="button" wire:click="$set('filter', 'overdue')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1 {{ $filter === 'overdue' ? 'bg-red-600 text-white font-black shadow-md' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                <span>🚨</span>
                <span>Vencidos ({{ $overdueCount }})</span>
            </button>
            <button type="button" wire:click="$set('filter', 'credit_allowed')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ $filter === 'credit_allowed' ? 'bg-purple-600 text-white font-black shadow-md' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                ⭐ Crédito Habilitado
            </button>
        </div>
    </div>

    <!-- Tabla de Clientes -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4 font-bold">Cliente / Identificación</th>
                        <th class="py-3.5 px-4 font-bold">Contacto</th>
                        <th class="py-3.5 px-4 font-bold">Límite y Plazo</th>
                        <th class="py-3.5 px-4 font-bold">Saldo Deudor</th>
                        <th class="py-3.5 px-4 font-bold">Estado de Vencimiento</th>
                        <th class="py-3.5 px-4 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    @forelse($customers as $c)
                        @php
                            $creditStatus = $c->credit_status;
                            $hasDebt = $c->current_balance > 0;
                        @endphp
                        <tr wire:key="customer-row-{{ $c->id }}" class="hover:bg-slate-800/40 transition-colors">
                            <!-- Nombre e ID -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white text-base leading-tight">{{ $c->name }}</div>
                                <span class="text-xs text-slate-400 font-mono">
                                    {{ $c->identification ? 'ID: ' . $c->identification : 'Sin Cédula/RIF' }}
                                </span>
                            </td>

                            <!-- Contacto -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-0.5 text-xs">
                                    @if($c->phone)
                                        <div class="flex items-center space-x-1.5 text-slate-200">
                                            <span>📱</span>
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c->phone) }}" target="_blank" class="hover:text-emerald-400 transition-colors">
                                                {{ $c->phone }}
                                            </a>
                                        </div>
                                    @endif
                                    @if($c->email)
                                        <div class="flex items-center space-x-1.5 text-slate-400">
                                            <span>✉️</span>
                                            <span class="truncate max-w-[160px]">{{ $c->email }}</span>
                                        </div>
                                    @endif
                                    @if(!$c->phone && !$c->email)
                                        <span class="text-slate-500 italic">Sin datos de contacto</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Límite y Plazo -->
                            <td class="py-3.5 px-4">
                                @if($c->allow_credit)
                                    <div>
                                        <span class="text-xs font-black text-purple-300 bg-purple-950/80 px-2 py-0.5 rounded border border-purple-800">
                                            Límite: ${{ number_format($c->credit_limit, 2) }}
                                        </span>
                                        <div class="text-[11px] text-slate-400 font-medium mt-1">
                                            Plazo: <strong>{{ $c->credit_days ?? 15 }} días</strong>
                                        </div>
                                        <div class="text-[10px] text-emerald-400 mt-0.5">
                                            Disp: ${{ number_format($c->available_credit, 2) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs font-semibold text-slate-500 bg-slate-800/80 px-2 py-0.5 rounded">
                                        Sin Crédito
                                    </span>
                                @endif
                            </td>

                            <!-- Saldo Deudor -->
                            <td class="py-3.5 px-4">
                                @if($hasDebt)
                                    <div>
                                        <span class="text-base font-black text-amber-400 font-mono">
                                            ${{ number_format($c->current_balance, 2) }}
                                        </span>
                                        @if($config && $config->bcv_exchange_rate > 0)
                                            <span class="block text-[11px] text-slate-400 font-mono">
                                                ≈ Bs {{ number_format($c->current_balance * $config->bcv_exchange_rate, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs font-bold text-emerald-400 bg-emerald-950/80 px-2 py-0.5 rounded border border-emerald-800">
                                        ✓ Al día ($0.00)
                                    </span>
                                @endif
                            </td>

                            <!-- Estado de Vencimiento -->
                            <td class="py-3.5 px-4">
                                @if(!$hasDebt)
                                    <span class="text-xs text-slate-500">—</span>
                                @elseif($creditStatus === 'overdue')
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center space-x-1 bg-red-950 text-red-300 border border-red-700 text-xs font-black px-2 py-0.5 rounded-lg animate-pulse">
                                            <span>🚨</span>
                                            <span>VENCIDO</span>
                                        </span>
                                        @if($c->credit_due_date)
                                            <span class="block text-[11px] text-red-400 font-semibold">
                                                Venció el {{ $c->credit_due_date->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @elseif($creditStatus === 'due_soon')
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center space-x-1 bg-amber-950 text-amber-300 border border-amber-700 text-xs font-black px-2 py-0.5 rounded-lg">
                                            <span>⏰</span>
                                            <span>POR VENCER</span>
                                        </span>
                                        @if($c->credit_due_date)
                                            <span class="block text-[11px] text-amber-400 font-semibold">
                                                Vence el {{ $c->credit_due_date->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center space-x-1 bg-emerald-950 text-emerald-300 border border-emerald-800 text-xs font-black px-2 py-0.5 rounded-lg">
                                            <span>🟢</span>
                                            <span>Al Día</span>
                                        </span>
                                        @if($c->credit_due_date)
                                            <span class="block text-[10px] text-slate-400">
                                                Límite: {{ $c->credit_due_date->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Acciones -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end space-x-1.5">
                                    @if($hasDebt)
                                        <button type="button" wire:click="openAbonoModal({{ $c->id }})"
                                            title="Registrar Abono o Pago de Deuda"
                                            class="px-2.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs flex items-center space-x-1 transition-all shadow-sm cursor-pointer">
                                            <span>💵</span>
                                            <span>Abonar</span>
                                        </button>

                                        @if($c->email)
                                            <button type="button" wire:click="sendReminderEmail({{ $c->id }})"
                                                title="Enviar Recordatorio de Cobro por Correo"
                                                class="p-1.5 rounded-xl bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-400 border border-slate-700 transition-all cursor-pointer">
                                                <span>✉️</span>
                                            </button>
                                        @endif
                                    @endif

                                    <button type="button" wire:click="openHistoryModal({{ $c->id }})"
                                        title="Ver Estado de Cuenta y Transacciones"
                                        class="p-1.5 rounded-xl bg-slate-800 hover:bg-blue-500/20 text-slate-300 hover:text-blue-400 border border-slate-700 transition-all cursor-pointer">
                                        <span>📋</span>
                                    </button>

                                    <button type="button" wire:click="editCustomer({{ $c->id }})"
                                        title="Editar datos del cliente"
                                        class="p-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition-all cursor-pointer">
                                        <span>✏️</span>
                                    </button>

                                    @if(!$hasDebt)
                                        <button type="button" wire:click="deleteCustomer({{ $c->id }})"
                                            wire:confirm="¿Seguro que deseas eliminar a este cliente?"
                                            title="Eliminar cliente"
                                            class="p-1.5 rounded-xl bg-slate-800 hover:bg-red-500/20 text-slate-400 hover:text-red-400 border border-slate-700 transition-all cursor-pointer">
                                            <span>🗑️</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500 text-sm">
                                <span class="text-3xl block mb-2">👥</span>
                                No se encontraron clientes con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers instanceof \Illuminate\Pagination\LengthAwarePaginator && $customers->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: Crear / Editar Cliente -->
    @if($showCustomerModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-5 sm:p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-lg font-black text-white flex items-center space-x-2">
                        <span>{{ $customerId ? '✏️ Editar Cliente' : '➕ Nuevo Cliente' }}</span>
                    </h3>
                    <button type="button" wire:click="$set('showCustomerModal', false)"
                        class="text-slate-400 hover:text-white text-xl font-bold p-1">
                        ✕
                    </button>
                </div>

                <form wire:submit.prevent="saveCustomer" class="space-y-4">
                    <!-- Nombre Completo -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre o Razón Social *</label>
                        <input type="text" wire:model="name" placeholder="Ej: Juan Pérez / Inversiones ABC"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @error('name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Identificación y Teléfono en dos columnas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Cédula / RIF</label>
                            <input type="text" wire:model="identification" placeholder="Ej: V-12345678"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @error('identification') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Teléfono (WhatsApp)</label>
                            <input type="text" wire:model="phone" placeholder="Ej: 0414-1234567"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @error('phone') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Correo Electrónico -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Correo Electrónico (Para Alertas y Recibos)</label>
                        <input type="email" wire:model="email" placeholder="cliente@correo.com"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @error('email') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Dirección -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Dirección / Ubicación</label>
                        <input type="text" wire:model="address" placeholder="Sector, calle, casa..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @error('address') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Configuración de Crédito -->
                    <div class="bg-slate-950/70 p-4 rounded-xl border border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-white">Autorizar Crédito / Cuenta Abierta</h4>
                                <span class="text-xs text-slate-400">Permite consumir y cargar la comanda a cuenta</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="allow_credit" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                            </label>
                        </div>

                        @if($allow_credit)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-800">
                                <div>
                                    <label class="block text-xs font-bold text-amber-400 uppercase mb-1">Límite de Crédito ($ USD) *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold">$</span>
                                        <input type="number" step="0.01" min="0" wire:model="credit_limit" placeholder="100.00"
                                            class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-8 pr-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    </div>
                                    @error('credit_limit') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-amber-400 uppercase mb-1">Plazo de Pago (Días) *</label>
                                    <select wire:model="credit_days"
                                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                        <option value="7">7 Días (Semanal)</option>
                                        <option value="15">15 Días (Quincenal)</option>
                                        <option value="30">30 Días (Mensual)</option>
                                        <option value="45">45 Días</option>
                                        <option value="60">60 Días</option>
                                    </select>
                                    @error('credit_days') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-800">
                        <button type="button" wire:click="$set('showCustomerModal', false)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition-all shadow-md">
                            Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 2: Registrar Abono / Pago de Deuda -->
    @if($showAbonoModal && $abonoCustomer)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-5 sm:p-6 shadow-2xl space-y-4 max-h-[95vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-lg font-black text-white flex items-center space-x-2">
                            <span>💵 Registrar Abono de Deuda</span>
                        </h3>
                        <span class="text-xs text-slate-400 font-medium">
                            Cliente: <strong class="text-amber-400">{{ $abonoCustomer->name }}</strong>
                        </span>
                    </div>
                    <button type="button" wire:click="$set('showAbonoModal', false)"
                        class="text-slate-400 hover:text-white text-xl font-bold p-1 cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Saldo Total Adeudado con Conversión Dinámica según el Método Elegido -->
                @php
                    $bcvR = (float)($config->bcv_rate ?? 60.00);
                    $copR = (float)($config->cop_rate ?? 4100.00);
                @endphp
                <div class="bg-slate-950/90 p-4 rounded-xl border border-slate-800 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase block">Saldo Total Adeudado:</span>
                            <span class="text-2xl font-black text-red-400 font-mono">${{ number_format($abonoCustomer->current_balance, 2) }} USD</span>
                        </div>

                        <!-- Lado Derecho: Equivalente Dinámico en la Moneda del Método Seleccionado -->
                        <div class="text-right">
                            <span class="text-xs text-slate-400 font-semibold block">
                                @if($abonoCurrency === 'COP')
                                    🇨🇴 Total en Pesos (Tasa COP {{ number_format($abonoExchangeRate, 0) }}):
                                @elseif($abonoCurrency === 'VED')
                                    🇻🇪 Total en Bolívares (BCV {{ number_format($abonoExchangeRate, 2) }}):
                                @elseif($abonoCurrency === 'USDT')
                                    🟡 Total en Cripto USDT (1:1 USD):
                                @else
                                    🇺🇸 Total en Dólares ($ USD):
                                @endif
                            </span>
                            <span class="text-lg font-black font-mono {{ $abonoCurrency === 'COP' ? 'text-emerald-400' : ($abonoCurrency === 'VED' ? 'text-amber-400' : ($abonoCurrency === 'USDT' ? 'text-yellow-400' : 'text-blue-400')) }}">
                                @if($abonoCurrency === 'COP')
                                    COP$ {{ number_format(round($abonoCustomer->current_balance * $abonoExchangeRate, 0)) }}
                                @elseif($abonoCurrency === 'VED')
                                    Bs {{ number_format($abonoCustomer->current_balance * $abonoExchangeRate, 2) }}
                                @elseif($abonoCurrency === 'USDT')
                                    ₮ {{ number_format($abonoCustomer->current_balance, 2) }}
                                @else
                                    ${{ number_format($abonoCustomer->current_balance, 2) }}
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Píldoras de Referencia en Todas las Monedas -->
                    <div class="flex flex-wrap items-center justify-between gap-1.5 pt-2 border-t border-slate-900 text-[11px] font-mono font-bold">
                        <span class="text-slate-500 font-sans text-[10px] uppercase">Equivalencias Rápidas:</span>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded transition-all {{ $abonoCurrency === 'VED' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 ring-1 ring-amber-400/30' : 'bg-slate-900 text-slate-400' }}">
                                🇻🇪 Bs {{ number_format($abonoCustomer->current_balance * $bcvR, 2) }}
                            </span>
                            <span class="px-2 py-0.5 rounded transition-all {{ $abonoCurrency === 'COP' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 ring-1 ring-emerald-400/30' : 'bg-slate-900 text-slate-400' }}">
                                🇨🇴 COP$ {{ number_format(round($abonoCustomer->current_balance * $copR, 0)) }}
                            </span>
                            <span class="px-2 py-0.5 rounded transition-all {{ in_array($abonoCurrency, ['USD', 'USDT']) ? 'bg-blue-500/20 text-blue-300 border border-blue-500/40 ring-1 ring-blue-400/30' : 'bg-slate-900 text-slate-400' }}">
                                🇺🇸 ${{ number_format($abonoCustomer->current_balance, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="processAbono" class="space-y-3.5">
                    <!-- Métodos de Pago Rápido -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">Método de Pago:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs font-bold">
                            @foreach([
                                'cash_usd' => '💵 Efectivo USD',
                                'cash_bs' => '🇻🇪 Efectivo Bs',
                                'pago_movil' => '📲 Pago Móvil',
                                'pos_card' => '💳 Tarjeta / Punto',
                                'cash_cop' => '🇨🇴 Pesos (COP)',
                                'nequi' => '🟣 Nequi',
                                'binance_usdt' => '🟡 Binance USDT',
                                'zelle' => '🇺🇸 Zelle',
                                'transfer' => '🏦 Transferencia'
                            ] as $mKey => $mLabel)
                                <button type="button" wire:click="setAbonoMethod('{{ $mKey }}')"
                                    class="p-2 rounded-xl border text-center transition-all cursor-pointer {{ $abonoMethod === $mKey ? 'bg-amber-500 text-slate-950 border-amber-500 font-black shadow-md ring-2 ring-amber-400/30' : 'bg-slate-950 border-slate-800 text-slate-300 hover:bg-slate-800' }}">
                                    {{ $mLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Banco Receptor (si aplica) -->
                    @if(in_array($abonoMethod, ['pago_movil', 'transfer', 'pos_card', 'zelle', 'nequi', 'binance_usdt']))
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Cuenta / Banco Receptor:</label>
                            <select wire:model.live="abonoBankId"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="">-- Seleccionar Banco / Cuenta --</option>
                                @foreach($banks as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->currency_code }}) - {{ $b->account_holder }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Monto a Abonar con Conversión Dinámica -->
                    @php
                        $currLabel = match($abonoCurrency) {
                            'VED' => 'Bolívares (Bs)',
                            'COP' => 'Pesos Colombianos (COP$)',
                            'USDT' => 'Binance USDT (₮)',
                            default => 'Dólares ($ USD)',
                        };
                        $currSym = match($abonoCurrency) {
                            'VED' => 'Bs',
                            'COP' => 'COP$',
                            'USDT' => '₮',
                            default => '$',
                        };
                    @endphp
                    <div class="space-y-1.5 bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-300 uppercase">
                                Monto a Abonar en {{ $currLabel }} *
                            </label>
                            @if($abonoCurrency !== 'USD')
                                <span class="text-[11px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                                    Tasa: 1 USD = {{ $abonoCurrency === 'COP' ? number_format($abonoExchangeRate, 0) : number_format($abonoExchangeRate, 2) }} {{ $abonoCurrency }}
                                </span>
                            @endif
                        </div>

                        <div class="flex rounded-xl overflow-hidden border border-slate-700 bg-slate-900 focus-within:ring-2 focus-within:ring-amber-500 focus-within:border-amber-500 transition-all shadow-inner">
                            <span class="inline-flex items-center justify-center px-3.5 bg-slate-800 text-amber-400 font-black text-sm border-r border-slate-700 shrink-0 select-none">
                                {{ $currSym }}
                            </span>
                            <input type="number" step="0.01" min="0.01" wire:model.live="abonoAmountCurrency"
                                style="background-color: #0f172a !important; color: #fbbf24 !important;"
                                class="w-full bg-slate-900 border-0 px-3.5 py-2.5 text-lg text-amber-400 font-black focus:ring-0 focus:outline-none font-mono">
                        </div>

                        <!-- Equivalencia en USD -->
                        <div class="flex items-center justify-between text-xs pt-1">
                            @if($abonoCurrency !== 'USD')
                                <span class="text-slate-400">Descontará de la deuda del cliente:</span>
                                <span class="font-black text-emerald-400 font-mono text-sm">
                                    ${{ number_format($abonoAmountUsd, 2) }} USD
                                </span>
                            @else
                                @if($config && ($config->bcv_rate ?? 0) > 0)
                                    <span class="text-slate-400">Equivalente en Bolívares (BCV):</span>
                                    <span class="font-black text-amber-400 font-mono text-sm">
                                        Bs {{ number_format($abonoAmountUsd * $config->bcv_rate, 2) }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Subir Comprobante / Capture de Pago (Vaucher) -->
                    @php
                        $isCash = in_array($abonoMethod, ['cash_usd', 'cash_bs', 'cash_cop']);
                    @endphp
                    <div class="p-3.5 rounded-xl border {{ !$isCash ? 'bg-amber-950/20 border-amber-500/40' : 'bg-slate-950/60 border-slate-800' }} space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold {{ !$isCash ? 'text-amber-400' : 'text-slate-300' }} uppercase flex items-center space-x-1">
                                <span>📎</span>
                                <span>Capture del Bauche / Comprobante {{ !$isCash ? '(OBLIGATORIO) *' : '(Opcional en efectivo)' }}</span>
                            </label>
                            @if(!$isCash)
                                <span class="text-[10px] font-black text-amber-400 bg-amber-500/20 px-2 py-0.5 rounded border border-amber-500/30">
                                    Requerido
                                </span>
                            @endif
                        </div>

                        <input type="file" wire:model="abonoProof" accept="image/*"
                            class="w-full text-xs text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-amber-400 hover:file:bg-slate-700 cursor-pointer">

                        <div wire:loading wire:target="abonoProof" class="text-xs text-amber-400 flex items-center space-x-1.5">
                            <span class="animate-spin">⏳</span>
                            <span>Cargando imagen del bauche...</span>
                        </div>

                        @if ($abonoProof)
                            <div class="mt-2 flex items-center space-x-3 p-2 bg-slate-900 rounded-lg border border-slate-800">
                                <img src="{{ $abonoProof->temporaryUrl() }}" class="w-14 h-14 object-cover rounded-lg border border-slate-700 shadow">
                                <div class="text-xs">
                                    <span class="text-emerald-400 font-bold block">✓ Comprobante listo para registrar</span>
                                    <span class="text-[11px] text-slate-400">Se guardará junto con la transacción</span>
                                </div>
                            </div>
                        @endif

                        @error('abonoProof')
                            <span class="text-red-400 text-xs font-bold block mt-1">⚠️ {{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Referencia y Notas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nº Referencia:</label>
                            <input type="text" wire:model="abonoReference" placeholder="Últimos 4 o 6 dígitos..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nota / Observación:</label>
                            <input type="text" wire:model="abonoNotes" placeholder="Opcional..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-800">
                        <button type="button" wire:click="$set('showAbonoModal', false)"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-colors cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs transition-all shadow-md cursor-pointer flex items-center space-x-2">
                            <span wire:loading.remove wire:target="processAbono">Confirmar Abono</span>
                            <span wire:loading wire:target="processAbono" class="flex items-center space-x-1">
                                <span class="animate-spin">⏳</span>
                                <span>Guardando...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 3: Estado de Cuenta & Historial de Transacciones -->
    @if($showHistoryModal && $historyCustomer)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl p-5 sm:p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-lg font-black text-white flex items-center space-x-2">
                            <span>📋 Estado de Cuenta & Movimientos</span>
                        </h3>
                        <span class="text-xs text-slate-400 font-medium">
                            Cliente: <strong class="text-white">{{ $historyCustomer->name }}</strong>
                            ({{ $historyCustomer->identification ?? 'S/R' }})
                        </span>
                    </div>
                    <button type="button" wire:click="$set('showHistoryModal', false)"
                        class="text-slate-400 hover:text-white text-xl font-bold p-1">
                        ✕
                    </button>
                </div>

                <!-- Resumen de Saldos -->
                <div class="grid grid-cols-3 gap-3 bg-slate-950/70 p-3.5 rounded-xl border border-slate-800 text-center">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Límite Aprobado:</span>
                        <span class="text-sm font-black text-purple-400">${{ number_format($historyCustomer->credit_limit, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Saldo Deudor Actual:</span>
                        <span class="text-base font-black text-amber-400 font-mono">${{ number_format($historyCustomer->current_balance, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Margen Disponible:</span>
                        <span class="text-sm font-black text-emerald-400">${{ number_format($historyCustomer->available_credit, 2) }}</span>
                    </div>
                </div>

                <!-- Botón de Recordatorio por Correo -->
                @if($historyCustomer->current_balance > 0 && $historyCustomer->email)
                    <div class="flex items-center justify-between bg-amber-950/40 border border-amber-500/30 p-3 rounded-xl">
                        <div class="text-xs text-amber-300">
                            <span>📧</span>
                            <span>Enviar recordatorio formal de cobro al correo: <strong>{{ $historyCustomer->email }}</strong></span>
                        </div>
                        <button type="button" wire:click="sendReminderEmail({{ $historyCustomer->id }})"
                            class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-sm transition-all cursor-pointer">
                            Enviar Correo
                        </button>
                    </div>
                @endif

                <!-- Historial de Transacciones -->
                <div class="space-y-2">
                    <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Historial de Cargos y Abonos</h4>
                    <div class="divide-y divide-slate-800/80 border border-slate-800 rounded-xl max-h-64 overflow-y-auto bg-slate-950/50">
                        @forelse($historyCustomer->transactions as $tx)
                            <div class="p-3 flex items-center justify-between text-xs hover:bg-slate-900/60 transition-colors">
                                <div class="flex items-start space-x-2.5">
                                    <span class="text-lg">
                                        {{ $tx->type === 'credit_charge' ? '🛒' : '💵' }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-white">
                                            {{ $tx->type === 'credit_charge' ? 'Consumo a Crédito' : 'Abono Recibido' }}
                                            @if($tx->order)
                                                <span class="text-amber-400 font-mono ml-1">#{{ $tx->order->order_number }}</span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-slate-400 block mt-0.5">
                                            {{ $tx->created_at->format('d/m/Y h:i A') }}
                                            @if($tx->reference)
                                                • Ref: {{ $tx->reference }}
                                            @endif
                                        </span>
                                        @if($tx->notes)
                                            <span class="text-[10px] text-slate-500 italic block mt-0.5">{{ $tx->notes }}</span>
                                        @endif
                                        @if($tx->proof_image)
                                            <a href="{{ asset('storage/' . $tx->proof_image) }}" target="_blank"
                                                class="text-[11px] text-blue-400 hover:text-blue-300 font-bold flex items-center space-x-1 mt-1">
                                                <span>📎</span>
                                                <span>Ver Capture / Vaucher</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="font-black text-sm {{ $tx->type === 'credit_charge' ? 'text-red-400' : 'text-emerald-400' }} font-mono">
                                        {{ $tx->type === 'credit_charge' ? '+' : '-' }}${{ number_format($tx->amount_usd, 2) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-xs text-slate-500">
                                No hay transacciones registradas para este cliente.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-800">
                    <button type="button" wire:click="$set('showHistoryModal', false)"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
