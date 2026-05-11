<?php 
namespace App\Traits;

use App\Models\BitacoraSistema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

trait Auditable {
    public static function bootAuditable() {
        static::updated(function ($model) {
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

        static::created(function ($model) {
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
}