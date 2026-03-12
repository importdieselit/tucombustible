@php
    $docsRequeridos = [
        'planilla_solicitud'    => 'Planilla de Solicitud',
        'declaracion_jurada'    => 'Declaración Jurada',
        'carta_ministerio'      => 'Carta Ministerio',
        'registro_mercantil'    => 'Registro Mercantil',
        'acta_constitutiva'     => 'Acta Constitutiva',
        'rif_legalizado'        => 'RIF Legalizado',
        'dni_contacto'          => 'C. I. de Persona de Contacto',
        'rif_contacto'          => 'RIF de Persona de Contacto',
        'islr'                  => 'ISLR (Impuesto sobre la renta)',
        'permiso_bomberos'      => 'Permiso de Bombero',
        'maquinaria_tanques'    => 'Maquinaria (Equipos y Tanques)',
        'croquis_ubicacion'     => 'Croquis de Ubicación'
    ];
    
    // Obtenemos los nombres técnicos de los documentos ya cargados
    $docsSubidos = $cliente->documentos->pluck('nombre_documento')->toArray();
@endphp

<div class="card shadow-sm border-0">
    <div class="card-header bg-white font-weight-bold">
        <i class="fas fa-file-upload mr-2 text-primary"></i> Carga de Expediente Digital (12 Requisitos)
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">
            Suba los documentos en formato <strong>PDF, DOC, DOCX u ODT</strong>. 
            Al completar los 12, se habilitará el botón de envío.
        </p>

        <ul class="list-group list-group-flush">
            @foreach($docsRequeridos as $slug => $label)
            @php $estaSubido = in_array($slug, $docsSubidos); @endphp
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div class="d-flex align-items-center">
                    @if($estaSubido)
                        <i class="fas fa-check-circle text-success fa-lg mr-2"></i>
                    @else
                        <i class="far fa-circle text-muted fa-lg mr-2"></i>
                    @endif
                    <span class="{{ $estaSubido ? 'font-weight-bold text-dark' : 'text-muted' }}">
                        {{ $label }}
                    </span>
                </div>

                {{-- Formulario de carga individual --}}
                <form action="{{ route('portal.clientes.upload.doc') }}" method="POST" enctype="multipart/form-data" class="m-0">
                    @csrf
                    <input type="hidden" name="tipo_documento" value="{{ $slug }}">
                    <input type="file" name="archivo" id="file_{{ $slug }}" class="d-none" accept=".pdf,.doc,.docx,.odt" onchange="this.form.submit()">
                    
                    <button type="button" class="btn btn-sm {{ $estaSubido ? 'btn-success' : 'btn-outline-primary' }}" 
                            onclick="document.getElementById('file_{{ $slug }}').click()">
                        <i class="fas {{ $estaSubido ? 'fa-sync-alt' : 'fa-upload' }} mr-1"></i>
                        {{ $estaSubido ? 'Reemplazar' : 'Subir' }}
                    </button>
                </form>
            </li>
            @endforeach
        </ul>

        {{-- Botón final: Solo se habilita si el conteo es exactamente 12 --}}
        <div class="mt-5 p-4 border {{ count($docsSubidos) >= 12 ? 'border-success bg-light' : 'border-light bg-light' }} rounded text-center shadow-sm">
            @if(count($docsSubidos) >= 12)
                <h5 class="text-success font-weight-bold"><i class="fas fa-check-double"></i> ¡Expediente Completo!</h5>
                <p class="small text-muted">Haga clic abajo para enviar su solicitud a revisión administrativa.</p>
                <form action="{{ route('portal.clientes.finalizar.paso2') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg btn-block shadow-sm font-weight-bold py-3">
                        <i class="fas fa-paper-plane mr-2"></i> ENVIAR EXPEDIENTE A REVISIÓN
                    </button>
                </form>
            @else
                <h5 class="text-muted font-weight-bold">Progreso: {{ count($docsSubidos) }} de 12</h5>
                <div class="progress mb-3" style="height: 10px;">
                    @php $porcentajeDocs = (count($docsSubidos) / 12) * 100; @endphp
                    <div class="progress-bar bg-primary" style="width: {{ $porcentajeDocs }}%"></div>
                </div>
                <button class="btn btn-secondary btn-lg btn-block py-3 shadow-sm" disabled>
                    <i class="fas fa-lock mr-2"></i> FALTAN DOCUMENTOS
                </button>
            @endif
        </div>
    </div>
</div>