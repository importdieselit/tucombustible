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
        'razon_social', 
        'rif', 
        'correo', 
        'telefono', 
        'tipo_cliente', 
        'tipo_solicitud', 
        'estado',          
        'ciudad',          
        'cantidad_litros', 
        'tipo_servicio',   
        'estatus_captacion'
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
        // 1. Buscamos solo los requisitos que le corresponden a este tipo de cliente
        $esPadre = ($this->tipo_cliente === 'padre');
        
        $requisitosIds = RequisitoCaptacion::where(function ($query) use ($esPadre) {
            $query->where('tipo_cliente', 'ambos');
            if ($esPadre) {
                $query->orWhere('tipo_cliente', 'padre');
            }
        })->pluck('id')->toArray();

        // 2. Obtenemos los IDs de los documentos que YA subió
        $cargados = $this->documentos()
                        ->pluck('requisito_id')
                        ->filter()
                        ->unique()
                        ->toArray();

        // 3. La diferencia son los que realmente le faltan SEGÚN SU TIPO
        return array_diff($requisitosIds, $cargados);
    }

    public function requisitosPendientes()
    {
        return RequisitoCaptacion::whereIn('id', $this->faltantes())->get();
    }

    public function requisitosCompletos()
    {
         return count($this->faltantes()) === 0;
    }

}
