<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDeviceAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Exclude public routes, login/logout, livewire assets, and access-denied
        if ($request->is('login', 'logout', 'register', 'password/*', 'access-denied', 'up')) {
            return $next($request);
        }

        // Exclude static assets and Livewire JS/CSS scripts
        if ($request->is('livewire*/livewire*.js*', 'livewire*/css/*', 'livewire*/js*') || preg_match('/\.(js|css|map|png|jpg|jpeg|svg|ico|woff|woff2|ttf)$/i', $request->path())) {
            return $next($request);
        }

        $cookieName = 'device_token';
        $headerName = 'X-Device-Token';

        // Check for token in Cookie, Session, or Custom Header (Apps)
        $token = $request->cookie($cookieName) ?? session($cookieName) ?? $request->header($headerName);
        $device = null;

        if ($token) {
            $device = \App\Models\DeviceAuthorization::where('uuid', $token)->first();
            
            // If found but cookie is missing (and it's a web request), re-sync cookie
            if ($device && !$request->expectsJson() && !$request->cookie($cookieName)) {
                $this->queueDeviceCookie($token);
            }
        }

        // FINGERPRINT FALLBACK: If no device found by token, find existing device by IP/Subnet + UA
        // This prevents duplication from mobile apps, localhost/IPv6, or router DHCP changes.
        if (!$device) {
            $clientIp = $request->ip();
            $ua = $request->userAgent() ?? 'Unknown';

            // 1. Check exact IP + UA (both approved, rejected, or pending)
            $device = \App\Models\DeviceAuthorization::where('ip_address', $clientIp)
                ->where('user_agent', $ua)
                ->orderBy('last_accessed_at', 'desc')
                ->first();

            // 2. Localhost / Loopback deduplication (127.0.0.1, ::1)
            if (!$device && ($clientIp === '127.0.0.1' || $clientIp === '::1' || $clientIp === 'localhost')) {
                $device = \App\Models\DeviceAuthorization::whereIn('ip_address', ['127.0.0.1', '::1'])
                    ->orderBy('last_accessed_at', 'desc')
                    ->first();
            }

            // 3. Subnet fallback: If client has same UA and accessed recently on same /24 subnet (DHCP change)
            if (!$device && filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $subnetPrefix = substr($clientIp, 0, strrpos($clientIp, '.') + 1);
                $device = \App\Models\DeviceAuthorization::where('ip_address', 'like', $subnetPrefix . '%')
                    ->where('user_agent', $ua)
                    ->where('last_accessed_at', '>=', now()->subDays(7))
                    ->orderBy('last_accessed_at', 'desc')
                    ->first();
            }
            
            if ($device) {
                $token = $device->uuid;
                try {
                    $device->update([
                        'ip_address' => $clientIp,
                        'last_accessed_at' => now(),
                    ]);
                } catch (\Throwable $e) {}
                // If it's a web request, sync the cookie so we don't have to fallback again
                if (!$request->expectsJson() && !$request->cookie($cookieName)) {
                    $this->queueDeviceCookie($token);
                }
            }
        }

        if (!$device) {
            $config = \App\Models\Configuration::first();
            $isRestricted = ($config && $config->device_access_mode === 'restricted');
            $isLocalServer = in_array($request->ip(), ['127.0.0.1', '::1', 'localhost']);
            
            // OPTIMIZATION: If it's an API request, we don't have a token, and we are NOT in restricted mode,
            // do NOT create a new device record.
            if ($request->expectsJson() && !$isRestricted) {
                $device = new \App\Models\DeviceAuthorization();
                $device->status = 'approved';
                $device->uuid = $token ?: 'anonymous';
                
                return $next($request);
            }

            // New Device - Use provided token or generate UUID
            $token = $token ?: (string) \Illuminate\Support\Str::uuid();
            
            $maxDevices = $config->max_devices ?? 10;
            $approvedCount = \App\Models\DeviceAuthorization::where('status', 'approved')->count();

            if ($approvedCount >= $maxDevices) {
                $status = 'pending';
            } else {
                $status = $isRestricted ? 'pending' : 'approved';
            }
            
            // Solo auto-aprobar Super Admin si está conectándose directamente en el servidor local
            if ($isLocalServer && auth()->check() && auth()->user()->hasRole('Super Admin')) {
                $status = 'approved';
            }

            try {
                $userAgent = $request->userAgent();
                $userAgent = iconv('UTF-8', 'UTF-8//IGNORE', $userAgent);
                if (!mb_check_encoding($userAgent, 'UTF-8')) {
                    $userAgent = 'Unknown User Agent';
                }

                $device = \App\Models\DeviceAuthorization::create([
                    'uuid' => $token,
                    'name' => 'Dispositivo ' . \Illuminate\Support\Str::random(4),
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent,
                    'status' => $status,
                    'last_accessed_at' => now(),
                ]);

                // Save to session and queue cookie if it's a web request
                if (!$request->expectsJson()) {
                    session([$cookieName => $token]);
                    $this->queueDeviceCookie($token);
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Device Auth Creation Failed: ' . $e->getMessage());
                $device = new \App\Models\DeviceAuthorization();
                $device->status = 'approved';
                $device->uuid = $token;
            }
        } else {
            // Existing Device - Update info
            try {
                if (!$device->last_accessed_at || $device->last_accessed_at->diffInMinutes(now()) >= 2) {
                    $device->update([
                        'ip_address' => $request->ip(),
                        'last_accessed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Device Auth Update Failed: ' . $e->getMessage());
            }
        }

        // VALIDACIÓN DE AUTORIZACIÓN DEL DISPOSITIVO
        if ($device->status !== 'approved') {
            // 1. Si el dispositivo fue RECHAZADO / BLOQUEADO, se bloquea SIEMPRE sin importar el usuario
            if ($device->status === 'rejected') {
                if ($request->header('X-Livewire')) {
                    return response('', 403)->header('X-Livewire-Redirect', route('access.denied', ['device_uuid' => $device->uuid]));
                }

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Dispositivo bloqueado por el administrador.',
                        'device_uuid' => $device->uuid,
                        'status' => 'rejected',
                        'ip' => $request->ip()
                    ], 403);
                }

                return redirect()->route('access.denied', ['device_uuid' => $device->uuid]);
            }

            // 2. Si está pendiente, permitir acceso solo si es Super Admin trabajando en la consola local del servidor
            $isLocalServer = in_array($request->ip(), ['127.0.0.1', '::1', 'localhost']);
            if ($isLocalServer && auth()->check() && auth()->user()->hasRole('Super Admin')) {
                if ($device->status === 'pending') {
                    $device->update(['status' => 'approved']);
                }
                return $next($request);
            }

            // 3. Para cualquier dispositivo no aprobado:
            if ($request->header('X-Livewire')) {
                return response('', 403)->header('X-Livewire-Redirect', route('access.denied', ['device_uuid' => $device->uuid]));
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Dispositivo no autorizado.',
                    'device_uuid' => $device->uuid,
                    'status' => $device->status,
                    'ip' => $request->ip()
                ], 403);
            }

            return redirect()->route('access.denied', ['device_uuid' => $device->uuid]);
        }

        $response = $next($request);

        // Add the device token to the header for API responses so the app can learn/save it
        if ($device && $device->uuid && $request->expectsJson() && $response instanceof \Illuminate\Http\Response) {
            $response->header('X-Device-Token', $device->uuid);
        }

        return $response;
    }

    /**
     * Helper to queue device cookie with consistent parameters
     */
    private function queueDeviceCookie($token)
    {
        // Format: Name, Value, Minutes
        // 5256000 minutes = 10 years
        \Illuminate\Support\Facades\Cookie::queue('device_token', $token, 5256000);
    }
}
