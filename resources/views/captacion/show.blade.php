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
                    <th>Documento</th>
                    <th style="width: 100px;">Vista</th>
                    <th style="width: 120px;">Acción</th>
                </tr>
            </thead>

            <tbody>
                @foreach($cliente->documentos_requeridos ?? [] as $doc)
                    @php
                        $subidos = $cliente->documentos_subidos ?? [];
                        $tiene = array_key_exists($doc, $subidos);
                        $ruta = $tiene ? asset('storage/' . $subidos[$doc]) : null;
                    @endphp

                    <tr data-doc="{{ $doc }}">
                        <td class="icon-cell text-center">
                            @if($tiene)
                                <i class="ri-check-line text-success fs-4"></i>
                            @else
                                <i class="ri-close-line text-danger fs-4"></i>
                            @endif
                        </td>

                        <td>{{ $doc }}</td>

                        <td class="preview-cell text-center">
                            @if($tiene)
                                <a href="{{ $ruta }}" target="_blank" class="btn btn-sm btn-dark">
                                    <i class="ri-eye-line"></i> Ver
                                </a>
                            @else
                                —
                            @endif
                        </td>

                        <td class="text-center">
                            @if(!$tiene)
                                <button 
                                    class="btn btn-sm btn-warning upload-btn" 
                                    data-doc="{{ $doc }}" 
                                    data-id="{{ $cliente->id }}"
                                >
                                    <i class="ri-upload-cloud-2-line"></i> Subir
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Input oculto -->
        <input type="file" id="upload-file" class="d-none">

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
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const fileInput = document.getElementById('upload-file');

    let currentDoc = null;
    let currentId = null;

    document.querySelectorAll('.upload-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentDoc = btn.dataset.doc;
            currentId  = btn.dataset.id;
            fileInput.click();
        });
    });

    fileInput.addEventListener('change', async function () {

        if (!this.files.length) return;

        const formData = new FormData();
        formData.append('documento', currentDoc);
        formData.append('archivo', this.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        const url = `/captacion/${currentId}/upload-doc`;
        let row = document.querySelector(`tr[data-doc="${currentDoc}"]`);

        try {
            let res = await fetch(url, {
                method: 'POST',
                body: formData
            });

            let json = await res.json();

            if (json.ok) {

                row.querySelector('.icon-cell').innerHTML =
                    `<i class="ri-check-line text-success fs-4"></i>`;

                row.querySelector('.preview-cell').innerHTML =
                    `<a href="${json.ruta}" target="_blank" class="btn btn-sm btn-dark">
                        <i class="ri-eye-line"></i> Ver
                    </a>`;

                row.querySelector('.text-center .upload-btn')?.remove();

                Toastify({
                    text: "Documento cargado correctamente",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#28a745"
                }).showToast();
            }

        } catch (err) {
            Toastify({
                text: "Error al subir el documento",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#dc3545"
            }).showToast();
        }

        this.value = "";
    });

});
</script>
@endsection