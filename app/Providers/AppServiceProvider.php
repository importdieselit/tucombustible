<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Pedido;
use App\Observers\PedidoObserver;
use App\Models\Deposito;
use App\Observers\DepositoObserver;
use App\Models\Cliente;
use App\Observers\ClienteObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Pedido::observe(PedidoObserver::class);
        Deposito::observe(DepositoObserver::class); 
        Cliente::observe(ClienteObserver::class);
    }
}
