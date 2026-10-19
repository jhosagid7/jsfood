<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dispositivo no autorizado - JSFood POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-950 text-slate-100">
    <div class="max-w-md w-full text-center bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl">
        @php
            $uuid = request()->query('device_uuid', 'Desconocido');
            $device = \App\Models\DeviceAuthorization::where('uuid', $uuid)->first();
            if (!$device && !empty($uuid) && $uuid !== 'Desconocido') {
                $device = \App\Models\DeviceAuthorization::where('uuid', $uuid)->first();
            }
            if (!$device) {
                $device = \App\Models\DeviceAuthorization::where('ip_address', request()->ip())->orderBy('last_accessed_at', 'desc')->first();
            }
            $isBlocked = ($device && $device->status === 'rejected');
        @endphp

        <!-- Icono Centrado Cuadrado Elegante (diseño original) -->
        <div class="mx-auto rounded-3xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-4xl mb-6 border border-amber-500/30 shadow-lg"
             style="width: 76px; height: 76px; min-width: 76px; min-height: 76px;">
            📱
        </div>

        <h1 class="text-2xl font-black text-white mb-2">
            {{ $isBlocked ? 'Dispositivo Bloqueado' : 'Dispositivo Pendiente' }}
        </h1>
        <p class="text-sm text-slate-400 mb-6">
            {{ $isBlocked 
                ? 'Este teléfono o terminal ha sido bloqueado por el administrador del sistema.' 
                : 'Este teléfono o terminal aún no ha sido autorizado para conectarse al restaurante.' }}
        </p>

        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800/80 text-left text-xs space-y-2 mb-6 shadow-inner">
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Identificador:</span>
                <span class="font-mono text-amber-400 font-bold truncate max-w-[200px]">{{ $device ? $device->uuid : $uuid }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Dirección IP:</span>
                <span class="font-mono text-slate-300 font-bold">{{ request()->ip() }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Estado:</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-400 border border-amber-500/30">
                    {{ $isBlocked ? 'BLOQUEADO' : ($device ? strtoupper($device->status) : 'PENDIENTE') }}
                </span>
            </div>
        </div>

        <p class="text-xs text-slate-400 mb-6 leading-relaxed">
            💡 Por favor, solicita al Administrador o Gerente que apruebe este dispositivo desde el menú:
            <span class="text-amber-400 font-bold block mt-0.5">Ajustes &gt; Dispositivos Autorizados</span>
        </p>

        <!-- Botón Elegante Pizarra (diseño original) -->
        <a href="{{ url('/') }}" class="inline-flex items-center justify-center w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm transition-all border border-slate-700/80 shadow-md">
            🔄 Reintentar Conexión
        </a>
    </div>
</body>
</html>
