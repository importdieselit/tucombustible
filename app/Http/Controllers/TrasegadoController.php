<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TrasegadoService;
use Illuminate\Support\Facades\DB;
use Exception;

class TrasegadoController extends Controller
{
    protected $trasegadoService;

    public function __construct(TrasegadoService $trasegadoService)
    {
        $this->trasegadoService = $trasegadoService;
    }

    /**
     * Muestra el historial general de Trasegados.
     */
    public function index()
    {
        // Traemos el histórico (puedes paginarlo luego)
        $trasegados = DB::table('trasegados')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('combustibles.trasegados.index', compact('trasegados'));
    }

    /**
     * Formulario para Trasegado Interno.
     */
    public function createInterno()
    {
        // Solo necesitamos las sedes y sus tanques
        $sedes = DB::table('sedes')->where('estatus', 1)->get();
        
        return view('combustibles.trasegados.create_interno', compact('sedes'));
    }

    /**
     * Formulario para Trasegado Inter-Sede.
     */
    public function createInterSede()
    {
        $sedes = DB::table('sedes')->where('activo', 1)->get();
        
        // Datos específicos para la planificación del viaje
        $choferes  = DB::table('choferes')->where('activo', 1)->get();
        $ayudantes = DB::table('ayudantes')->where('activo', 1)->get();
        $vehiculos = DB::table('vehiculos')->where('activo', 1)->get(); 
        // Nota: En la vista evaluarás por JS si el vehículo seleccionado es tipo 'chuto' para mostrar el select de cisternas.

        return view('combustibles.trasegados.create_intersede', compact('sedes', 'choferes', 'ayudantes', 'vehiculos'));
    }

    /**
     * Formulario para Trasegado Externo.
     */
    public function createExterno()
    {
        $sedes   = DB::table('sedes')->where('activo', 1)->get();
        $aliados = DB::table('aliados_comerciales')->where('activo', 1)->get();
        
        // Si el externo puede opcionalmente requerir viaje, nos traemos la flota también
        $choferes  = DB::table('choferes')->where('activo', 1)->get();
        $ayudantes = DB::table('ayudantes')->where('activo', 1)->get();
        $vehiculos = DB::table('vehiculos')->where('activo', 1)->get();

        return view('combustibles.trasegados.create_externo', compact('sedes', 'aliados', 'choferes', 'ayudantes', 'vehiculos'));
    }

    /**
     * Procesa el guardado de cualquier tipo de trasegado.
     */
    public function store(Request $request)
    {
        // Aquí puedes meter un FormRequest personalizado o validar según el tipo_trasegado
        $this->validarCamposSegunTipo($request);

        try {
            $data = $request->all();
            $data['user_id'] = auth()->id() ?? 1; // Aseguramos el usuario de la sesión

            $trasegadoId = $this->trasegadoService->procesarTrasegado($data);

            return redirect()
                ->route('combustibles.trasegados.index')
                ->with('success', "Trasegado #{$trasegadoId} registrado y procesado correctamente.");

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al procesar la operación: ' . $e->getMessage());
        }
    }

    /**
     * Validación dinámica en backend antes de tocar el Service.
     */
    protected function validarCamposSegunTipo(Request $request)
    {
        $rules = [
            'tipo_trasegado'  => 'required|in:interno,inter_sede,external',
            'cantidad_litros' => 'required|numeric|min:0.01',
        ];

        // Sumar reglas específicas según el flujo
        if ($request->tipo_trasegado === 'interno') {
            $rules['sede_origen_id']      = 'required';
            $rules['deposito_origen_id']  = 'required';
            $rules['deposito_destino_id'] = 'required|different:deposito_origen_id';
        }

        if ($request->tipo_trasegado === 'inter_sede') {
            $rules['sede_origen_id']      = 'required';
            $rules['sede_destino_id']     = 'required|different:sede_origen_id';
            $rules['deposito_origen_id']  = 'required';
            $rules['deposito_destino_id'] = 'required';
            $rules['vehiculo_id']         = 'required';
            $rules['chofer_id']           = 'required';
        }

        $request->validate($rules);
    }
}