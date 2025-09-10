<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\MovimientoCombustible;
use App\Models\Deposito;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MovimientoCombustibleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $movimientos = MovimientoCombustible::with(['deposito', 'proveedor', 'cliente'])->get();

        return response()->json([
            'success' => true,
            'data' => $movimientos
        ]);
    }

    /**
     * Get movements by deposito ID
     */
    public function getByDeposito($depositoId): JsonResponse
    {
        try {
            $movimientos = MovimientoCombustible::with(['deposito', 'proveedor', 'cliente'])
                ->where('deposito_id', $depositoId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $movimientos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos del depósito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipo_movimiento' => 'required|in:entrada,salida',
            'deposito_id' => 'required|exists:depositos,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'cantidad_litros' => 'required|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Verificar que hay suficiente combustible para salidas
            if ($request->tipo_movimiento === 'salida') {
                $deposito = Deposito::find($request->deposito_id);
                if ($deposito->nivel_actual_litros < $request->cantidad_litros) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay suficiente combustible en el depósito'
                    ], 400);
                }
            }

            $movimiento = MovimientoCombustible::create($request->all());

            // Actualizar el nivel del depósito
            $deposito = Deposito::find($request->deposito_id);
            if ($request->tipo_movimiento === 'entrada') {
                $deposito->nivel_actual_litros += $request->cantidad_litros;
            } else {
                $deposito->nivel_actual_litros -= $request->cantidad_litros;
            }
            $deposito->save();

            DB::commit();

            $movimiento->load(['deposito', 'proveedor', 'cliente']);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de combustible registrado exitosamente',
                'data' => $movimiento
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar movimiento de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MovimientoCombustible $movimiento): JsonResponse
    {
        $movimiento->load(['deposito', 'proveedor', 'cliente']);

        return response()->json([
            'success' => true,
            'data' => $movimiento
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MovimientoCombustible $movimiento): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipo_movimiento' => 'required|in:entrada,salida',
            'deposito_id' => 'required|exists:depositos,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'cantidad_litros' => 'required|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Revertir el movimiento anterior
            $depositoAnterior = Deposito::find($movimiento->deposito_id);
            if ($movimiento->tipo_movimiento === 'entrada') {
                $depositoAnterior->nivel_actual_litros -= $movimiento->cantidad_litros;
            } else {
                $depositoAnterior->nivel_actual_litros += $movimiento->cantidad_litros;
            }
            $depositoAnterior->save();

            // Verificar que hay suficiente combustible para el nuevo movimiento
            if ($request->tipo_movimiento === 'salida') {
                $depositoNuevo = Deposito::find($request->deposito_id);
                if ($depositoNuevo->nivel_actual_litros < $request->cantidad_litros) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay suficiente combustible en el depósito'
                    ], 400);
                }
            }

            $movimiento->update($request->all());

            // Aplicar el nuevo movimiento
            $depositoNuevo = Deposito::find($request->deposito_id);
            if ($request->tipo_movimiento === 'entrada') {
                $depositoNuevo->nivel_actual_litros += $request->cantidad_litros;
            } else {
                $depositoNuevo->nivel_actual_litros -= $request->cantidad_litros;
            }
            $depositoNuevo->save();

            DB::commit();

            $movimiento->load(['deposito', 'proveedor', 'cliente']);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de combustible actualizado exitosamente',
                'data' => $movimiento
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar movimiento de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MovimientoCombustible $movimiento): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Revertir el movimiento
            $deposito = Deposito::find($movimiento->deposito_id);
            if ($movimiento->tipo_movimiento === 'entrada') {
                $deposito->nivel_actual_litros -= $movimiento->cantidad_litros;
            } else {
                $deposito->nivel_actual_litros += $movimiento->cantidad_litros;
            }
            $deposito->save();

            $movimiento->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de combustible eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar movimiento de combustible',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
