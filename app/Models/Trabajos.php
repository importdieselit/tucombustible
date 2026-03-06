<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trabajos extends Model
{
    use HasFactory;
    protected $table = 'trabajos';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_orden',
        'id_categoria',
        'id_tempario_servicio',
        'descripcion',
        'id_personal',
        'fecha_inicio',
        'fecha_fin',
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
            return $this->belongsTo(Personal::class, 'id_personal');
        }
        
}
