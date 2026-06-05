<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
     protected $table = 'inventario';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_usuario',
        'prioridad',
        'estatus',
        'id_almacen',
        'codigo',
        'codigo_fabricante',
        'fabricante',
        'referencia',
        'descripcion',
        'existencia',
        'costo',
        'costo_div',
        'existencia_minima',
        'marca',
        'modelo',
        'salida_motivo',
        'salida_fecha',
        'salida_id_usuario',
        'fecha_in',
        'observacion',
        'avatar',
        'factura_referencia',
        'grupo',
        'codigo_interno',
        'clasificacion',
        'incorporacion',
        'existencia_maxima',
        'condicion',
        'fecha_conteo',
        'serialized',
    ];


    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen', 'id');
    }

    public function usuarioSalida()
    {
        return $this->belongsTo(User::class, 'salida_id_usuario', 'id');
    }
    // Asumiendo que 'marca' y 'modelo' son FKs a tablas de marcas/modelos
    public function marcaObj()
    {
        return $this->belongsTo(Marca::class, 'marca', 'id');
    }

    public function equivalentes()
    {
        return $this->belongsToMany(Inventario::class, 'inventario_equivalentes', 'inventario_id', 'equivalente_id');
    }

    /**
     * RELACIÓN: Modelos de Vehículos Asociados
     */
    public function modelosAsociados()
    {
        return $this->hasMany(InventarioAsociado::class, 'inventario_id');
    }

    /**
     * RELACIÓN: Stock Físico (Las ubicaciones donde está guardado)
     */
    public function stocks()
    {
        return $this->hasMany(InventarioStock::class, 'item_id'); // InventarioStock creado en la respuesta anterior
    }

    public function ubicacion()
    {
        return $this->hasOneThrough(Ubicacion::class, InventarioStock::class, 'inventario_id', 'id', 'id', 'ubicacion_id');
    }

    /**
     * ATRIBUTO VIRTUAL: Existencia Total Consolidada
     * Esto reemplaza la antigua columna "existencia". Suma el stock de todas sus ubicaciones en caliente.
     */
    public function getExistenciaTotalAttribute()
    {
        return $this->stocks()->sum('cantidad_actual');
    }
    


}