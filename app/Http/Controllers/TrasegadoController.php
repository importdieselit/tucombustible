<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TrasegadoService;
use App\Models\Trasegado;
use App\Models\Sedes;   
use App\Models\Deposito;   
use App\Models\Cliente;
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
        $query = Trasegado::with([
            'user', 
            'sedeOrigen', 
            'depositoOrigen', 
            'sedeDestino', 
            'depositoDestino', 
            'aliado', 
            'tipoCombustible'
        ]);

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

        $trasegados = $query->orderBy('created_at', 'desc')->paginate(20);
        $sedes = Sedes::orderBy('nombre')->get();

        return view('combustibles.trasegados.index', compact('trasegados', 'sedes'));
    }

    /**
     * Formulario para Trasegado Interno.
     */
    public function createInterno()
    {
        $sedes = Sedes::where('estatus', 1)->get();
        
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
        $sedes = Sedes::where('estatus', 1)->orderBy('nombre')->get();
    
        $tanques = Deposito::leftJoin('tipos_combustible', 'depositos.tipo_combustible_id', '=', 'tipos_combustible.id')
            ->select(
                'depositos.*', 
                'tipos_combustible.nombre as combustible_nombre'
            )
            ->get();

        return view('combustibles.trasegados.create_inter_sedes', compact('sedes', 'tanques'));
    }

    /**
     * Formulario para Trasegado Externo (Préstamos con Aliados Comerciales / Entidades Externas).
     */
    public function createExterno()
    {
        $sedes = Sedes::where('estatus', 1)->orderBy('nombre')->get();

        $tanques = Deposito::leftJoin('tipos_combustible', 'depositos.tipo_combustible_id', '=', 'tipos_combustible.id')
            ->select('depositos.*', 'tipos_combustible.nombre as combustible_nombre')
            ->get();

        $aliados = Cliente::where('es_aliado_comercial', true)
            ->orderBy('nombre')
            ->get();

        return view('combustibles.trasegados.create_externo', compact('sedes', 'tanques', 'aliados'));
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

    /**
     * Valida dinámicamente según la naturaleza de la operación.
     */
    protected function validarCamposSegunTipo(Request $request)
    {
        // 1. Reglas base obligatorias para todos los tipos
        $rules = [
            'tipo_trasegado'      => 'required|in:interno,inter_sede,externo',
            'cantidad_litros'     => 'required|numeric|gt:0',
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'observaciones'       => 'nullable|string',
        ];

        // 2. Trasegado Interno
        if ($request->tipo_trasegado === 'interno') {
            $rules['sede_origen_id']      = 'required|exists:sedes,id';
            $rules['deposito_origen_id']  = 'required|exists:depositos,id';
            $rules['deposito_destino_id'] = 'required|exists:depositos,id|different:deposito_origen_id';
            $rules['bolsa_origen_tipo']   = 'required|in:general,prepagado';
            $rules['bolsa_destino_tipo']  = 'required|in:general,prepagado';

            $request->merge(['sede_destino_id' => $request->sede_origen_id]);
        }

        // 3. Trasegado Inter-Sede
        if ($request->tipo_trasegado === 'inter_sede') {
            $rules['sede_origen_id']      = 'required|exists:sedes,id';
            $rules['sede_destino_id']     = 'required|exists:sedes,id|different:sede_origen_id';
            $rules['deposito_origen_id']  = 'required|exists:depositos,id';
            $rules['deposito_destino_id'] = 'required|exists:depositos,id';
            $rules['bolsa_origen_tipo']   = 'required|in:general,prepagado';
            $rules['bolsa_destino_tipo']  = 'required|in:general,prepagado';
        }

        // 4. Trasegado Externo (Préstamos / Entradas / Salidas Externas)
        if ($request->tipo_trasegado === 'externo') {
            // Si seleccionó "OTRO" o vino vacío, convertimos a null el cliente_id
            if ($request->cliente_id === 'OTRO' || empty($request->cliente_id)) {
                $request->merge(['cliente_id' => null]);
            }

            // Normalizamos el sentido de la operación
            $operacion = $request->input('direccion_movimiento') ?? $request->input('operacion_externa');
            $request->merge(['operacion_externa' => $operacion]);

            $rules['operacion_externa'] = 'required|in:salida,entrada';
            $rules['cliente_id']        = 'nullable|exists:clientes,id';
            // entidad_externa es obligatoria SOLO SI NO se seleccionó un cliente_id del select
            $rules['entidad_externa']   = 'required_without:cliente_id|nullable|string|max:255';

            if ($operacion === 'salida') {
                $rules['sede_origen_id']     = 'required|exists:sedes,id';
                $rules['deposito_origen_id'] = 'required|exists:depositos,id';
                $rules['bolsa_origen_tipo']  = 'required|in:general,prepagado';

                $request->merge([
                    'sede_destino_id'     => null,
                    'deposito_destino_id' => null,
                    'bolsa_destino_tipo'  => null,
                ]);
            } else {
                $rules['sede_destino_id']     = 'required|exists:sedes,id';
                $rules['deposito_destino_id'] = 'required|exists:depositos,id';
                $rules['bolsa_destino_tipo']  = 'required|in:general,prepagado';

                $request->merge([
                    'sede_origen_id'     => null,
                    'deposito_origen_id' => null,
                    'bolsa_origen_tipo'  => null,
                ]);
            }
        }

        // 5. Ejecutamos la validación
        $request->validate($rules);
    }
}