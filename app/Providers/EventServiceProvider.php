<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Models\Vehiculo;
use App\Observers\VehiculoObserver;
use App\Models\Cliente;
use App\Observers\ClienteObserver;
use App\Models\Deposito;
use App\Observers\DepositoObserver;
use App\Models\Pedido;
use App\Observers\PedidoObserver;
use App\Models\Viaje;
use App\Observers\ViajeObserver;
use App\Models\Inspeccion;
use App\Observers\InspeccionObserver;
use App\Models\Orden;
use App\Observers\OrdenObserver;



class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
         parent::boot();

        // NUEVO: Asocia el VehiculoObserver al modelo Vehiculo
        Vehiculo::observe(VehiculoObserver::class);
        Cliente::observe(ClienteObserver::class);
        Deposito::observe(DepositoObserver::class);
        Pedido::observe(PedidoObserver::class);
        Viaje::observe(ViajeObserver::class);
        Inspeccion::observe(InspeccionObserver::class);
        Orden::observe(OrdenObserver::class);

    }
}
