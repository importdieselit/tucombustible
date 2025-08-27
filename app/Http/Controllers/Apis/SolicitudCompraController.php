<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SolicitudCompraController extends Controller
{
    /**
     * Obtener todas las solicitudes de compra del usuario autenticado
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            $solicitudes = DB::table('inventario_compras as ic')
                ->leftJoin('proveedores as p', 'ic.id_proveedor', '=', 'p.id')
                ->leftJoin('users as u', 'ic.id_usuario', '=', 'u.id')
                ->leftJoin('personas as per', 'u.persona_id', '=', 'per.id')
                ->select([
                    'ic.id_inventario_compra as id',
                    'ic.id_usuario',
                    'ic.nro_orden',
                    'ic.destino',
                    'ic.id_auto',
                    'ic.id_proveedor',
                    'ic.observacion',
                    'ic.fecha_in',
                    'ic.compra',
                    'ic.anulacion',
                    'ic.id_emisor',
                    'ic.tipo',
                    'ic.estatus',
                    'ic.created_at',
                    'ic.updated_at',
                    'p.nombre as nombre_proveedor',
                    DB::raw("CONCAT(per.first_name, ' ', per.last_name) as nombre_usuario")
                ])
                ->where('ic.id_usuario', $user->id)
                ->orderBy('ic.created_at', 'desc')
                ->get();

            // Transformar los datos para el frontend
            $solicitudesTransformadas = $solicitudes->map(function ($solicitud) {
                $compraData = json_decode($solicitud->compra, true);
                
                return [
                    'id' => $solicitud->id,
                    'nro_orden' => $solicitud->nro_orden,
                    'destino' => $solicitud->destino,
                    'proveedor' => $solicitud->nombre_proveedor,
                    'observacion' => $solicitud->observacion,
                    'fecha_solicitud' => $solicitud->fecha_in,
                    'compra' => $compraData,
                    'estatus' => $this->mapEstatus($solicitud->estatus),
                    'anulacion' => $solicitud->anulacion,
                    'created_at' => $solicitud->created_at,
                    'updated_at' => $solicitud->updated_at,
                    'usuario' => $solicitud->nombre_usuario,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $solicitudesTransformadas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes de compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una solicitud de compra específica
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            
            $solicitud = DB::table('inventario_compras as ic')
                ->leftJoin('proveedores as p', 'ic.id_proveedor', '=', 'p.id')
                ->leftJoin('users as u', 'ic.id_usuario', '=', 'u.id')
                ->leftJoin('personas as per', 'u.persona_id', '=', 'per.id')
                ->select([
                    'ic.id_inventario_compra as id',
                    'ic.id_usuario',
                    'ic.nro_orden',
                    'ic.destino',
                    'ic.id_auto',
                    'ic.id_proveedor',
                    'ic.observacion',
                    'ic.fecha_in',
                    'ic.compra',
                    'ic.anulacion',
                    'ic.id_emisor',
                    'ic.tipo',
                    'ic.estatus',
                    'ic.created_at',
                    'ic.updated_at',
                    'p.nombre as nombre_proveedor',
                    DB::raw("CONCAT(per.first_name, ' ', per.last_name) as nombre_usuario")
                ])
                ->where('ic.id_inventario_compra', $id)
                ->where('ic.id_usuario', $user->id)
                ->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud de compra no encontrada'
                ], 404);
            }

            $compraData = json_decode($solicitud->compra, true);

            $solicitudTransformada = [
                'id' => $solicitud->id,
                'nro_orden' => $solicitud->nro_orden,
                'destino' => $solicitud->destino,
                'proveedor' => $solicitud->nombre_proveedor,
                'observacion' => $solicitud->observacion,
                'fecha_solicitud' => $solicitud->fecha_in,
                'compra' => $compraData,
                'estatus' => $this->mapEstatus($solicitud->estatus),
                'anulacion' => $solicitud->anulacion,
                'created_at' => $solicitud->created_at,
                'updated_at' => $solicitud->updated_at,
                'usuario' => $solicitud->nombre_usuario,
            ];

            return response()->json([
                'success' => true,
                'data' => $solicitudTransformada
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitud de compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva solicitud de compra
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'destino' => 'required|integer',
                'proveedor' => 'nullable|string',
                'observacion' => 'required|string|max:500',
                'compra' => 'required|array',
                'compra.combustible' => 'required|string',
                'compra.cantidad' => 'required|numeric|min:0',
                'compra.unidad' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            // Generar número de orden
            $ultimoNroOrden = DB::table('inventario_compras')
                ->max('nro_orden');
            $nroOrden = ($ultimoNroOrden ?? 0) + 1;

            // Buscar proveedor por nombre si se proporciona
            $idProveedor = null;
            if ($request->proveedor) {
                $proveedor = DB::table('proveedores')
                    ->where('nombre', $request->proveedor)
                    ->first();
                $idProveedor = $proveedor ? $proveedor->id : null;
            }

            $solicitudId = DB::table('inventario_compras')->insertGetId([
                'id_usuario' => $user->id,
                'nro_orden' => $nroOrden,
                'destino' => $request->destino,
                'id_proveedor' => $idProveedor,
                'observacion' => $request->observacion,
                'fecha_in' => date('Ymd'),
                'compra' => json_encode($request->compra),
                'estatus' => 1, // 1 = pendiente
                'anulacion' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Obtener la solicitud creada
            $solicitudCreada = DB::table('inventario_compras as ic')
                ->leftJoin('proveedores as p', 'ic.id_proveedor', '=', 'p.id')
                ->select([
                    'ic.id_inventario_compra as id',
                    'ic.nro_orden',
                    'ic.destino',
                    'ic.observacion',
                    'ic.fecha_in',
                    'ic.compra',
                    'ic.estatus',
                    'ic.anulacion',
                    'ic.created_at',
                    'ic.updated_at',
                    'p.nombre as nombre_proveedor',
                ])
                ->where('ic.id_inventario_compra', $solicitudId)
                ->first();

            $compraData = json_decode($solicitudCreada->compra, true);

            $solicitudTransformada = [
                'id' => $solicitudCreada->id,
                'nro_orden' => $solicitudCreada->nro_orden,
                'destino' => $solicitudCreada->destino,
                'proveedor' => $solicitudCreada->nombre_proveedor,
                'observacion' => $solicitudCreada->observacion,
                'fecha_solicitud' => $solicitudCreada->fecha_in,
                'compra' => $compraData,
                'estatus' => $this->mapEstatus($solicitudCreada->estatus),
                'anulacion' => $solicitudCreada->anulacion,
                'created_at' => $solicitudCreada->created_at,
                'updated_at' => $solicitudCreada->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de compra creada exitosamente',
                'data' => $solicitudTransformada
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear solicitud de compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una solicitud de compra
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'destino' => 'required|integer',
                'proveedor' => 'nullable|string',
                'observacion' => 'required|string|max:500',
                'compra' => 'required|array',
                'compra.combustible' => 'required|string',
                'compra.cantidad' => 'required|numeric|min:0',
                'compra.unidad' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            // Verificar que la solicitud pertenece al usuario
            $solicitud = DB::table('inventario_compras')
                ->where('id_inventario_compra', $id)
                ->where('id_usuario', $user->id)
                ->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud de compra no encontrada'
                ], 404);
            }

            // Buscar proveedor por nombre si se proporciona
            $idProveedor = null;
            if ($request->proveedor) {
                $proveedor = DB::table('proveedores')
                    ->where('nombre', $request->proveedor)
                    ->first();
                $idProveedor = $proveedor ? $proveedor->id : null;
            }

            DB::table('inventario_compras')
                ->where('id_inventario_compra', $id)
                ->update([
                    'destino' => $request->destino,
                    'id_proveedor' => $idProveedor,
                    'observacion' => $request->observacion,
                    'compra' => json_encode($request->compra),
                    'updated_at' => now(),
                ]);

            // Obtener la solicitud actualizada
            $solicitudActualizada = DB::table('inventario_compras as ic')
                ->leftJoin('proveedores as p', 'ic.id_proveedor', '=', 'p.id')
                ->select([
                    'ic.id_inventario_compra as id',
                    'ic.nro_orden',
                    'ic.destino',
                    'ic.observacion',
                    'ic.fecha_in',
                    'ic.compra',
                    'ic.estatus',
                    'ic.anulacion',
                    'ic.created_at',
                    'ic.updated_at',
                    'p.nombre as nombre_proveedor',
                ])
                ->where('ic.id_inventario_compra', $id)
                ->first();

            $compraData = json_decode($solicitudActualizada->compra, true);

            $solicitudTransformada = [
                'id' => $solicitudActualizada->id,
                'nro_orden' => $solicitudActualizada->nro_orden,
                'destino' => $solicitudActualizada->destino,
                'proveedor' => $solicitudActualizada->nombre_proveedor,
                'observacion' => $solicitudActualizada->observacion,
                'fecha_solicitud' => $solicitudActualizada->fecha_in,
                'compra' => $compraData,
                'estatus' => $this->mapEstatus($solicitudActualizada->estatus),
                'anulacion' => $solicitudActualizada->anulacion,
                'created_at' => $solicitudActualizada->created_at,
                'updated_at' => $solicitudActualizada->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de compra actualizada exitosamente',
                'data' => $solicitudTransformada
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar solicitud de compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una solicitud de compra
     */
    public function cancelar($id)
    {
        try {
            $user = Auth::user();
            
            // Verificar que la solicitud pertenece al usuario
            $solicitud = DB::table('inventario_compras')
                ->where('id_inventario_compra', $id)
                ->where('id_usuario', $user->id)
                ->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud de compra no encontrada'
                ], 404);
            }

            if ($solicitud->estatus !== 1) { // 1 = pendiente
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cancelar solicitudes pendientes'
                ], 400);
            }

            DB::table('inventario_compras')
                ->where('id_inventario_compra', $id)
                ->update([
                    'estatus' => 5, // 5 = cancelado
                    'anulacion' => 'Cancelada por el usuario',
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de compra cancelada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar solicitud de compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mapear estatus de la base de datos al formato del frontend
     */
    private function mapEstatus($estatus)
    {
        switch ($estatus) {
            case 1:
                return 'pendiente';
            case 2:
                return 'aprobado';
            case 3:
                return 'en_proceso';
            case 4:
                return 'completado';
            case 5:
                return 'cancelado';
            default:
                return 'pendiente';
        }
    }
} 