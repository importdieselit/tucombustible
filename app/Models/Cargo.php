<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;
    protected $table = 'cargo';
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
    ];

    public function evaluacionForm()
    {
        return $this->hasOne(RrhhEvaluacionForm::class);
    }
    public function personal()
    {
        return $this->hasMany(Personal::class);
    }
}
