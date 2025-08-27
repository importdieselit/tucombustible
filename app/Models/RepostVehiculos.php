<?php

namespace App\Models;// si usas Laravel 7+

use Illuminate\Database\Eloquent\Model;

class RepostajeVehiculo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'repostaje_vehiculos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int'; // bigint unsigned

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true; // Tiene created_at y updated_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_vehiculo',
        'id_tanque',
        'qty',
        'qtya',
        'rest',
        'fecha',
        'obs',
        'id_us',
        'pic',
        'type',
        'ref',
        'placa_ext',
        'nombre_ext',
        'origin',
        'id_admin',
        'ticket',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id_vehiculo' => 'integer',
        'id_tanque' => 'integer',
        'qty' => 'float',
        'qtya' => 'float',
        'rest' => 'float',
        'fecha' => 'datetime',
        'id_us' => 'integer',
        'type' => 'integer',
        'id_admin' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    /**
     * Get the vehiculo that was refueled.
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id');
    }

    /**
     * Get the tanque from which the refueling was made.
     */
    public function tanque()
    {
        return $this->belongsTo(Tanque::class, 'id_tanque', 'id');
    }

    /**
     * Get the user who registered the refueling.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_us', 'id');
    }

    /**
     * Get the admin who approved/registered the refueling.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'id_admin', 'id');
    }
}