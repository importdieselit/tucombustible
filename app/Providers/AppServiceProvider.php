<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\AlertsComposer;
use App\Models\BitacoraSistema;
use App\Observers\VehiculoObserver;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;


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
        // Verifica usando el nombre de la clase como string para evitar errores
        if (class_exists('App\\Observers\\VehiculoObserver')) {
            Vehiculo::observe('App\\Observers\\VehiculoObserver');
        }

        View::composer('layouts.header', AlertsComposer::class); 
        if (app()->environment('local')) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
        }

        // Escuchar CUALQUIER actualización en CUALQUIER modelo
        Event::listen('eloquent.updated: *', function ($eventName, array $data) {
            $model = $data[0];

            if ($this->shouldSkip($model)) return;

            BitacoraSistema::create([
                'id_usuario'    => Auth::id(),
                'tipo'          => 'DATABASE',
                'actividad'     => class_basename($model),
                'metodo_accion' => 'UPDATED',
                'data_antes'    => json_encode($model->getOriginal()),
                'data_despues'  => json_encode($model->getAttributes()),
                'ip'            => request()->ip(),
            ]);
        });

        // Escuchar CUALQUIER creación
        Event::listen('eloquent.created: *', function ($eventName, array $data) {
            $model = $data[0];            
            if ($this->shouldSkip($model)) return;

            BitacoraSistema::create([
                'id_usuario'    => Auth::id(),
                'tipo'          => 'DATABASE',
                'actividad'     => class_basename($model),
                'metodo_accion' => 'CREATED',
                'data_despues'  => json_encode($model->getAttributes()),
                'ip'            => request()->ip(),
            ]);
        });
    }

    private function shouldSkip($model)
    {
        $modelName = class_basename($model);

        $exclude = [
            'BitacoraSistema', // Evita bucle infinito
            'Session',         // Tablas de sesión de Laravel
            'Notification',    // Notificaciones masivas
            'PersonalAccessToken',             // Tokens de Sanctum/API
            'HistorialGpsVehiculo',
            'Migration', 
            'Alerta'      // Registros de migraciones
        ];

        if (in_array($modelName, $exclude)) {
            return true;
        }

        // 2. Omisión dinámica: Si el modelo tiene una propiedad pública $audit = false
        if (isset($model->audit) && $model->audit === false) {
            return true;
        }

        if (isset($model->ignorarEnBitacora)) {
            $cambios = array_keys($model->getChanges()); 
            $cambiosRelevantes = array_diff($cambios, $model->ignorarEnBitacora);
            if (empty($cambiosRelevantes)) {
                return true;
            }
        }

        return false;
    }
}
