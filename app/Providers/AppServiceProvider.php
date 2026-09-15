<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\AlertsComposer;
use App\Models\BitacoraSistema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use App\Channels\WhatsAppChannel;
use App\Services\GoogleSheetsService;
use Illuminate\Support\Facades\Blade;
// Contratos
use App\Repositories\Contracts\PersonalRepositoryInterface;
use App\Repositories\Contracts\PermisoRepositoryInterface;
use App\Repositories\Contracts\CargoRepositoryInterface;
use App\Repositories\Contracts\DepartamentoRepositoryInterface;

// Implementaciones
use App\Repositories\Eloquent\PersonalRepository;
use App\Repositories\Eloquent\PermisoRepository;
use App\Repositories\Eloquent\CargoRepository;
use App\Repositories\Eloquent\DepartamentoRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * @return void
     */
    public function register()
    {
        // Carga manual del helper
        $file = app_path('Helpers/helpers.php');
        if (file_exists($file)) {
            require_once($file);
        }

        $this->app->singleton(GoogleSheetsService::class, function ($app) {
            return new GoogleSheetsService();
        });
        $this->app->bind(PersonalRepositoryInterface::class, PersonalRepository::class);
        $this->app->bind(PermisoRepositoryInterface::class, PermisoRepository::class);
        $this->app->bind(CargoRepositoryInterface::class, CargoRepository::class);
        $this->app->bind(DepartamentoRepositoryInterface::class, DepartamentoRepository::class);
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

        Notification::extend('whatsapp', function ($app) {
            return new WhatsAppChannel();
        });

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

        Blade::if('canAccess', function (string $action, int $moduleId) {
            $user = Auth::user();
            if (!$user) return false;
            
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            return method_exists($user, 'canAccess') ? $user->canAccess($action, $moduleId) : true;
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
