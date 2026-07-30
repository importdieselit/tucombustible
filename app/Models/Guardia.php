<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardia extends Model
{
    protected $table = 'guardias';
    protected $fillable = ['personal_id', 'fecha', 'rol_guardia'];

    public function personal()
    {
            return $this->belongsTo(Personal::class, 'personal_id', 'id_personal');
    }

    public function getPersonalNameAttribute(): string
    {
        return $this->personal->personaData->nombre . ' ' . $this->personal->personaData->apellido;
    }


}
