@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background-color: #f8fafc; min-height: 100vh;" id="area-reporte-checklist">
    
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 UI-elemento">
        <div>
            <h2 class="font-weight-bold text-dark m-0" style="letter-spacing: -0.5px;">Auditoría de Procesos: Checklist Operativo</h2>
            <p class="text-muted small m-0">Evaluación de concordancia de tiempos físicos en patio contra registros digitales de la App.</p>
        </div>
        
        <form method="GET" action="{{ route('inspecciones.reporte') }}" class="form-inline bg-white p-2 border rounded shadow-sm">
            <div class="form-group mx-2">
                <input type="date" name="fecha_inicio" class="form-control form-control-sm border-secondary" value="{{ $fechaInicio }}">
            </div>
            <div class="form-group mx-2">
                <input type="date" name="fecha_fin" class="form-control form-control-sm border-secondary" value="{{ $fechaFin }}">
            </div>
            <button type="submit" class="btn btn-sm text-white px-3 style-btn-filtrar" style="background-color: #1e293b;">Filtrar Rango</button>
        </form>
    </div>

    <div class="row mb-4" id="kpi-group-wrapper">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-lg p-3 bg-white border-left-custom" style="border-left: 4px solid #1e293b;">
                <span class="text-muted text-uppercase font-weight-bold small">Inspecciones Evaluadas</span>
                <h3 class="font-weight-bold text-dark mt-1 mb-0">{{ $kpisGlobales['total_viajes']*2 }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-lg p-3 bg-white border-left-custom" style="border-left: 4px solid #ef4444;">
                <span class="text-muted text-uppercase font-weight-bold small">Salidas Fuera de Horario</span>
                <h3 class="font-weight-bold text-danger mt-1 mb-0">{{ $kpisGlobales['salidas_tardias'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-lg p-3 bg-white border-left-custom" style="border-left: 4px solid #f97316;">
                <span class="text-muted text-uppercase font-weight-bold small">Llegadas Fuera de Horario</span>
                <h3 class="font-weight-bold mt-1 mb-0" style="color: #f97316;">{{ $kpisGlobales['llegadas_tardias'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-lg p-3 bg-white border-left-custom" style="border-left: 4px solid #ff6600;">
                <span class="text-muted text-uppercase font-weight-bold small">Checklists Incompletos</span>
                <h3 class="font-weight-bold mt-1 mb-0" style="color: #ff6600;">{{ $kpisGlobales['incompletos'] }}</h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-lg bg-white overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-clipboard-check mr-2 text-muted"></i>Rendimiento de Tripulación de Flotas</h5>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill font-weight-bold px-3 UI-elemento" onclick="window.print()">
                <i class="fas fa-file-pdf mr-1"></i> Exportar / Imprimir
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table table-executive-style table-hover m-0">
                <thead>
                    <tr>
                        <th class="py-3 px-4">Personal Evaluado</th>
                        <th class="py-3 text-center">Rol Logístico</th>
                        <th class="py-3 text-center">Viajes Asignados</th>
                        <th class="py-3 text-center text-danger">Salidas Tardías</th>
                        <th class="py-3 text-center" style="color: #f97316;">Llegadas Tardías (+1h)</th>
                        <th class="py-3 text-center" style="color: #ff6600;">Checklists Incompletos</th>
                        <th class="py-3 text-right px-4">Índice de Cumplimiento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportePersonal as $p)
                        <tr>
                            <td class="font-weight-bold text-dark py-3 px-4">{{ $p['nombre'] }}</td>
                            <td class="text-center py-3">
                                <span class="badge px-3 py-1 rounded-pill {{ $p['rol'] == 'Chofer' ? 'badge-chofer-style' : 'badge-ayudante-style' }}">
                                    {{ $p['rol'] }}
                                </span>
                            </td>
                            <td class="text-center font-weight-bold py-3 text-secondary">{{ $p['total_viajes'] }}</td>
                            <td class="text-center font-weight-bold py-3 {{ $p['salidas_tardias'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $p['salidas_tardias'] }}</td>
                            <td class="text-center font-weight-bold py-3" style="color: {{ $p['llegadas_tardias'] > 0 ? '#f97316' : '#64748b' }}">{{ $p['llegadas_tardias'] }}</td>
                            <td class="text-center font-weight-bold py-3" style="color: {{ $p['incompletos'] > 0 ? '#ff6600' : '#64748b' }}">{{ $p['incompletos'] }}</td>
                            <td class="text-right font-weight-bold py-3 px-4 data-raw-numbers">
                                <span style="color: {{ $p['porcentaje_cumplimiento'] < 80 ? '#ef4444' : '#10b981' }}">
                                    {{ number_format($p['porcentaje_cumplimiento'], 4, ',', '.') }} %
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-exclamation-circle d-block mb-2 fa-2x"></i>
                                No se encontraron registros de auditoría en las fechas seleccionadas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Estructura de cabeceras fijas estilo Pizarra Corporativa */
    .table-executive-style thead tr th {
        background-color: #1e293b !important;
        color: #ffffff !important;
        font-weight: 600;
        font-size: 13px;
        border: none;
    }
    
    .table-executive-style tbody tr:nth-of-type(odd) {
        background-color: #f8fafc;
    }

    /* Fuentes monospaciadas para visualización numérica analítica pura */
    .data-raw-numbers {
        font-family: 'Courier New', Courier, monospace;
        font-size: 14px;
    }

    /* Estilos de Badges según rol logístico */
    .badge-chofer-style {
        background-color: #1e293b;
        color: #ffffff;
    }
    
    .badge-ayudante-style {
        background-color: #e2e8f0;
        color: #334155;
    }

    /* --- PROTOCOLO ONE-PAGE PRINT --- */
    @media print {
        /* Ocultar barra lateral, barra superior y botones de filtros web */
        .layouts-app-sidebar, .navbar, .UI-elemento, form {
            display: none !important;
            visibility: hidden !important;
        }
        
        body, #area-reporte-checklist, #area-reporte-checklist * {
            visibility: visible !important;
            background: #ffffff !important;
        }
        
        #area-reporte-checklist {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0 !important;
        }

        .table-executive-style thead tr th {
            color: #000000 !important;
            border-bottom: 2px solid #000000 !important;
        }
    }
</style>
@endpush
@endsection