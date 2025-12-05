@extends('layouts.app')

@section('title', 'Detalle Cliente en Captación')
@push('styles')
{{-- CSS para Toastr --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush
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
                        <button type="button" class="btn btn-sm btn-warning upload-btn"
                                data-id="{{ $cliente->id }}"
                                data-req="{{ $req->id }}"
                                data-cod="{{ $req->codigo }}">
                            <i class="fa fa-upload"></i> Subir
                        </button>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>

    <!-- Input oculto -->
    <input type="file" id="upload-file" class="d-none" name="archivo" accept="image/*,application/pdf">

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
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('upload-file');
    if (!input) {
        console.error('No se encontró input#upload-file');
        return;
    }
    console.log('input#upload-file encontrado correctamente');

    // contexto compartido
    let currentReq = null;
    let currentCliente = null;
    let currentCod = null;

    // función única y reutilizable para procesar la subida
    async function handleFileChange() {
        console.log('Archivo seleccionado para subir:', input.files);
        if (!input.files || !input.files.length) return;

        const file = input.files[0];
        if (!file) return;

        if (!currentReq || !currentCliente) {
            toastr?.error('Contexto inválido: falta requisito o cliente.');
            input.value = '';
            return;
        }

        const form = new FormData();
        form.append('archivo', file);
        form.append('requisito_id', currentReq);
        if (currentCod) form.append('codigo', currentCod);
        form.append('_token', '{{ csrf_token() }}');
        console.log('Iniciando subida para cliente:', currentCliente, 'requisito:', currentReq);

         // Realiza la petición
        try {
            // muestra feedback básico
            toastr?.info('Subiendo documento...');

            const resp = await fetch(`/captacion/${currentCliente}/subir-documento`, {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            });

            const json = await resp.json();

            if (resp.ok && json.status === 'ok') {
                toastr?.success('Documento cargado correctamente.');
                console.log('Upload exitoso:', json);
                // Actualizar fila sin recargar
                const row = document.getElementById('row-' + currentReq);
                if (row) {
                    const iconCell = row.querySelector('.icon-cell');
                    if (iconCell) iconCell.innerHTML = '<i class="ri-check-line text-success fs-4"></i>';

                    const previewCell = row.querySelector('.preview-cell');
                    if (previewCell) {
                        previewCell.innerHTML = `<button class="btn btn-sm btn-dark preview-btn" data-file="/storage/${json.ruta}">
                            <i class="ri-eye-line"></i> Ver
                        </button>`;
                    }
                    
                    // reemplaza el botón subir por el badge/validar si quieres:
                    const actionCell = row.querySelector('td:last-child');
                    if (actionCell) {
                        actionCell.innerHTML = json.validado ? '<span class="badge bg-success">Validado</span>'
                            : `<form action="/captacion/validar-documento/${json.documento_id}" method="POST">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button class="btn btn-sm btn-success">Validar</button>
                               </form>`;
                    }
                }
            } else {
                const msg = json.message || 'Error al subir documento';
                toastr?.error(msg);
                console.error('Upload error', json);
            }
        } catch (err) {
            console.error('Error de red al subir documento', err);
            toastr?.error('Error de red al subir documento');
        } finally {
            // limpiar estado para próxima operación
            input.value = '';
            currentReq = null;
            currentCliente = null;
            currentCod = null;
        }
    }

    // attach once
    input.addEventListener('change', handleFileChange);

    // Delegación: escucha clicks en todo el body para botones .upload-btn
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.upload-btn');
        if (!btn) return;

        currentReq = btn.dataset.req ?? btn.getAttribute('data-req');
        currentCliente = btn.dataset.id ?? btn.getAttribute('data-id');
        currentCod = btn.dataset.cod ?? btn.getAttribute('data-cod');

        console.log('Iniciando upload -> req:', currentReq, 'cliente:', currentCliente, 'cod:', currentCod);

        if (!currentReq || !currentCliente) {
            toastr?.error('Botón mal configurado (falta data-req o data-id).');
            return;
        }

        // dispara selector
        input.click();
    });

    // Delegación para preview (soporta elementos dinámicos)
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.preview-btn');
        if (!btn) return;

        const url = btn.dataset.file;
        if (!url) return;

        const ext = url.split('.').pop().toLowerCase();
        const modalEl = document.getElementById('previewModal');
        const modal = new bootstrap.Modal(modalEl);
        const container = document.getElementById('previewContent');

        container.innerHTML = '<div>Cargando vista previa...</div>';

        if (ext === 'pdf') {
            container.innerHTML = `<iframe src="${url}" style="width:100%;height:75vh;border:none;"></iframe>`;
        } else if (['jpg','jpeg','png','webp'].includes(ext)) {
            container.innerHTML = `<img src="${url}" style="max-width:100%;max-height:75vh;border-radius:6px;">`;
        } else {
            container.innerHTML = `<div class="p-4 text-center">
                <p class="mb-3">No es posible mostrar este formato en el visor.</p>
                <a href="${url}" target="_blank" class="btn btn-primary">Abrir / Descargar</a>
            </div>`;
        }
        modal.show();
    });

});

</script>
@endpush
@endsection