@extends('layouts.app')

@php
function renderDocMixto($valor) {
    if (empty($valor) || $valor == '-' || strtoupper($valor) == 'N/A') {
        return '<span class="text-muted small">N/A</span>';
    }

    // Intentamos extraer una fecha con formato DD/MM/YYYY o YYYY-MM-DD
    preg_match('/(\d{2}\/\d{2}\/\d{4})|(\d{4}-\d{2}-\d{2})/', $valor, $matches);
    
    if (!empty($matches[0])) {
        $fechaExtraida = $matches[0];
        // Reemplazamos la fecha en el texto original por el badge de color
        $badge = formatVencimiento($fechaExtraida);
        $textoLimpio = str_replace($fechaExtraida, '', $valor);
        
        return '<div class="d-flex flex-column">' . 
               '<span class="fw-bold" style="font-size: 0.75rem;">' . trim($textoLimpio, ' -') . '</span>' .
               $badge . 
               '</div>';
    }

    // Si no hay fecha, es solo un código/texto
    return '<span class="badge bg-light text-dark border shadow-sm">' . $valor . '</span>';
}
@endphp

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h3 class="fw-bold text-dark"><i class="fa-solid fa-file-shield me-2 text-chutos"></i>Control de Documentación</h3>
        </div>
        <div class="col-md-6">
            <form action="{{ route('vehiculos.documentacion') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control border-0 shadow-sm" placeholder="Buscar Placa o Unidad..." value="{{ request('search') }}">
                <button type="submit" class="btn bg-chutos text-white px-4 shadow-sm">Filtrar</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 py-3">Vehículo / Placa</th>
                        @foreach($docsV as $doc)
                            <th class="text-center">{{ $doc->nombre }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehiculos as $v)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $v->flota }}</div>
                            <span class="badge bg-light text-dark border">{{ $v->placa }}</span>
                        </td>

                        @foreach($docsV as $doc)
                            @php
                                $campo = $doc->campo_destino;
                                $valorCrudo = $campo ? $v->$campo : null;
                                // Si es null pero el documento existe en el histórico, podrías traer esa fecha (opcional)
                                if(!$valorCrudo && $campo) {
                                    $regDoc = $v->$campo;
                                    $valorCrudo = $regDoc ? $regDoc : null;
                                }

                                // 2. Lógica de archivo estándar (la que ya usas)
                                $filename = "{$doc->abreviatura}_{$v->id}";
                                $extensions = ['pdf', 'jpg', 'png'];
                                $finalPath = null;
                                
                                foreach($extensions as $ext) {
                                    $testPath = "storage/vehiculos/{$v->id}/documentos/{$filename}.{$ext}";
                                    if(file_exists(public_path($testPath))) {
                                        $finalPath = asset($testPath);
                                        break;
                                    }
                                }
                            @endphp
                            
                            <td class="text-center clickable-cell" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalDoc"
                                data-vehiculo-id="{{ $v->id }}"
                                data-doc-id="{{ $doc->id }}"
                                data-label="{{ $doc->nombre }}"
                                data-tipo="{{ $doc->tipo }}"
                                data-valor-actual="{{ $valorCrudo }}" {{-- Enviamos el texto completo --}}
                                data-path="{{ $finalPath }}"
                                {{-- Si no tiene campo_destino, le decimos al JS que la fecha es opcional --}}
                                data-require-date="{{ $campo ? 'true' : 'false' }}">
                                
                                <div class="d-flex flex-column align-items-center">
                                    {!! renderDocMixto($valorCrudo) !!}
                                    
                                    @if($finalPath)
                                        <i class="fas fa-file-circle-check text-primary mt-1"></i>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">
        {{ $vehiculos->appends(request()->query())->links() }}
    </div>
</div>

<div class="modal fade ba" id="modalDoc" data-bs-backdrop="false"  tabindex="-1" aria-hidden="true" style="z-index: 100; margin-top:5%">
    <div class="modal-dialog modal-xl"> 
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalLabel">Detalle del Documento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 border-end">
                        <form id="formDoc" action="{{ route('vehiculos.documentacion.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="vehiculo_id" id="input_vehiculo_id">
                            <input type="hidden" name="doc_id" id="input_doc_id">

                            <div class="mb-3">
                                <label class="small fw-bold">VALOR / VENCIMIENTO</label>
                                <input type="text" name="valor_texto" id="input_valor_texto" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold">SUBIR NUEVO ARCHIVO</label>
                                <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.png">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                <p id="current-doc-status" class="small text-center mt-2"></p>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-8 bg-light d-flex align-items-center justify-content-center" style="min-height: 500px;">
                        <div id="viewer-container" class="w-100 h-100 d-none">
                            <iframe id="doc-viewer" src="" width="100%" height="600px" style="border: none;"></iframe>
                        </div>
                        <div id="viewer-placeholder" class="text-center text-muted">
                            <i class="fas fa-file-invoice fa-4x mb-3"></i>
                            <p>No hay una previsualización disponible para este documento.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.clickable-cell').on('click', function() {
        const data = $(this).data();
        $('#doc-viewer').attr('src', '');
        $('#viewer-container').addClass('d-none');
        $('#viewer-placeholder').removeClass('d-none');
        $('#input_valor_texto').val('Cargando...');
    
        $('#modalLabel').text(data.label + ' - ' + data.placa);
        $('#input_valor_texto').val(data.valorActual);
        
        // Configurar el Modal
        console.log("ID Vehiculo:", data.vehiculoId); 
        console.log("ID Tipo Doc:", data.docId);

        $('#input_vehiculo_id').val(data.vehiculoId);
        $('#input_doc_id').val(data.docId);
        $('#input_tipo').val(data.tipo);
        // Limpiar el form y preview antes de cargar (Simulación de carga AJAX para datos extra)
        $('#view-doc-btn').addClass('d-none');
        $('#current-doc-status').text('Buscando información...');

        // Opcional: Fetch para obtener datos del modelo DocumentosVehiculo si existen
        fetch(`/vehiculos/documento-detalle/${data.vehiculoId}/${data.docId}`)
            .then(response => response.json())
            .then(response => {
                if(response.success) {
                        // Quitamos el ".data" porque las propiedades están en la raíz
                        console.log("Detalle Doc:", response); 

                        if(response.file_url) {
                            // Cargamos la URL en el iframe
                            $('#doc-viewer').attr('src', response.file_url);
                            $('#viewer-container').removeClass('d-none');
                            $('#viewer-placeholder').addClass('d-none');
                            $('#current-doc-status').text('Documento digitalizado disponible');
                        } else {
                            $('#view-doc-btn').addClass('d-none');
                            $('#current-doc-status').text('Sin soporte digital');
                        }

                        // También recuerda llenar el input del valor actual
                        $('#input_valor_texto').val(response.valor_actual);
                } else {
                    $('#view-doc-btn').addClass('d-none');
                    $('#current-doc-status').text('Sin soporte digital');
                }
            });

        $('#formDoc').attr('action', `/vehiculos/documentacion/update`);
    });
});
</script>
@endpush