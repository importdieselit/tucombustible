@extends('layouts.app')

@section('title', 'Control de Inventario y Almacenes')

@push('styles')
<style>
    /* Estándares Corporativos */
    .bg-navy { background-color: #002855 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .text-navy { color: #002855 !important; }
    .text-orange { color: #ff6600 !important; }
    .border-orange { border-color: #ff6600 !important; }
    
    .card-kpi { border: none; border-radius: 8px; transition: transform 0.2s; }
    .card-kpi:hover { transform: translateY(-5px); }
    .stats-number { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stats-label { font-size: 0.7rem; text-uppercase; font-weight: 700; color: #6c757d; letter-spacing: 0.5px; }
    
    .table-alerts thead { font-size: 0.7rem; background: #f8f9fa; }
    .badge-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-file-import me-2"></i> Registrar Entrada de Almacén</h5>
    </div>
    <div class="card-body">
        <form id="form-entrada">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Artículo</label>
                    <select name="articulo_id" class="form-select" required>
                        <option value="">Seleccione artículo...</option>
                        @foreach($articulos as $art)
                            <option value="{{ $art->id }}">{{ $art->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Ubicación (Slot)</label>
                    <select name="slot_id" class="form-select" required>
                        <option value="">Seleccione posición...</option>
                        @foreach($slots as $slot)
                            <option value="{{ $slot->id }}">{{ $slot->codigo_posicion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">ID Orden de Compra (Opcional)</label>
                    <input type="number" name="compra_id" class="form-control" placeholder="Ej: 1045">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Cantidad a Ingresar</label>
                    <input type="number" step="0.01" name="cantidad" class="form-control" required min="0.01">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4 w-100 fw-bold">Confirmar Entrada</button>
        </form>
    </div>
</div>
@push('scripts')
<script>
$('#form-entrada').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: "{{ route('inventario.entry') }}",
        method: "POST",
        data: $(this).serialize(),
        success: function(res) {
            if(res.success) {
                alert(res.message);
                $('#form-entrada')[0].reset();
            }
        },
        error: function(err) { alert('Error al procesar la entrada'); }
    });
});
</script>
@endpush
@endsection