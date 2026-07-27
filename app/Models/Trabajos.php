<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trabajos extends Model
{
    use HasFactory;
    protected $table = 'trabajos';
    protected $primaryKey = 'id_trabajo';
    protected $fillable = [
        'id_orden',
        'id_categoria',
        'id_tempario_servicio',
        'descripcion',
        'id_mecanico',
        'fecha_inicio',
        'fecha_fin',
        'costo'
    ];

        public function orden()
        {
            return $this->belongsTo(Orden::class, 'id_orden');
        }

        public function categoria()
        {
            return $this->belongsTo(TemparioCategoria::class, 'id_categoria');
        }

        public function servicio()
        {
            return $this->belongsTo(TemparioServicio::class, 'id_tempario_servicio');
        }

       public function personal()
        {
            // Ajusta 'id_personal' si la llave primaria en la tabla personal se llama distinto
            return $this->belongsTo(Personal::class, 'id_mecanico', 'id_personal');
        }

       
        public function persona()
        {
            return $this->hasOneThrough(
                Persona::class,  // Modelo destino
                Personal::class, // Modelo intermedio
                'id_personal',   // FK en tabla 'personal' que conecta con Trabajo
                'id',            // FK en tabla 'persona' que conecta con Personal (id_persona)
                'id_mecanico',   // Local Key en tabla 'trabajos'
                'id_persona'     // Local Key en tabla 'personal'
            );
        }

    
        public function getMecanicosAsignadosAttribute()
        {
            if (empty($this->id_mecanico)) {
                return collect();
            }

            $ids = is_array($this->id_mecanico)
                ? $this->id_mecanico
                : array_filter(explode(',', $this->id_mecanico));

            return Personal::with('persona')->whereIn('id_personal', $ids)->get();
        }
        
}
