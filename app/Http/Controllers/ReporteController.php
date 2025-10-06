<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\TipoReporte;
use App\Models\ReporteEstatusHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\BaseController; // Asumiendo que esta es la ruta a tu BaseController

class ReporteController extends BaseController 
{
    // ID del módulo de Reportes (Necesario para el método canAccess del BaseController)
    // private $moduloIdReportes = 99; 

    public function __construct()
    {
        $this->model = new Reporte();
        parent::__construct(); 
    }

    // Sobrescribe el método 'create' para cargar los catálogos en la vista del formulario
    public function create()
    {
        // Aquí se aplicaría la validación de permisos:
        // if (!auth()->user()->canAccess('create', $this->moduloIdReportes)) { abort(403); } 

        $tiposReporte = TipoReporte::where('activo', true)->pluck('nombre_tipo', 'id');
        
        return view('reportes.create', compact('tiposReporte'));
    }

    // El método index puede ser heredado o modificado para añadir filtros por estatus.

    public function store(Request $request)
    {
        // 1. Validación de datos y archivo opcional (máx 2MB)
        $request->validate([
            'id_tipo_reporte' => 'required|exists:catalogo_tipo_reporte,id',
            'descripcion'        => 'required|string|max:5000',
            'lugar_reporte'      => 'required|string|max:255',
            'imaen'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
            // Añadir validación para 'origen_cliente_id' si es necesario
        ]);

        DB::beginTransaction();
        try {
            $path = null;
            
            // 2. Manejo de la imagen (Guardado en storage/app/public/reportes/evidencia)
            if ($request->hasFile('imagen')) {
                $path = $request->file('imaen')->store('reportes/evidencia', 'public');
            }

            // 3. Creación de la Reporte (Ticket)
            $reporte = Reporte::create([
                'id_tipo_reporte'    => $request->id_tipo_reporte,
                'origen_usuario_id'     => Auth::check() ? Auth::id() : null, // ID del usuario autenticado
                'descripcion'           => $request->descripcion,
                'lugar_reporte'         => $request->lugar_reporte,
                'url_imaen'  => $path, 
                'estatus_actual'        => 'ABIERTO', // Estatus inicial por defecto
                // 'origen_cliente_id' => $request->origen_cliente_id ?? null,
            ]);

            // 4. Registro del Historial inicial (Auditoría)
            ReporteEstatusHistorial::create([
                'reporte_id'       => $reporte->id,
                'usuario_modifica_id' => Auth::id(), 
                'estatus_anterior'    => 'N/A', 
                'estatus_nuevo'       => 'ABIERTO', 
                'nota_cambio'         => 'Reporte reportada y abierta.',
            ]);

            DB::commit();
            Session::flash('success', 'Reporte #' . $reporte->id . ' reportada exitosamente.');
            return redirect()->route('reportes.index');

        } catch (\Exception $e) {
            DB::rollback();
            // Si hay error, eliminar la imagen si se subió
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            Session::flash('error', 'Error al registrar la reporte: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
    }

    public function updateStatus(Request $request, Reporte $reporte)
    {
        // 1. Validación: Requiere el nuevo estatus y una razón para el historial
        $request->validate([
            'nuevo_estatus' => 'required|in:ABIERTO,EN_PROCESO,CERRADO',
            'nota_cambio'   => 'required|string|max:1000',
        ]);

        $estatusAnterior = $reporte->estatus_actual;
        $nuevoEstatus = strtoupper($request->nuevo_estatus);
        $usuarioId = Auth::id();

        // Evitar cambios si el estatus es el mismo
        if ($estatusAnterior === $nuevoEstatus) {
            Session::flash('warning', 'El estatus ya es ' . $nuevoEstatus . '.');
            return Redirect::back();
        }
        
        // 2. Lógica de Transición (Reglas del Flujo)
        if (!$this->isValidTransition($estatusAnterior, $nuevoEstatus)) {
            Session::flash('error', "Transición de estatus no permitida: De $estatusAnterior a $nuevoEstatus.");
            return Redirect::back();
        }

        DB::beginTransaction();
        try {
            // 3. Actualización de la Reporte Principal
            $reporte->estatus_actual = $nuevoEstatus;
            $reporte->save();

            // 4. Registro en el Historial de Auditoría
            ReporteEstatusHistorial::create([
                'reporte_id'       => $reporte->id,
                'usuario_modifica_id' => $usuarioId,
                'estatus_anterior'    => $estatusAnterior,
                'estatus_nuevo'       => $nuevoEstatus,
                'nota_cambio'         => $request->nota_cambio,
            ]);

            DB::commit();
            Session::flash('success', "Estatus de Reporte #{$reporte->id} actualizado a $nuevoEstatus.");
            return Redirect::back();

        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('error', 'Error al actualizar el estatus: ' . $e->getMessage());
            return Redirect::back();
        }
    }

    /**
     * Define las reglas del Ciclo de Vida del Ticket.
     */
    private function isValidTransition(string $anterior, string $nuevo): bool
    {
        // Solo permite las transiciones lógicas del proceso
        switch ($anterior) {
            case 'ABIERTO':
                // Puede pasar a ser procesado o, excepcionalmente, cerrado si el reporte fue inválido.
                return in_array($nuevo, ['EN_PROCESO', 'CERRADO']);
            case 'EN_PROCESO':
                // Solo puede pasar a ser cerrado al finalizar la gestión.
                return $nuevo === 'CERRADO';
            case 'CERRADO':
                // Una vez cerrado, no se puede reabrir directamente.
                return false; 
            default:
                return false;
        }
    }
/**
     * Marca la reporte como REQUIERE OT, simula la creación de la Orden de Trabajo
     * y registra el historial.
     * @param \App\Models\Reporte $reporte
     * @return \Illuminate\Http\RedirectResponse
     */



    public function generarOT(Reporte $reporte)
    {
        // 1. Validaciones: Estructuras y Reutilización
        if ($reporte->estatus_actual === 'CERRADO') {
            Session::flash('error', 'No se puede generar una Orden de Trabajo desde una reporte cerrada.');
            return Redirect::back();
        }

        if ($reporte->requiere_ot) {
            Session::flash('warning', 'Esta reporte ya tiene una Orden de Trabajo asociada (#'.$reporte->orden_trabajo_id.').');
            return Redirect::back();
        }

        DB::beginTransaction();
        try {
            $usuarioId = Auth::id();
            $estatusAnterior = $reporte->estatus_actual;
            $nuevoEstatus = 'EN_PROCESO'; 
            
            // --- SIMULACIÓN DE CREACIÓN DE ORDEN DE TRABAJO ---
            // IMPORTANTE: En un sistema real, el módulo de OT iría aquí.
            // Aquí se llamaría a 'OrdenTrabajo::create()' y se obtendría el ID real.
            $newOtId = OrdenController::generateOrdenCode();
            // ----------------------------------------------------

            // 2. Actualizar la Reporte Principal
            $reporte->requiere_ot = true;
            $reporte->orden_trabajo_id = $newOtId;
            
            // Si la reporte estaba 'ABIERTO', forzamos el estatus a 'EN_PROCESO' 
            // para reflejar que ya hay acción tomada (la generación de la OT).
            if ($estatusAnterior === 'ABIERTO') {
                 $reporte->estatus_actual = $nuevoEstatus;
            }
            $reporte->save();   

            // 3. Registrar el Historial de Auditoría
            $nota = "Orden de Trabajo #{$newOtId} generada y vinculada. Estatus actualizado a {$reporte->estatus_actual}.";

            ReporteEstatusHistorial::create([
                'reporte_id'       => $reporte->id,
                'usuario_modifica_id' => $usuarioId,
                'estatus_anterior'    => $estatusAnterior,
                'estatus_nuevo'       => $reporte->estatus_actual,
                'nota_cambio'         => $nota,
            ]);

            DB::commit();
            Session::flash('success', "Orden de Trabajo #{$newOtId} generada exitosamente. Reporte #{$reporte->id} marcada como EN PROCESO.");
            return Redirect::back();

        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('error', 'Error al generar la Orden de Trabajo: ' . $e->getMessage());
            return Redirect::back();
        }
    }

    // En ReporteController.php, añade:
    public function show(Reporte $reporte)
    {
        // Carga las relaciones de historial y usuario que reportó
        $reporte->load(['historialEstatus.usuarioModifica', 'reportadoPor', 'tipo']); 
        
        return view('reportes.show', compact('reporte'));
    }
    // Continuamos con el método para generar la OT en el siguiente paso.
}
