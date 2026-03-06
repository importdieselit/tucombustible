@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0 text-primary">{{ $cliente->razon_social }}</h3>
                        <span class="text-muted">RIF: {{ $cliente->rif }}</span>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-warning p-2 text-uppercase">
                            Estatus: Paso {{ $cliente->registro_paso }} - Carga de Expediente
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header bg-white font-weight-bold">
                    <i class="fas fa-file-upload mr-2"></i> Documentación Obligatoria para Activación
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Por favor, cargue los siguientes documentos en formato PDF. Una vez completados los 6, podrá enviar su expediente a revisión por ImporDiesel.</p>

                    @php
                        $docsRequeridos = [
                            'rif_legalizado' => 'RIF Legalizado (Vigente)',
                            'documento_constitutivo' => 'Registro Mercantil / Acta Constitutiva',
                            'copia_representante_legal' => 'Cédula del Representante Legal',
                            'lista_equipos_tanques' => 'Listado de Equipos y Tanques',
                            'croquis_ubicacion' => 'Croquis de Ubicación (Google Maps o Dibujo)',
                            'constancia_bomberos' => 'Certificado de Bomberos'
                        ];
                        // Obtenemos solo los tipos de documentos que el cliente ya subió
                        $docsSubidos = $cliente->documentos->pluck('tipo_documento')->toArray();
                    @endphp

                    <ul class="list-group list-group-flush">
                        @foreach($docsRequeridos as $slug => $label)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                @if(in_array($slug, $docsSubidos))
                                    <i class="fas fa-check-circle text-success fa-lg mr-2"></i>
                                @else
                                    <i class="far fa-circle text-muted fa-lg mr-2"></i>
                                @endif
                                <span class="{{ in_array($slug, $docsSubidos) ? 'font-weight-bold' : '' }}">
                                    {{ $label }}
                                </span>
                            </div>

                            <form action="{{ route('portal.clientes.upload.doc') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="tipo_documento" value="{{ $slug }}">
                                <input type="file" name="archivo" id="file_{{ $slug }}" class="d-none" onchange="this.form.submit()">
                                
                                <button type="button" class="btn btn-sm {{ in_array($slug, $docsSubidos) ? 'btn-success' : 'btn-outline-primary' }}" 
                                        onclick="document.getElementById('file_{{ $slug }}').click()">
                                    {{ in_array($slug, $docsSubidos) ? 'Reemplazar PDF' : 'Subir PDF' }}
                                </button>
                            </form>
                        </li>
                        @endforeach
                    </ul>

                    @if(count($docsSubidos) >= 6)
                    <div class="mt-5 p-3 border border-success rounded bg-light text-center">
                        <h5 class="text-success"><i class="fas fa-check-double"></i> ¡Expediente Completo!</h5>
                        <p class="small">Haga clic abajo para enviar sus datos a revisión oficial.</p>
                        <form action="{{ route('portal.clientes.finalizar.paso2') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg btn-block shadow-sm">
                                ENVIAR EXPEDIENTE A REVISIÓN
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection