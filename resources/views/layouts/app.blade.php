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
                    <div x-data="{ 
                            enabled: true,
                            toggle() {
                                this.enabled = !this.enabled;
                                if (window.JSFoodAudio) {
                                    window.JSFoodAudio.enabled = this.enabled;
                                    if (this.enabled) {
                                        window.JSFoodAudio.unlock();
                                        window.JSFoodAudio.playKitchenChime();
                                        window.dispatchEvent(new CustomEvent('notify', {
                                            detail: { message: '🔔 Alertas de audio activadas', type: 'info' }
                                        }));
                                    } else {
                                        window.dispatchEvent(new CustomEvent('notify', {
                                            detail: { message: '🔕 Alertas silenciadas', type: 'warning' }
                                        }));
                                    }
                                }
                            },
                            testSound() {
                                if (window.JSFoodAudio) {
                                    window.JSFoodAudio.enabled = true;
                                    this.enabled = true;
                                    window.JSFoodAudio.unlock();
                                    window.JSFoodAudio.playKitchenChime();
                                    window.dispatchEvent(new CustomEvent('notify', {
                                        detail: { message: '🔊 Timbre de cocina probado con éxito', type: 'info' }
                                    }));
                                }
                            }
                        }" class="flex items-center space-x-1.5">
                        <button type="button" 
                            @click="testSound()"
                            title="Haz clic para probar que la alarma suena en tus cornetas"
                            class="px-2.5 py-1.5 rounded-xl border border-amber-500/40 bg-amber-500/15 hover:bg-amber-500/25 text-amber-400 transition-all text-xs flex items-center space-x-1 cursor-pointer shadow-sm">
                            <span>🔊</span>
                            <span class="hidden sm:inline text-[11px] font-bold">Probar Alarma</span>
                        </button>
                        <button type="button" 
                            @click="toggle()"
                            :title="enabled ? 'Sonido activado (clic para silenciar)' : 'Sonido silenciado (clic para activar)'"
                            class="p-1.5 rounded-xl border transition-all text-xs flex items-center justify-center cursor-pointer"
                            :class="enabled ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' : 'bg-slate-800 border-slate-700 text-slate-500'">
                            <span x-text="enabled ? '🔔' : '🔕'"></span>
                        </button>
                    </div>

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

    <!-- Motor de Audio Dual Ultra-Resiliente: Web Audio API Sintetizado + HTML5 Audio (100% Funcional en Móviles y PC) -->
    <script>
        // Generador en memoria de sonidos WAV (PCM 16-bit) Data URI para máxima compatibilidad
        function generateWavDataUri(tones) {
            try {
                const sampleRate = 22050;
                let totalDuration = 0;
                tones.forEach(t => { totalDuration = Math.max(totalDuration, (t.start || 0) + (t.dur || 0.3)); });
                const numSamples = Math.ceil(totalDuration * sampleRate);
                const buffer = new Int16Array(numSamples);

                tones.forEach(t => {
                    const startSample = Math.floor((t.start || 0) * sampleRate);
                    const endSample = Math.min(numSamples, Math.floor(((t.start || 0) + (t.dur || 0.3)) * sampleRate));
                    const decay = t.decay || 4.5;
                    const vol = t.vol || 0.85;
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

                // Convertir a Base64 Data URI seguro
                const bytes = new Uint8Array(44 + dataSize);
                bytes.set(new Uint8Array(header), 0);
                bytes.set(new Uint8Array(buffer.buffer), 44);

                let binary = '';
                const len = bytes.byteLength;
                for (let i = 0; i < len; i++) {
                    binary += String.fromCharCode(bytes[i]);
                }
                return 'data:audio/wav;base64,' + btoa(binary);
            } catch (e) {
                return null;
            }
        }

        window.JSFoodAudio = {
            ctx: null,
            enabled: true,
            kitchenDataUri: null,
            waiterDataUri: null,
            unlocked: false,

            init() {
                // 1. Inicializar Web Audio API Context
                if (!this.ctx) {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (AudioContextClass) {
                        this.ctx = new AudioContextClass();
                    }
                }
                if (this.ctx && this.ctx.state === 'suspended') {
                    this.ctx.resume().catch(() => {});
                }

                // 2. Pre-generar Data URIs
                if (!this.kitchenDataUri) {
                    this.kitchenDataUri = generateWavDataUri([
                        { freq: 784, start: 0, dur: 0.25, decay: 4.0, vol: 0.9 },
                        { freq: 1046.5, start: 0.12, dur: 0.45, decay: 3.5, vol: 0.95 },
                        { freq: 880, start: 0.40, dur: 0.25, decay: 4.0, vol: 0.9 },
                        { freq: 1320, start: 0.52, dur: 0.60, decay: 3.0, vol: 1.0 }
                    ]);
                }

                if (!this.waiterDataUri) {
                    this.waiterDataUri = generateWavDataUri([
                        { freq: 523.25, start: 0, dur: 0.18, decay: 4.5, vol: 0.85 },
                        { freq: 659.25, start: 0.15, dur: 0.18, decay: 4.5, vol: 0.9 },
                        { freq: 783.99, start: 0.30, dur: 0.20, decay: 4.0, vol: 0.95 },
                        { freq: 1046.50, start: 0.45, dur: 0.65, decay: 3.2, vol: 1.0 }
                    ]);
                }
            },

            // Desbloquear audio formalmente en el navegador ante toque/clic
            unlock() {
                this.init();
                if (this.ctx) {
                    if (this.ctx.state === 'suspended') {
                        this.ctx.resume().catch(() => {});
                    }
                    try {
                        const buf = this.ctx.createBuffer(1, 1, 22050);
                        const src = this.ctx.createBufferSource();
                        src.buffer = buf;
                        src.connect(this.ctx.destination);
                        src.start(0);
                        this.unlocked = true;
                    } catch(e) {}
                }
            },

            // Timbre de Cocina: Campana doble potente ("Ding-Dong ... Ding-Dong!")
            playKitchenChime() {
                if (!this.enabled) return;
                this.init();

                // 1. Motor Web Audio API (Osciladores en paralelo, volumen alto y resonante)
                let playedWebAudio = false;
                if (this.ctx) {
                    const playOscillators = () => {
                        const now = this.ctx.currentTime;
                        const tones = [
                            { freq: 784, start: 0, dur: 0.30, gain: 0.8 },
                            { freq: 1046.5, start: 0.10, dur: 0.45, gain: 0.85 },
                            { freq: 880, start: 0.40, dur: 0.30, gain: 0.8 },
                            { freq: 1320, start: 0.50, dur: 0.65, gain: 0.9 }
                        ];

                        tones.forEach(t => {
                            const osc = this.ctx.createOscillator();
                            const gain = this.ctx.createGain();
                            osc.type = 'triangle';
                            osc.frequency.setValueAtTime(t.freq, now + t.start);
                            gain.gain.setValueAtTime(0.001, now + t.start);
                            gain.gain.exponentialRampToValueAtTime(t.gain, now + t.start + 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + t.start + t.dur);
                            osc.connect(gain);
                            gain.connect(this.ctx.destination);
                            osc.start(now + t.start);
                            osc.stop(now + t.start + t.dur);
                        });
                        playedWebAudio = true;
                    };

                    if (this.ctx.state === 'suspended') {
                        this.ctx.resume().then(() => playOscillators()).catch(() => {});
                    } else {
                        playOscillators();
                    }
                }

                // 2. Motor de respaldo HTML5 Audio nativo (por si Web Audio API no arrancó)
                if (this.kitchenDataUri) {
                    try {
                        const audio = new Audio(this.kitchenDataUri);
                        audio.volume = 1.0;
                        audio.play().catch(() => {});
                    } catch(e) {}
                }
            },

            // Timbre de Mesonero: Arpegio ascendente alegre ("¡Comanda lista!")
            playWaiterChime() {
                if (!this.enabled) return;
                this.init();

                // 1. Motor Web Audio API
                if (this.ctx) {
                    const playOscillators = () => {
                        const now = this.ctx.currentTime;
                        const tones = [
                            { freq: 523.25, start: 0, dur: 0.20, gain: 0.75 },
                            { freq: 659.25, start: 0.15, dur: 0.20, gain: 0.8 },
                            { freq: 783.99, start: 0.30, dur: 0.25, gain: 0.85 },
                            { freq: 1046.50, start: 0.45, dur: 0.65, gain: 0.95 }
                        ];

                        tones.forEach(t => {
                            const osc = this.ctx.createOscillator();
                            const gain = this.ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(t.freq, now + t.start);
                            gain.gain.setValueAtTime(0.001, now + t.start);
                            gain.gain.exponentialRampToValueAtTime(t.gain, now + t.start + 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + t.start + t.dur);
                            osc.connect(gain);
                            gain.connect(this.ctx.destination);
                            osc.start(now + t.start);
                            osc.stop(now + t.start + t.dur);
                        });
                    };

                    if (this.ctx.state === 'suspended') {
                        this.ctx.resume().then(() => playOscillators()).catch(() => {});
                    } else {
                        playOscillators();
                    }
                }

                // 2. Motor de respaldo HTML5 Audio
                if (this.waiterDataUri) {
                    try {
                        const audio = new Audio(this.waiterDataUri);
                        audio.volume = 1.0;
                        audio.play().catch(() => {});
                    } catch(e) {}
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
            if (window.JSFoodAudio) window.JSFoodAudio.unlock();
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
                    if (window.JSFoodAudio) {
                        window.JSFoodAudio.init();
                    }

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
                        this.kitchenCount = data.count || 0;

                        // 1. Alerta: Nueva comanda recibida para preparar en cocina
                        if (data.latest_id && data.latest_id > this.lastSeenKitchenOrderId) {
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
                            this.lastSeenKitchenOrderId = data.latest_id;
                        }

                        // 2. Alerta: Comanda lista para servir (para el mesonero en su teléfono / tablet)
                        if (data.latest_ready_id && data.latest_ready_id > this.lastSeenReadyOrderId) {
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
