<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoRequerimiento extends Model
{
    use HasFactory;

    protected $table = 'tipo_req';
    protected $fillable = ['tipo'];

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'tipo', 'tipo');
    }
}
