<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'clientes_documentos';

    protected $fillable = [
        'cliente_id', 'requisito_id', 'tipo_anexo', 'nombre_documento', 'ruta', 'validado', 'validado_por'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}