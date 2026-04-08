<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\AlertsComposer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Carga manual del helper
        $file = app_path('Helpers/helpers.php');
        if (file_exists($file)) {
            require_once($file);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() : void
    {
        View::composer('layouts.header', AlertsComposer::class); 
        if (app()->environment('local')) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
        }
    }
}
