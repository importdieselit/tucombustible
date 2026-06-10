<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;
    protected $table = 'pagos';
    protected $primary = 'id';
    protected $fillable = [
        'id_usuario',
        'id_cliente',
        'id_pedido',
        'litros',
        'referencia',
        'fecha_pago',
        'fecha_solicitud'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }
    // Método estándar para que Laravel sepa a qué número enviar WhatsApp
    public function routeNotificationForWhatsApp()
    {
        $telefono = $this->cliente->telefono;

        // 1. Limpiar caracteres no numéricos (espacios, guiones, paréntesis)
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        // 2. Si el número empieza por 0, lo quitamos (ej: 0412 -> 412)
        if (strpos($telefono, '0') === 0) {
            $telefono = substr($telefono, 1);
        }

        // 3. Validar si ya tiene el código de país (58)
        // Si la longitud es 10 (ej: 4125449993), le agregamos el 58
        if (strlen($telefono) === 10) {
            $telefono = '58' . $telefono;
        }
        
        // Si el usuario guardó el número con el código de país pero sin el 58, 
        // ej: 4125449993, ya lo cubrimos arriba.
        
        return $telefono;
    }

}
