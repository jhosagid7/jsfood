<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dispositivo no autorizado - JSFood POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-950 text-slate-100">
    @php
        $uuid = request()->query('device_uuid');
        if (!$uuid || $uuid === 'Desconocido') {
            $uuid = request()->cookie('device_token') ?? session('device_token');
        }
        $device = null;
        if ($uuid) {
            $device = \App\Models\DeviceAuthorization::where('uuid', $uuid)->first();
        }
        if (!$device) {
            $device = \App\Models\DeviceAuthorization::where('ip_address', request()->ip())
                ->orderBy('last_accessed_at', 'desc')
                ->first();
        }
        if ($device && (!$uuid || $uuid === 'Desconocido')) {
            $uuid = $device->uuid;
        }
        $uuid = $uuid ?: 'Desconocido';
        $isBlocked = ($device && $device->status === 'rejected');
    @endphp

    <div class="max-w-md w-full text-center bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl">
        @if($isBlocked)
            <!-- Banner Icono Bloqueado (Diseño idéntico a la captura) -->
            <div class="w-full py-2.5 px-4 rounded-lg bg-red-950/40 border border-red-500/30 flex items-center justify-center text-xl mb-6 shadow-inner">
                <span class="text-red-400">🚫</span>
            </div>

            <h1 class="text-2xl font-black text-white mb-2">Dispositivo Bloqueado</h1>
            <p class="text-sm text-slate-400 mb-6 leading-relaxed">
                Este teléfono o terminal ha sido bloqueado por el administrador del sistema.
            </p>
        @else
            <!-- Icono Dispositivo Pendiente (Ámbar) -->
            <div class="w-20 h-20 mx-auto rounded-3xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-4xl mb-6 border border-amber-500/30">
                📱
            </div>

            <h1 class="text-2xl font-black text-white mb-2">Dispositivo Pendiente</h1>
            <p class="text-sm text-slate-400 mb-6 leading-relaxed">
                Este teléfono o terminal aún no ha sido autorizado para conectarse al restaurante.
            </p>
        @endif

        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800/80 text-left text-xs space-y-2 mb-6">
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Identificador:</span>
                <span class="font-mono text-amber-400 font-bold truncate max-w-[200px]">{{ $uuid }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Dirección IP:</span>
                <span class="font-mono text-slate-100 font-bold">{{ request()->ip() }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Estado:</span>
                @if($isBlocked)
                    <span class="px-3 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-950/40 text-red-400 border border-red-500/40">
                        BLOQUEADO
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-400 border border-amber-500/30">
                        {{ $device ? strtoupper($device->status) : 'PENDIENTE' }}
                    </span>
                @endif
            </div>
        </div>

        @if($isBlocked)
            <div class="text-xs text-slate-400 mb-6 leading-relaxed text-left flex items-start gap-2">
                <span class="text-amber-400 text-sm leading-none flex-shrink-0">⚠️</span>
                <span>El acceso desde este dispositivo ha sido revocado.<br>Contacta al Administrador si crees que es un error.</span>
            </div>

            <a href="{{ url('/') }}" class="inline-flex items-center justify-center w-full py-3.5 px-4 rounded-2xl bg-red-600 hover:bg-red-500 active:bg-red-700 text-white font-bold text-sm transition-all shadow-lg shadow-red-950/50">
                🔄 Reintentar Conexión
            </a>
        @else
            <p class="text-xs text-slate-500 mb-6 leading-relaxed text-left">
                💡 Por favor, solicita al Administrador o Gerente que apruebe este dispositivo desde el menú:
                <span class="text-amber-400 font-bold">Ajustes &gt; Dispositivos Autorizados</span>.
            </p>

            <a href="{{ url('/') }}" class="inline-flex items-center justify-center w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm transition-all">
                🔄 Reintentar Conexión
            </a>
        @endif
    </div>
</body>
</html>
