<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente;
use App\Models\Guia;
use App\Models\Viaje;


class Buques extends Model
{
    use HasFactory;
    protected $table = 'buques';
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'imo',
        'capacidad',
        'cliente_id',
        'bandera',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function guias()
    {
        return $this->hasMany(Guia::class, 'buque_id');
    }    

}
