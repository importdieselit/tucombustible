@extends('layouts.app')

@section('title', 'Despacho de Combustible')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Despacho de Combustible</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Despacho de Combustible</li>
                </ol>
            </div>
        </div>
    </div>
    <a href="{{ route('combustible.createPrepago') }}" class="btn btn-primary btn-block shadow mb-3">
            <i class="ti-plus"></i> Recargar Saldo Prepago
        </a>
    
            <a href="{{ route('combustible.historialIndustrial') }}" class="btn btn-info mb-3">Ver Historial de Despachos</a>
            <a href="{{ route('combustible.resumenDesp') }}" class="btn btn-info mb-3">Ver Resumen</a>
            <a href="{{ route('combustible.estadisticas') }}" class="btn btn-info mb-3">Ver Reporte</a>

            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalTraspaso">
                <i class="fa fa-exchange"></i> Traspaso T3 -> T00
            </button>

<div class="modal fade" id="modalTraspaso" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('combustible.storeTraspaso') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold">Traspaso Interno de Combustible</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    Esta operación rebajará el stock del <b>Tanque 3</b> e incrementará el <b>Tanque 00</b>.
                </div>
                <div class="mb-3">
                    <label class="form-label">Cantidad de Litros a Traspasar</label>
                    <input type="number" step="1" name="cantidad" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Observaciones / Motivo</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning">Confirmar Traspaso</button>
            </div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
        <div class="card-body text-center">
            <h5 class="card-title">Precarga de Cisterna</h5>
            <p class="card-text">Si necesita cargar combustible en una cisterna para un futuro despacho.</p>
            <a href="{{ route('combustible.precarga') }}" class="btn btn-outline-info">Realizar Precarga</a>
        </div>
    </div>
    <div class="card shadow-sm">
        
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Registrar Nuevo Despacho</h5>
        </div>
        <div class="card-body">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(Session::has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ Session::get('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
<form action="{{ route('combustible.storeDespachoIndustrial') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Cliente</label>
            <select name="cliente_id" id="cliente_selector" class="form-select select2" required>
                <option value="">Seleccione Cliente</option>
                @foreach($clientes as $c)
                    <option value="{{ $c->id }}">{{ $c->alias ?? $c->nombre }}  <strong @if($c->prepagado<0) style="color: red" @endif>({{ $c->prepagado }} Lts)</strong></option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-3" id="vehiculo_container">
            <label class="form-label">Vehículo</label>
            <div class="input-group">
                <select name="vehiculo_id" id="vehiculo_id" class="form-select">
                    <option value="">Seleccione Cliente Primero</option>
                </select>
                <button type="button" class="btn btn-outline-secondary" onclick="toggleNuevoVehiculo()">+</button>
            </div>
        </div>

        <div id="nuevo_vehiculo_fields" class="row d-none bg-light p-3 mb-3 border rounded">
            <div class="col-md-6">
                <input type="text" name="nueva_placa" class="form-control" placeholder="Placa Nueva">
            </div>
            <div class="col-md-6">
                <input type="text" name="nuevo_modelo" class="form-control" placeholder="Modelo / Alias">
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Litros Diesel Industrial</label>
            <input type="number" step="0.01" name="cantidad_litros" class="form-control" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Nro Ticket</label>
            <input type="text" name="nro_ticket" class="form-control">
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Fecha</label>
            <input type="datetime-local" name="fecha" class="form-control" value="{{ $hoy }}" required>
        </div>
        <div class="col-md-12 mb-3">
            <label class="form-label">Observacion</label>
            <textarea name="observaciones" id="observaciones" rows="10" style="width:95%; height:100px;"></textarea>
        </div>
        
        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-success w-100">Confirmar Despacho (Tanque 00)</button>
        </div>
    </div>
</form>
     </div>
    </div>
    <div class="card border-primary mt-4" style="border-style: dashed;">
    <div class="card-body">
        <h6 class="text-primary"><i class="ti-ticket"></i> Vista Previa del Ticket</h6>
        <div id="ticket_preview" class="bg-light p-3 font-monospace" style="font-size: 0.9rem;">
            <div><b>🎫 TICKET DE DESPACHO <span id="ticket_number"></span></b></div>
            <div>---------------------------</div>
            <div>📍 Origen: Tanque 00</div>
            <div id="p_cliente">🏢 Cliente: --</div>
            <div id="p_vehiculo">🚚 Vehículo: --</div>
            <div id="p_litros">💧 Cantidad: 0.00 Lts</div>
            <div>---------------------------</div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
// Script dinámico para cargar vehículos
$('#cliente_selector').on('change', function() {
    let clienteId = $(this).val();
    let $vehiculoSelect = $('#vehiculo_id');
    
    $vehiculoSelect.html('<option>Cargando...</option>');
    
    fetch(`/api/clientes/${clienteId}/vehiculos`)
        .then(response => response.json())
        .then(data => {
            let options = '<option value="">Seleccione Vehículo</option>';
            data.forEach(v => {
                options += `<option value="${v.id}">${v.placa} - ${v.alias}</option>`;
            });
            $vehiculoSelect.html(options);
        });
});



// Actualización en tiempo real del ticket mientras escriben
$('input[name="cantidad_litros"]').on('input', function() {
    $('#p_litros').text('💧 Cantidad: ' + $(this).val() + ' Lts');
});
$('#vehiculo_id').on('change', function() {
    let selectedText = $("#vehiculo_id option:selected").text();
    $('#p_vehiculo').text('🚚 Vehículo: ' + selectedText);
});
$('input[name="nro_ticket"]').on('input', function() {
    $('#ticket_number').text($(this).val());
});

$('#cliente_selector').on('change', function() {
    $('#p_cliente').text('🏢 Cliente: ' + $("#cliente_selector option:selected").text());
});

function toggleNuevoVehiculo() {
    $('#nuevo_vehiculo_fields').toggleClass('d-none');
    $('#vehiculo_id').val(''); // Limpia el select si va a crear uno nuevo
}

// Coloca esto en la sección de scripts de tu vista
$('#modalTraspaso form').on('submit', function(e) {
    let cantidad = parseFloat($(this).find('input[name="cantidad"]').val());
    let stockT3 = {{ $t3->nivel_actual_litros }}; // Aquí podrías pasar dinámicamente el stock actual del T3

    if (cantidad <= 0) {
        alert("La cantidad debe ser mayor a 0");
        e.preventDefault();
        return;
    }

    if (!confirm("¿Está seguro de traspasar " + cantidad + " Lts del Tanque 3 al Tanque 00? Esta acción no se puede deshacer.")) {
        e.preventDefault();
    }
});
</script>
@endpush