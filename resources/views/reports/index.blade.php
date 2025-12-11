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
                        <i class="bi bi-search"></i> Generar Reporte
                    </button>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="button" class="btn btn-danger w-100" id="export-pdf-btn" style="display: none;">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> Exportar PDF
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
                        <input class="form-check-input report-item" type="checkbox" value="compras_litros" id="compras_litros" checked> 
                        <label class="form-check-label" for="compras_litros">Compras (Litros Cargados)</label> 
                    </div>
                    <div class="form-check">
                        <input class="form-check-input report-item" type="checkbox" value="ordenes_abiertas" id="ordenes_abiertas" checked>
                        <label class="form-check-label" for="ordenes_abiertas">Órdenes Abiertas (Conteo)</label>
                    </div>
                </div>
                <div class="col-md-3">
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
<div id="report-printable-area">
{{-- Contenedor para el Contenido Dinámico del Reporte --}}
<div id="report-content" class="mb-5">
    <div class="text-center p-5">
        <i class="bi bi-info-circle fs-3 text-muted"></i>
        <p class="text-muted mt-2">Seleccione los filtros y presione Aplicar para generar el resumen gerencial.</p>
    </div>
</div>

<div class="row">
    <div class="col-12" id="details-header">
        <h3 class="mt-4 mb-3 text-primary" style="border-bottom: 2px solid #007bff;">Detalles del Reporte</h3>
    </div>
</div>

<div class="row" id="details-tables-container">
    {{-- Contenedor Gráfico (Torta Clientes) --}}
    <div class="col-lg-6 mb-4" id="despachos_chart_container"></div>
    
    {{-- Contenedor Ventas/Despachos --}}
    <div class="col-12 mb-4" id="ventas_litros_details"></div>
    
    {{-- Contenedor Compras/Cargas (Compras) --}}
    <div class="col-12 mb-4" id="compras_litros_details"></div> 
    
    {{-- Contenedor Gasto Suministros --}}
    <div class="col-12 mb-4" id="gasto_suministros_details"></div>
    
    {{-- Contenedor Reportes de Falla --}}
    <div class="col-12 mb-4" id="reportes_falla_details"></div>

    {{-- Contenedor Nuevos Clientes --}}
    <div class="col-12 mb-4" id="nuevos_clientes_details"></div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
