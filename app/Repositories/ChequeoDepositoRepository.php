<?php

namespace App\Repositories;

use App\Models\ChequeoDeposito;
use App\Models\ChequeoDepositoDetalle;
use Illuminate\Support\Facades\DB;
use Exception;

class ChequeoDepositoRepository
{
    /**
     * Verificar si ya existe un chequeo registrado para una sede, fecha y turno específico.
     * Útil para validaciones previas en el controlador.
     */
    public function existeChequeo(int $idSede, string $fecha, string $turno): bool
    {
        return ChequeoDeposito::where('id_sede', $idSede)
            ->where('fecha', $fecha)
            ->where('turno', $turno)
            ->exists();
    }

    public function obtenerUltimoDetallePorDeposito(int $idDeposito): ?ChequeoDepositoDetalle
    {
        return ChequeoDepositoDetalle::where('id_deposito', $idDeposito)
            ->join('chequeos_depositos', 'chequeos_depositos_detalles.id_chequeo', '=', 'chequeos_depositos.id')
            ->select('chequeos_depositos_detalles.*')
            ->orderBy('chequeos_depositos.fecha', 'desc')
            ->orderBy('chequeos_depositos.created_at', 'desc')
            ->first();
    }

    /**
     * Guardar el chequeo completo (Cabecera + Detalles) usando una Transacción Atómica.
     */
    public function guardarChequeoCompleto(array $datosCabecera, array $detallesTanques): ChequeoDeposito
    {
        return DB::transaction(function () use ($datosCabecera, $detallesTanques) {
            
            // 1. Crear la cabecera del chequeo
            $chequeo = ChequeoDeposito::create([
                'id_sede'    => $datosCabecera['id_sede'],
                'id_usuario' => $datosCabecera['id_usuario'],
                'fecha'      => $datosCabecera['fecha'],
                'turno'      => $datosCabecera['turno'],
                'observaciones' => $datosCabecera['observaciones'] ?? null,
            ]);

            // 2. Iterar y guardar cada línea de detalle (tanques)
            foreach ($detallesTanques as $detalle) {
                // Cada elemento de $detallesTanques debe traer: id_deposito, centimetros_medidos, litros_calculados
                $chequeo->detalles()->create([
                    'id_deposito'         => $detalle['id_deposito'],
                    'centimetros_medidos' => $detalle['centimetros_medidos'],
                    'litros_calculados'   => $detalle['litros_calculados'],
                    'id_tipos_combustible' => $detalle['id_tipos_combustible'],
                    'litros_teoricos'      => $detalle['litros_teoricos'],
                    'merma_calculada'      => $detalle['merma_calculada'],
                ]);

                DB::table('depositos')->where('id', $detalle['id_deposito'])->update([
                    'nivel_actual_litros' => $detalle['litros_calculados'],
                    'tipo_combustible_id' => $detalle['id_tipos_combustible'],
                    'nivel_cm'            => $detalle['centimetros_medidos'],
                    'updated_at'          => now() // Al usar DB::table manual, forzamos el timestamp
                ]);
            }

            // Devolvemos el objeto completo con sus relaciones cargadas si se necesita en el flujo
            return $chequeo->load('detalles.deposito', 'detalles.tipoCombustible');
        });
    }

    /**
     * Obtener los chequeos de una sede con paginación para los históricos.
     */
    public function obtenerHistorialPorSede(int $idSede, int $perPage = 15)
    {
        return ChequeoDeposito::where('id_sede', $idSede)
            ->with(['usuario', 'detalles.deposito', 'detalles.tipoCombustible'])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Buscar un chequeo específico por su ID con todo su desglose.
     */
    public function buscarPorId(int $id): ?ChequeoDeposito
    {
        return ChequeoDeposito::with(['sede', 'usuario', 'detalles.deposito', 'detalles.tipoCombustible'])->find($id);
    }
}