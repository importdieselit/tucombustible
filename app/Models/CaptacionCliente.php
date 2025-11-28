<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\CaptacionDocumento;
use App\Models\EquipoCliente;
use App\Models\Cliente;


class CaptacionCliente extends Model
{
    protected $table = 'captacion_clientes';
    protected $guarded = [];

    protected $fillable = [
        'cliente_id',
        'tipo_cliente',
        'rif',
        'razon_social',
        'representante',
        'correo',
        'telefono',
        'direccion',
        'datos_adicionales',
        'estatus_captacion',
        'observaciones'
    ];

    protected $casts = [
        'datos_adicionales' => 'array',
    ];

    public function documentos()
    {
        return $this->hasMany(CaptacionDocumento::class, 'captacion_id');
    }

    public function equipos()
    {
        return $this->hasMany(EquipoCliente::class, 'captacion_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
