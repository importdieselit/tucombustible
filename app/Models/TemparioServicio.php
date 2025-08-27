<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class TemparioServicio extends Model
{
    protected $table = 'tempario_servicios';
    protected $primaryKey = 'id_tempario_servicio';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_tempario_categoria',
        'id_usuario',
        'codigo',
        'serivicio',
        'horas',
        'costo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'id_tempario_categoria' => 'integer',
        'id_usuario' => 'integer',
        'horas' => 'decimal:2',
        'costo' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(TemparioCategoria::class, 'id_tempario_categoria', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}