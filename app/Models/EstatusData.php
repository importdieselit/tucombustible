<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatusData extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'estatus_data';

    /**
     * La clave primaria de la tabla.
     * @var string
     */
    protected $primaryKey = 'id_estatus';

    /**
     * Los atributos que son asignables masivamente.
     * @var array
     */
    protected $fillable = [
        'css',
        'hex',
        'icon_auto',
        'auto',
        'icon_orden',
        'orden',
    ];

    /**
     * Indica si el modelo debe ser timestamped.
     * @var bool
     */
    public $timestamps = false;
}
