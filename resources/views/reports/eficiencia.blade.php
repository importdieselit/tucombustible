@extends('layouts.app') {{-- O tu layout base corporativo --}}

@section('content')
<div class="container-fluid py-4 px-3" style="background-color: #f8f9fa; min-height: 100vh;">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Control de Eficiencia Operativa</h1>
            <p class="text-muted mb-0 font-size-sm">Auditoría de tiempos de ejecución y cumplimiento de Checklists.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <form action="{{ route('reporte.eficiencia.cerrar') }}" method="POST" onsubmit="return confirm('¿Confirmas el cierre del período actual? Esta acción consolidará los datos en el histórico y reiniciará el conteo mensual.')">
                @csrf
                <button type="submit" class="btn btn-primary shadow-sm px-4">
                    <i class="fas fa-archive mr-2"></i> Realizar Cierre de Mes
                </button>
            </form>
        </div>
    </div>

    @php
        $globalTotal = $reporteActual->sum('total_realizados');
        $globalSalidas = $reporteActual->sum('salidas_tardias');
        $globalEntradas = $reporteActual->sum('entradas_tardias');
        $globalTardios = $globalSalidas + $globalEntradas;
        $globalEficiencia = $globalTotal > 0 ? round((($globalTotal - $globalTardios) / $globalTotal) * 100) : 100;
    @endphp
    
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 bg-white">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Checklists Ejecutados</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $globalTotal }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 bg-white">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Alertas: Salidas Adelantadas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $globalSalidas }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2 bg-white">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Alertas: Retraso en Cierre (>60m)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $globalEntradas }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 bg-white">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Índice de Eficiencia Global</div>
                            <div class="row no-gutters align-items-center mt-2">
                                <div class="col-auto mr-3">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $globalEficiencia }}%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $globalEficiencia }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4 border-0">
                <div class="card-header bg-dark py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-white text-uppercase tracking-wide" style="font-size: 0.85rem;">
                        Rendimiento del Personal - Período Actual
                    </h6>
                    <span class="badge badge-secondary p-2 font-weight-normal text-light" style="background: rgba(255,255,255,0.15)">
                        Último refresco: {{ count($reporteActual) > 0 ? \Carbon\Carbon::parse($reporteActual->first()->ultima_actualizacion)->format('H:i A') : 'N/A' }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="bg-light text-muted text-uppercase" style="font-size: 0.75rem;">
                                <tr>
                                    <th class="pl-4 border-0">Auditor / Operador</th>
                                    <th class="text-center border-0">Total Checklists</th>
                                    <th class="text-center border-0">Salidas Destiempo</th>
                                    <th class="text-center border-0">Cierres Tardíos</th>
                                    <th class="text-center pr-4 border-0" style="width: 180px;">Cumplimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reporteActual as $row)
                                    @php 
                                        $tardios = $row->salidas_tardias + $row->entradas_tardias;
                                        $eficiencia = $row->total_realizados > 0 
                                            ? round((($row->total_realizados - $tardios) / $row->total_realizados) * 100) 
                                            : 100;
                                        $colorBarra = $eficiencia >= 85 ? 'bg-success' : ($eficiencia >= 60 ? 'bg-warning' : 'bg-danger');
                                    @endphp
                                    <tr>
                                        <td class="pl-4 font-weight-bold text-gray-700">{{ $row->name }}</td>
                                        <td class="text-center font-weight-bold">{{ $row->total_realizados }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $row->salidas_tardias > 0 ? 'badge-soft-warning text-warning-dark' : 'badge-light text-muted' }} px-2 py-1">
                                                {{ $row->salidas_tardias }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $row->entradas_tardias > 0 ? 'badge-soft-danger text-danger-dark' : 'badge-light text-muted' }} px-2 py-1">
                                                {{ $row->entradas_tardias }}
                                            </span>
                                        </td>
                                        <td class="pr-4">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="font-weight-bold text-dark" style="font-size: 0.8rem;">{{ $eficiencia }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px; border-radius: 10px;">
                                                <div class="progress-bar {{ $colorBarra }}" role="progressbar" style="width: {{ $eficiencia }}%; border-radius: 10px;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-info-circle mr-2"></i> No se han registrado checklists en el período actual.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4 border-0">
                <div class="card-header bg-secondary py-3">
                    <h6 class="m-0 font-weight-bold text-white text-uppercase tracking-wide" style="font-size: 0.85rem;">
                        Histórico Consolidados
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 415px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="bg-light text-muted text-uppercase" style="font-size: 0.75rem;">
                                <tr>
                                    <th class="pl-4 border-0">Período</th>
                                    <th class="border-0">Auditor</th>
                                    <th class="text-center pr-4 border-0">Eficiencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($historico as $h)
                                    @php 
                                        $hTardios = $h->salidas_tardias + $h->entradas_tardias;
                                        $hEficiencia = $h->total_realizados > 0 
                                            ? round((($h->total_realizados - $hTardios) / $h->total_realizados) * 100) 
                                            : 100;
                                        $hColorBadge = $hEficiencia >= 85 ? 'badge-success' : ($hEficiencia >= 60 ? 'badge-warning text-dark' : 'badge-danger');
                                    @endphp
                                    <tr>
                                        <td class="pl-4">
                                            <span class="badge badge-light border font-weight-bold text-gray-700 p-2">
                                                {{ $h->periodo }}
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 120px;">{{ $h->name }}</td>
                                        <td class="text-center pr-4">
                                            <span class="badge {{ $hColorBadge }} px-2 py-1 font-weight-bold">
                                                {{ $hEficiencia }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            No existen cierres consolidados guardados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    .border-left-danger { border-left: 4px solid #e74a3b !important; }
    
    .badge-soft-warning { background-color: #fff3cd; color: #856404; }
    .text-warning-dark { color: #856404; font-weight: bold; }
    
    .badge-soft-danger { background-color: #f8d7da; color: #721c24; }
    .text-danger-dark { color: #721c24; font-weight: bold; }
    
    .tracking-wide { letter-spacing: 0.05em; }
</style>
@endsection