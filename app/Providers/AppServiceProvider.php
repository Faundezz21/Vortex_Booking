<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        Carbon::setLocale(config('app.locale'));

        Route::resourceVerbs(['create' => 'crear', 'edit' => 'editar']);

        // fuerza esquema https en entorno de produccion detras de proxy
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
