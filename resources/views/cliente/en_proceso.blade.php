@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    
                    {{-- CABECERA DINÁMICA DE ESTADO --}}
                    <div class="text-center mb-4">
                        <h2 class="text-primary font-weight-bold">Estado de tu Registro</h2>
                        <div class="mt-2">
                            <span class="badge badge-pill badge-primary px-4 py-2 text-uppercase">
                                Paso {{ $cliente->registro_paso }}: {{ $cliente->nombre_paso_actual }}
                            </span>
                        </div>
                        
                        @php $porcentaje = ($cliente->registro_paso / 10) * 100; @endphp
                        <div class="progress mt-4" style="height: 10px; border-radius: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $porcentaje }}%;"></div>
                        </div>
                    </div>

                    {{-- PASO 2: CARGA DE DOCUMENTOS --}}
                    @if($cliente->registro_paso == 2)
                        
                        {{-- BLOQUE DE DESCARGA DE PLANILLAS --}}
                        <div class="card bg-light border-info mb-4 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="mr-4 d-none d-md-block text-info">
                                    <i class="fas fa-file-archive fa-4x"></i>
                                </div>
                                <div>
                                    <h5 class="font-weight-bold text-info">¿Aún no tienes las planillas?</h5>
                                    <p class="mb-2 text-dark">Descarga el paquete comprimido con todos los formatos necesarios (.doc, .docx y .pdf), llénalos y fírmalos antes de subirlos.</p>
                                    <a href="{{ route('portal.clientes.descargar.formatos') }}" class="btn btn-info btn-sm font-weight-bold shadow-sm">
                                        <i class="fas fa-download mr-1"></i> DESCARGAR TODAS LAS PLANILLAS (.ZIP)
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-4 shadow-sm">
                            <i class="fas fa-info-circle mr-2"></i> 
                            <strong>Instrucciones:</strong> Por favor, suba los 12 documentos requeridos a continuación. El sistema habilitará el envío a revisión automáticamente al completar la carga.
                        </div>

                        {{-- 
                             Inyectamos el Partial del formulario. 
                             Este archivo debe manejar los campos para los 12 documentos.
                        --}}
                        @include('cliente.partials.formulario_carga_docs')

                    @else
                        {{-- MENSAJE PARA PASOS DE REVISIÓN O ESPERA (PASO 3 EN ADELANTE) --}}
                        <div class="text-center py-5">
                            <div class="mb-4">
                                @if($cliente->registro_paso == 3)
                                    <i class="fas fa-file-medical fa-5x text-info"></i>
                                @else
                                    <i class="fas fa-user-clock fa-5x text-muted"></i>
                                @endif
                            </div>
                            
                            <h3 class="h4 font-weight-bold">{{ $cliente->nombre_paso_actual }}</h3>
                            <p class="text-muted mx-auto" style="max-width: 600px;">
                                Actualmente tu expediente se encuentra en la etapa de <strong>"{{ $cliente->nombre_paso_actual }}"</strong>. 
                                Nuestro equipo administrativo está procesando la información. No es necesario realizar acciones adicionales.
                            </p>
                            
                            <div class="mt-4">
                                <button class="btn btn-outline-primary shadow-sm" onclick="window.location.reload()">
                                    <i class="fas fa-sync-alt mr-2"></i> Refrescar Estatus
                                </button>
                            </div>
                        </div>
                    @endif

                </div>
                <div class="card-footer bg-white text-center py-3 border-0">
                    <small class="text-muted">Portal de Clientes - ImporDiesel &copy; {{ date('Y') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection