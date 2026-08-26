<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Personal;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';
    protected $fillable = ['departamento', 'descripcion'];

    public function personal()
    {
        return $this->hasMany(Personal::class, 'dependencia', 'id');
    } 

}
