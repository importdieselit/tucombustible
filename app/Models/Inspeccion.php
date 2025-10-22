<?php

// app/Models/Inspeccion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspeccion extends Model
{
    protected $table = 'inspecciones'; 

    protected $fillable = [
        'vehiculo_id', 
        'checklist_id', 
        'usuario_id', 
        'estatus_general', 
        'respuesta_json'
    ];
    
    // Crucial: Almacena el resultado completo como JSON
    protected $casts = [
        'respuesta_json' => 'array',
    ];

    // Relaciones (opcional, pero buena práctica)
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }
    
    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
    
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    
    public function imagenes()
    {
        return $this->hasMany(InspeccionImagen::class, 'inspeccion_id')->orderBy('orden');
    }
    
    // Método auxiliar para obtener todas las rutas de imágenes
    public function getRutasImagenes()
    {
        return $this->imagenes()->pluck('ruta_imagen')->toArray();
    }
}