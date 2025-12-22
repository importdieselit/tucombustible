@extends('layouts.app')

@section('title', 'Planificar Despacho MGO')

@section('content')
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="bi bi-ship"></i> Nueva Planificación Marine Gas Oil (MGO)</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('mgo.store') }}" method="POST" id="mgo-form">
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
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Vehículo (Flota)</label>
                        <select name="vehiculo_id" id="vehiculo_id" class="form-select">
                            <option value="">Seleccione...</option>
                            @foreach($vehiculos as $v)
                                <option value="{{ $v->id }}">{{ $v->flota }} ({{ $v->placa }})</option>
                            @endforeach
                        </select>
                        <input type="text" name="otro_vehiculo" id="otro_vehiculo" class="form-control mt-2" style="display:none" placeholder="Nombre vehículo externo">
                    </div>
                    <div class="col-md-3">
                            <label for="chofer">Cisterna</label>
                            <select name="cisterna_id" id="cisterna_id" class="form-select" required>
                                <option value="">Seleccione una cisterna</option>
                                @foreach($vehiculos as $cisterna)
                                    @if($cisterna->tipo==2)
                                        <option value="{{ $cisterna->id }}">{{ $cisterna->flota }} {{ $cisterna->placa }}</option>
                                    @endif
                                @endforeach 
                            </select>
                        </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                    <div class="card-header bg-primary text-white py-1">Datos del Cliente y Embarcación</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small fw-bold">Buscar Cliente</label>
                                <select name="search_cliente" id="search_cliente" class="form-select" >
                                    <option value="">Seleccione...</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" data-tipo="1">{{ $cliente->nombre }} ({{ $cliente->alias ? $cliente->alias : ''}})</option>
                                    @endforeach
                                    @foreach($clientesC as $cliente)
                                        <option value="{{ $cliente->id }}" data-tipo="0">{{ $cliente->nombre }} (en Captacion)</option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="c_id">
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Razón Social</label>
                                <input type="text" id="c_nombre" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">RIF</label>
                                <input type="text" id="c_rif" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Teléfono</label>
                                <input type="text" id="c_tel" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Dirección Legal</label>
                                <input type="text" id="c_dir" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Contacto</label>
                                <input type="text" id="c_contacto" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Email</label>
                                <input type="email" id="c_email" class="form-control form-control-sm">
                            </div>

                            <div class="col-md-12 border-top mt-2 pt-2">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="small fw-bold">Seleccionar Buque</label>
                                        <select name="search_buque" id="search_buque" class="form-select" >
                                            <option value="">Seleccione...</option>
                                        </select>
                                        <input type="hidden" id="b_id">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Nombre Buque</label>
                                        <input type="text" id="b_nombre" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small fw-bold">IMO</label>
                                        <input type="text" id="b_imo" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small fw-bold">Bandera</label>
                                        <input type="text" id="b_bandera" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" id="btn-add-despacho" class="btn btn-primary btn-sm w-100"><i class="fa fa-plus"></i> Agregar</button>
                                    </div>
                                </div>
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
    let despachoIdx = 0;

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

    $('#search_cliente').on('change', function() {
        const id = $(this).val(); // ID del destino (TabuladorViatico)
        const $m = $('#search_buque'); // El selector de muelles
        const tipo_cliente = $(this).find('option:selected').data('tipo');
        // Bloqueamos y limpiamos mientras carga
        $m.prop('disabled', true).html('<option value="">Cargando buques...</option>');
        
        if(id) {
            $.getJSON(`/api/destinos/${id}/clientes/${tipo_cliente}`)
                .done(function(data) {
                    $('#c_id').val(data.id);
                    $('#c_nombre').val(data.nombre);
                    $('#c_rif').val(data.rif);
                    $('#c_tel').val(data.telefono);
                    $('#c_dir').val(data.direccion);
                    $('#c_contacto').val(data.representante);
                    $('#c_email').val(data.correo);

                    $('#search_buque').prop('disabled', false).focus();
                    cargarBuques(data.id);
                })
                .fail(function() {
                    $m.html('<option value="">Error al cargar datos</option>');
                });
        } else {
            $m.html('<option value="">Seleccione destino primero</option>');
        }
    });

    function cargarBuques(clienteId) {
        const $select = $('#search_buque');
        $select.prop('disabled', false).html('<option>Cargando...</option>');
        $.getJSON(`/api/cliente/${clienteId}/buques`, function(data) {
            let html = '<option value="">Seleccione Buque...</option>';
            data.forEach(b => { html += `<option value="${b.id}" data-imo="${b.imo}" data-bandera="${b.bandera}" data-nombre="${b.nombre}">${b.nombre} (IMO: ${b.imo})</option>`; });
            $select.html(html);
        });
    }


    // 3. Autocomplete Buque (Filtrado por cliente)
    $('#search_buque').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const id = $(this).val(); // ID del buque
        if(id) {
            $('#b_id').val(id);
            $('#b_nombre').val(selectedOption.data('nombre'));
            $('#b_imo').val(selectedOption.data('imo'));
            $('#b_bandera').val(selectedOption.data('bandera'));
        } else {
            $('#b_id, #b_nombre, #b_imo, #b_bandera').val('');
        }
    });

    // 4. Agregar a la Tabla
    $('#btn-add-despacho').click(function() {
        const c_nom = $('#c_nombre').val();
        const b_nom = $('#b_nombre').val();
        
        if(!c_nom || !b_nom) return alert('Debe completar datos de Cliente y Buque');

        const row = `
            <tr>
                <td>
                    <strong>${c_nom}</strong><br><small>${$('#c_rif').val()}</small>
                    <input type="hidden" name="despachos[${despachoIdx}][cliente_id]" value="${$('#c_id').val()}">
                    <input type="hidden" name="despachos[${despachoIdx}][cliente_nombre]" value="${c_nom}">
                    <input type="hidden" name="despachos[${despachoIdx}][cliente_rif]" value="${$('#c_rif').val()}">
                    <input type="hidden" name="despachos[${despachoIdx}][cliente_dir]" value="${$('#c_dir').val()}">
                    <input type="hidden" name="despachos[${despachoIdx}][cliente_tel]" value="${$('#c_tel').val()}">
                    <input type="hidden" name="despachos[${despachoIdx}][cliente_email]" value="${$('#c_email').val()}">
                    <input type="hidden" name="despachos[${despachoIdx}][cliente_contacto]" value="${$('#c_contacto').val()}">
                </td>
                <td>
                    <strong>${b_nom}</strong><br><small>IMO: ${$('#b_imo').val()}</small>
                    <input type="hidden" name="despachos[${despachoIdx}][buque_id]" value="${$('#b_id').val()}">
                    <input type="hidden" name="despachos[${despachoIdx}][buque_nombre]" value="${b_nom}">
                    <input type="hidden" name="despachos[${despachoIdx}][buque_imo]" value="${$('#b_imo').val()}">
                    <input type="hidden" name="despachos[${despachoIdx}][buque_bandera]" value="${$('#b_bandera').val()}">
                </td>
                <td><input type="number" name="despachos[${despachoIdx}][litros]" class="form-control" required placeholder="Litros"></td>
                <td><input type="text" name="despachos[${despachoIdx}][observacion]" class="form-control" placeholder="..."></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
            </tr>
        `;
        
        $('#tabla-despachos tbody').append(row);
        despachoIdx++;
        // Limpiar campos de entrada
        $('#search_cliente, #c_id, #c_nombre, #c_rif, #c_tel, #c_dir, #c_contacto, #c_email').val('');
       // $('#search_buque, #b_id, #b_nombre, #b_imo, #b_bandera').val('').prop('disabled', true);
    });

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); });

});
</script>
@endpush