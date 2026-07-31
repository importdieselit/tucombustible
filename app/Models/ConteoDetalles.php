<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class ConteoDetalles extends Model
{
    use HasFactory;
    protected $table = 'conteo_detalles';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'conteo_id',
        'inventario_id',
        'ubicacion_codigo',
        'stock_teorico',
        'stock_fisico',
        'diferencia'
    ];

    public function conteo(): BelongsTo
    {
        return $this->belongsTo(Conteo::class, 'conteo_id');
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function getUbicacionDesglosadaAttribute() {
        $partes = explode('-', $this->ubicacion_codigo);
        return [
            'almacen' => $partes[0] ?? '--',
            'estante' => $partes[1] ?? '--',
            'nivel'   => $partes[2] ?? '--',
            'celda'   => $partes[3] ?? '--',
        ];
    }

}
