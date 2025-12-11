@extends('layouts.app')

@section('title', 'Resumen Gerencial y Reportes')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2 text-primary">Resumen Gerencial Dinámico</h1>
        <p class="text-muted">Seleccione los filtros de tiempo y los indicadores a visualizar.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Filtros de Reporte</h5>
    </div>
    <div class="card-body">
        <form id="report-filter-form">
            @csrf
            <div class="row align-items-end">
                {{-- Selector de Rango de Fechas Predefinido --}}
                <div class="col-md-3 mb-3">
                    <label for="date_range" class="form-label">Rango Rápido</label>
                    <select id="date_range" class="form-select">
                        <option value="month" selected>Mes en Curso</option>
                        <option value="week">Semana en Curso</option>
                        <option value="day">Día de Hoy</option>
                        <option value="custom">Rango Personalizado</option>
                    </select>
                </div>

                {{-- Campos para Rango Personalizado (Ocultos por defecto) --}}
                <div class="col-md-3 mb-3 custom-range-fields" style="display: none;">
                    <label for="date_start" class="form-label">Fecha Inicio</label>
                    <input type="date" id="date_start" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
                </div>
                <div class="col-md-3 mb-3 custom-range-fields" style="display: none;">
                    <label for="date_end" class="form-label">Fecha Fin</label>
                    <input type="date" id="date_end" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                
                {{-- Botón de Aplicar --}}
                <div class="col-md-3 mb-3">
                    <button type="button" id="apply-filters" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Aplicar Filtros
                    </button>
                </div>
            </div>
            
            <hr>
            
            {{-- Selector de Items (Checkboxes) --}}
            <h6 class="mt-3">Indicadores a Mostrar:</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input report-item" type="checkbox" value="ventas_litros" id="ventas_litros" checked>
                        <label class="form-check-label" for="ventas_litros">Ventas (Litros Despachados)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input report-item" type="checkbox" value="ordenes_abiertas" id="ordenes_abiertas" checked>
                        <label class="form-check-label" for="ordenes_abiertas">Órdenes Abiertas (Conteo)</label>
                    </div>
                </div>
                <div class="col-md-4">
                     <div class="form-check">
                        <input class="form-check-input report-item" type="checkbox" value="gasto_suministros" id="gasto_suministros" checked>
                        <label class="form-check-label" for="gasto_suministros">Gasto Total en Suministros</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input report-item" type="checkbox" value="nuevos_clientes" id="nuevos_clientes" checked>
                        <label class="form-check-label" for="nuevos_clientes">Nuevos Clientes Registrados</label>
                    </div>
                </div>
                 <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input report-item" type="checkbox" value="reportes_falla" id="reportes_falla" checked>
                        <label class="form-check-label" for="reportes_falla">Reportes de Falla/Mantenimiento</label>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Contenedor para el Contenido Dinámico del Reporte --}}
<div id="report-content" class="mb-5">
    <div class="text-center p-5">
        <i class="bi bi-info-circle fs-3 text-muted"></i>
        <p class="text-muted mt-2">Seleccione los filtros y presione Aplicar para generar el resumen gerencial.</p>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const API_URL = '{{ route("reports.summary") }}'; // Se define la ruta de la API

    document.addEventListener('DOMContentLoaded', function() {
        const dateRangeSelect = document.getElementById('date_range');
        const customRangeFields = document.querySelectorAll('.custom-range-fields');
        const applyFiltersBtn = document.getElementById('apply-filters');
        const reportContent = document.getElementById('report-content');

        // Función para mostrar/ocultar los campos de fecha personalizada
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customRangeFields.forEach(field => field.style.display = 'block');
            } else {
                customRangeFields.forEach(field => field.style.display = 'none');
            }
        });

        // Evento principal para aplicar los filtros y obtener datos
        applyFiltersBtn.addEventListener('click', async function() {
            const range = dateRangeSelect.value;
            const indicators = Array.from(document.querySelectorAll('.report-item:checked')).map(cb => cb.value);
            
            if (indicators.length === 0) {
                 Swal.fire('Atención', 'Debe seleccionar al menos un indicador a mostrar.', 'warning');
                 return;
            }

            let startDate = '';
            let endDate = '';
            
            if (range === 'custom') {
                startDate = document.getElementById('date_start').value;
                endDate = document.getElementById('date_end').value;
                if (!startDate || !endDate) {
                    Swal.fire('Advertencia', 'Debe seleccionar un rango de fechas válido.', 'warning');
                    return;
                }
            }

            // Deshabilitar botón y mostrar spinner
            applyFiltersBtn.disabled = true;
            applyFiltersBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...';
            reportContent.innerHTML = '<div class="text-center p-5"><span class="spinner-border spinner-border-lg"></span><p class="mt-2">Generando reporte...</p></div>';


            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        range: range,
                        start_date: startDate,
                        end_date: endDate,
                        indicators: indicators // Enviar los indicadores seleccionados
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    renderReport(data); // Función para dibujar el reporte (definida abajo)
                } else {
                    Swal.fire('Error', 'No se pudieron cargar los datos del reporte: ' + data.message, 'error');
                }

            } catch (error) {
                console.error('Error de fetch:', error);
                Swal.fire('Error de Conexión', 'No se pudo contactar al servidor de reportes.', 'error');
            } finally {
                applyFiltersBtn.disabled = false;
                applyFiltersBtn.innerHTML = '<i class="bi bi-search"></i> Aplicar Filtros';
            }
        });
        
        // Ejecutar la carga inicial al cargar la página (ej. Mes en Curso)
        document.getElementById('apply-filters').click(); 


        // Función para dibujar el reporte (Ejemplo: Usando tarjetas)
        function renderReport(data) {
            let html = '<div class="row">';
            
            // Iterar sobre los resultados y crear tarjetas dinámicamente
            for (const indicator in data) {
                let title = '';
                let value = data[indicator];
                let icon = '';
                let color = '';

                switch (indicator) {
                    case 'ventas_litros':
                        title = 'Total Litros Despachados';
                        value = parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Lts';
                        icon = 'bi-fuel-pump';
                        color = 'bg-primary';
                        break;
                    case 'ordenes_abiertas':
                        title = 'Órdenes Abiertas';
                        value = parseInt(value).toLocaleString('es-VE');
                        icon = 'bi-list-task';
                        color = 'bg-warning text-dark';
                        break;
                    case 'gasto_suministros':
                        title = 'Gasto Total Suministros';
                        value = '$ ' + parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2 });
                        icon = 'bi-currency-dollar';
                        color = 'bg-danger';
                        break;
                    case 'nuevos_clientes':
                        title = 'Nuevos Clientes';
                        value = parseInt(value).toLocaleString('es-VE');
                        icon = 'bi-people';
                        color = 'bg-info text-dark';
                        break;
                    case 'reportes_falla':
                        title = 'Reportes de Falla/Mantenimiento';
                        value = parseInt(value).toLocaleString('es-VE');
                        icon = 'bi-tools';
                        color = 'bg-secondary';
                        break;
                    default:
                        continue; // Ignorar si no se reconoce
                }

                html += `
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card ${color} text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase m-0">${title}</h6>
                                        <h2 class="display-6 fw-bold">${value}</h2>
                                    </div>
                                    <i class="${icon} fs-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            html += '</div>';
            
            // Si también quieres listar los viajes/despachos (item 3), aquí se renderizaría una tabla.
            
            reportContent.innerHTML = html;
        }

    });
</script>
@endpush