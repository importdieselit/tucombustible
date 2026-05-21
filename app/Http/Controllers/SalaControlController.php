<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo;
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
        // 1. DATA PARA EL MAPA (Geolocalización con relaciones básicas)
        // =========================================================
        $unidadesMapa = Vehiculo::miFlota()->with(['isMarca', 'isModelo'])
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get();

        // =========================================================
        // 2. DATA PARA EL REPORTE (Tu lógica exacta replicada)
        // =========================================================
        $today = now();
        $data = Vehiculo::miFlota()->with(['tipoVehiculo', 'cisternaAcoplada', 'ordenActiva'])->get();

        $vehiculosEnRuta = $data->where('estatus', 2);
        $enRuta = $vehiculosEnRuta->count();
        $total = $data->count();
        $operativosCount = $data->where('estatus', 1)->count();
        
        $cisternas = $data->where('tipo', 2);
        $totalCisternas = $cisternas->count();
        $camiones = $data->whereIn('tipoVehiculo.tipo', ['CAMION','CAMION CISTERNA']);
        $chutos = $data->whereIn('tipoVehiculo.tipo', ['CHUTO']);
        $totalCamiones = $camiones->count();
        $totalChutos = $chutos->count();

        $fallaCount = $data->whereIn('estatus', [3,4,5])->count();
        $porcentajeDisponibilidad = $total > 0 ? round(($operativosCount / $total) * 100) : 0;
        
        $ligero = Vehiculo::misVehiculos()->with(['tipoVehiculo', 'ordenActiva'])->where('tipo', 6)->get();
        $totalLivianos = $ligero->count();
        
        // Estatus detallados
        $cisternasFalla = $cisternas->where('estatus', '>', 2);
        $camionesFalla = $camiones->where('estatus', '>', 2);
        $chutosFalla = $chutos->where('estatus', '>', 2);
        $camionetasFalla = $ligero->where('estatus', '>', 2);
        $cisternasOperativas = $cisternas->where('estatus', 1);
        $camionetasOperativas = $ligero->where('estatus', 1);
        $camionesOperativos = $camiones->where('estatus', 1);
        $chutosOperativos = $chutos->where('estatus', 1);
        $chutosEnRuta = $chutos->where('estatus', 2);
        $camionetasEnRuta = $ligero->where('estatus', 2);
        $camionesEnRuta = $camiones->where('estatus', 2);
        $cisternasEnRuta = $cisternas->where('estatus', 2);

        $today = Carbon::parse($today);
        $queryViajesHoy = Viaje::whereDate('fecha_salida', now()->format('Y-m-d'));

        $vehiculosEnUsoHoy = (clone $queryViajesHoy)->distinct()->count('vehiculo_id');
        $utilizacionFlota = $operativosCount > 0 
            ? round(($vehiculosEnUsoHoy / $operativosCount) * 100, 1) 
            : 0;

        $despachosHoy = $queryViajesHoy->with(['vehiculo', 'chofer'])->get();

        // =========================================================
        // 3. RENDERIZADO DEL PARTIAL Y RESPUESTA JSON
        // =========================================================
        // Usamos la misma vista que ya maquetaste para no duplicar código
        $htmlDashboard = view('vehiculo.partials._tabla_disponibilidad_tv', compact(
            'today', 'cisternasFalla', 'enRuta', 'totalCisternas','camionesFalla', 'despachosHoy', 
            'camionetasFalla', 'camionetasOperativas', 'totalLivianos','totalCamiones', 'totalChutos',
            'chutosFalla', 'chutosOperativos', 'camionesOperativos', 
            'total', 'operativosCount', 'fallaCount', 'porcentajeDisponibilidad','cisternasOperativas','utilizacionFlota',
            'chutosEnRuta', 'camionetasEnRuta', 'camionesEnRuta', 'cisternasEnRuta'
        ))->render();

        return response()->json([
            'unidades' => $unidadesMapa,
            'html_dashboard' => $htmlDashboard
        ]);
    }
}