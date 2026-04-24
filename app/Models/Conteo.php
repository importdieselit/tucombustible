<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conteo extends Model
{
    use HasFactory;
    protected $table = 'conteos';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'codigo',
        'user_id',
        'observaciones',
        'estatus'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ConteoDetalles::class, 'conteo_id');
    }
}
