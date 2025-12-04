<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\CaptacionDocumento;
use App\Models\EquipoCliente;
use App\Models\RequisitoCaptacion;
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
        'observaciones',
        'atendido_por',
        'gestion',
        'solicitados'
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

    public function documentosCargadosPorCodigo()
    {
        return $this->documentos
            ->pluck('ruta','tipo_anexo')
            ->filter()
            ->toArray();
    }

    public function faltantes()
    {
        $requisitos = RequisitoCaptacion::pluck('id')->toArray();

        $cargados = $this->documentos
                        ->pluck('requisito_id')
                        ->filter()
                        ->unique()
                        ->toArray();

        return array_diff($requisitos, $cargados);
    }

    public function requisitosPendientes()
    {
        return RequisitoCaptacion::whereIn('id', $this->faltantes())->get();
    }

    public function requisitosCompletos()
    {
        $faltantes = $this->faltantes();

        return RequisitoCaptacion::when(
            count($faltantes) > 0,
            fn ($q) => $q->whereNotIn('id', $faltantes)
        )->get();
    }

}
