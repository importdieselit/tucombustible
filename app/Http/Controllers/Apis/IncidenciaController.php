<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Incidencia;
use App\Models\Persona;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class IncidenciaController extends Controller
{
    protected $telegramService;

    public function __construct(
        TelegramNotificationService $telegramService
    ) {
        $this->telegramService = $telegramService;
    }

    /**
     * Listar incidencias del conductor autenticado
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $incidencias = Incidencia::where('conductor_id', $user->id)
                ->with(['vehiculo', 'pedido'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'incidencias' => $incidencias->map(function ($incidencia) {
                    return [
                        'id' => $incidencia->id,
                        'tipo' => $incidencia->tipo,
                        'titulo' => $incidencia->titulo,
                        'descripcion' => $incidencia->descripcion,
                        'ubicacion' => $incidencia->ubicacion,
                        'latitud' => $incidencia->latitud,
                        'longitud' => $incidencia->longitud,
                        'foto' => $incidencia->foto_url,
                        'estado' => $incidencia->estado,
                        'notas_admin' => $incidencia->notas_admin,
                        'fecha_resolucion' => $incidencia->fecha_resolucion,
                        'vehiculo_id' => $incidencia->vehiculo_id,
                        'pedido_id' => $incidencia->pedido_id,
                        'created_at' => $incidencia->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $incidencia->updated_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las incidencias',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva incidencia
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'tipo' => 'required|in:averia,accidente,otro',
                'titulo' => 'required|string|max:200',
                'descripcion' => 'required|string',
                'ubicacion' => 'nullable|string|max:300',
                'latitud' => 'nullable|numeric|between:-90,90',
                'longitud' => 'nullable|numeric|between:-180,180',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // max 5MB
                'vehiculo_id' => 'nullable|exists:vehiculos,id',
                'pedido_id' => 'nullable|exists:pedidos,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->all();
            $data['conductor_id'] = $user->id;
            $User=User::find($user->id);
            $persona=Persona::find($User->persona_id);
            $nombreUser = $persona->nombre;
            $data['estado'] = 'pendiente';

            // Manejar la subida de la foto
            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $nombreFoto = time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                $ruta = $foto->storeAs('incidencias', $nombreFoto, 'public');
                $data['foto'] = $ruta;
            }

            $incidencia = Incidencia::create($data);
            $message = "*📌 REPORTE DE INCIDENCIA*\n\n" .
                        "*Fecha:* {$incidencia->created_at}\n" .
                        "*Conductor:* {$nombreUser}\n" .
                        "*Tipo:* {$incidencia->tipo}\n" .
                        "*Título:* {$incidencia->titulo}\n" .
                        "*Ubicación:* {$incidencia->ubicacion}\n" .
                        "*Estatus:* _{$incidencia->estado}_\n\n" .
                        "--------------------------------------\n\n" .
                        "*📝 Descripción:*\n" .
                        $incidencia->descripcion . "\n\n" .
                        "*📎 Evidencia:* Foto adjunta.\n";

           
            $this->telegramService->sendPhotoOrden($request->file('foto'), $message);

            
            return response()->json([
                'success' => true,
                'message' => 'Incidencia reportada exitosamente',
                'incidencia' => [
                    'id' => $incidencia->id,
                    'tipo' => $incidencia->tipo,
                    'titulo' => $incidencia->titulo,
                    'descripcion' => $incidencia->descripcion,
                    'ubicacion' => $incidencia->ubicacion,
                    'foto' => $incidencia->foto_url,
                    'estado' => $incidencia->estado,
                    'created_at' => $incidencia->created_at->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la incidencia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver detalle de una incidencia
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $incidencia = Incidencia::where('id', $id)
                ->where('conductor_id', $user->id)
                ->with(['vehiculo', 'pedido'])
                ->first();

            if (!$incidencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incidencia no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'incidencia' => [
                    'id' => $incidencia->id,
                    'tipo' => $incidencia->tipo,
                    'titulo' => $incidencia->titulo,
                    'descripcion' => $incidencia->descripcion,
                    'ubicacion' => $incidencia->ubicacion,
                    'latitud' => $incidencia->latitud,
                    'longitud' => $incidencia->longitud,
                    'foto' => $incidencia->foto_url,
                    'estado' => $incidencia->estado,
                    'notas_admin' => $incidencia->notas_admin,
                    'fecha_resolucion' => $incidencia->fecha_resolucion,
                    'vehiculo' => $incidencia->vehiculo,
                    'pedido' => $incidencia->pedido,
                    'created_at' => $incidencia->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $incidencia->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la incidencia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar todas las incidencias (solo admin)
     */
    public function indexAdmin(Request $request)
    {
        try {
            $query = Incidencia::with(['conductor', 'vehiculo', 'pedido'])
                ->orderBy('created_at', 'desc');

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->has('conductor_id')) {
                $query->where('conductor_id', $request->conductor_id);
            }

            $incidencias = $query->get();

            return response()->json([
                'success' => true,
                'incidencias' => $incidencias->map(function ($incidencia) {
                    return [
                        'id' => $incidencia->id,
                        'tipo' => $incidencia->tipo,
                        'titulo' => $incidencia->titulo,
                        'descripcion' => $incidencia->descripcion,
                        'ubicacion' => $incidencia->ubicacion,
                        'foto' => $incidencia->foto_url,
                        'estado' => $incidencia->estado,
                        'conductor' => [
                            'id' => $incidencia->conductor->id,
                            'nombre' => $incidencia->conductor->name,
                        ],
                        'vehiculo_id' => $incidencia->vehiculo_id,
                        'pedido_id' => $incidencia->pedido_id,
                        'created_at' => $incidencia->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las incidencias',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar estado de incidencia (solo admin)
     */
    public function updateEstado(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:pendiente,en_revision,resuelto,cancelado',
                'notas_admin' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $incidencia = Incidencia::find($id);

            if (!$incidencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incidencia no encontrada',
                ], 404);
            }

            $incidencia->estado = $request->estado;
            
            if ($request->has('notas_admin')) {
                $incidencia->notas_admin = $request->notas_admin;
            }

            if ($request->estado === 'resuelto' && !$incidencia->fecha_resolucion) {
                $incidencia->fecha_resolucion = now();
            }

            $incidencia->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado de incidencia actualizado',
                'incidencia' => $incidencia,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

