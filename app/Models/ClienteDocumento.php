<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteDocumento extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 
        'user_id', 
        'nombre_archivo', 
        'ruta_archivo', 
        'mime_type',
        'peso_archivo'
    ];

    public function cliente() {
        return $this->belongsTo(Cliente::class);
    }
}