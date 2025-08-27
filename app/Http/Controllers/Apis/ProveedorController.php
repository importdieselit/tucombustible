<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProveedorController extends Controller
{
    /**
     * Obtener todos los proveedores
     */
    public function index()
    {
        try {
            $proveedores = DB::table('proveedores')
                ->select([
                    'id',
                    'nombre',
                    'rif',
                    'telefono',
                    'email',
                    'direccion',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $proveedores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un proveedor específico
     */
    public function show($id)
    {
        try {
            $proveedor = DB::table('proveedores')
                ->select([
                    'id',
                    'nombre',
                    'rif',
                    'telefono',
                    'email',
                    'direccion',
                    'created_at',
                    'updated_at'
                ])
                ->where('id', $id)
                ->first();

            if (!$proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $proveedor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo proveedor
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:proveedores,nombre',
                'rif' => 'nullable|string|max:50|unique:proveedores,rif',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'direccion' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $proveedorId = DB::table('proveedores')->insertGetId([
                'nombre' => $request->nombre,
                'rif' => $request->rif,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'direccion' => $request->direccion,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $proveedor = DB::table('proveedores')
                ->select([
                    'id',
                    'nombre',
                    'rif',
                    'telefono',
                    'email',
                    'direccion',
                    'created_at',
                    'updated_at'
                ])
                ->where('id', $proveedorId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'data' => $proveedor
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear proveedor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un proveedor
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:proveedores,nombre,' . $id,
                'rif' => 'nullable|string|max:50|unique:proveedores,rif,' . $id,
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'direccion' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que el proveedor existe
            $proveedorExistente = DB::table('proveedores')
                ->where('id', $id)
                ->first();

            if (!$proveedorExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            DB::table('proveedores')
                ->where('id', $id)
                ->update([
                    'nombre' => $request->nombre,
                    'rif' => $request->rif,
                    'telefono' => $request->telefono,
                    'email' => $request->email,
                    'direccion' => $request->direccion,
                    'updated_at' => now(),
                ]);

            $proveedor = DB::table('proveedores')
                ->select([
                    'id',
                    'nombre',
                    'rif',
                    'telefono',
                    'email',
                    'direccion',
                    'created_at',
                    'updated_at'
                ])
                ->where('id', $id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $proveedor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar proveedor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un proveedor
     */
    public function destroy($id)
    {
        try {
            // Verificar que el proveedor existe
            $proveedor = DB::table('proveedores')
                ->where('id', $id)
                ->first();

            if (!$proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            // Verificar si el proveedor está siendo usado en solicitudes de compra
            $solicitudesConProveedor = DB::table('inventario_compras')
                ->where('id_proveedor', $id)
                ->count();

            if ($solicitudesConProveedor > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el proveedor porque está siendo usado en solicitudes de compra'
                ], 400);
            }

            DB::table('proveedores')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Proveedor eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proveedor: ' . $e->getMessage()
            ], 500);
        }
    }
} 