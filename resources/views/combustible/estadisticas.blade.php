@extends('layouts.app')
@push('styles')
    <!-- CSS de DataTables -->
    <style>
    /* Esta clase solo se activará vía JS durante la captura */
    .hide-for-capture .no-tg {
        display: none !important;
    }
</style>
@endpush
@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
           <h3>
                @if($clienteSeleccionado)
                    📊 Reporte de Consumo: {{ $clienteSeleccionado->nombre }}
                @else
                    📊 Indicadores de Despacho Industrial (Tanque 00)
                @endif
            </h3>
        </div>
 <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
    <div class="btn-group shadow-sm">
        <a href="?view=hoy" class="btn btn-outline-primary {{ $view == 'hoy' ? 'active' : '' }}">Día</a>
        <a href="?view=semana" class="btn btn-outline-primary {{ $view == 'semana' ? 'active' : '' }}">Semana</a>
        <a href="?view=mes" class="btn btn-outline-primary {{ $view == 'mes' ? 'active' : '' }}">Mes</a>
    </div>
<form action="{{ route('combustible.estadisticas') }}" method="GET" class="d-flex align-items-center">
    <input type="hidden" name="view" value="{{ $view }}">
    
    <div class="me-3" style="min-width: 250px;">
        <select name="cliente_id" class="form-select fw-bold border-primary" onchange="this.form.submit()">
            <option value="">-- Todos los Clientes --</option>
            @foreach($clientes as $c)
                <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="input-group">
        <span class="input-group-text bg-white border-end-0"><i class="fa fa-calendar-check-o text-primary"></i></span>
        
        @if($view == 'hoy')
            <input type="date" name="date" class="form-control border-start-0 fw-bold" value="{{ $date }}" onchange="this.form.submit()">
        @elseif($view == 'semana')
            <input type="week" name="date" class="form-control border-start-0 fw-bold" value="{{ \Carbon\Carbon::parse($date)->format('Y-\WW') }}" onchange="this.form.submit()">
        @else
            <input type="month" name="date" class="form-control border-start-0 fw-bold" value="{{ \Carbon\Carbon::parse($date)->format('Y-m') }}" onchange="this.form.submit()">
        @endif
    </div>

    <div class="ms-3 d-none d-md-block">
        <span class="badge bg-light text-primary border p-2 text-uppercase">
            <i class="fa fa-info-circle"></i> {{ $label }}
        </span>
    </div>

    @if(request('cliente_id') || request('date') != now()->format('Y-m-d'))
        <a href="{{ route('combustible.estadisticas', ['view' => $view]) }}" class="ms-2 btn btn-sm btn-outline-danger" title="Resetear Filtros">
            <i class="fa fa-times"></i>
        </a>
    @endif
