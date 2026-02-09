<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\DespachoViaje;
use App\Models\Pedido;
use App\Models\Viaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminViajeController extends Controller
{
    /**
     * Permite a un administrador planificar un viaje y agendarlo en el calendario.
     */
    public function planificar(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !in_array($user->id_perfil, [1, 2])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores pueden planificar viajes.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'fecha_salida' => 'required|date',
            'destino_ciudad' => 'nullable|string|max:255',
            'despachos' => 'required|array|min:1',
            'despachos.*.cliente_id' => 'nullable|exists:clientes,id',
            'despachos.*.otro_cliente' => 'nullable|string|max:255',
            'despachos.*.litros' => 'required|numeric|min:0.01',
            'despachos.*.pedido_id' => 'nullable|exists:pedidos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $user) {
                $destino = $request->input('destino_ciudad');
                if (empty($destino)) {
                    $destino = 'Despacho programado';
                    $firstCliente = collect($request->input('despachos'))
                        ->firstWhere('cliente_id');
                    if ($firstCliente && isset($firstCliente['cliente_nombre'])) {
                        $destino = 'Despacho programado - ' . $firstCliente['cliente_nombre'];
                    }
                }

                $viaje = Viaje::create([
                    'destino_ciudad' => $destino,
                    'fecha_salida' => $request->input('fecha_salida'),
                    'status' => 'PENDIENTE_ASIGNACION',
                    'usuario_id' => $user->id,
                    'has_viatico' => false,
                    'litros' => 0,
                ]);

                $totalLitros = 0;

                foreach ($request->input('despachos') as $despachoData) {
                    $despacho = DespachoViaje::create([
                        'viaje_id' => $viaje->id,
                        'cliente_id' => $despachoData['cliente_id'] ?? null,
                        'otro_cliente' => $despachoData['otro_cliente'] ?? null,
                        'litros' => $despachoData['litros'],
                    ]);

                    $totalLitros += $despacho->litros;

                    if (!empty($despachoData['pedido_id'])) {
                        $pedido = Pedido::find($despachoData['pedido_id']);
                        if ($pedido) {
                            $updateData = [
                                'estado' => $pedido->estado === 'pendiente' ? 'en_proceso' : $pedido->estado,
                                'cantidad_aprobada' => $despacho->litros,
                            ];

                            $observacion = trim(($pedido->observaciones_admin ?? '') . ' | Planificado para ' . $viaje->fecha_salida);
                            $updateData['observaciones_admin'] = ltrim($observacion, ' |');
                            $pedido->update($updateData);
                        }
                    }
                }

                $viaje->update(['litros' => $totalLitros]);
                $viaje->load(['despachos.cliente', 'chofer.persona', 'ayudante_chofer.persona', 'vehiculo']);

                $viajeData = [
                    'id' => $viaje->id,
                    'destino_ciudad' => $viaje->destino_ciudad,
                    'fecha_salida' => $viaje->fecha_salida,
                    'status' => $viaje->status,
                    'duracion_dias' => 1,
                    'total_litros' => $viaje->litros ?? $totalLitros,
                    'chofer' => [
                        'id' => $viaje->chofer_id,
                        'nombre' => optional(optional($viaje->chofer)->persona)->nombre ?? 'PENDIENTE',
                    ],
                    'ayudante' => $viaje->ayudante ? [
                        'id' => $viaje->ayudante,
                        'nombre' => optional(optional($viaje->ayudante_chofer)->persona)->nombre ?? 'N/A',
                    ] : null,
                    'vehiculo' => [
                        'id' => $viaje->vehiculo_id,
                        'flota' => optional($viaje->vehiculo)->flota ?? 'PENDIENTE',
                        'placa' => optional($viaje->vehiculo)->placa ?? 'N/A',
                    ],
                    'despachos' => $viaje->despachos->map(function (DespachoViaje $despacho) {
                        return [
                            'id' => $despacho->id,
                            'cliente_id' => $despacho->cliente_id,
                            'cliente_nombre' => optional($despacho->cliente)->nombre ?? $despacho->otro_cliente ?? 'Cliente Desconocido',
                            'litros' => $despacho->litros,
                        ];
                    }),
                    'custodia_count' => $viaje->custodia_count ?? 0,
                    'has_viatico' => (bool) $viaje->has_viatico,
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Viaje planificado exitosamente',
                    'data' => $viajeData,
                ], 201);
            });
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al planificar viaje: ' . $th->getMessage(),
            ], 500);
        }
    }
}

