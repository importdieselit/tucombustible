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

                    <ul class="list-group">
                        @foreach($cliente->documentos_requeridos ?? [] as $doc)
                            @php
                                $subidos = $cliente->documentos_subidos ?? [];
                                $tiene = array_key_exists($doc, $subidos);
                            @endphp

                            <li class="list-group-item d-flex justify-content-between align-items-center
                                {{ $tiene ? 'list-group-item-success' : 'list-group-item-danger' }}">
                                
                                <span>
                                    <i class="{{ $tiene ? 'ri-check-line text-success' : 'ri-alert-line text-danger' }}"></i>
                                    {{ $doc }}
                                </span>

                                @if($tiene)
                                    <a href="{{ asset('storage/'.$subidos[$doc]) }}" target="_blank" class="btn btn-sm btn-dark">
                                        <i class="ri-eye-line"></i> Ver
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>


                    <!-- SUBIR NUEVOS DOCUMENTOS -->
                    <form action="{{ route('captacion.validar_documentos', $cliente->id) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                        @csrf

                        <label class="fw-bold mb-2">Subir Nuevos Documentos</label>

                        <input type="file" name="documentos[]" class="form-control mb-3" multiple>

                        <button class="btn btn-primary">
                            <i class="ri-upload-cloud-line"></i> Guardar Documentos
                        </button>
                    </form>

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
