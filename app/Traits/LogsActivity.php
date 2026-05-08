<?php
namespace App\Traits;

use App\Models\BitacoraSistema;
use Illuminate\Support\Facades\Auth;

trait LogsActivity {
    protected static function bootLogsActivity() {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                BitacoraSistema::create([
                    'id_usuario' => Auth::id(),
                    'evento'     => $event,
                    'modelo'     => get_class($model),
                    'accion'     => $event,
                    'datos'      => json_encode($model->getAttributes()),
                    'modelo_id'  => $model->id,
                    'antes'      => $event === 'updated' ? json_encode($model->getOriginal()) : null,
                    'despues'    => $event !== 'deleted' ? json_encode($model->getAttributes()) : null,
                    'ip'         => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            });
        }
    }

    // Función para logs personalizados manuales
    public static function logCustom($descripcion, $evento = 'personalizado') {
        BitacoraSistema::create([
            'id_usuario'  => Auth::id(),
            'evento'      => $evento,
            'descripcion' => $descripcion,
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}