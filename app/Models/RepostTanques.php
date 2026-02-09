<?php

namespace App\Models;// si usas Laravel 7+

use Illuminate\Database\Eloquent\Model;

class RepostTanque extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'repost_tanques';

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
        'id_tanque',
        'id_us',
        'lectura_in',
        'lectura_out',
        'qty',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id_tanque' => 'integer',
        'id_us' => 'integer',
        'lectura_in' => 'float',
        'lectura_out' => 'float',
        'qty' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    /**
     * Get the tanque associated with the repost.
     */
    public function tanque()
    {
        return $this->belongsTo(Tanque::class, 'id_tanque', 'id');
    }

    /**
     * Get the user that made the repost.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_us', 'id');
    }
}