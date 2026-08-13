<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'personal';

    /**
     * La clave primaria de la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id_personal';

    /**
     * Indica si la clave primaria es auto-incrementable.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Los atributos que son asignables de forma masiva.
     *
     * @var array
     */
    protected $fillable = [
        'id_sede',
        'id_usuario',
        'estatus',
        'dependencia',
        'cargo',
        'direccion',
        'telefono',
        'email',
        'observaciones',
        'fecha_in',
        'jefe_taller',
        'id_persona',
        'cargo_id'
    ];

    /**
     * Los atributos que deben ser ocultados para los arrays.
     *
     * @var array
     */
    protected $hidden = [
        // 'password', // Puedes añadir campos sensibles aquí si existen
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array   
     */
    protected $casts = [
        // 'fecha_in' => 'date', // Laravel puede manejar la conversión si el formato es estándar
        'id_sede' => 'integer',
        'id_usuario' => 'integer',
        'estatus' => 'integer',
        'dependencia' => 'integer',
        'jefe_taller' => 'integer',
        'id_persona' => 'integer'
    ];

    /**
     * Define la relación con el modelo de Taller.
     * Asumiendo que existe un modelo Taller.
     */
    public function sede()
    {
        return $this->belongsTo(Sedes::class, 'id_sede');
    }

    /**
     * Define la relación con el modelo de Usuario.
     * Asumiendo que existe un modelo User o Usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
        public function persona(){
            return $this->belongsTo(Persona::class, 'id_persona', 'id');
        }

        public function personaData(){
            return $this->hasOne(Persona::class, 'id', 'id_persona');
        }

    public function cargo(){
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function evaluaciones()
    {
        return $this->hasOne(RrhhEvaluacionForm::class, 'cargo_id', 'cargo_id');
    }

    public function getEvaluacionForm()
    {
        return $this->evaluaciones()->first()->google_form_url ?? null;
    }
 

}
