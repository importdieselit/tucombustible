<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Deposito;


class Aforo extends Model
{
    use HasFactory;

    protected $table= 'aforo';
    protected $fillable = [
        'deposito_id',
        'profundidad_cm',
        'litros',
    ];

    public function deposito()
    {
        return $this->belongsTo(Deposito::class,'id','deposito_id');
    }


}