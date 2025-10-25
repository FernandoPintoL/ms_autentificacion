<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AuthService;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar AuthService como singleton
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Aquí podemos definir policies, gates, etc.
        // Por ejemplo:
        // Gate::define('delete-user', function (User $user, User $target) {
        //     return $user->hasRole('admin');
        // });
    }
}
