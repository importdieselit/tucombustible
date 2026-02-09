<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CaptacionCliente;
use App\Models\RequisitoCaptacion;

class CaptacionDocumento extends Model
{
    protected $table = 'captacion_documentos';
    protected $guarded = [];
    protected $fillable = [
        'captacion_id',
        'requisito_id',
        'tipo_anexo',
        'nombre_documento',
        'ruta',
        'validado',
        'validado_por'
    ];

    public function captacion()
    {
        return $this->belongsTo(CaptacionCliente::class, 'captacion_id');
    }
    public function requisito()
    {
        return $this->belongsTo(RequisitoCaptacion::class, 'requisito_id');
    }

}
