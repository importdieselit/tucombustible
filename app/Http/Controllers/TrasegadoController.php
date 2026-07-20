<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TrasegadoService;
use App\Models\Trasegado;
use App\Models\Sedes;   
use App\Models\Deposito;   
use Exception;

class TrasegadoController extends Controller
{
    protected $trasegadoService;

    public function __construct(TrasegadoService $trasegadoService)
    {
        $this->trasegadoService = $trasegadoService;
    }

    /**
     * Muestra el historial general de Trasegados con filtros activos.
     */
    public function index(Request $request)
    {
        // Iniciamos la consulta con Eager Loading para todas las relaciones de la vista
        $query = Trasegado::with([
            'user', 
            'sedeOrigen', 
            'depositoOrigen', 
            'sedeDestino', 
            'depositoDestino', 
            'aliado', 
            'tipoCombustible'
        ]);

        // Procesamos los filtros avanzados que vienen de la vista
        if ($request->filled('id_sede_origen')) {
            $query->where('sede_origen_id', $request->id_sede_origen);
        }

        if ($request->filled('id_sede_destino')) {
            $query->where('sede_destino_id', $request->id_sede_destino);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Paginamos el resultado ordenado cronológicamente
        $trasegados = $query->orderBy('created_at', 'desc')->paginate(20);

        // Traemos las sedes para que el SELECT del filtro se llene correctamente
        $sedes = Sedes::orderBy('nombre')->get();

        return view('combustibles.trasegados.index', compact('trasegados', 'sedes'));
    }

    /**
     * Formulario para Trasegado Interno.
     */
    public function createInterno()
    {
        $sedes = Sedes::where('estatus', 1)->get();
        
        // CORREGIDO: Iniciamos el join directamente desde el modelo Eloquent
        $tanques = Deposito::leftJoin('tipos_combustible', 'depositos.tipo_combustible_id', '=', 'tipos_combustible.id')
            ->select('depositos.*', 'tipos_combustible.nombre as combustible_nombre')
            ->get();
        
        return view('combustibles.trasegados.create_interno', compact('sedes', 'tanques'));
    }

    /**
     * Formulario para Trasegado Inter-Sede.
     */
    public function createInterSede()
    {

    }

    /**
     * Formulario para Trasegado Externo.
     */
    public function createExterno()
    {
        
    }

    /**
     * Procesa el guardado de cualquier tipo de trasegado.
     */
    public function store(Request $request)
    {
        $this->validarCamposSegunTipo($request);

        try {
            $data = $request->all();
            $data['user_id'] = auth()->id() ?? 1;

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

    public function destroy($id)
    {
        try {
            $this->trasegadoService->anularTrasegado($id, auth()->id());

            return redirect()
                ->route('combustibles.trasegados.index')
                ->with('success', "El trasegado #{$id} fue anulado y el inventario fue restablecido.");

        } catch (Exception $e) {
            return back()->with('error', 'No se pudo anular la operación: ' . $e->getMessage());
        }
    }

    protected function validarCamposSegunTipo(Request $request)
    {
        $rules = [
            'tipo_trasegado'      => 'required|in:interno,inter_sede,externo', 
            'cantidad_litros'     => 'required|numeric|min:0.01',
            'tipo_combustible_id' => 'required',
            'bolsa_origen_tipo'   => 'required',
            'bolsa_destino_tipo'  => 'required',
        ];

        if ($request->tipo_trasegado === 'interno') {
            $rules['sede_origen_id']      = 'required';
            $rules['deposito_origen_id']  = 'required';
            $rules['deposito_destino_id'] = 'required|different:deposito_origen_id';

            // Sincronizamos la sede destino para cumplir con el esquema del Service
            $request->merge(['sede_destino_id' => $request->sede_origen_id]);
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