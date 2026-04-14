<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentosVehiculo extends Model
{
    use HasFactory;
    protected $table = 'documentos_vehiculos';
    protected $primaryKey = 'id';
    protected $fillable = [
        'vehiculo_id',
        'tipo',
        'doc',
        'fecha_in',
        'fecha_venc',
        'nro'];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo', 'id');
    }

}
