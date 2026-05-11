<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;
    protected $table = 'tipo_documento';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // La tabla 'ordenes' no tiene 'created_at' ni 'updated_at' en tu SQL
    public $fillable = ['nombre', 'descripcion', 'abreviatura', 'tipo','tabla_destino','campo_destino','campo_fecha_vencimiento','campo_ruta'];
    
    public function doc_vehiculos()
    {
        return $this->hasMany(DocumentosVehiculo::class, 'tipo', 'id');
    }

    
}
