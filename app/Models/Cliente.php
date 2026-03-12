<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    const PASOS_REGISTRO = [
        1  => 'Registro de Datos',
        2  => 'Carga de Documentos y Adjuntos',
        3  => 'Recepción de Documentos',
        4  => 'Revisión de Documentos',
        5  => 'Carpeta de Documentos Realizada',
        6  => 'Carpeta de Documentos Enviada al Ministerio',
        7  => 'En espera de Respuesta del Ministerio',
        8  => 'Fecha de Inspección Asignada',
        9  => 'Validación Final de Expediente',
        10 => 'Cliente Aprobado'
    ];

    protected $fillable = [
        'nombre', 'rif', 'contacto', 'dni', 'estado_id', 'ciudad_id',
        'registro_paso', 'token_registro', 'direccion_operativa', 'telefono',
        'email', 'disponible', 'cupo', 'ciiu', 'parent', 'sector',
        'periodo', 'status', 'prepagado', 'alias'
    ];

    public function getNombrePasoActualAttribute()
    {
        return self::PASOS_REGISTRO[$this->registro_paso] ?? 'Paso Desconocido';
    }

    public function user() { return $this->hasOne(User::class, 'cliente_id'); }
    public function documentos() { return $this->hasMany(Documento::class, 'cliente_id'); }
    public function estado() { return $this->belongsTo(Estado::class, 'estado_id'); }
    public function ciudad() { return $this->belongsTo(Ciudad::class, 'ciudad_id'); }
    
    // Relación para obtener las sucursales vinculadas
    public function sucursales() { return $this->hasMany(Cliente::class, 'parent', 'id'); }
    
    // Relación para obtener el cliente principal (Padre)
    public function padre() { return $this->belongsTo(Cliente::class, 'parent', 'id'); }
}