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
            ]);

            // 2. Iterar y guardar cada línea de detalle (tanques)
            foreach ($detallesTanques as $detalle) {
                // Cada elemento de $detallesTanques debe traer: id_deposito, centimetros_medidos, litros_calculados
                $chequeo->detalles()->create([
                    'id_deposito'         => $detalle['id_deposito'],
                    'centimetros_medidos' => $detalle['centimetros_medidos'],
                    'litros_calculados'   => $detalle['litros_calculados'],
                ]);
            }

            // Devolvemos el objeto completo con sus relaciones cargadas si se necesita en el flujo
            return $chequeo->load('detalles.deposito');
        });
    }

    /**
     * Obtener los chequeos de una sede con paginación para los históricos.
     */
    public function obtenerHistorialPorSede(int $idSede, int $perPage = 15)
    {
        return ChequeoDeposito::where('id_sede', $idSede)
            ->with(['usuario', 'detalles.deposito'])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Buscar un chequeo específico por su ID con todo su desglose.
     */
    public function buscarPorId(int $id): ?ChequeoDeposito
    {
        return ChequeoDeposito::with(['sede', 'usuario', 'detalles.deposito'])->find($id);
    }
}