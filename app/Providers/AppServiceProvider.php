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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-sincronizar APP_URL y raíz con el host del request (para tablets/teléfonos por IP en red local)
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
