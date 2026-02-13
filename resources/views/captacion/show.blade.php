@extends('layouts.app')

@section('title', 'Detalle Cliente en Captación')

@push('styles')
{{-- CSS para Toastr --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    .preview-cell .btn { transition: all 0.2s; }
    .preview-cell .btn:hover { transform: scale(1.05); }
    .card-header { border-bottom: none; font-weight: 600; }
    
    /* Visor mejorado para evitar cuadros negros y asegurar visibilidad */
    #previewContent {
        background-color: #f4f4f4 !important;
        min-height: 75vh;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        overflow-y: auto;
    }
    
    #previewContent img {
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        border: 10px solid white;
        border-radius: 4px;
        max-width: 95%;
        max-height: 70vh;
        object-fit: contain;
        display: block;
        margin: 20px auto;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- MENSAJES DE RETROALIMENTACIÓN --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4" role="alert">
            <i class="ri-check-double-line"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- COLUMNA IZQUIERDA --}}
        <div class="col-lg-8">
            {{-- INFORMACIÓN DEL SOLICITANTE --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="ri-building-line"></i> Información del Solicitante</h5>
                    <span class="badge bg-info fs-6 text-uppercase px-3 shadow-sm">
                        {{ str_replace('_',' ', $cliente->estatus_captacion) }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold text-uppercase">Razón Social</label>
                            <p class="fs-5 mb-0 text-dark fw-bold">{{ $cliente->razon_social }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold text-uppercase">RIF</label>
                            <p class="fs-5 mb-0">{{ $cliente->rif }}</p>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold text-uppercase">Correo Electrónico</label>
                            <p class="mb-0 text-primary">{{ $cliente->correo ?? 'N/D' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold text-uppercase">Teléfono</label>
                            <p class="mb-0">{{ $cliente->telefono ?? 'N/D' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <label class="text-muted small fw-bold text-uppercase">Ubicación y Dirección</label>
                            <p class="mb-0">{{ $cliente->estado }}, {{ $cliente->ciudad }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- EXPEDIENTE DIGITAL --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0"><i class="ri-folder-2-line"></i> Expediente Digital</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 80px;">Estado</th>
                                    <th>Documento Requerido</th>
                                    <th class="text-center" style="width: 100px;">Vista</th>
                                    <th class="text-center" style="width: 200px;">Validación / Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requisitos as $req)
                                    @php
                                        $archivo = $cliente->documentos->where('requisito_id', $req->id)->first();
                                    @endphp
                                    <tr id="row-{{ $req->id }}">
                                        <td class="text-center icon-cell">
                                            @if($archivo)
                                                <i class="ri-checkbox-circle-fill text-success fs-3"></i>
                                            @else 
                                                <i class="ri-close-circle-fill text-danger fs-3"></i> 
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold d-block">{{ $req->codigo }}</span>
                                            <small class="text-muted">{{ $req->descripcion }}</small>
                                        </td>
                                        <td class="text-center preview-cell">
                                            @if($archivo)
                                                <button class="btn btn-sm btn-dark preview-btn" data-file="{{ asset('storage/'.$archivo->ruta) }}">
                                                    <i class="ri-eye-line"></i> Ver
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                @if($archivo)
                                                    @if($archivo->validado)
                                                        <span class="badge bg-success py-2 px-3"><i class="ri-check-line"></i> Validado</span>
                                                    @else
                                                        <form action="{{ route('captacion.validar_documentos', $archivo->id) }}" method="POST">
                                                            @csrf
                                                            <button class="btn btn-sm btn-outline-success">Validar</button>
                                                        </form>
                                                    @endif
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-warning upload-btn"
                                                        data-id="{{ $cliente->id }}" data-req="{{ $req->id }}">
                                                    <i class="ri-upload-2-line"></i> Subir
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- Input de archivo con aria-label para evitar error de accesibilidad --}}
                    <input type="file" id="upload-file" class="d-none" name="archivo" accept="image/*,application/pdf" aria-label="Seleccionar documento para cargar">
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA --}}
        <div class="col-lg-4">

            {{-- APROBACIÓN FINAL --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0"><i class="ri-shield-check-line"></i> Aprobación Final</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <label class="text-muted small fw-bold text-uppercase">Consumo Solicitado</label>
                        <p class="fs-4 fw-bold text-success mb-0">{{ number_format($cliente->cantidad_litros, 0, ',', '.') }} Lts</p>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <form action="{{ route('captacion.aprobar', $cliente->id) }}" method="POST" 
                              onsubmit="return confirm('¿Confirmar aprobación final?')">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                <i class="ri-checkbox-circle-line"></i> APROBAR CLIENTE
                            </button>
                        </form>

                        <button type="button" class="btn btn-outline-danger w-100 py-2" data-bs-toggle="modal" data-bs-target="#modalRechazo">
                            <i class="ri-delete-bin-line"></i> RECHAZAR SOLICITUD
                        </button>
                    </div>
                </div>
            </div>
            
            <a href="{{ route('captacion.index') }}" class="btn btn-link w-100 mt-3 text-muted text-decoration-none">
                <i class="ri-arrow-left-line"></i> Volver al Listado
            </a>
        </div>
    </div>
</div>

{{-- MODAL VISOR --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="ri-eye-line"></i> Visor de Documentos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="previewContent">
                {{-- Contenido dinámico --}}
            </div>
        </div>
    </div>
</div>

{{-- MODAL RECHAZO --}}
<div class="modal fade" id="modalRechazo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Rechazo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="fs-5">¿Seguro que desea rechazar la solicitud de <strong>{{ $cliente->razon_social }}</strong>?</p>
                <div class="alert alert-danger mb-0">
                    <i class="ri-error-warning-fill"></i> Se eliminarán permanentemente sus documentos y su acceso.
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route('captacion.rechazar', $cliente->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger px-4">Confirmar y Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('upload-file');
    let currentReq = null;
    let currentCliente = null;

    // Delegación para subida
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.upload-btn');
        if (!btn) return;
        currentReq = btn.dataset.req;
        currentCliente = btn.dataset.id;
        input.click();
    });

    input.addEventListener('change', async function() {
        if (!input.files.length) return;
        const form = new FormData();
        form.append('archivo', input.files[0]);
        form.append('requisito_id', currentReq);
        form.append('_token', '{{ csrf_token() }}');

        toastr.info('Subiendo archivo...');
        try {
            const resp = await fetch(`/admin/captacion/subir-documento/${currentCliente}`, {
                method: 'POST',
                body: form
            });
            if (resp.ok) {
                toastr.success('Archivo cargado correctamente.');
                setTimeout(() => window.location.reload(), 1000);
            } else { toastr.error('Error al subir el archivo.'); }
        } catch (err) { toastr.error('Error de conexión.'); }
        input.value = '';
    });

    // Delegación para vista previa
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.preview-btn');
        if (!btn) return;

        const url = btn.dataset.file;
        const ext = url.split('.').pop().toLowerCase();
        const container = document.getElementById('previewContent');
        const modalEl = document.getElementById('previewModal');
        const modal = new bootstrap.Modal(modalEl);

        // Reset inicial del contenedor
        container.innerHTML = '<div class="text-center w-100 p-5"><div class="spinner-border text-success" role="status"></div><p class="mt-2">Procesando archivo...</p></div>';

        setTimeout(() => {
            if (ext === 'pdf') {
                container.innerHTML = `<iframe src="${url}" style="width:100%; height:80vh; border:none;"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'PNG', 'JPEG'].includes(ext)) {
                // Inyección limpia de imagen con botón de fallback
                container.innerHTML = `
                    <div class="w-100 p-3 text-center">
                        <img src="${url}" class="img-fluid shadow-lg" style="max-height: 70vh; width: auto; display: block; margin: 0 auto;">
                        <div class="mt-3 p-3 bg-white w-100 border-top">
                            <a href="${url}" target="_blank" class="btn btn-success px-4">
                                <i class="ri-external-link-line"></i> Abrir en pestaña nueva
                            </a>
                        </div>
                    </div>`;
            } else {
                container.innerHTML = `
                    <div class="p-5 text-center">
                        <i class="ri-file-warning-line fs-1 text-warning"></i>
                        <p class="mt-3">El formato (.${ext}) no permite vista previa directa.</p>
                        <a href="${url}" target="_blank" class="btn btn-dark px-4">Descargar Archivo</a>
                    </div>`;
            }
        }, 300);

        modal.show();
    });
});
</script>
@endpush
@endsection