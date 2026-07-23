<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentosChofer extends Model
{
    use HasFactory;
    protected $table = 'documentos_choferes';
    protected $primaryKey = 'id';
    protected $fillable = [
        'chofer_id',
        'tipo',
        'doc',
        'fecha_in',
        'fecha_venc',
        'nro'];

    public function chofer()
    {
        return $this->belongsTo(Chofer::class, 'chofer_id', 'id');
    }


    public function tipo()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo', 'id');
    }

}
