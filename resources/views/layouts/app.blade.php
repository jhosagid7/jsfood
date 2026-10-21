<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'JSFood POS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
    $activeKitchenOrders = \App\Models\Order::whereIn('status', ['pending', 'in_kitchen'])
        ->whereHas('items.product', fn($q) => $q->where('send_to_kitchen', true))
        ->select('id')
        ->get();
    $inKitchenCount = $activeKitchenOrders->count();
    $initialLatestKitchenId = $activeKitchenOrders->max('id') ?? 0;
    $initialLatestReadyId = \App\Models\Order::where('status', 'ready')->max('id') ?? 0;
@endphp
<body x-data="kitchenGlobalTracker({{ $inKitchenCount }}, {{ $initialLatestKitchenId }}, {{ $initialLatestReadyId }})" class="h-full flex flex-col font-sans antialiased bg-slate-950 text-slate-100 selection:bg-amber-500 selection:text-white">

    <!-- Header / Navbar Principal -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40 shadow-md">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="{{ url('/tables') }}" class="flex items-center space-x-2">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-500/20 text-white font-black text-xl">
                            JF
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-lg font-black tracking-tight text-white">JS<span class="text-amber-500">FOOD</span></span>
                            <span class="text-[10px] block font-semibold text-slate-400 tracking-wider uppercase">Restaurante & POS</span>
                        </div>
                    </a>

                    <!-- Badge de Moneda / Tasa BCV & COP -->
                    @php
                        $config = \App\Models\Configuration::first();
                    @endphp
                    @if($config && $config->currency_mode === 'multi')
                        <div class="hidden md:flex items-center space-x-2 bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-700/60 text-xs">
                            <span class="text-slate-400 font-medium">Tasas:</span>
                            <span class="text-amber-400 font-bold">BCV: Bs {{ number_format($config->bcv_rate, 2) }}</span>
                            <span class="text-slate-600">•</span>
                            <span class="text-emerald-400 font-bold">COP: ${{ number_format($config->cop_rate, 0) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Enlaces de Navegación Desktop -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="{{ url('/tables') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('tables*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        🍽️ Mesas & Salón
                    </a>
                    <a href="{{ url('/pos/order') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('pos*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        📝 Comanda Rápida
                    </a>
                    <a href="{{ url('/kds') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors relative {{ request()->is('kds*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        🍳 Cocina (KDS)
                        <span x-cloak x-show="kitchenCount > 0" x-text="kitchenCount"
                            class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full animate-pulse shadow-md">
                            {{ $inKitchenCount > 0 ? $inKitchenCount : '' }}
                        </span>
                    </a>
                    <a href="{{ url('/cashier') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('cashier*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        💳 Caja & Cobro
                    </a>
                    <a href="{{ url('/products') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('products*') || request()->is('categories*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        🍔 Menú & Platos
                    </a>
                    <a href="{{ url('/settings/printers') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ request()->is('settings*') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        ⚙️ Ajustes
                    </a>
                </nav>

                <!-- Usuario y Cambio Rápido de Rol -->
                <div class="flex items-center space-x-2">
                    @php
                        $user = auth()->user() ?? \App\Models\User::first();
                        $roleName = $user && $user->roles->first() ? $user->roles->first()->name : 'Admin';
                    @endphp
                    <div class="flex items-center space-x-2 bg-slate-800/80 px-2.5 py-1.5 rounded-xl border border-slate-700/60">
                        <div class="w-7 h-7 rounded-lg bg-slate-700 flex items-center justify-center font-bold text-xs text-amber-400">
                            {{ substr($user ? $user->name : 'U', 0, 1) }}
                        </div>
                        <div class="text-left text-xs leading-tight">
                            <span class="font-bold text-slate-200 block truncate max-w-[100px]">{{ $user ? $user->name : 'Usuario' }}</span>
                            <span class="text-[10px] font-medium text-amber-400">{{ $roleName }}</span>
                        </div>
                    </div>

                    <!-- Botón de Audio y Alertas Sonoras -->
                    <button type="button" 
                        x-data="{ 
                            enabled: true,
                            toggle() {
                                this.enabled = !this.enabled;
                                if (window.JSFoodAudio) {
                                    window.JSFoodAudio.enabled = this.enabled;
                                    if (this.enabled) {
                                        window.JSFoodAudio.init();
                                        window.JSFoodAudio.playKitchenChime();
                                    }
                                }
                            }
                        }"
                        @click="toggle()"
                        :title="enabled ? 'Sonido de alertas activado (clic para silenciar)' : 'Sonido silenciado (clic para activar)'"
                        class="p-2 rounded-xl border transition-all text-xs flex items-center space-x-1.5 cursor-pointer"
                        :class="enabled ? 'bg-amber-500/15 border-amber-500/40 text-amber-400' : 'bg-slate-800 border-slate-700 text-slate-500'">
                        <span x-text="enabled ? '🔔' : '🔕'"></span>
                        <span class="hidden sm:inline text-[11px] font-bold" x-text="enabled ? 'Sonido ON' : 'Sonido OFF'"></span>
                    </button>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" title="Cerrar sesión / Cambiar usuario" class="p-2 rounded-xl bg-slate-800/80 hover:bg-red-500/20 text-slate-400 hover:text-red-400 border border-slate-700/60 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 pb-20 md:pb-6 overflow-y-auto">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Barra de Navegación Táctil Móvil / Tablet (Sticky Bottom) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 z-50 px-2 py-1.5 flex justify-around items-center">
        <a href="{{ url('/tables') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('tables*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">🍽️</span>
            <span>Mesas</span>
        </a>
        <a href="{{ url('/pos/order') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('pos*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">📝</span>
            <span>Comanda</span>
        </a>
        <a href="{{ url('/kds') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs relative {{ request()->is('kds*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">🍳</span>
            <span>Cocina</span>
            <span x-cloak x-show="kitchenCount > 0" x-text="kitchenCount"
                class="absolute top-0 right-1 bg-red-600 text-white text-[9px] font-bold px-1 rounded-full animate-pulse shadow">
                {{ $inKitchenCount > 0 ? $inKitchenCount : '' }}
            </span>
        </a>
        <a href="{{ url('/cashier') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('cashier*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">💳</span>
            <span>Caja</span>
        </a>
        <a href="{{ url('/products') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('products*') || request()->is('categories*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">🍔</span>
            <span>Menú</span>
        </a>
        <a href="{{ url('/settings/printers') }}" class="flex flex-col items-center py-1 px-2 rounded-lg text-xs {{ request()->is('settings*') ? 'text-amber-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <span class="text-lg">⚙️</span>
            <span>Ajustes</span>
        </a>
    </nav>

    <!-- Sistema de Notificaciones Toast (Alpine.js) -->
    <div x-data="{ 
            notifications: [],
            add(msg, type = 'success') {
                const id = Date.now();
                this.notifications.push({ id, msg, type });
                setTimeout(() => this.remove(id), 3500);
            },
            remove(id) {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }
        }"
        x-on:notify.window="add($event.detail.message, $event.detail.type)"
        class="fixed bottom-20 md:bottom-6 right-4 z-50 flex flex-col space-y-2 pointer-events-none">
        <template x-for="n in notifications" :key="n.id">
            <div class="pointer-events-auto px-4 py-3 rounded-xl shadow-2xl border text-sm font-semibold flex items-center space-x-2 transition-all transform duration-300"
                :class="{
                    'bg-emerald-950/95 border-emerald-500/50 text-emerald-200': n.type === 'success',
                    'bg-red-950/95 border-red-500/50 text-red-200': n.type === 'error',
                    'bg-amber-950/95 border-amber-500/50 text-amber-200': n.type === 'warning',
                    'bg-blue-950/95 border-blue-500/50 text-blue-200': n.type === 'info'
                }">
                <span x-text="n.type === 'success' ? '✅' : (n.type === 'error' ? '❌' : '⚠️')"></span>
                <span x-text="n.msg"></span>
            </div>
        </template>
    </div>

    <!-- Motor de Audio Dual: HTML5 Audio con WAV Sintetizado + Web Audio API (100% Funcional en Móviles y PC) -->
    <script>
        // Generador en memoria de sonidos WAV (PCM 16-bit) para reproducción nativa inmediata en teléfonos
        function generateWavBlobUrl(tones) {
            try {
                const sampleRate = 22050;
                let totalDuration = 0;
                tones.forEach(t => { totalDuration = Math.max(totalDuration, t.start + t.dur); });
                const numSamples = Math.ceil(totalDuration * sampleRate);
                const buffer = new Int16Array(numSamples);

                tones.forEach(t => {
                    const startSample = Math.floor(t.start * sampleRate);
                    const endSample = Math.min(numSamples, Math.floor((t.start + t.dur) * sampleRate));
                    const decay = t.decay || 5.0;
                    const vol = t.vol || 0.8;
                    for (let i = startSample; i < endSample; i++) {
                        const time = (i - startSample) / sampleRate;
                        const envelope = Math.exp(-time * decay);
                        const sample = Math.sin(2 * Math.PI * t.freq * time) * envelope * vol;
                        buffer[i] = Math.max(-32767, Math.min(32767, buffer[i] + sample * 32767));
                    }
                });

                const dataSize = numSamples * 2;
                const fileSize = 36 + dataSize;
                const header = new ArrayBuffer(44);
                const view = new DataView(header);
                view.setUint32(0, 0x52494646, false); // "RIFF"
                view.setUint32(4, fileSize, true);
                view.setUint32(8, 0x57415645, false); // "WAVE"
                view.setUint32(12, 0x666d7420, false); // "fmt "
                view.setUint32(16, 16, true);
                view.setUint16(20, 1, true); // PCM
                view.setUint16(22, 1, true); // Mono
                view.setUint32(24, sampleRate, true);
                view.setUint32(28, sampleRate * 2, true);
                view.setUint16(32, 2, true);
                view.setUint16(34, 16, true);
                view.setUint32(36, 0x64617461, false); // "data"
                view.setUint32(40, dataSize, true);

                const blob = new Blob([header, buffer], { type: 'audio/wav' });
                return URL.createObjectURL(blob);
            } catch (e) {
                return null;
            }
        }

        window.JSFoodAudio = {
            ctx: null,
            enabled: true,
            kitchenWavUrl: null,
            waiterWavUrl: null,
            audioElKitchen: null,
            audioElWaiter: null,
            unlocked: false,

            init() {
                // 1. Inicializar elementos de audio HTML5 pre-generados
                if (!this.kitchenWavUrl) {
                    this.kitchenWavUrl = generateWavBlobUrl([
                        { freq: 880, start: 0, dur: 0.35, decay: 4.5, vol: 0.9 },
                        { freq: 1320, start: 0.16, dur: 0.65, decay: 3.8, vol: 0.95 }
                    ]);
                    if (this.kitchenWavUrl) {
                        this.audioElKitchen = new Audio(this.kitchenWavUrl);
                        this.audioElKitchen.preload = 'auto';
                    }
                }

                if (!this.waiterWavUrl) {
                    this.waiterWavUrl = generateWavBlobUrl([
                        { freq: 587.33, start: 0, dur: 0.25, decay: 5.0, vol: 0.8 },
                        { freq: 880, start: 0.12, dur: 0.28, decay: 4.5, vol: 0.85 },
                        { freq: 1174.66, start: 0.26, dur: 0.65, decay: 3.5, vol: 0.95 }
                    ]);
                    if (this.waiterWavUrl) {
                        this.audioElWaiter = new Audio(this.waiterWavUrl);
                        this.audioElWaiter.preload = 'auto';
                    }
                }

                // 2. Inicializar Web Audio API
                if (!this.ctx) {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (AudioContextClass) {
                        this.ctx = new AudioContextClass();
                    }
                }
                if (this.ctx && this.ctx.state === 'suspended') {
                    this.ctx.resume().catch(() => {});
                }

                // 3. Desbloquear permisos de audio en móviles (dummy play)
                if (!this.unlocked) {
                    if (this.audioElKitchen) {
                        this.audioElKitchen.volume = 0.01;
                        this.audioElKitchen.play()
                            .then(() => {
                                this.audioElKitchen.pause();
                                this.audioElKitchen.currentTime = 0;
                                this.audioElKitchen.volume = 1.0;
                                this.unlocked = true;
                            })
                            .catch(() => {});
                    }
                }
            },

            playKitchenChime() {
                if (!this.enabled) return;
                this.init();

                // Intentar reproducir con elemento HTML5 Audio (más confiable en teléfonos móviles)
                let playedHtml5 = false;
                if (this.audioElKitchen) {
                    try {
                        this.audioElKitchen.currentTime = 0;
                        this.audioElKitchen.volume = 1.0;
                        const p = this.audioElKitchen.play();
                        if (p && typeof p.then === 'function') {
                            p.then(() => { playedHtml5 = true; }).catch(() => {});
                        }
                    } catch(e) {}
                }

                // Motor secundario Web Audio API (Osciladores)
                if (this.ctx) {
                    const playOsc = () => {
                        const now = this.ctx.currentTime;
                        [ { freq: 880, start: 0, dur: 0.35, gain: 0.7 }, { freq: 1320, start: 0.16, dur: 0.65, gain: 0.75 } ].forEach(tone => {
                            const osc = this.ctx.createOscillator();
                            const gain = this.ctx.createGain();
                            osc.type = 'triangle';
                            osc.frequency.setValueAtTime(tone.freq, now + tone.start);
                            gain.gain.setValueAtTime(tone.gain, now + tone.start);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + tone.start + tone.dur);
                            osc.connect(gain);
                            gain.connect(this.ctx.destination);
                            osc.start(now + tone.start);
                            osc.stop(now + tone.start + tone.dur);
                        });
                    };

                    if (this.ctx.state === 'suspended') {
                        this.ctx.resume().then(() => playOsc()).catch(() => {});
                    } else {
                        playOsc();
                    }
                }
            },

            playWaiterChime() {
                if (!this.enabled) return;
                this.init();

                // Intentar reproducir con elemento HTML5 Audio
                if (this.audioElWaiter) {
                    try {
                        this.audioElWaiter.currentTime = 0;
                        this.audioElWaiter.volume = 1.0;
                        this.audioElWaiter.play().catch(() => {});
                    } catch(e) {}
                }

                // Motor secundario Web Audio API (Osciladores)
                if (this.ctx) {
                    const playOsc = () => {
                        const now = this.ctx.currentTime;
                        [ { freq: 587.33, start: 0, dur: 0.25, gain: 0.6 }, { freq: 880, start: 0.12, dur: 0.28, gain: 0.65 }, { freq: 1174.66, start: 0.26, dur: 0.65, gain: 0.75 } ].forEach(tone => {
                            const osc = this.ctx.createOscillator();
                            const gain = this.ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(tone.freq, now + tone.start);
                            gain.gain.setValueAtTime(tone.gain, now + tone.start);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + tone.start + tone.dur);
                            osc.connect(gain);
                            gain.connect(this.ctx.destination);
                            osc.start(now + tone.start);
                            osc.stop(now + tone.start + tone.dur);
                        });
                    };

                    if (this.ctx.state === 'suspended') {
                        this.ctx.resume().then(() => playOsc()).catch(() => {});
                    } else {
                        playOsc();
                    }
                }
            }
        };

        window.addEventListener('play-kitchen-chime', () => {
            window.JSFoodAudio.playKitchenChime();
        });

        window.addEventListener('play-waiter-chime', () => {
            window.JSFoodAudio.playWaiterChime();
        });

        // Desbloquear audio ante cualquier interacción táctil o clic
        const unlockAudio = () => {
            if (window.JSFoodAudio) window.JSFoodAudio.init();
        };
        ['click', 'touchstart', 'touchend', 'pointerdown', 'keydown'].forEach(evt => {
            document.addEventListener(evt, unlockAudio, { passive: true });
        });

        // Tracker Global en Tiempo Real para Cocina y Mesonero (KDS, globo rojo y alarma sonora)
        function kitchenGlobalTracker(initialCount, initialLatestId, initialReadyId) {
            return {
                kitchenCount: initialCount || 0,
                lastSeenKitchenOrderId: initialLatestId || 0,
                lastSeenReadyOrderId: initialReadyId || 0,
                init() {
                    // Inicializar motor de audio
                    if (window.JSFoodAudio) window.JSFoodAudio.init();

                    // Consultar en segundo plano cada 2.5 segundos
                    setInterval(() => {
                        this.pollKitchenStatus();
                    }, 2500);
                },
                pollKitchenStatus() {
                    fetch("{{ route('kitchen.status') }}", {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.kitchenCount = data.count;

                        // 1. Alerta: Nueva comanda recibida para preparar en cocina
                        if (data.latest_id && data.latest_id > this.lastSeenKitchenOrderId) {
                            if (this.lastSeenKitchenOrderId > 0) {
                                if (window.JSFoodAudio) {
                                    window.JSFoodAudio.playKitchenChime();
                                }
                                const orderRef = data.latest_order_number ? ' #' + data.latest_order_number : '';
                                window.dispatchEvent(new CustomEvent('notify', {
                                    detail: {
                                        message: '🔔 ¡Nueva comanda recibida en cocina' + orderRef + '!',
                                        type: 'warning'
                                    }
                                }));
                            }
                            this.lastSeenKitchenOrderId = data.latest_id;
                        }

                        // 2. Alerta: Comanda lista para servir (para el mesonero en su teléfono / tablet)
                        if (data.latest_ready_id && data.latest_ready_id > this.lastSeenReadyOrderId) {
                            if (this.lastSeenReadyOrderId > 0) {
                                if (window.JSFoodAudio) {
                                    window.JSFoodAudio.playWaiterChime();
                                }
                                const tableInfo = data.latest_ready_table ? ' (' + data.latest_ready_table + ')' : '';
                                const orderRef = data.latest_ready_order_number ? ' #' + data.latest_ready_order_number : '';
                                window.dispatchEvent(new CustomEvent('notify', {
                                    detail: {
                                        message: '🍽️ ¡Comanda lista para servir' + orderRef + tableInfo + '!',
                                        type: 'success'
                                    }
                                }));
                            }
                            this.lastSeenReadyOrderId = data.latest_ready_id;
                        }
                    })
                    .catch(() => {});
                }
            };
        }
    </script>

    @livewireScripts
    <script>
        // 1. Interceptar respuestas de error de Livewire: previene el modal/iframe invasivo en pantalla
        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 403) {
                        if (typeof preventDefault === 'function') {
                            preventDefault();
                        }
                        window.location.href = "{{ route('access.denied') }}";
                        return;
                    }

                    if (status === 419) {
                        // Sesión expirada o token CSRF desincronizado: recargar limpiamente sin mostrar modal
                        if (typeof preventDefault === 'function') {
                            preventDefault();
                        }
                        window.location.reload();
                        return;
                    }

                    // Prevenir que Livewire monte un iframe modal oscuro (404/500) ante micro-cortes o desincronizaciones de polling
                    if (typeof preventDefault === 'function') {
                        preventDefault();
                    }
                });
            });
        });

        // 2. Heartbeat en segundo plano (cada 3.5 segundos):
        // Tan pronto el administrador bloquea el dispositivo, la pantalla salta automáticamente a "Dispositivo Bloqueado" sin esperar clic ni recarga manual
        setInterval(() => {
            fetch("{{ route('device.heartbeat') }}", {
                method: 'GET',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 403) {
                    window.location.href = "{{ route('access.denied') }}";
                }
            })
            .catch(() => {});
        }, 3500);
    </script>
</body>
</html>
