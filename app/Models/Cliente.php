<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'rif',
        'contacto',
        'cedula',
        'direccion',
        'telefono',
        'email'
    ];

    /**
     * Define los atributos que se añadirán al modelo si se serializa.
     * Esto permite acceder a los "accesorios" como si fueran columnas de la tabla.
     *
     * @var array
     */
    protected $appends = [
        'consumo_mensual_promedio',
        'historico_compras',
        'proxima_compra_prediccion',
        'dias_faltantes_proxima_compra'
    ];

    /**
     * Accesor para calcular el consumo promedio mensual.
     * En producción, esta lógica se conectaría a la base de datos.
     *
     * @return float
     */
    public function getConsumoMensualPromedioAttribute()
    {
        // --- Lógica de prueba ---
        // En un futuro, podrías hacer algo como:
        // return $this->despachos()->sum('litros') / $this->despachos()->distinct('fecha')->count();
        return 1500;
    }

    /**
     * Accesor para obtener el histórico de compras.
     * En producción, esta lógica se conectaría a la base de datos.
     *
     * @return array
     */
    public function getHistoricoComprasAttribute()
    {
        // --- Lógica de prueba ---
        return [
            ['fecha' => Carbon::now()->subMonths(3), 'litros' => 1200],
            ['fecha' => Carbon::now()->subMonths(2), 'litros' => 1550],
            ['fecha' => Carbon::now()->subMonth(), 'litros' => 1480],
        ];
    }

    /**
     * Accesor para predecir la próxima compra.
     * En producción, se usaría una lógica más robusta.
     *
     * @return array
     */
    public function getProximaCompraPrediccionAttribute()
    {
        // --- Lógica de prueba ---
        $diasEntreCompras = 32; // Promedio de días
        $ultimaCompra = Carbon::now()->subMonth(); // Última fecha de compra
        
        return [
            'fecha' => $ultimaCompra->addDays($diasEntreCompras)->toDateString(),
            'litros_predichos' => $this->getConsumoMensualPromedioAttribute(),
        ];
    }

    /**
     * Accesor para calcular los días que faltan para la próxima compra predicha.
     *
     * @return int
     */
    public function getDiasFaltantesProximaCompraAttribute()
    {
        // --- Lógica de prueba ---
        $proximaCompra = $this->getProximaCompraPrediccionAttribute();
        return Carbon::parse($proximaCompra['fecha'])->diffInDays(Carbon::now());
    }
}
