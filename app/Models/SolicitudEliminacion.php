<?php
// app/Models/SolicitudEliminacion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudEliminacion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_eliminacion';

    protected $fillable = [
        'user_identifier',
        'user_type',
        'reason',
        'status',
        'approved_by_admin',
    ];
}