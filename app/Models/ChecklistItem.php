<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Modelo para la tabla 'checklist_items'
class ChecklistItem extends Model
{
    use HasFactory;

    protected $table = 'checklist_items';

    protected $fillable = [
        'item',
        'tipo',
        'activo'
    ];


    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Scope para filtrar por tipo de checklist.
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}