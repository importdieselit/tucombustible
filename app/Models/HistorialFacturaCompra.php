<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialFacturaCompra extends Model
{
    use HasFactory;

    protected $table = 'historial_facturas_compras';

    protected $fillable = [
        'compra_id',
        'usuario_id',
        'numero_factura_anterior',
        'numero_factura_nuevo',
        'factura_path_anterior',
        'factura_path_nuevo',
    ];

    public function compra()
    {
        return $this->belongsTo(CompraCombustible::class, 'compra_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}