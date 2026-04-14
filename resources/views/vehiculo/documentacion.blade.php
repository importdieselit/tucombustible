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

<div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form id="formDoc" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold" id="modalLabel">Editar Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="hidden" name="vehiculo_id" id="input_vehiculo_id">
                            <input type="hidden" name="tipo_documento" id="input_tipo">
                            
                            <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Información del Documento (Texto o Fecha)</label>
                            <div class="input-group">
                                <input type="text" name="valor_texto" id="input_valor_texto" class="form-control border-light bg-light" placeholder="Ej: 135861 - AL 30/07/2026">
                                <button type="button" class="btn btn-outline-secondary" onclick="setHoy()">Hoy</button>
                            </div>
                            <small class="text-muted">Puedes ingresar solo texto, solo fecha o ambos.</small>
                        </div>

                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Cargar Nuevo Documento (PDF/Imagen)</label>
                                <input type="file" name="archivo" class="form-control border-light bg-light" accept=".pdf,image/*">
                            </div>
                        </div>

                        <div class="col-md-6 border-start d-flex flex-column align-items-center justify-content-center bg-light rounded-3">
                            <div id="preview-container" class="text-center p-3">
                                <i class="fa-solid fa-file-pdf fa-4x text-muted mb-3"></i>
                                <p class="small text-muted mb-0" id="current-doc-status">No hay documento digital cargado</p>
                                <a href="#" id="view-doc-btn" target="_blank" class="btn btn-sm btn-outline-primary mt-3 d-none">
                                    <i class="fa-solid fa-eye me-1"></i> Ver Documento Actual
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn bg-chutos text-white rounded-pill px-4 shadow">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.clickable-cell').on('click', function() {
        const data = $(this).data();
    
        $('#modalLabel').text(data.label + ' - ' + data.placa);
        $('#input_valor_texto').val(data.valorActual);
        
        // Configurar el Modal
        $('#input_vehiculo_id').val(data.vehiculoId);
        $('#input_tipo').val(data.tipo);
        // Limpiar el form y preview antes de cargar (Simulación de carga AJAX para datos extra)
        $('#view-doc-btn').addClass('d-none');
        $('#current-doc-status').text('Buscando información...');

        // Opcional: Fetch para obtener datos del modelo DocumentosVehiculo si existen
        fetch(`/api/documento-detalle/${data.vehiculoId}/${data.tipo}`)
            .then(response => response.json())
            .then(res => {
                if(res.success) {
                    
                    if(res.data.doc_path) {
                        $('#view-doc-btn').attr('href', '/storage/' + res.data.doc_path).removeClass('d-none');
                        $('#current-doc-status').text('Documento digitalizado disponible');
                    } else {
                        $('#current-doc-status').text('Sin soporte digital');
                    }
                }
            });

        $('#formDoc').attr('action', `/vehiculos/documentacion/update`);
    });
});
</script>
@endpush