<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Respaldo de seguridad contra desincronización de variables de entorno en servidores locales (Laragon mod_php)
        if (empty(config('app.key'))) {
            config(['app.key' => 'base64:ih9ogwJWXyPZujm5eU4JrHQ67jpPvy6gYb9aq3dB7Ko=']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Fijar rutas deterministas para Livewire 3 (evita colisiones de hash entre proyectos en Laragon mod_php)
        try {
            \Livewire\Livewire::setUpdateRoute(function ($handle) {
                return \Illuminate\Support\Facades\Route::post('/livewire/update', $handle)->middleware(['web']);
            });
            \Livewire\Livewire::setScriptRoute(function ($handle) {
                return \Illuminate\Support\Facades\Route::get('/livewire/livewire.js', $handle);
            });
        } catch (\Throwable $e) {}

        // 2. Auto-sincronizar APP_URL y raíz con el host del request (para tablets/teléfonos por IP en red local)
        try {
            if (!app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $currentUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                config(['app.url' => $currentUrl]);
                \Illuminate\Support\Facades\URL::forceRootUrl($currentUrl);
            }
        } catch (\Throwable $e) {}
    }
}
