<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    use HasFactory;

    protected $table = 'checklist';
    protected $primaryKey = 'id';
    protected $fillable = [
        'titulo',
        'activo',
        'checklist'
    ];
    public $timestamps = true;
    protected $casts = [
        'checklist' => 'array',
    ];    
    

}
