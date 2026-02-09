<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CaptacionCliente;

class EquipoCliente extends Model
{
    protected $table = 'equipos_clientes';
    protected $guarded = [];
    protected $fillable = [
        'captacion_id',
        'tipo_equipo',
        'identificador',
        'caracteristicas'
    ];

    protected $casts = [
        'caracteristicas' => 'array'
    ];

    public function captacion()
    {
        return $this->belongsTo(CaptacionCliente::class, 'captacion_id');
    }
}
