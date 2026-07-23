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
        // Asumiendo que formatVencimiento está disponible globalmente[cite: 4]
        $badge = formatVencimiento($fechaExtraida);
        $textoLimpio = str_replace($fechaExtraida, '', $valor);
        
        return '<div class="d-flex flex-column">' . 
               '<span class="fw-bold" style="font-size: 0.75rem;">' . trim($textoLimpio, ' -') . '</span>' .
               $badge . 
               '</div>';
    }

    return '<span class="badge bg-light text-dark border shadow-sm">' . $valor . '</span>';
}

// Simulando el Helper si no existe globalmente en la vista
if (!function_exists('formatVencimiento')) {
    function formatVencimiento($fecha) {
        $f = \Carbon\Carbon::parse(str_replace('/', '-', $fecha));
        $diff = now()->diffInDays($f, false);
        
        $class = 'bg-success';
        if ($diff < 0) $class = 'bg-danger';
        elseif ($diff <= 30) $class = 'bg-warning text-dark';

        return '<span class="badge ' . $class . '">' . $f->format('d/m/Y') . '</span>';
    }
}
@endphp

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h3 class="fw-bold text-dark"><i class="fa-solid fa-id-card me-2 text-primary"></i>Control de Documentación - Personal</h3>
        </div>
        <div class="col-md-6">
            <form action="{{ route('choferes.documentacion') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control border-0 shadow-sm" placeholder="Buscar por Nombre, Apellido o ID..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary px-4 shadow-sm">Filtrar</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="bg-light">
                        <th class="ps-4 py-3">Personal</th>
                        <th class="text-center">Cargo</th>
                        @foreach($docsCh as $doc)
                            <th class="text-center">{{ $doc->nombre }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($choferes as $ch)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                @if(is_null($ch->foto))
                                    <i class="fas fa-user-circle fa-2x text-secondary me-2"></i>
                                @else
                                    <img src="{{ asset('storage/choferes/foto/' . $ch->foto) }}" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;" alt="foto">
                                @endif
                                <div>
                                    <div class="fw-bold">{{ $ch->persona->nombre }}</div>
                                    <span class="badge bg-light text-dark border">{{ $ch->persona->dni ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $ch->cargo == 'CHOFER' ? 'bg-primary' : 'bg-warning text-dark' }}">{{ $ch->cargo }}</span>
                        </td>

                        @foreach($docsCh as $doc)
                            @php
                                $campo = $doc->campo_destino;
                                $f_venc = $doc->campo_fecha_vencimiento;
                                $valorCrudo = $campo ? $ch->$campo : null;

                                // Lógica de búsqueda física adaptada al path del chofer[cite: 3]
                                $filename = "{$doc->abreviatura}_{$ch->id}";
                                $extensions = ['pdf', 'jpg', 'png', 'jpeg'];
                                $finalPath = null;
                                
                                foreach($extensions as $ext) {
                                    $testPath = "storage/choferes/{$ch->id}/documentos/{$filename}.{$ext}";
                                    if(file_exists(public_path($testPath))) {
                                        $finalPath = asset($testPath);
                                        break;
                                    }
                                }
                            @endphp
                            
                            <td class="text-center clickable-cell" 
                                style="cursor: pointer;"
                                data-bs-toggle="modal" 
                                data-bs-target="#modalDoc"
                                data-chofer-id="{{ $ch->id }}"
                                data-doc-id="{{ $doc->id }}"
                                data-label="{{ $doc->nombre }}"
                                data-nombre="{{ $ch->persona->nombre }}"
                                data-tipo="{{ $doc->tipo }}"
                                data-vencimiento="{{ $f_venc ? $ch->$f_venc : 'false' }}"
                                data-valor-actual="{{ $valorCrudo }}"
                                data-path="{{ $finalPath }}"
                                data-require-date="{{ $campo ? 'true' : 'false' }}">
                                
                                <div class="d-flex flex-column align-items-center border rounded p-2 bg-white" style="transition: all 0.2s;">
                                    {!! renderDocMixto($valorCrudo) !!}
                                    
                                    @if($finalPath)
                                        <i class="fas fa-file-circle-check text-success mt-2" title="Documento Digital Cargado"></i>
                                    @else
                                        <i class="fas fa-file-upload text-muted mt-2 opacity-50" title="Falta Documento Digital"></i>
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
        {{ $choferes->appends(request()->query())->links() }}
    </div>
</div>

{{-- MODAL PARA DOCUMENTACIÓN DE CHOFERES --}}
<div class="modal fade" id="modalDoc" data-bs-backdrop="false" tabindex="-1" aria-hidden="true" style="z-index: 1050; margin-top:5%">
    <div class="modal-dialog modal-xl shadow-lg"> 
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title" id="modalLabel"><i class="fas fa-id-card text-primary me-2"></i>Detalle del Documento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-4 border-end p-4 bg-white">
                        <form id="formDoc" action="{{ route('choferes.documentacion.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="chofer_id" id="input_chofer_id">
                            <input type="hidden" name="doc_id" id="input_doc_id">

                            <div class="mb-4">
                                <label class="small fw-bold text-muted mb-2"><i class="fas fa-edit me-1"></i> VALOR / VENCIMIENTO</label>
                                <input type="text" name="valor_texto" id="input_valor_texto" class="form-control bg-light border-primary" placeholder="Ej: 12/12/2025 o A-123456">
                            </div>

                            <div class="mb-4">
                                <label class="small fw-bold text-muted mb-2"><i class="fas fa-cloud-upload-alt me-1"></i> SUBIR NUEVO ARCHIVO</label>
                                <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.png,.jpeg">
                                <small class="text-muted mt-1 d-block" style="font-size: 10px;">Formatos permitidos: PDF, JPG, PNG.</small>
                            </div>

                            <div class="d-grid gap-2 mt-5">
                                <button type="submit" class="btn btn-primary fw-bold py-2"><i class="fas fa-save me-2"></i> Guardar Cambios</button>
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded text-center">
                                <p id="current-doc-status" class="small fw-bold text-muted mb-0"></p>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-8 bg-light d-flex align-items-center justify-content-center p-3" style="min-height: 500px;">
                        <div id="viewer-container" class="w-100 h-100 d-none shadow-sm rounded overflow-hidden">
                            <iframe id="doc-viewer" src="" width="100%" height="600px" style="border: none;"></iframe>
                        </div>
                        <div id="viewer-placeholder" class="text-center text-muted">
                            <i class="fas fa-file-invoice fa-4x mb-3 text-secondary opacity-50"></i>
                            <h6 class="fw-bold">No hay previsualización</h6>
                            <p class="small">El archivo digital aún no ha sido cargado en el sistema.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .clickable-cell:hover {
        background-color: #f8f9fa !important;
    }
    .clickable-cell:hover .border {
        border-color: #0d6efd !important;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    $('.clickable-cell').on('click', function() {
        const data = $(this).data();
        
        // Reset Viewer[cite: 4]
        $('#doc-viewer').attr('src', '');
        $('#viewer-container').addClass('d-none');
        $('#viewer-placeholder').removeClass('d-none');
        
        // Set Modal Data
        $('#modalLabel').html(`<i class="fas fa-id-card text-primary me-2"></i>${data.label} - ${data.nombre}`);
        $('#input_valor_texto').val(data.valorActual);
        $('#input_chofer_id').val(data.choferId);
        $('#input_doc_id').val(data.docId);
        
        $('#current-doc-status').html('<i class="fas fa-spinner fa-spin me-1"></i> Buscando información...');

        // Fetch de la info del documento (Ajustar ruta en web.php)
        fetch(`/choferes/documento-detalle/${data.choferId}/${data.docId}`)
            .then(response => response.json())
            .then(response => {
                if(response.success) {
                    if(response.file_url) {
                        $('#doc-viewer').attr('src', response.file_url);
                        $('#viewer-container').removeClass('d-none');
                        $('#viewer-placeholder').addClass('d-none');
                        $('#current-doc-status').html('<i class="fas fa-check-circle text-success me-1"></i> Documento digitalizado disponible');
                    } else {
                        $('#current-doc-status').html('<i class="fas fa-exclamation-circle text-warning me-1"></i> Sin soporte digital');
                    }
                    if(response.valor_actual) {
                        $('#input_valor_texto').val(response.valor_actual);
                    }
                } else {
                    $('#current-doc-status').html('<i class="fas fa-exclamation-circle text-warning me-1"></i> Sin soporte digital');
                }
            })
            .catch(error => {
                $('#current-doc-status').html('<i class="fas fa-times-circle text-danger me-1"></i> Error al consultar documento');
            });
    });
});
</script>
@endpush