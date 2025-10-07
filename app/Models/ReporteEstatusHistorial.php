<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReporteEstatusHistorial extends Model
{
    protected $table = 'reporte_estatus_historial';
    protected $fillable = ['reporte_id', 'usuario_modifica_id', 'estatus_anterior', 'estatus_nuevo', 'nota_cambio'];
    
    public function reporte()
    {
        return $this->belongsTo(Reporte::class, 'reporte_id');
    }
    
    // Relación con el usuario de Laravel que modificó el estatus
    public function usuarioModifica()
    {
        return $this->belongsTo(User::class, 'usuario_modifica_id'); 
    }
}