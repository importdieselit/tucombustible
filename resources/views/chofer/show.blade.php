@extends('layouts.app')

@section('title', 'Detalle de Chofer')
@push('styles')
<style>
    .form-check-input.custom-switch:checked {
        background-color: #0d6efd; /* Color para CHOFER */
        border-color: #0d6efd;
    }
    .form-check-input.custom-switch {
        background-color: #f2A435; /* Color para AYUDANTE */
        border-color: #f2A435;
    }
    .fw-black { font-weight: 900 !important; }
    .text-orange { color: #f2A435 !important; }
</style>
@endpush
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
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                            <div>
                                <span class="fw-black text-uppercase small text-muted d-block" style="font-size: 10px;">Cargo Actual</span>
                                <h6 id="cargo-label" class="mb-0 fw-bold {{ $chofer->cargo == 'CHOFER' ? 'text-primary' : 'text-orange' }}">
                                    {{ $chofer->cargo }}
                                </h6>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input custom-switch" type="checkbox" role="switch" 
                                    id="switchCargo" 
                                    {{ $chofer->cargo == 'CHOFER' ? 'checked' : '' }}
                                    data-id="{{ $chofer->id }}"
                                    style="cursor: pointer; width: 3em; height: 1.5em;">
                            </div>
                        </div>
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
                  {{-- SECCIÓN: DOCUMENTACIÓN DIGITAL --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-start border-4 border-orange">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center py-3">
                <h6 class="text-white mb-0 fw-black text-uppercase small">
                    <i class="fas fa-id-card me-2 text-orange"></i> Documentación del Chofer
                </h6>
            </div>
            <div class="card-body bg-light">
                <div class="row g-4">
                    @foreach($documentosRequeridos as $doc)
                        @php
                            $pathBase = "storage/choferes/{$chofer->id}/documentos/{$doc->abreviatura}_{$chofer->id}";
                            $extensiones = ['pdf', 'jpg', 'png', 'jpeg'];
                            $fileUrl = null;
                            $isPdf = false;

                            foreach($extensiones as $ext) {
                                if(file_exists(public_path("{$pathBase}.{$ext}"))) {
                                    $fileUrl = asset("{$pathBase}.{$ext}");
                                    $isPdf = ($ext === 'pdf');
                                    break;
                                }
                            }
                        @endphp

                        <div class="col-md-12 col-lg-12">
                            <div class="card h-100 shadow-sm border-0 overflow-hidden">
                                <div class="card-header bg-white border-bottom py-2">
                                    <label class="fw-black text-uppercase text-muted mb-0" style="font-size: 10px;">
                                        {{ $doc->nombre }} ({{ $doc->abreviatura }})
                                    </label>
                                </div>
                                
                                {{-- Visor en vivo --}}
                                <div class="ratio ratio-4x3 bg-dark d-flex align-items-center justify-content-center overflow-hidden">
                                    @if($fileUrl)
                                        @if($isPdf)
                                            <iframe src="{{ $fileUrl }}#toolbar=0&navpanes=0" width="100%" height="100%"></iframe>
                                        @else
                                            <img src="{{ $fileUrl }}" class="img-fluid object-fit-cover" style="cursor: pointer;" onclick="window.open('{{ $fileUrl }}', '_blank')">
                                        @endif
                                    @else
                                        <div class="text-center text-white-50 p-4">
                                            <i class="fas fa-file-upload fa-2x mb-2"></i>
                                            <p class="small fw-bold text-uppercase mb-0" style="font-size: 9px;">Pendiente por cargar</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Botón de Carga/Edición --}}
                                <div class="card-footer bg-white p-2">
                                    <form action="{{ route('choferes.upload.doc', $chofer->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="tipo_id" value="{{ $doc->id }}">
                                        <div class="input-group input-group-sm">
                                            <input type="file" name="documento" class="form-control" accept=".pdf,.jpg,.png" onchange="this.form.submit()">
                                            <span class="input-group-text bg-{{ $fileUrl ? 'orange' : 'secondary' }} text-white border-0">
                                                <i class="fas fa-{{ $fileUrl ? 'sync-alt' : 'cloud-upload-alt' }}"></i>
                                            </span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card mb-3 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="m-0">Resumen de pagos del mes</h5>
                            <div>
                                <label for="filtro-mes" class="me-2 mb-0">Mes:</label>
                                <input 
                                    type="month" 
                                    id="filtro-mes"
                                    class="form-control d-inline-block"
                                    style="width: 180px;"
                                    value="{{ now()->format('Y-m') }}">
                            </div>
                        </div>

                        <div class="alert alert-info py-2 mb-0" id="resumen-mensual">
                            Total del mes actual: 
                            <strong id="total-mensual" class="text-success fs-5">$0.00</strong>
                        </div>
                    </div>
                    
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
                                        $pago= App\Models\ViaticoViaje::where('viaje_id', $viaje['viaje_id']);
                                        if($chofer->cargo=='CHOFER'){
                                            $pago=$pago->where('concepto','Pago Chofer');
                                        }else{
                                            $pago=$pago->where('concepto','Pago Ayudantes');
                                        }
                                        $pago=$pago->get()->first();
                                    @endphp
                                    
                                    <tr class="viaje-row" 
                                        data-fecha="{{ \Carbon\Carbon::parse($viaje['fecha'] ?? $viaje['created_at'])->format('Y-m-d') }}" 
                                        data-monto="{{ $pago->monto_ajustado ?? $pago->monto_base ?? 0 }}">
                                        <td>{{ $viaje['ruta'] }}</td>
                                        <td>{{ date('d/m/Y',strtotime($viaje['fecha'])) }}</td>
                                        <td>{{ $viaje['incidencias'] ?? 'No hay incidencias'}}</td>
                                        <td>{{ $pago->monto_ajustado ?? $pago->monto_base ?? 0 }}</td>                                    </tr>
                                    <tr>
                                        
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
        
         const filtroMes = document.getElementById('filtro-mes');
        const totalMensual = document.getElementById('total-mensual');

        function filtrarYSumar(mesSeleccionado) {
            const filas = document.querySelectorAll('.viaje-row');
            let total = 0;
            let visibles = 0;

            filas.forEach(fila => {
                const fecha = fila.dataset.fecha || '';
                const monto = parseFloat(fila.dataset.monto || 0);

                if (fecha.startsWith(mesSeleccionado)) {
                    fila.style.display = ''; // mostrar
                    total += monto;
                    visibles++;
                } else {
                    fila.style.display = 'none'; // ocultar
                }
            });

            totalMensual.textContent = `$${total.toFixed(2)}`;
            
            // Mensaje si no hay viajes en el mes
            if (visibles === 0) {
                totalMensual.textContent = 'Sin registros';
                totalMensual.classList.remove('text-success');
                totalMensual.classList.add('text-muted');
            } else {
                totalMensual.classList.remove('text-muted');
                totalMensual.classList.add('text-success');
            }
        }

        // Inicializa con el mes actual
        const mesActual = filtroMes.value;
        filtrarYSumar(mesActual);

        // Recalcular al cambiar el mes
        filtroMes.addEventListener('change', e => {
            filtrarYSumar(e.target.value);
        });
    });

    $(document).ready(function() {
    $('#switchCargo').on('change', function() {
        const isChecked = $(this).is(':checked');
        const cargoValue = isChecked ? 'CHOFER' : 'AYUDANTE DE CHOFER';
        const choferId = $(this).data('id');
        const $label = $('#cargo-label');

        // Bloqueamos visualmente el switch mientras procesa
        $(this).prop('disabled', true);

        $.ajax({
            url: `{{ route('choferes.update-cargo', '') }}/${choferId}`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                cargo: cargoValue
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar interfaz
                    $label.text(response.nuevo_cargo);
                    if (response.nuevo_cargo === 'CHOFER') {
                        $label.removeClass('text-orange').addClass('text-primary');
                    } else {
                        $label.removeClass('text-primary').addClass('text-orange');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: response.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo actualizar el cargo', 'error');
                // Revertir el switch si falla
                $('#switchCargo').prop('checked', !isChecked);
            },
            complete: function() {
                $('#switchCargo').prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
@endsection