</form>
</div>
    </div>

    <div class="row mb-4">
        @if($clienteSeleccionado)
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-dark text-white">
                    <div class="card-body">
                        <h6>DISPONIBLE ACTUAL</h6>
                        <h2 class="mb-0">{{ number_format($clienteSeleccionado->prepagado, 2) }} L</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 {{ $diasAutonomia < 3 ? 'bg-danger' : 'bg-success' }} text-white">
                    <div class="card-body">
                        <h6>AUTONOMÍA ESTIMADA</h6>
                        <h2 class="mb-0">{{ round($diasAutonomia) }} Días</h2>
                    </div>
                </div>
            </div>
        @endif
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6>TOTAL SURTIDO</h6>
                    <h2 class="mb-0">{{ number_format($stats->total_litros, 2) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <h6>@if($clienteSeleccionado) CONSUMO PROMEDIO @else PROMEDIO POR CLIENTE @endif</h6>
                    <h2 class="mb-0">{{ number_format($stats->promedio_ticket, 2) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-info text-white">
                <div class="card-body">
                    <h6>TOTAL DESPACHOS</h6>
                    <h2 class="mb-0">{{ $stats->total_despachos }} ops</h2>
                </div>
            </div>
        </div>
        @if(!$clienteSeleccionado)
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-warning text-dark">
                    <div class="card-body">
                        <h6>CLIENTE MÁS ACTIVO</h6>
                        <h5 class="mb-0">{{ $porCliente->sortByDesc('total')->first()->cliente->nombre ?? 'N/A' }}</h5>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="grafico-tendencia"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="grafico-clientes"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti-layout-list-post text-primary"></i> Ranking de Clientes (Consumo vs Saldo)</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="exportTableToCSV('resumen.csv')">Exportar</button>
            </div>
            <div class="card-body">
                <button id="sendTelegramButton" class="btn btn-primary mt-3">
                    <i class="fa fa-paper-plane"></i> Enviar Reporte a Telegram
                </button>
                <div class="table-responsive printableArea">
                    <table class="table table-hover datatable align-middle" id="tabla-resumen">
                        <thead class="table-light">
                            <tr>
                                @if($clienteSeleccionado)
                                    <th>Fecha</th>
                                    <th>Vehículo/Placa</th>
                                    <th>Ticket</th>
                                    <th class="text-end">Saldo Inicial</th>
                                    <th class="text-end">Litros</th>
                                    <th class="text-end">Saldo Restante</th>
                                @else 
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Disponible Actual (Lts)</th>
                                    <th class="text-end">Total Consumido</th>
                                    <th class="text-end no-tg">Promedio x Despacho</th>
                                    <th class="text-center">Nro. Despachos</th>
                                    <th class="text-center no-tg">Estatus Saldo</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @if($clienteSeleccionado)

                                <tr class="table-info">
                                    <td class="small"><strong>{{ $fechaInicio->format('d/m/Y') }}</strong></td>
                                    <td  class="text-end"><strong>INICIO DE PERIODO </strong></td>
                                    <td  ><strong>(Saldo Anterior)</strong></td>
                                    <td  class="text-end fw-bold">{{ number_format($saldoInicialPeriodo, 2) }} L</td>
                                    <td  class="text-center"><i class="fa fa-lock text-muted"></i></td>
                                    <td  class="text-center"><i class="fa fa-lock text-muted"></i></td>
                                </tr>
                                @foreach($tendenciaDetallada as $mov) {{-- Necesitarás traer estos movs en el controlador --}}
                                <tr>
                                    <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($mov->tipo_movimiento == 'salida')
                                            <span class="badge bg-light text-dark border">{{ $mov->vehiculo->placa ?? 'N/A' }}</span>
                                        @else
                                            <span class="badge bg-success text-white"><i class="fa fa-arrow-up"></i> RECARGA</span>
                                        @endif
                                    </td>
                                    <td>{{ $mov->nro_ticket }}</td>
                                    <td class="text-end fw-bold">{{ number_format($mov->cant_inicial ?? 0, 2) }} L</td>
                                    <td class="text-end fw-bold">{{ number_format($mov->cantidad_litros, 2) }} L</td>
                                    <td class="text-end fw-bold">{{ number_format($mov->cant_final ?? $mov->cantidad_litros, 2) }} L</td>
                                    
                                </tr>
                                @endforeach
                            @else
                                @foreach($resumenClientes as $index => $c)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>{{ $c->nombre }}</td>
                                    <td class="text-end fw-bold">{{ number_format($c->prepagado, 2) }} L</td>
                                    <td class="text-end text-primary fw-bold">{{ number_format($c->total_consumido ?? 0, 2) }} L</td>
                                    <td class="text-end no-tg">{{ number_format($c->promedio_consumo ?? 0, 2) }} L</td>
                                    <td class="text-center">{{ $c->total_despachos }}</td>
                                    <td class="text-center no-tg">
                                        @if($c->prepagado <= 50)
                                            <span class="badge bg-danger">Saldo Crítico</span>
                                        @elseif($c->prepagado <= 100)
                                            <span class="badge bg-warning text-dark">Saldo Bajo</span>
                                        @else
                                            <span class="badge bg-success">Óptimo</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push('scripts')

    <!-- Script de jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
    
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
<script>

    const printableArea = document.querySelector('.printableArea');
const sendTelegramButton = document.querySelector('#sendTelegramButton');
const elementToCaptureSelector = '.printableArea';

document.addEventListener('DOMContentLoaded', function () {
    // Grafico de Tendencia (Lineas)
        Highcharts.chart('grafico-tendencia', {
            title: { 
                text: '{{ $view == "hoy" ? "Consumo por Horas" : "Tendencia de Consumo Diaria" }}' 
            },
            chart: { type: 'line' },
            xAxis: { 
                // Usamos la columna 'tiempo' que ahora trae o la hora o la fecha
                categories: @json($tendencia->pluck('tiempo')),
                title: { text: '{{ $view == "hoy" ? "Intervalo Horario" : "Días del Periodo" }}' }
            },
            yAxis: { 
                title: { text: 'Litros' },
                labels: { format: '{value} L' } 
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true,
                        format: '{y} L',
                        style: { fontWeight: 'bold' }
                    }
                }
            },
            series: [{
                name: 'Litros Despachados',
                // Aseguramos que los datos sean numéricos para Highcharts
                data: @json($tendencia->pluck('total')).map(Number),
                color: '#007bff'
            }],
            credits: false
        });

    // Grafico de Distribucion (Pastel)
    Highcharts.chart('grafico-clientes', {
        chart: { type: 'pie' },
        title: { text: '{{ $clienteSeleccionado ? "% Por Unidad" : "% Por Cliente" }}' },
        series: [{
            name: 'Litros',
            data: @json($clienteSeleccionado 
                ? $distribucionUnidades->map(fn($u) => ['name' => $u->vehiculo->placa ?? 'S/P', 'y' => (float)$u->total])
                : $porCliente->map(fn($c) => ['name' => $c->cliente->nombre, 'y' => (float)$c->total]))
        }]
    });


async function sendReportToTelegram() {
    sendTelegramButton.disabled = true;
    
    // 1. Ocultar columnas no deseadas para la foto
    const area = document.querySelector('.printableArea');
    area.classList.add('hide-for-capture');

    try {
        // 1. Extraer datos básicos del DOM
        const totalSurtido = document.querySelector('.bg-primary h2').innerText;
        const totalOps = document.querySelector('.bg-info h2').innerText;
        const periodoLabel = "{{ $label }}";
        const esIndividual = "{{ $cliente_id ? 'true' : 'false' }}" === 'true';

        let caption = `📊 *REPORTE DE CONSUMO INDUSTRIAL*\n`;
        caption += `📅 *Periodo:* ${periodoLabel}\n`;
        caption += `━━━━━━━━━━━━━━━━━━\n`;

        if (esIndividual) {
            // --- MODO CLIENTE ESPECÍFICO ---
            const clienteNombre = "{{ $clienteSeleccionado->nombre ?? '' }}";
            const saldoActual = "{{ number_format($clienteSeleccionado->prepagado ?? 0, 2) }} L";
            const autonomia = document.querySelector('.bg-info, .bg-danger')?.querySelector('h2')?.innerText || 'N/A';

            caption += `👤 *Cliente:* ${clienteNombre}\n`;
            caption += `⛽ *Consumido:* ${totalSurtido}\n`;
            caption += `💰 *Saldo Disponible:* ${saldoActual}\n`;
            caption += `⏳ *Autonomía Est.:* ${autonomia}\n`;
            caption += `🎫 *Despachos:* ${totalOps}\n`;
        } else {
            // --- MODO GENERAL (TODOS LOS CLIENTES) ---
            const topCliente = document.querySelector('.bg-warning h5').innerText;
            
            caption += `👥 *Cobertura:* Todos los Clientes\n`;
            caption += `⛽ *Total Despachado:* ${totalSurtido}\n`;
            caption += `🎫 *Total Operaciones:* ${totalOps}\n`;
            caption += `🏆 *Mayor Consumo:* ${topCliente}\n`;
            
            // Opcional: Agregar conteo de la tabla de resumen
            const cantClientes = document.querySelectorAll('#tabla-resumen tbody tr').length;
            caption += `📋 *Clientes Atendidos:* ${cantClientes}\n`;
        }

        caption += `━━━━━━━━━━━━━━━━━━\n`;
        caption += `✅ _Generado por Sistema Impordiesel_`;

        // 3. Capturar la imagen con html2canvas
        const canvas = await html2canvas(area, {
            allowTaint: true, 
            useCORS: true,
            scale: 2,
            backgroundColor: "#ffffff"
        });

        // Revelar columnas nuevamente
        area.classList.remove('hide-for-capture');

        // 4. Procesar imagen
        const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
        
        // 5. Preparar envío
        const formData = new FormData();
        formData.append('chart_image', imageBlob, 'reporte_industrial.png');
        formData.append('caption', caption); // Usamos el caption generado arriba

        const response = await fetch('{{ route('telegram.send.photo') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: formData
        });

        if (response.ok) {
            alert('✅ Reporte y resumen enviados a Telegram.');
        }

    } catch (error) {
        area.classList.remove('hide-for-capture');
        console.error('Error:', error);
        alert('❌ Error al generar el reporte.');
    } finally {
        area.classList.remove('hide-for-capture');
        document.querySelector('h3').innerText = tituloOriginal;
        sendTelegramButton.disabled = false;
    }
}

if (sendTelegramButton) {
        sendTelegramButton.addEventListener('click', sendReportToTelegram);
    }

});

 $(document).ready(function() {
     $('.datatable').DataTable({
                language: {
                    "decimal": "",
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Entradas",
                    "infoFiltered": "(Filtrado de _MAX_ total entradas)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ Entradas",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "pageLength": 20,
                "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"] ],
                layout: {
                    topStart: {
                        buttons: ['csv', 'excel', 'pdf', 'print']
                    }
                },
            });
});
</script>
@endpush