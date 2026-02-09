<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    use HasFactory;

    protected $table = 'incidencias';

    protected $fillable = [
        'conductor_id',
        'vehiculo_id',
        'pedido_id',
        'tipo',
        'titulo',
        'descripcion',
        'ubicacion',
        'latitud',
        'longitud',
        'foto',
        'estado',
        'notas_admin',
        'fecha_resolucion',
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'fecha_resolucion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con el conductor (usuario)
    public function conductor()
    {
        return $this->belongsTo(User::class, 'conductor_id');
    }

    // Relación con el vehículo
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    // Relación con el pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    // Scope para filtrar por estado
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    // Scope para filtrar por tipo
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Scope para filtrar por conductor
    public function scopeConductor($query, $conductorId)
    {
        return $query->where('conductor_id', $conductorId);
    }

    // Obtener URL completa de la foto
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return url('storage/' . $this->foto);
        }
        return null;
    }
}

