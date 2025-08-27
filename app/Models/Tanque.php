<?php

namespace App\Models;// si usas Laravel 7+

use Illuminate\Database\Eloquent\Model;

class Tanque extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tanques';

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
        'id_us',
        'serial',
        'capacidad',
        'producto',
        'ubicacion',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id_us' => 'integer',
        'capacidad' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    /**
     * Get the user that owns the tanque.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_us', 'id');
    }

    /**
     * Get the reposts associated with the tanque.
     */
    public function reposts()
    {
        return $this->hasMany(RepostTanque::class, 'id_tanque', 'id');
    }

    /**
     * Get the vehicle refuels associated with the tanque.
     */
    public function repostajesVehiculos()
    {
        return $this->hasMany(RepostajeVehiculo::class, 'id_tanque', 'id');
    }
}