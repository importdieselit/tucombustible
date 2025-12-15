<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Guia;

class Nominacion extends Model
{
    use HasFactory;
    protected $table = 'nominaciones';
    protected $fillable = [
        'nominacion',
        'guia_id'
    ];
    public function guia()
    {
        return $this->belongsTo(Guia::class);
    }
    public function viaje()
    {
        return $this->guia->viaje();    
    }

    public function cliente()
    {
        return $this->guia->cliente();
    }   
}
