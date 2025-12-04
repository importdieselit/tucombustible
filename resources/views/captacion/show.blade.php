@extends('layouts.app')

@section('title', 'Detalle Cliente en Captación')

@section('content')
<div class="container-fluid py-4">

    {{-- MENSAJES TIPO TOAST --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="ri-check-double-line"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="ri-error-warning-line"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif


    <div class="row g-4">
        <!-- =======================
             PANEL PRINCIPAL
        =========================== -->
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ri-building-line"></i> Información del Cliente
                    </h5>

                    <span class="badge bg-info fs-6 text-uppercase">
                        {{ str_replace('_',' ', $cliente->estatus_captacion) }}
                    </span>
                </div>

                <div class="card-body">

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Nombre / Razón Social:</strong><br>
                            {{ $cliente->razon_social }}
                        </div>

                        <div class="col-md-6">
                            <strong>RIF:</strong><br>
                            {{ $cliente->rif }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Correo:</strong><br>
                            {{ $cliente->correo ?? 'N/D' }}
                        </div>

                        <div class="col-md-6">
                            <strong>Teléfono:</strong><br>
                            {{ $cliente->telefono ?? 'N/D' }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col">
                            <strong>Dirección Fiscal:</strong><br>
                            {{ $cliente->direccion ?? 'N/D' }}
                        </div>
                    </div>

                    <hr>

                    {{-- <a href="{{ route('captacion.edit', $cliente->id) }}" class="btn btn-warning">
                        <i class="ri-edit-2-line"></i> Editar Datos
                    </a> --}}

                </div>
            </div>

          <!-- ==================================================
     DOCUMENTOS SUBIDOS Y VALIDACIÓN
=================================================== -->
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="ri-folder-2-line"></i> Documentación</h5>
    </div>
<div class="card-body">

    <h6 class="fw-bold mb-3">Documentos Requeridos</h6>

    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 60px;">Estado</th>
                <th>Anexo</th>
                <th>Documento</th>
                <th style="width: 100px;">Vista</th>
                <th style="width: 120px;">Acción</th>
            </tr>
        </thead>
        <tbody>

            @foreach($requisitos as $req)
                @php
                    $archivo = $cliente->requisitosCompletos()
                        ->where('requisito_id', $req->id)
                        ->first();
                @endphp

                <tr id="row-{{ $req->id }}">
                    <td class="text-center icon-cell">
                        @if($archivo)
                            <i class="fa fa-check text-success fs-4"></i>
                        @else 
                            <i class="fa fa-close text-danger fs-4"></i> 
                        @endif
                    </td>
                    <td>{{ $req->codigo }} </td>
                    <td>{{ $req->descripcion }}</td>

                    <td class="text-center preview-cell">
                        @if($archivo)
                            <button class="btn btn-sm btn-dark preview-btn"
                                    data-file="{{ asset('storage/'.$archivo->ruta) }}">
                                <i class="ri-eye-line"></i> Ver
                            </button>
                        @else
                            —
                        @endif
                    </td>

                    <td class="text-center">
                        <button class="btn btn-sm btn-warning upload-btn"
                                data-id="{{ $cliente->id }}"
                                data-req="{{ $req->id }}"
                                data-cod="{{ $req->codigo }}">
                            <i class="ri-upload-cloud-2-line"></i> Subir
                        </button>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>

    <!-- Input oculto -->
    <input type="file" id="upload-file" class="d-none" accept="image/*,application/pdf">

</div>

</div>


        </div>


        <!-- =======================
             PANEL DERECHO – ACCIONES
        =========================== -->
        <div class="col-lg-4">

            <!-- =======================
                 PLANILLAS
            =========================== -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="ri-file-paper-2-line"></i> Planillas
                    </h5>
                </div>

                <div class="card-body">

                    @if($cliente->estatus_captacion !== 'planillas_enviadas')
                        <form action="{{ route('captacion.enviar_planillas', $cliente->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-secondary w-100">
                                <i class="ri-mail-send-line"></i> Enviar Planillas
                            </button>
                        </form>
                    @else
                        <div class="alert alert-success">
                            <i class="ri-check-double-line"></i> Planillas ya enviadas.
                        </div>
                    @endif

                </div>
            </div>


            <!-- =======================
                 ESTATUS Y APROBACIÓN
            =========================== -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="ri-shield-check-line"></i> Estatus de Aprobación
                    </h5>
                </div>

                <div class="card-body">

                    <p><strong>Estatus actual:</strong></p>

                    <span class="badge bg-dark text-uppercase p-2">
                        {{ str_replace('_',' ', $cliente->estatus_captacion) }}
                    </span>


                    <!-- SI TIENE DOCUMENTOS FALTANTES -->
                    @if(count($cliente->faltantes()) > 0)
                        <div class="alert alert-warning mt-3">
                            <i class="ri-alert-line"></i>
                            Faltan documentos para continuar.
                        </div>
                    @endif


                    <!-- APROBAR SOLO SI YA PASÓ TODO -->
                    @if($cliente->estatus_captacion === 'esperando_inspeccion')
                        <form action="{{ route('captacion.aprobar', $cliente->id) }}" method="POST" class="mt-3">
                            @csrf
                            <button class="btn btn-success w-100">
                                <i class="ri-checkbox-circle-line"></i> Aprobar Cliente
                            </button>
                        </form>
                    @endif

                </div>
            </div>

        </div>

    </div>
</div>

<!-- MODAL PREVIEW -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="ri-eye-line"></i> Vista Previa del Documento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center" id="previewContent" 
                 style="min-height: 75vh; display:flex; align-items:center; justify-content:center;">
                <!-- Aquí se carga la vista previa -->
            </div>

        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('upload-file');
    let currentReq = null;
    let currentCliente = null;

    document.querySelectorAll('.upload-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentReq = btn.dataset.req;
            currentCliente = btn.dataset.id;
            currentCod = btn.dataset.cod;
            input.click();
        });
    });

    input.addEventListener('change', function () {
        if (!this.files.length) return;

        let formData = new FormData();
        formData.append('documento', this.files[0]);
        formData.append('requisito_id', currentReq);
        formData.append('codigo', currentCod);
        formData.append('_token', '{{ csrf_token() }}');

        fetch(`/captacion/${currentCliente}/subir-documento`, {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (data.status === 'ok') {
                const row = document.getElementById('row-'+currentReq);

                // Actualizar icono a CHECK
                row.querySelector('.icon-cell').innerHTML =
                    `<i class="ri-check-line text-success fs-4"></i>`;

                // Actualizar botón de vista previa
                row.querySelector('.preview-cell').innerHTML =
                    `<a href="/storage/${data.ruta}" target="_blank" class="btn btn-sm btn-dark">
                        <i class="ri-eye-line"></i> Ver
                    </a>`;

                // Notificación
                toastr.success("Documento cargado correctamente.");
            }
        })
        .catch(() => toastr.error("Error al subir el documento."));
    });

     document.querySelectorAll('.preview-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.dataset.file;
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            const container = document.getElementById('previewContent');

            container.innerHTML = `<div>Cargando vista previa...</div>`;

            const ext = url.split('.').pop().toLowerCase();

            // PDFs → iframe
            if (ext === "pdf") {
                container.innerHTML = `
                    <iframe src="${url}" 
                        style="width:100%; height:75vh; border:none;"></iframe>
                `;
            }

            // Imagenes → <img>
            else if (["jpg", "jpeg", "png", "webp"].includes(ext)) {
                container.innerHTML = `
                    <img src="${url}" 
                        style="max-width:100%; max-height:75vh; border-radius:6px;">
                `;
            }

            // Otros formatos → descargar o abrir en pestaña nueva
            else {
                container.innerHTML = `
                    <div class="p-4 text-center">
                        <p class="fs-5">No es posible mostrar este formato en vista previa.</p>
                        <a href="${url}" target="_blank" class="btn btn-primary">
                            <i class="ri-external-link-line"></i> Abrir/Descargar
                        </a>
                    </div>
                `;
            }

            modal.show();
        });
    });
});
</script>
@endsection