<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Reporte extends Model
{

    protected $table = 'reportes';

    protected $fillable = [
        'id_tipo_reporte',
        'usuario_id',
        'cliente_id',
        'descripcion',
        'lugar_reporte',
        'url_imagen_evidencia',
        'estatus_actual',
        'requiere_ot',
        'orden_trabajo_id',
        'latitud',
        'longitud',
        'vehiculo_id'
    ];

    // Relaciones:

    public function tipo()
    {
        return $this->belongsTo(TipoReporte::class, 'id_tipo_reporte');
    }

    public function reportadoPor()
    {
        // Asumiendo que existe un modelo User para autenticación
        return $this->belongsTo(User::class, 'origen_usuario_id'); 
    }

    public function historialEstatus()
    {
        return $this->hasMany(ReporteEstatusHistorial::class, 'reporte_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
    public function ordenTrabajo()
    {
        return $this->belongsTo(Orden::class, 'orden_trabajo_id');
    }
}
    