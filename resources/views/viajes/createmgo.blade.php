@extends('layouts.app')

@section('title', 'Planificar Despacho MGO')

@section('content')
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="bi bi-ship"></i> Nueva Planificación Marine Gas Oil (MGO)</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('viajes.store') }}" method="POST" id="mgo-form">
                @csrf
                <input type="hidden" name="tipo" value="1"> {{-- 1 para MGO --}}

                <h4 class="mt-4 mb-3 text-success border-bottom pb-1">Detalles de Transporte</h4>
                <div class="row g-3 mb-4">
                    {{-- Destino y Muelle --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ciudad de Destino</label>
                        <select name="destino_ciudad" id="destino_ciudad" class="form-select" required>
                            <option value="">Seleccione...</option>
                            @foreach($destinos as $ciudad)
                                <option value="{{ $ciudad->id }}">{{ $ciudad->destino }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Muelle de Atraque</label>
                        <select name="muelle_id" id="muelle_id" class="form-select" disabled required>
                            <option value="">Seleccione destino primero</option>
                        </select>
                    </div>

                    {{-- Fecha y Flete --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fecha de Salida</label>
                        <input type="datetime-local" name="fecha_salida" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check form-switch pt-4">
                            <input class="form-check-input" type="checkbox" id="es_flete" name="es_flete" value="1">
                            <label class="form-check-label fw-bold" for="es_flete">¿Unidades externas?</label>
                        </div>
                    </div>
                </div>

                {{-- Selección de Vehículo, Chofer y Ayudante (Mantenida de tu código) --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Vehículo (Flota)</label>
                        <select name="vehiculo_id" id="vehiculo_id" class="form-select">
                            <option value="">Seleccione...</option>
                            @foreach($vehiculos as $v)
                                <option value="{{ $v->id }}">{{ $v->flota }} ({{ $v->placa }})</option>
                            @endforeach
                        </select>
                        <input type="text" name="otro_vehiculo" id="otro_vehiculo" class="form-control mt-2" style="display:none" placeholder="Nombre vehículo externo">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Chofer</label>
                        <select name="chofer_id" id="chofer_id" class="form-select">
                            <option value="">Seleccione...</option>
                            @foreach($choferes as $c)
                                @if($c->cargo == 'CHOFER')
                                    <option value="{{ $c->id }}">{{ $c->persona->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="otro_chofer" id="otro_chofer" class="form-control mt-2" style="display:none" placeholder="Nombre chofer externo">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ayudante</label>
                        <select name="ayudante" id="ayudante" class="form-select">
                            <option value="">Seleccione...</option>
                            @foreach($choferes as $c)
                                @if($c->cargo != 'CHOFER')
                                    <option value="{{ $c->id }}">{{ $c->persona->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="card bg-light mb-4 border-primary">
                    <div class="card-header bg-primary text-white py-1">Agregar Cliente y Buque al Despacho</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small fw-bold">Buscar Cliente (Nombre o RIF)</label>
                                <input type="text" id="search_cliente" class="form-control form-control-sm" placeholder="Escriba para buscar...">
                                <input type="hidden" id="tmp_c_id">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Seleccionar Buque</label>
                                <select id="select_buque" class="form-select form-select-sm" disabled>
                                    <option value="">Primero seleccione cliente</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Litros</label>
                                <input type="number" id="tmp_litros" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Observación</label>
                                <input type="text" id="tmp_obs" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" id="btn-add-row" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped" id="tabla-despachos">
                        <thead class="table-dark">
                            <tr>
                                <th>Cliente / RIF</th>
                                <th>Buque / IMO</th>
                                <th width="150">Litros</th>
                                <th>Observación</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Aquí se agregan las filas dinámicamente --}}
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 shadow">Guardar Planificación</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {
    let rowIndex = 0;

    // 1. Filtrar Muelles por Destino (ID = Ubicacion)
    $('#destino_ciudad').on('change', function() {
        const id = $(this).val(); // ID del destino (TabuladorViatico)
        const $m = $('#muelle_id'); // El selector de muelles
        
        // Bloqueamos y limpiamos mientras carga
        $m.prop('disabled', true).html('<option value="">Cargando muelles...</option>');
        
        if(id) {
            $.getJSON(`/api/destinos/${id}/muelles`)
                .done(function(data) {
                    let html = '<option value="">Seleccione Muelle...</option>';
                    if(data.length > 0) {
                        data.forEach(item => { 
                            html += `<option value="${item.id}">${item.nombre}</option>`; 
                        });
                        $m.prop('disabled', false); // Solo habilitamos si hay data
                    } else {
                        html = '<option value="">No hay muelles en este destino</option>';
                    }
                    $m.html(html);
                })
                .fail(function() {
                    $m.html('<option value="">Error al cargar datos</option>');
                });
        } else {
            $m.html('<option value="">Seleccione destino primero</option>');
        }
    });

    // Lógica para unidades externas (Flete)
    $('#es_flete').on('change', function() {
        const isChecked = $(this).is(':checked');
        if(isChecked) {
            $('#vehiculo_id, #chofer_id').hide().val('');
            $('#otro_vehiculo, #otro_chofer').show();
        } else {
            $('#vehiculo_id, #chofer_id').show();
            $('#otro_vehiculo, #otro_chofer').hide().val('');
        }
    });

    // Búsqueda de Cliente por Autocomplete
    $('#search_cliente').autocomplete({
        source: function(request, response) {
            $.getJSON("{{ route('api.search.generic', ['field' => 'cliente']) }}", { term: request.term }, response);
        },
        select: function(event, ui) {
            $('#tmp_c_id').val(ui.item.id);
            $(this).val(ui.item.nombre);
            cargarBuques(ui.item.id);
            return false;
        }
    });

    function cargarBuques(clienteId) {
        const $select = $('#select_buque');
        $select.prop('disabled', false).html('<option>Cargando...</option>');
        $.getJSON(`/api/clientes/${clienteId}/buques`, function(data) {
            let html = '<option value="">Seleccione Buque...</option>';
            data.forEach(b => { html += `<option value="${b.id}" data-imo="${b.imo}" data-nombre="${b.nombre}">${b.nombre} (IMO: ${b.imo})</option>`; });
            $select.html(html);
        });
    }

    // Agregar fila a la tabla
    $('#btn-add-row').click(function() {
        const c_id = $('#tmp_c_id').val();
        const c_nom = $('#search_cliente').val();
        const b_opt = $('#select_buque option:selected');
        const litros = $('#tmp_litros').val();

        if(!c_id || !b_opt.val() || !litros) return alert('Complete Cliente, Buque y Litros');

        const row = `
            <tr>
                <td>${c_nom} <input type="hidden" name="despachos[${rowIndex}][cliente_id]" value="${c_id}"></td>
                <td>${b_opt.data('nombre')} (IMO: ${b_opt.data('imo')}) <input type="hidden" name="despachos[${rowIndex}][buque_id]" value="${b_opt.val()}"></td>
                <td><input type="number" name="despachos[${rowIndex}][litros]" class="form-control form-control-sm" value="${litros}" required></td>
                <td><input type="text" name="despachos[${rowIndex}][observacion]" class="form-control form-control-sm" value="${$('#tmp_obs').val()}"></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
            </tr>
        `;
        $('#tabla-despachos tbody').append(row);
        rowIndex++;
        // Limpiar
        $('#search_cliente, #tmp_c_id, #tmp_litros, #tmp_obs').val('');
        $('#select_buque').html('<option>Primero seleccione cliente</option>').prop('disabled', true);
    });

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); });
});
</script>
@endpush