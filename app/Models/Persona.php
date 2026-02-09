<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';

    protected $fillable = [
        'nombre',
        'dni',
        'dni_exp',
        'telefono',
        'address',
        'city',
        'state',
        'country',
        'date_of_birth',
        'gender',
        'notes',
    ];

    protected $casts = [
        'dni_exp' => 'date',
        'date_of_birth' => 'date',
    ];

    /**
     * Una persona puede tener una cuenta de usuario.
     */
    public function chofer()
    {
        return $this->hasOne(Chofer::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
