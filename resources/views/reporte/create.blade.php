@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-navy text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-bullhorn me-2"></i>REPORTE EN RUTA</h5>
        </div>
        <div class="card-body">
            <form id="form-reporte" enctype="multipart/form-data">
                @csrf
                {{-- Inputs ocultos para GPS --}}
                <input type="hidden" name="latitud" id="latitud">
                <input type="hidden" name="longitud" id="longitud">

                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de Incidencia</label>
                    <select name="tipo_reporte" class="form-select form-select-lg" required>
                        <option value="">Seleccione...</option>
                        <option value="FALLA MECANICA">Falla Mecánica</option>
                        <option value="ACCIDENTE">Accidente / Colisión</option>
                        <option value="RETRASO">Retraso en Vía (Tráfico/Cierre)</option>
                        <option value="COMBUSTIBLE">Problema con Combustible</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" placeholder="Detalle lo sucedido..."></textarea>
                </div>

                <div class="mb-3 text-center">
                    <label class="form-label fw-bold d-block text-start">Evidencia Fotográfica</label>
                    <div class="upload-area p-4 border rounded bg-light" onclick="document.getElementById('foto').click()">
                        <i class="fas fa-camera fa-2x text-muted mb-2"></i>
                        <p class="small mb-0">Tocar para tomar foto</p>
                        <input type="file" name="foto" id="foto" accept="image/*" capture="environment" class="d-none">
                    </div>
                    <div id="preview" class="mt-2 d-none">
                        <img src="" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                </div>

                <div id="gps-status" class="alert alert-info py-2 small mb-3">
                    <i class="fas fa-spinner fa-spin me-2"></i>Obteniendo ubicación GPS...
                </div>

                <button type="submit" class="btn btn-navy btn-lg w-100 fw-bold shadow">
                    <i class="fas fa-paper-plane me-2"></i>ENVIAR REPORTE
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // 1. Obtener Ubicación automáticamente al abrir
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            $('#latitud').val(position.coords.latitude);
            $('#longitud').val(position.coords.longitude);
            $('#gps-status').removeClass('alert-info').addClass('alert-success')
                .html('<i class="fas fa-check-circle me-2"></i>Ubicación GPS fijada');
        }, function(error) {
            $('#gps-status').removeClass('alert-info').addClass('alert-warning')
                .html('<i class="fas fa-exclamation-triangle me-2"></i>GPS no disponible. Active su ubicación.');
        }, { enableHighAccuracy: true });
    }

    // 2. Previsualización de foto
    $('#foto').change(function(e) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#preview img').attr('src', e.target.result);
            $('#preview').removeClass('d-none');
        }
        reader.readAsDataURL(this.files[0]);
    });

    // 3. Envío AJAX
    $('#form-reporte').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        
        // Bloquear botón
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>ENVIANDO...');

        $.ajax({
            url: "{{ route('reporte.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                Swal.fire('¡Enviado!', res.message, 'success')
                    .then(() => window.location.reload());
            },
            error: function() {
                Swal.fire('Error', 'No se pudo enviar el reporte.', 'error');
                $btn.prop('disabled', false).text('ENVIAR REPORTE');
            }
        });
    });
});
</script>
@endpush