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

        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        if (strpos($telefono, '0') === 0) {
            $telefono = substr($telefono, 1);
        }

        if (strlen($telefono) === 10) {
            $telefono = '58' . $telefono;
        }
        
        return $telefono;
    }

}
