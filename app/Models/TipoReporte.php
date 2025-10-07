<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TipoReporte extends Model
{
    protected $table = 'tipo_reporte';
    protected $fillable = ['nombre_tipo', 'descripcion', 'activo'];

    public function reportes()
    {
        return $this->hasMany(Reporte::class, 'id_tipo_reporte');
    }
}