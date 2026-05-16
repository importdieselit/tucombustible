<?php

namespace App\Http\Controllers;

use App\Services\ReporteEficienciaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReporteEficienciaController extends Controller {
    protected $service;

    public function __construct(ReporteEficienciaService $service) {
        $this->service = $service;
    }

    public function index() {
        // Refrescamos antes de mostrar para asegurar data real
        $this->service->refrescarAgregados();

        $reporteActual = DB::table('reporte_eficiencia_actual as r')
            ->join('users as u', 'r.usuario_id', '=', 'u.id')
            ->join('personas as p', 'u.persona_id', '=', 'p.id')
            ->select('p.nombre as name', 'r.*')
            ->get();

        $historico = DB::table('historico_eficiencia_checklist as h')
            ->join('users as u', 'h.usuario_id', '=', 'u.id')
            ->join('personas as p', 'u.persona_id', '=', 'p.id')
            ->select('p.nombre as name', 'h.*')
            ->orderBy('h.periodo', 'desc')
            ->get();

        return view('reports.eficiencia', compact('reporteActual', 'historico'));
    }

    public function cerrarMes() {
        $this->service->ejecutarCierre();
        return redirect()->back()->with('success', 'Cierre de periodo realizado correctamente.');
    }
}