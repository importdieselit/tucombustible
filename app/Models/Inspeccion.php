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
        'respuesta_json',
        'respuesta_in'
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

     public function getResponsableInspeccionAttribute()
    {
        // Ruta al valor: sections[0] -> items[0] -> value
         $this->respuesta_json = json_decode($this->respuesta_json, true);
        $sections = $this->respuesta_json['sections'] ?? [];
        //dd($sections);
        if (empty($sections)) {
            return null;
        }
        foreach ($sections as $section) {
            if (isset($section['section_title']) && $section['section_title'] === 'Información General') {
                $items = $section['items'] ?? [];
                foreach ($items as $item) {
                    if (isset($item['label']) && $item['label'] === 'Responsable de inspeccion') {
                        return $item['value'] ?? null;
                    }
                }
            }
        }
        return null; // Valor no encontrado
    }
}