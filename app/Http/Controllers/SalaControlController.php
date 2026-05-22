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
        // Capturamos el token (si existe) para inyectarlo en el JavaScript
        $token = $request->header('X-TV-Token');
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

        // =========================================================
        // 2. DATA PARA EL REPORTE Y GRÁFICOS
        // =========================================================
        $today = now();
        $data = Vehiculo::miFlota()->with(['tipoVehiculo', 'cisternaAcoplada', 'ordenActiva'])->get();

        $total = $data->count();
        $enRuta = $data->where('estatus', 2)->count();
        $operativosCount = $data->where('estatus', 1)->count();
        $fallaCount = $data->whereIn('estatus', [3, 4, 5])->count();
        $porcentajeDisponibilidad = $total > 0 ? round(($operativosCount / $total) * 100) : 0;
        $tanque00=Deposito::where('id', 3)->first();
        $disponiblidadCombustible =  Deposito::where('id','!=', 3)->selectRaw('SUM(nivel_actual_litros) as total_combustible, sum(capacidad_litros) as capacidad_total')->get();
    
        // Segmentación de Flota
        $cisternas = $data->where('tipo', 2);
        $camiones = $data->whereIn('tipoVehiculo.tipo', ['CAMION', 'CAMION CISTERNA']);
        $chutos = $data->whereIn('tipoVehiculo.tipo', ['CHUTO']);
        
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

        // =========================================================
        // 3. RENDERIZADO Y RESPUESTA JSON
        // =========================================================
        $htmlDashboard = view('vehiculo.partials._tabla_disponibilidad_tv', compact(
            'today', 'cisternasFalla', 'enRuta', 'totalCisternas', 'camionesFalla',  
            'camionetasFalla', 'camionetasOperativas', 'totalLivianos', 'totalCamiones', 'totalChutos',
            'chutosFalla', 'chutosOperativos', 'camionesOperativos', 
            'total', 'operativosCount', 'fallaCount', 'porcentajeDisponibilidad', 'cisternasOperativas',
            'chutosEnRuta', 'camionetasEnRuta', 'camionesEnRuta', 'cisternasEnRuta', 'tanque00', 'disponiblidadCombustible'
        ))->render();

        return response()->json([
            'unidades' => $unidadesMapa,
            'html_dashboard' => $htmlDashboard,
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
