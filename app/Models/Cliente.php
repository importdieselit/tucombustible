<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Cliente extends Model
{
    use HasFactory;

    // --- CONSTANTE DE PASOS OFICIALES IMPORDIESEL ---
    const PASOS_REGISTRO = [
        1  => 'Registro de Datos',
        2  => 'Carga de Documentos y Adjuntos',
        3  => 'Recepción de Documentos',
        4  => 'Revisión de Documentos',
        5  => 'Carpeta de Documentos Realizada',
        6  => 'Carpeta de Documentos Enviada al Ministerio de Hidrocarburos',
        7  => 'En espera de Respuesta del Ministerio de Hidrocarburos',
        8  => 'Fecha de Inspección Asignada',
        9  => 'En espera de Respuesta del Ministerio de Hidrocarburos',
        10 => 'Cliente Aprobado'
    ];

    protected $casts = [
        'disponible' => 'float',
        'prepagado'  => 'boolean',
    ];

    protected $fillable = [
        'nombre', 'rif', 'contacto', 'dni', 'estado_id', 'ciudad_id',
        'registro_paso', 'token_registro', 'direccion_operativa', 'telefono',
        'email', 'disponible', 'cupo', 'ciiu', 'parent', 'sector',
        'periodo', 'status', 'prepagado', 'alias', 'user_id'
    ];

    /*
    |--------------------------------------------------------------------------
    | LÓGICA DE NEGOCIO (ACCESORES)
    |--------------------------------------------------------------------------
    */

    /**
     * Devuelve la etiqueta de estatus según la lógica de pasos y activación.
     */
    public function getEstatusOperativoAttribute()
    {
        if ($this->registro_paso < 10) {
            return self::PASOS_REGISTRO[$this->registro_paso] ?? 'Paso Desconocido';
        }

        return $this->status == 1 ? 'Activo' : 'Inactivo';
    }

    /**
     * Devuelve la clase de CSS (Bootstrap) para el estatus.
     */
    public function getEstatusColorAttribute()
    {
        if ($this->registro_paso < 10) return 'warning';
        return $this->status == 1 ? 'success' : 'danger';
    }

    // ... (Relaciones y métodos de prueba que ya tenías se mantienen igual) ...
    public function cupos() { return $this->hasMany(ClienteCupo::class, 'cliente_id'); }
    public function placas() { return $this->hasMany(PlacaVehiculo::class, 'cliente_id'); }
    public function choferesExternos() { return $this->hasMany(ChoferCliente::class, 'cliente_id'); }
    public function sucursales() { return $this->hasMany(Cliente::class, 'parent'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}