<script src="{{ asset('js/jquery.PrintArea.js') }}" defer></script>
<script>
    const API_URL = '{{ route("reports.summary") }}'; // Se define la ruta de la API

    document.addEventListener('DOMContentLoaded', function() {
        const dateRangeSelect = document.getElementById('date_range');
        const customRangeFields = document.querySelectorAll('.custom-range-fields');
        const applyFiltersBtn = document.getElementById('apply-filters');
        const reportContent = document.getElementById('report-content');
        const exportPdfBtn = document.getElementById('export-pdf-btn');
        const form = document.getElementById('report-filter-form');

        // Función para mostrar/ocultar los campos de fecha personalizada
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customRangeFields.forEach(field => field.style.display = 'block');
            } else {
                customRangeFields.forEach(field => field.style.display = 'none');
            }
        });

        const formatDate = (dateString) => {
            if (!dateString) return 'N/A';
            // Usar la zona horaria UTC para evitar problemas de desfase
            return new Date(dateString + 'T00:00:00').toLocaleDateString('es-ES'); 
        };

        const formatDateTime = (dateObject) => {
            if (!dateObject) return 'N/A';
            
            // Opciones de formato: Día, Mes, Año + Hora, Minuto
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true // o false, según tu preferencia (AM/PM)
            };
            
            // Obtener la fecha y hora actual en el formato deseado (ej: 11/12/2025 03:41 PM)
            return dateObject.toLocaleDateString('es-ES', options);
        };

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
            console.log('Datos recibidos para el reporte:', data); // Depuración
            console.log('Indicadores seleccionados:', data.indicators); // Depuración
            console.log('Totales recibidos:', data.totals); // Depuración
            console.log('Fechas del reporte:', data.report_dates); // Depuración

            const reportContent = document.getElementById('report-content');
            const reportPrintableArea = document.getElementById('report-printable-area');
            let cardsHtml = '<div class="row">';
            const reportRange = document.getElementById('date_range').value; // 'day', 'week', 'month', 'custom'
            const reportStartDate = data.report_dates.start_date;
            const reportEndDate = data.report_dates.end_date;
            const totals = data.totals || {}; // Aseguramos que data.totals existe
            const indicators = data.indicators || []; // Aseguramos que data.indicators existe
            const generatedDateTime = formatDateTime(new Date());

            let tipoReporte = '';
            switch (reportRange) {
                case 'day': tipoReporte = 'Diario'; break;
                case 'week': tipoReporte = 'Semanal'; break;
                case 'month': tipoReporte = 'Mensual'; break;
                case 'custom': tipoReporte = 'Personalizado'; break;
                default: tipoReporte = 'General';
            }
            
            if (Object.keys(data.totals).length > 0) {
                exportPdfBtn.setAttribute('data-start-date', reportStartDate);
                exportPdfBtn.setAttribute('data-end-date', reportEndDate);
                exportPdfBtn.style.display = 'block';
            } else {
                exportPdfBtn.style.display = 'none';
            }

            let headerHtml = `
                <div class="row mb-4 border-bottom pb-3 print-header">
                    <div class="col-6">
                        <img src="{{ asset('img/logo1.png') }}" alt="Logo de la Empresa" style="max-height: 50px;">
                    </div>
                    <div class="col-6 text-end">
                        <h4 class="text-primary mb-1">REPORTE GERENCIAL</h4>
                        <p class="mb-0  text-muted">Tipo: <strong>${tipoReporte}</strong></p>
                        <p class="mb-0 text-muted">Fecha: <strong>${formatDate(reportStartDate)} al ${formatDate(reportEndDate)}</strong></p>
                        <p class="mb-0 text-muted">Generado: <strong>${generatedDateTime}</strong></p>
                    </div>
                </div>
            `;

            // 1. GENERAR CARDS DE TOTALES (Iteramos sobre data.totals)
            for (const indicator in totals) { 
                let title = '';
                let value = totals[indicator]; // <-- ¡CORREGIDO! Usamos el valor dentro de totals
                let icon = '';
                let color = '';

                switch (indicator) {
                    case 'ventas_litros':
                        title = 'Total Litros Despachados';
                        value = parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Lts';
                        icon = 'bi-fuel-pump';
                        color = 'bg-primary';
                        break;
                    case 'compras_litros': // NUEVO INDICADOR
                        title = 'Total Litros Comprados';
                        value = parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Lts';
                        icon = 'bi-truck';
                        color = 'bg-success';
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

                cardsHtml += `
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
            
            cardsHtml += '</div>';
            
            // Inyectar las tarjetas en el contenedor principal
            reportContent.innerHTML = headerHtml + cardsHtml;



            // -----------------------------------------------------
            // SECCIÓN DE DETALLES (Se llama después de inyectar las cards)
            // -----------------------------------------------------
            
            // Asegurarse de que el encabezado de detalles esté visible si hay cards
            const detailsHeader = document.getElementById('details-header');
            if (Object.keys(totals).length > 0 && detailsHeader) {
                 detailsHeader.style.display = 'block';
            } else if(detailsHeader) {
                 detailsHeader.style.display = 'none';
            }


            if (data.details && indicators.includes('ventas_litros')) {
                renderVentasDespachos(data.details.ventas_litros_data);
                renderDespachosChart(data.details.despachos_by_client_data);
            } else {
                 document.getElementById('ventas_litros_details').innerHTML = '';
                 document.getElementById('despachos_chart_container').innerHTML = '';
            }

            if (data.details && indicators.includes('compras_litros')) {
                renderComprasLitros(data.details.compras_litros_data); 
            } else {
                 document.getElementById('compras_litros_details').innerHTML = '';
            }

            if (data.details && indicators.includes('gasto_suministros')) {
                renderGastoSuministros(data.details.gasto_suministros_data);
            } else {
                 document.getElementById('gasto_suministros_details').innerHTML = '';
            }
            
            if (data.details && indicators.includes('reportes_falla')) {
                // Notar que la función necesita la lista y la data agrupada
                renderReportesFalla(data.details.reportes_falla_data, data.details.reportes_falla_grouped,reportStartDate,reportEndDate);
            } else {
                 document.getElementById('reportes_falla_details').innerHTML = '';
            }
            
            if (data.details && indicators.includes('nuevos_clientes')) {
                renderNuevosClientes(data.details.nuevos_clientes_data);
            } else {
                 document.getElementById('nuevos_clientes_details').innerHTML = '';
            }
        } // Fin de renderReport

    // --- FUNCIONES ESPECÍFICAS DE RENDERIZADO DE TABLAS ---
    
    // Función 1: Ventas / Despachos
    function renderVentasDespachos(viajes) {
        let html = `
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Listado de Viajes y Despachos (${viajes.length} Registros)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped table-hover align-middle">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th># Viaje</th>
                                    <th>Fecha Salida</th>
                                    <th>Vehículo</th>
                                    <th>Cliente</th>
                                    <th>Litros Despachados</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        if (viajes.length === 0) {
            html += `<tr><td colspan="6" class="text-center text-muted">No hay viajes/despachos en este período.</td></tr>`;
        } else {
            viajes.forEach(viaje => {
                // Iterar sobre los despachos de cada viaje
                viaje.despachos.forEach((despacho, index) => {
                    const clienteNombre = despacho.cliente ? despacho.cliente.nombre : (despacho.otro_cliente || 'N/A');
                    const litros = parseFloat(despacho.litros).toFixed(2);
                   // const costo = (despacho.litros * 0.95).toFixed(2); // Ejemplo de costo/litro
                    
                    html += `
                        <tr>
                            <td>${viaje.id}</td>
                            <td>${new Date(viaje.fecha_salida).toLocaleDateString()}</td>
                            <td>${viaje.vehiculo ? viaje.vehiculo.flota + ' (' + viaje.vehiculo.placa + ')' : 'N/A'}</td>
                            <td>${clienteNombre}</td>
                            <td>${litros} Lts</td>
                        </tr>
                    `;
                });
            });
        }
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('ventas_litros_details').innerHTML = html;
    }

    // Función 2: Gráfico de Despachos por Cliente (Torta)
    let despachosChartInstance = null;
    function renderDespachosChart(data) {
        const container = document.getElementById('despachos_chart_container');
        container.innerHTML = `
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="m-0">Despachos Agrupados por Cliente (Litros)</h5>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <canvas id="despachosChart"></canvas>
                </div>
            </div>
        `;

        if (despachosChartInstance) {
            despachosChartInstance.destroy(); // Destruir la instancia anterior si existe
        }
        
        if (Object.keys(data).length === 0) {
             document.getElementById('despachosChart').parentElement.innerHTML = '<p class="text-center text-muted">No hay datos de despachos para graficar.</p>';
             return;
        }

        const labels = Object.keys(data);
        const values = Object.values(data);
        const backgroundColors = labels.map((_, i) => `hsl(${(i * 30)}, 70%, 50%)`); // Generar colores dinámicos

        const ctx = document.getElementById('despachosChart').getContext('2d');
        despachosChartInstance = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Litros Despachados',
                    data: values,
                    backgroundColor: backgroundColors,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.formattedValue + ' Lts';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // --- NUEVA FUNCIÓN ESPECÍFICA DE RENDERIZADO DE COMPRAS ---
        function renderComprasLitros(viajes) {
            //console.log('Datos de viajes para compras:', viajes); // Depuración
            let html = `
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="m-0">Listado de Viajes y Cargas (Compras) (${viajes.length} Registros)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-striped table-hover align-middle">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th># Viaje</th>
                                        <th>Fecha Salida</th>
                                        <th>Vehículo</th>
                                        <th>Litros Cargados</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            if (viajes.length === 0) {
                html += `<tr><td colspan="6" class="text-center text-muted">No hay compras/cargas en este período.</td></tr>`;
            } else {
                viajes.forEach(viaje => {
                    // Asumimos que compraCombustible es la relación cargada (Laravel usa snake_case)
                    const compra = viaje.compra_combustible; 
                    if (compra) {
                        // Aquí asumimos que CompraCombustible tiene campos como 'proveedor_nombre' y 'monto'
                        const proveedor = compra.proveedor_nombre || 'N/A'; 
                        const litros = parseFloat(viaje.litros).toFixed(2);
                        //const monto = parseFloat(compra.monto).toFixed(2);
                        
                        html += `
                            <tr>
                                <td>${viaje.id}</td>
                                <td>${new Date(viaje.fecha_salida).toLocaleDateString()}</td>
                                <td>${viaje.vehiculo ? viaje.vehiculo.flota + ' (' + viaje.vehiculo.placa + ')' :  viaje.otro_vehiculo+' (flete)'}</td>
                                <td>${litros} Lts</td>

                            </tr>
                        `;
                    }
                });
            }
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('compras_litros_details').innerHTML = html;
        }
    
    // Función 3: Gasto en Suministros
    function renderGastoSuministros(requerimientos) {
        let html = `
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="m-0">Detalle de Gasto en Suministros por Requerimiento</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped table-hover align-middle">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th># Req.</th>
                                    <th>Fecha</th>
                                    <th>Estatus</th>
                                    <th>Suministro</th>
                                    <th>Cant. Aprobada</th>
                                    <th>Costo Unitario</th>
                                    <th>Total Gasto</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        if (requerimientos.length === 0) {
            html += `<tr><td colspan="7" class="text-center text-muted">No hay requerimientos aprobados/recibidos en este período.</td></tr>`;
        } else {
            requerimientos.forEach(req => {
                req.detalles.forEach(detalle => {
                    const totalGasto = (detalle.costo_unitario_aprobado * detalle.cantidad_solicitada).toFixed(2);
                    
                    html += `
                        <tr>
                            <td>${req.id}</td>
                            <td>${new Date(req.created_at).toLocaleDateString()}</td>
                            <td><span class="badge bg-${req.estatus === 2 ? 'success' : 'info'}">${req.estatus === 2 ? 'Aprobado' : 'Recibido'}</span></td>
                            <td>${detalle.descripcion}</td>
                            <td>${detalle.cantidad_solicitada}</td>
                            <td>$ ${parseFloat(detalle.costo_unitario_aprobado).toFixed(2)}</td>
                            <td>$ ${totalGasto}</td>
                        </tr>
                    `;
                });
            });
        }
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('gasto_suministros_details').innerHTML = html;
    }
    
   // Función 4: Reportes de Falla (Actualizada)
        function renderReportesFalla(ordenes, ordenesAgrupadas,reportStartDate,reportEndDate) {
            const container = document.getElementById('reportes_falla_details');
            let html = '';

            // -----------------------------------------------------
            // 1. Agrupamiento Visual (Gráfico o Lista)
            // -----------------------------------------------------
            let groupedHtml = `
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="m-0">Resumen de Ordenes Agrupadas por Unidad</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                `;
                
            if (Object.keys(ordenesAgrupadas).length === 0) {
                groupedHtml += '<div class="col-12"><p class="text-center text-muted">No hay reportes de falla en este período.</p></div>';
            } else {
                // Iterar sobre los grupos (Unidad/N/A)
                for (const unidad in ordenesAgrupadas) {
                    const data = ordenesAgrupadas[unidad];
                    const isVehicle = data.vehiculo_id > 0;
    
                    const link = isVehicle 
                        ? `{{ route('ordenes.list') }}?vehiculo_id=${data.vehiculo_id}&start_date=${reportStartDate}&end_date=${reportEndDate}` 
                        : '#';
                    let textoOrden = data.count === 1 ? 'Orden' : 'Órdenes';
                    groupedHtml += `
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="p-3 border rounded h-100">
                                <a href="${link}" target="_blank" class="text-decoration-none">
                                <h6 class="text-truncate" title="${unidad}">${unidad}</h6>
                                <h4 class="fw-bold text-danger">${data.count} <small>${textoOrden}</small></h4>
                                </a>
                                <small class="text-muted">Órdenes: 
                                    ${Object.keys(data.ordenes).map(id => 
                                        `<a href="/ordenes/show/${id}" target="_blank" class="badge bg-light text-dark text-decoration-none me-1">${data.ordenes[id]}</a>`
                                    ).join('')}
                                </small>
                            </div>
                        </div>
                    `;
                }
            }
                
            groupedHtml += `
                        </div>
                    </div>
                </div>
                `;
            html += groupedHtml;


            // -----------------------------------------------------
            // 2. Listado Detallado (Tabla)
            // -----------------------------------------------------
            let listHtml = `
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="m-0">Listado Detallado de Órdenes de Mantenimiento y Falla (${ordenes.length} Registros)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-striped table-hover align-middle">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th># Orden</th>
                                        <th>Tipo</th>
                                        <th>Fecha Apertura</th>
                                        <th>Unidad/Instalación</th>
                                        <th>Descripción de Falla</th>
                                        <th>Observaciones</th>
                                        <th>Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            if (ordenes.length === 0) {
                listHtml += `<tr><td colspan="6" class="text-center text-muted">No hay órdenes de falla/mantenimiento en este período.</td></tr>`;
            } else {
                ordenes.forEach(orden => {
                    const estatusText = orden.estatus === 2 ? 'Abierta' : (orden.estatus === 1 ? 'Pendiente' : 'Cerrada');
                    const estatusClass = orden.estatus === 2 ? 'warning' : (orden.estatus === 1 ? 'primary' : 'success');
                   // console.log('Datos de órden de falla:', orden); // Depuración
                    
                    // Determinar nombre de la unidad o N/A
                    const unidadNombre = orden.vehiculo_belong 
                        ? `${orden.vehiculo_belong.flota} (${orden.vehiculo_belong.placa})` 
                        : 'N/A (Sin Unidad)';

                    listHtml += `
                        <tr>
                            <td><a href="/ordenes/show/${orden.id}" target="_blank">${orden.nro_orden}</a></td>
                            <td>${orden.tipo}</td>
                            <td>${new Date(orden.created_at).toLocaleDateString()}</td>
                            <td>${unidadNombre}</td>
                            <td>${orden.descripcion_1 ? orden.descripcion_1.substring(0, 50) + '...' : 'N/A'}</td>
                            <td>${orden.observacion ? orden.observacion.substring(0, 50) + '...' : 'N/A'}</td>
                            <td><span class="badge bg-${estatusClass}">${estatusText}</span></td>
                        </tr>
                    `;
                });
            }
            
            listHtml += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            html += listHtml;
            
            container.innerHTML = html;
        }

    // Función 5: Nuevos Clientes
    function renderNuevosClientes(clientes) {
        let html = `
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Listado de Nuevos Clientes Registrados (${clientes.length} Registros)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped table-hover align-middle">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre/Razón Social</th>
                                    <th>Fecha Registro</th>
                                    <th>Ubicación</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        if (clientes.length === 0) {
            html += `<tr><td colspan="4" class="text-center text-muted">No hay nuevos clientes en este período.</td></tr>`;
        } else {
            clientes.forEach(cliente => {
                html += `
                    <tr>
                        <td>${cliente.id}</td>
                        <td>${cliente.nombre}</td>
                        <td>${new Date(cliente.created_at).toLocaleDateString()}</td>
                        <td>${cliente.direccion.substring(0, 50)}...</td>
                    </tr>
                `;
            });
        }
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('nuevos_clientes_details').innerHTML = html;
    }

    function getCurrentFilters() {
            const exportPdfBtn = document.getElementById('export-pdf-btn');
            const filters = {};
            
            filters.range = document.getElementById('date_range').value; 
            
            filters.start_date = exportPdfBtn.getAttribute('data-start-date');
            filters.end_date = exportPdfBtn.getAttribute('data-end-date');
            
            filters.indicators = [];
            document.querySelectorAll('.report-item:checked').forEach(checkbox => {
                filters.indicators.push(checkbox.value);
            });
            return filters;
        }

    exportPdfBtn.addEventListener('click', function() {
            
            const printArea = $('#report-printable-area').html(); 
            
            const options = {
                mode: 'iframe', // Utiliza un iframe para simular la página
                popTitle: 'Reporte Gerencial',
                extraCss: '{{ asset('css/app.css') }}', // Asumiendo que tienes un archivo CSS principal
            };
            
            $('#report-printable-area').printArea(options);
            
            
        });
    });
</script>
@endpush