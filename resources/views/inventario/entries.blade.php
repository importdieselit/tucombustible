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