<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Chofer extends Model
{
    use HasFactory;


    protected $table = 'choferes';

    protected $dates = [
        'licencia_vencimiento',
        'documento_vialidad_vencimiento',
        'created_at',
        'updated_at',
    ];
    /**
     * Los atributos que son rellenables de forma masiva.
     *
     * @var array
     */
    protected $fillable = [
        'licencia_numero',
        'licencia_vencimiento',
        'documento_vialidad_numero',
        'documento_vialidad_vencimiento',
        'vehiculo_id',
        'persona_id',
        'tipo_licencia',
        'cargo',
        'certificado_medico',
        'certificado_medico_vencimiento',
        'foto',
        'soporte_documento',
        'soporte_licencia',
        'soporte_certificado'
    ];

    /**
     * Define la relación con el modelo Vehiculo.
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * Define la relación con el modelo Persona.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * Indica si la licencia está a punto de vencer o ya venció.
     *
     * @return bool
     */
    public function licenciaPorVencer()
    {
        $fechaVencimiento = Carbon::parse($this->licencia_vencimiento);
        $fechaLimite = now()->addDays(30); // Notificar 30 días antes
        return $fechaVencimiento->lt($fechaLimite) && $fechaVencimiento->gt(now());
    }

    /**
     * Indica si la licencia ya venció.
     *
     * @return bool
     */
    public function licenciaVencida()
    {
        return Carbon::parse($this->licencia_vencimiento)->lt(now());
    }

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        $term = '%' . $term . '%';

        return $query->where(function ($q) use ($term) {
            // Campos del propio modelo Chofer
            $q->where('licencia_numero', 'like', $term)
              ->orWhere('tipo_licencia', 'like', $term)
              ->orWhere('cargo', 'like', $term)
              ->orWhere('documento_vialidad_numero', 'like', $term);

            // Buscar en la relación Persona (ej. nombre, apellido, documento)
            $q->orWhereHas('persona', function ($qPersona) use ($term) {
                $qPersona->where('nombre', 'like', $term)
                         ->orWhere('apellido', 'like', $term)
                         ->orWhere('documento', 'like', $term);
            });

            // Buscar en la relación Vehiculo (ej. placa, modelo)
            $q->orWhereHas('vehiculo', function ($qVehiculo) use ($term) {
                $qVehiculo->where('placa', 'like', $term)
                          ->orWhere('modelo', 'like', $term);
            });
        });
    }

    public function viajes()
        {
            return $this->hasMany(Viaje::class, 'chofer_id');
        }

    public function viajes_ayudante()
        {
            return $this->hasMany(Viaje::class, 'ayudante');
        }
        

        

}
