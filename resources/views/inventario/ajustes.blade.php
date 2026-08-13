<div class="card shadow-sm border-0 border-start border-danger border-4">
    <div class="card-header bg-white text-danger">
        <h5 class="mb-0 fw-bold"><i class="fas fa-sliders-h me-2"></i> Ajuste Manual / Auditoría Física</h5>
    </div>
    <div class="card-body">
        <form id="form-ajuste">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Artículo a Auditar</label>
                    <select name="articulo_id" id="ajuste_articulo" class="form-select" required>
                        <option value="">Seleccione...</option>
                        @foreach($articulos as $art)
                            <option value="{{ $art->id }}">{{ $art->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Ubicación Física</label>
                    <select name="slot_id" id="ajuste_slot" class="form-select" required>
                        <option value="">Seleccione...</option>
                        @foreach($slots as $slot)
                            <option value="{{ $slot->id }}">{{ $slot->codigo_posicion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stock en Sistema</label>
                    <input type="text" id="stock_sistema" class="form-control bg-light" readonly value="0.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-primary">Conteo Real Físico</label>
                    <input type="number" step="0.01" name="stock_real" id="stock_real" class="form-control border-primary" required min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Diferencia (Delta)</label>
                    <input type="text" id="lbl_diferencia" class="form-control bg-light fw-bold text-muted" readonly value="0.00">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold text-danger">Motivo del Ajuste (Obligatorio)</label>
                    <textarea name="motivo_ajuste" class="form-control" rows="2" placeholder="Ej: Pérdida por rotura de empaque, error de conteo inicial, etc." required></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-danger mt-4 w-100 fw-bold">Aplicar Ajuste e Indexar Kardex</button>
        </form>
    </div>
</div>

<script>
// Lógica interactiva para calcular la diferencia sobre la marcha
$('#stock_real').on('input', function() {
    let sistema = parseFloat($('#stock_sistema').val()) || 0;
    let real = parseFloat($(this).val()) || 0;
    let diff = real - sistema;
    
    let el = $('#lbl_diferencia');
    el.val((diff >= 0 ? '+' : '') + diff.toFixed(2));
    
    if(diff < 0) el.removeClass('text-success text-muted').addClass('text-danger');
    else if(diff > 0) el.removeClass('text-danger text-muted').addClass('text-success');
    else el.removeClass('text-danger text-success').addClass('text-muted');
});

$('#form-ajuste').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: "{{ route('inventario.ajuste') }}",
        method: "POST",
        data: $(this).serialize(),
        success: function(res) {
            if(res.success) {
                alert(res.message);
                $('#form-ajuste')[0].reset();
                $('#lbl_diferencia').val('0.00').removeClass('text-danger text-success').addClass('text-muted');
            }
        },
        error: function() { alert('Error al procesar el ajuste'); }
    });
});
</script>