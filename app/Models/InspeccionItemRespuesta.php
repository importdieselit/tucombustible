<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ChecklistItem;
use App\Models\Inspeccion;

// Modelo para la tabla 'inspeccion_item_respuestas'
class InspeccionItemRespuesta extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_item_respuestas';

    protected $fillable = [
        'inspeccion_id',
        'checklist_item_id',
        'respuesta'
    ];

    protected $casts = [
        'respuesta' => 'boolean',
    ];

    /**
     * Relación con el ítem del checklist.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }

    /**
     * Relación con la inspección.
     */
    public function inspeccion(): BelongsTo
    {
        return $this->belongsTo(Inspeccion::class, 'inspeccion_id');
    }
    /**
     * Scope para filtrar por inspección.
     */
    public function scopeInspeccion($query, $inspeccionId)
    {
        return $query->where('inspeccion_id', $inspeccionId);   
    }
    
}

