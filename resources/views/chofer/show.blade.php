@extends('layouts.app')

@section('title', 'Detalle de Chofer')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Detalle de Chofer</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('choferes.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('choferes.list') }}">Listado</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Información del Chofer</h5>
                    <a href="{{ route('choferes.edit', $chofer->id) }}" class="btn btn-warning text-white btn-sm" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center">
                        @if(is_null($chofer->foto))
                            <i class="fas fa-user-circle fa-8x text-secondary mb-3"></i>
                        @else
                            <img src="{{ asset('storage/choferes/foto/' . $chofer->foto) }}" class="text-secondary mb-3 round" style="border-radius: 50%; height: 250px;" alt="foto {{ $chofer->persona->nombre }}">
                        @endif
                        <h4 class="mb-0">{{ $chofer->persona->nombre }}</h4>
                        <p class="text-muted">{{ $chofer->persona->dni }}</p>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Licencia No.</h6>
                            <p class="font-weight-bold">{{ $chofer->licencia_numero }}</p>
                            <h6 class="text-muted">Vencimiento Licencia</h6>
                            <p class="font-weight-bold">
                                <span class="badge {{ $chofer->licenciaVencida() ? 'bg-danger' : ($chofer->licenciaPorVencer() ? 'bg-warning' : 'bg-success') }}">
                                    {{ $chofer->licencia_vencimiento }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Documento Vialidad No.</h6>
                            <p class="font-weight-bold">{{ $chofer->documento_vialidad_numero ?? 'N/A' }}</p>
                            <h6 class="text-muted">Vencimiento Doc. Vialidad</h6>
                            <p class="font-weight-bold">{{ $chofer->documento_vialidad_vencimiento ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <h6 class="text-muted mt-3">Vehículo Asignado</h6>
                    <p class="font-weight-bold">{{ $chofer->vehiculo ? $chofer->vehiculo->placa . ' - ' . $chofer->vehiculo->marca : 'No asignado' }}</p>
                </div>
                <div class="card-body">
                    @if(!is_null($chofer->soporte_documento))
                        @php
                            $documentos= explode(';',$chofer->soporte_documento);
                        @endphp
                        @foreach($documentos as $documento)
                            @php 
                                // Esto es una forma básica en PHP/Blade de obtener la extensión
                                $extension = pathinfo(asset('storage/choferes/documentos/'.$documento), PATHINFO_EXTENSION);
                                $extension = strtolower($extension);
                                $ruta_soporte = asset('storage/choferes/documentos/' . $documento);
                            @endphp
                            @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif']))
                                <div class="text-center mb-4 border rounded p-3 bg-light">
                                    <img src="{{ $ruta_soporte }}" 
                                        alt="Documento de Soporte - Imagen" 
                                        class="img-fluid rounded shadow-sm"
                                        style="max-height: 80vh; border: 1px solid #ddd;">
                                </div>
                            @elseif($extension === 'pdf')
                                <div class="mb-4">
                                    <h5 class="text-danger mb-3"><i class="bi bi-file-pdf-fill me-1"></i> Documento PDF</h5>
                                    
                                    {{-- Opción 1: Usar la etiqueta <iframe> para incrustar --}}
                                    <div class="embed-responsive embed-responsive-16by9" style="height: 600px; width: 100%;">
                                        <iframe src="{{ $ruta_soporte }}" 
                                                style="width: 100%; height: 100%; border: none;"
                                                title="Documento PDF de Soporte"
                                                loading="lazy">
                                            <p>Este navegador no soporta iframes. <a href="{{ $ruta_soporte }}" target="_blank">Descargar PDF</a></p>
                                        </iframe>
                                    </div>

                                    {{-- Botón de descarga para PDF --}}
                                    <div class="text-center mt-3">
                                        <a href="{{ $ruta_soporte }}" target="_blank" class="btn btn-outline-danger">
                                            <i class="bi bi-cloud-arrow-down-fill me-2"></i> Abrir o Descargar PDF en Pestaña Nueva
                                        </a>
                                    </div>
                                </div>

                            {{-- Lógica para Otros Tipos de Archivos (Fallback) --}}
                            @else
                                <div class="alert alert-warning text-center">
                                    <p class="mb-2"><i class="bi bi-file-earmark-exclamation me-2"></i> **Tipo de archivo no compatible para previsualización directa ({{ strtoupper($extension) }}).**</p>
                                    <p class="mb-0">Solo se previsualizan Imágenes y PDF.</p>
                                </div>
                                {{-- Botón de descarga general --}}
                                <div class="text-center mt-3">
                                    <a href="{{ $ruta_soporte }}" target="_blank" class="btn btn-warning">
                                        <i class="bi bi-cloud-arrow-down-fill me-2"></i> Descargar Archivo ({{ strtoupper($extension) }})
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle me-2"></i> No hay ningún documento o imagen de soporte asociado.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title m-0">Historial de Viajes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ruta</th>
                                    <th>Fecha</th>
                                    <th>Incidencias</th>
                                    <th>Pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($historialViajes as $viaje)
                                    @php
                                        $pago= ViaticoViaje::where('viaje_id', $viaje->id);
                                        if($chofer->cargo=='CHOFER'){
                                            $pago->where('concepto','Pago Chofer');
                                        }else{
                                            $pago->where('concepto','Pago Ayudantes');
                                        }

                                        $pago->get()->first();

                                    @endphp
                                    <tr>
                                        <td>{{ $viaje['ruta'] }}</td>
                                        <td>{{ date('d/m/Y',strtotime($viaje['fecha'])) }}</td>
                                        <td>{{ $viaje['incidencias'] ?? 'No hay incidencias'}}</td>
                                        <td>{{ $pago->monto }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="card-title m-0">Rendimiento Histórico</h5>
                </div>
                <div class="card-body">
                    <canvas id="rendimientoHistoricoChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const graficaRendimiento = @json($graficaRendimiento);
        const ctx = document.getElementById('rendimientoHistoricoChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: graficaRendimiento.labels,
                datasets: [{
                    label: 'Calificación',
                    data: graficaRendimiento.data,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        title: {
                            display: true,
                            text: 'Puntuación (1-5 Estrellas)'
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
