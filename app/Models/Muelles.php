<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\TabuladorViaticos;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Guia;
use App\Models\Viaje;
use App\Models\DespachoViaje;

class Muelles extends Model
{
    use HasFactory;
    protected $table = 'muelles';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public $softDeletes = true;
    protected $fillable = [
        'nombre',
        'ubicacion',
    ];

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(TabuladorViatico::class, 'ubicacion', 'id');
    }
    public function guias(): HasMany
    {
        return $this->hasMany(Guia::class, 'muelle', 'nombre');
    }
    public function viajes(): HasMany
    {
        return $this->hasMany(Viaje::class, 'id_muelle', 'id');
    }

}