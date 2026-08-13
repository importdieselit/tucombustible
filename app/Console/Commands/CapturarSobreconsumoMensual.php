<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CapturarSobreconsumoMensual extends Command
{
    protected $signature = 'cupos:capturar-sobreconsumo';
    protected $description = 'Captura el histórico de cupos y sobreconsumo de clientes al cierre de mes';

    public function handle()
    {
        // Se evalúa el mes inmediatamente anterior
        $fechaCierre = Carbon::now()->subMonth();
        $mes = $fechaCierre->month;
        $anio = $fechaCierre->year;

        $clientes = Cliente::where('status', 2) // Aprobados
            ->whereNotNull('cupo')
            ->where('cupo', '>', 0)
            ->get();

        $registros = 0;

        foreach ($clientes as $cliente) {
            $cupoAutorizado = (float) $cliente->cupo;
            $disponible = (float) $cliente->disponible;
            
            // Consumo = Cupo - Disponible (si disponible es negativo, consumió más del cupo)
            $consumido = max(0, $cupoAutorizado - $disponible);

            DB::table('gasco_cupos_mensuales')->updateOrInsert(
                [
                    'cliente_id' => $cliente->id,
                    'mes'        => $mes,
                    'anio'       => $anio,
                ],
                [
                    'litros_autorizados' => $cupoAutorizado,
                    'litros_consumidos'  => $consumido,
                    'updated_at'         => now(),
                    'created_at'         => now(),
                ]
            );

            $registros++;
        }

        $this->info("Cierre mensual completado para {$registros} clientes ({$mes}/{$anio}).");
        return Command::SUCCESS;
    }
}