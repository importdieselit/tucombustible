<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\Deposito;
use App\Models\Viaje;
use Carbon\Carbon;

class SalaControlController extends Controller
{
    /**
     * Renderiza la estructura base (pantalla dividida).
     */
    public function index(Request $request)
    {
        $token = null;
        if ($request->has('tv_init_token')) {
            $token = $request->query('tv_init_token');
            // Lo guardamos en la sesión para persistencia interna
           
        }else{
            // Capturamos el token (si existe) para inyectarlo en el JavaScript
            $token = $request->header('X-TV-Token');
        }
        session(['tv_token' => $token]);
        return view('vehiculo.sala_control', compact('token'));
    }



    /**
     * Endpoint unificado: Trae las coordenadas GPS y renderiza el partial de disponibilidad.
     */
    public function getDataStream(Request $request)
    {
        // =========================================================
        // 1. DATA PARA EL MAPA
        // =========================================================
        $unidadesMapa = Vehiculo::miFlota()->with(['isMarca', 'isModelo'])
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get();

        $fechaInicio = $request->filled('fecha_inicio') 
        ? Carbon::parse($request->fecha_inicio)->startOfDay() 
        : Carbon::now()->startOfDay();

        $fechaFin = $request->filled('fecha_fin') 
        ? Carbon::parse($request->fecha_fin)->endOfDay() 
        : Carbon::now()->addDays(2)->endOfDay();

        // =========================================================
        // 2. DATA PARA EL REPORTE Y GRÁFICOS
        // =========================================================
        $today = now();
        $data = Vehiculo::miFlota()->with(['tipoVehiculo', 'cisternaAcoplada', 'ordenActiva'])->where('id_cliente', 348)->get();

        $total = $data->count();
        $dataAcsa = $data->where('id_cliente', 348)->whereIn('tipo', [1,2,3,5]);

        // 2. Realizamos los conteos sobre la data ya filtrada
       // $total           = $dataAcsa->count();
        $enRuta          = $dataAcsa->where('estatus', 2)->count();
        $operativosCount = $dataAcsa->where('estatus', 1)->count();
        $fallaCount      = $dataAcsa->whereIn('estatus', [3, 4, 5])->count();
        $porcentajeDisponibilidad = $total > 0 ? round(($operativosCount + $enRuta) / $total * 100) : 0;
        
        
        $disponiblidadCombustible =  Deposito::where('id','!=', 3)->selectRaw('SUM(nivel_actual_litros) as total_combustible, sum(capacidad_litros) as capacidad_total')->first();
        
        // Segmentación de Flota
        $cisternas = $data->whereIn('tipo', [2,4,5]);
        $camiones = $data->where('tipo', 1);
        $chutos = $data->whereIn('tipo', [3]);
         $fallaCount = $data->whereIn('estatus', [3,4,5])->count();
        
        $ligero = Vehiculo::misVehiculos()->with(['tipoVehiculo', 'ordenActiva'])->where('tipo', 6)->get();

        // Conteos para colecciones de la vista Partial
        $totalCisternas = $cisternas->count();
        $totalCamiones = $camiones->count();
        $totalChutos = $chutos->count();
        $totalLivianos = $ligero->count();

        $cisternasFalla = $cisternas->where('estatus', '>', 2);
        $camionesFalla = $camiones->where('estatus', '>', 2);
        $chutosFalla = $chutos->where('estatus', '>', 2);
        $camionetasFalla = $ligero->where('estatus', '>', 2);

        $cisternasOperativas = $cisternas->where('estatus', 1);
        $camionesOperativos = $camiones->where('estatus', 1);
        $chutosOperativos = $chutos->where('estatus', 1);
        $camionetasOperativas = $ligero->where('estatus', 1);

        $chutosEnRuta = $chutos->where('estatus', 2);
        $camionesEnRuta = $camiones->where('estatus', 2);
        $cisternasEnRuta = $cisternas->where('estatus', 2);
        $camionetasEnRuta = $ligero->where('estatus', 2);

        // 1. Eager Loading: Traemos relaciones necesarias para evitar N+1
    $viajesRaw = Viaje::with([
            'vehiculo', 'chofer', 'producto', 'despachos.cliente', 
            'cliente', 'cisternaAcoplada'
        ])
        ->whereBetween('fecha_salida', [$fechaInicio, $fechaFin])->orderBy('fecha_salida', 'asc')
        ->get();

    // 2. Procesamiento y Enriquecimiento de la data
    $viajesDelDia = $viajesRaw->map(function($v) {
        $destinoRaw = strtoupper($v->destino_ciudad);
        $v->es_flete = str_contains($destinoRaw, 'FLETE');
        $v->es_despacho = is_null($v->litros);
        $v->es_carga = !$v->es_despacho && !$v->es_flete;

        // Limpieza de destino
        $v->destino_limpio = trim(str_ireplace(['FLETE', ' ->'], ['', ''], $v->destino_ciudad));

        // Cálculo de Litros Totales (Centralizado)
        $v->litros_totales = $v->es_despacho 
            ? $v->despachos->sum('litros') 
            : ($v->litros ?? 0);

        // Lógica de Jerarquía de Cliente
        $clienteFinal = null;
        if (!$v->es_carga) {
            // A. Cliente directo del viaje
            $clienteFinal = $v->cliente ? ($v->cliente->alias ?? $v->cliente->nombre) : null;

            // B. Si no hay, buscar en el primer despacho que tenga cliente
            if (!$clienteFinal && $v->despachos->isNotEmpty()) {
                $conCliente = $v->despachos->whereNotNull('cliente_id')->first();
                if ($conCliente && $conCliente->cliente) {
                    $clienteFinal = $conCliente->cliente->alias ?? $conCliente->cliente->nombre;
                }else{
                    // Si no hay cliente_id, pero hay otro_cliente en el despacho
                    $conOtroCliente = $v->despachos->whereNotNull('otro_cliente')->first();
                    if ($conOtroCliente) {
                        $clienteFinal = $conOtroCliente->otro_cliente;
                    }
                }
            }

            // C. Si aún no hay, usar el campo manual
            if (!$clienteFinal) {
                $clienteFinal = $v->otro_cliente;
            }
        }
        $v->cliente_reporte = $clienteFinal;

        

        return $v;
    });

         $fletes = $viajesDelDia->filter(fn($v) => $v->es_flete);
        $operacionesBase = $viajesDelDia->reject(fn($v) => $v->es_flete);
        $cargas = $operacionesBase->where('es_carga', true);
        $despachos = $operacionesBase->where('es_despacho', true);


        // 5. Estadísticas para las Cards (Usando los litros ya procesados)
        $totalDisponibles = Deposito::sum('nivel_actual_litros');
        $tanque00=Deposito::where('serial', '00')->first()?->nivel_actual_litros ?? 0;

        $totalDespachados = $despachos->whereIn('status', ['EN RUTA', 'COMPLETADO'])
            ->sum('litros_totales');

        $totalCarga = $cargas->whereIn('status', ['EN RUTA', 'COMPLETADO'])
            ->sum('litros_totales');

        $totalProgDespacho = $despachos->where('status', 'Programado')
            ->sum('litros_totales');

        $totalProgCarga = $cargas->where('status', 'Programado')
            ->sum('litros_totales');
        
        $stats = [
            'disponibles' => $totalDisponibles,
            'despachados' => $totalDespachados,
            'cargas'      => $totalCarga,
            'prog_desp'   => $totalProgDespacho,
            'prog_carg'   => $totalProgCarga,
            'tanque00'    => $tanque00
        ];

        // =========================================================
        // 3. RENDERIZADO Y RESPUESTA JSON
        // =========================================================
        $htmlDashboard = view('vehiculo.partials._tabla_disponibilidad_tv', compact(
            'today', 'cisternasFalla', 'enRuta', 'totalCisternas', 'camionesFalla',  
            'camionetasFalla', 'camionetasOperativas', 'totalLivianos', 'totalCamiones', 'totalChutos',
            'chutosFalla', 'chutosOperativos', 'camionesOperativos', 
            'total', 'operativosCount', 'fallaCount', 'porcentajeDisponibilidad', 'cisternasOperativas',
            'chutosEnRuta', 'camionetasEnRuta', 'camionesEnRuta', 'cisternasEnRuta', 'tanque00', 'disponiblidadCombustible',
            'stats'

        ))->render();

        $cssPath = public_path('css/noc_tv.css');
        $jsPath = public_path('js/noc_tv.js');
        
        $cssVersion = file_exists($cssPath) ? (string)filemtime($cssPath) : '1';
        $jsVersion = file_exists($jsPath) ? (string)filemtime($jsPath) : '1';

        return response()->json([
            'unidades' => $unidadesMapa,
            'html_dashboard' => $htmlDashboard,
            'css_version'    => $cssVersion,
            'js_version'     => $jsVersion, 
            'charts' => [
                'global' => [
                    'operativos' => $operativosCount,
                    'enRuta' => $enRuta,
                    'fallas' => $fallaCount
                ],
                'segmentos' => [
                    'operativos' => [
                        $chutosOperativos->count(),
                        $camionesOperativos->count(),
                        $cisternasOperativas->count(),
                        $camionetasOperativas->count()
                    ],
                    'enRuta' => [
                        $chutosEnRuta->count(),
                        $camionesEnRuta->count(),
                        $cisternasEnRuta->count(),
                        $camionetasEnRuta->count()
                    ],
                    'fallas' => [
                        $chutosFalla->count(),
                        $camionesFalla->count(),
                        $cisternasFalla->count(),
                        $camionetasFalla->count()
                    ]
                ]
            ]
        ]);
    }
}
