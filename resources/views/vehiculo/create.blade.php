@extends('layouts.app')

{{-- Usamos el mismo título dinámico para la página --}}
@section('title', isset($item) ? 'Editar Vehículo' : 'Crear Vehículo')

@push('styles')
    <!-- Aquí puedes añadir estilos específicos para el formulario si es necesario -->
@endpush

@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            {{-- Título dinámico --}}
            <h3 class="text-themecolor mb-0">{{ isset($item) ? 'Editar Vehículo' : 'Agregar Vehículo' }}</h3>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vehiculos.index') }}">Vehículos</a></li>
                <li class="breadcrumb-item active">{{ isset($item) ? 'Editar Vehículo' : 'Agregar Vehículo' }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    {{-- Formulario dinámico para crear o editar --}}
                    <form action="{{ isset($item) ? route('vehiculos.update', $item->id) : route('vehiculos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        {{-- Si estamos editando, usamos el método PUT --}}
                        @if (isset($item))
                            @method('PUT')
                        @endif
                        
                        <!-- Pestañas de navegación -->
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="info-basica-tab" data-bs-toggle="tab" data-bs-target="#info-basica" type="button" role="tab" aria-controls="info-basica" aria-selected="true">
                                    <i class="fa-solid fa-car me-2"></i> Información Básica
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="detalles-tecnicos-tab" data-bs-toggle="tab" data-bs-target="#detalles-tecnicos" type="button" role="tab" aria-controls="detalles-tecnicos" aria-selected="false">
                                    <i class="fa-solid fa-gear me-2"></i> Detalles Técnicos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="poliza-seguros-tab" data-bs-toggle="tab" data-bs-target="#poliza-seguros" type="button" role="tab" aria-controls="poliza-seguros" aria-selected="false">
                                    <i class="fa-solid fa-shield-halved me-2"></i> Póliza y Seguros
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="informacion-adicional-tab" data-bs-toggle="tab" data-bs-target="#informacion-adicional" type="button" role="tab" aria-controls="informacion-adicional" aria-selected="false">
                                    <i class="fa-solid fa-circle-info me-2"></i> Información Adicional
                                </button>
                            </li>
                        </ul>

                        <!-- Contenido de las pestañas -->
                        <div class="tab-content pt-4" id="myTabContent">
                            <!-- Pestaña 1: Información Básica -->
                            <div class="tab-pane fade show active" id="info-basica" role="tabpanel" aria-labelledby="info-basica-tab">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="flota" class="form-label text-primary">Flota</label>
                                        <input type="text" class="form-control" id="flota" name="flota" value="{{ old('flota', $item->flota ?? '') }}">
                                        @error('flota')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="placa" class="form-label text-primary">Placa</label>
                                        <input type="text" class="form-control" id="placa" name="placa" value="{{ old('placa', $item->placa ?? '') }}">
                                        @error('placa')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="marca" class="form-label text-primary">Marca</label>
                                        <select name="marca" id="marca" class="form-control">
                                            <option value="">Seleccione una marca</option>
                                            @foreach($marcas as $id => $marca)
                                                <option value="{{ $id }}" {{ old('marca', $item->marca ?? '') == $id ? 'selected' : '' }}>{{ $marca }}</option>
                                            @endforeach
                                        </select>
                                        @error('marca')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modelo" class="form-label text-primary">Modelo</label>
                                        <select name="modelo" id="modelo" class="form-control">
                                            <option value="">Seleccione un modelo</option>
                                            @foreach($modelos as $id => $modelo)
                                                <option value="{{ $id }}" {{ old('modelo', $item->modelo ?? '') == $id ? 'selected' : '' }}>{{ $modelo }}</option>
                                            @endforeach
                                        </select>
                                        @error('modelo')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="anno" class="form-label text-primary">Año</label>
                                        <input type="number" class="form-control" id="anno" name="anno" value="{{ old('anno', $item->anno ?? '') }}">
                                        @error('anno')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="color" class="form-label text-primary">Color</label>
                                        <input type="text" class="form-control" id="color" name="color" value="{{ old('color', $item->color ?? '') }}">
                                        @error('color')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fecha_in" class="form-label text-primary">Fecha de Ingreso</label>
                                        <input type="date" class="form-control" id="fecha_in" name="fecha_in" value="{{ old('fecha_in', $item->fecha_in ?? '') }}">
                                        @error('fecha_in')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="disp" class="form-label text-primary">Disponibilidad</label>
                                        <select name="disp" id="disp" class="form-control">
                                            <option value="1" {{ old('disp', $item->disp ?? '') == '1' ? 'selected' : '' }}>Disponible</option>
                                            <option value="0" {{ old('disp', $item->disp ?? '') == '0' ? 'selected' : '' }}>No Disponible</option>
                                        </select>
                                        @error('disp')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña 2: Detalles Técnicos -->
                            <div class="tab-pane fade" id="detalles-tecnicos" role="tabpanel" aria-labelledby="detalles-tecnicos-tab">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="serial_motor" class="form-label text-primary">Serial del Motor</label>
                                        <input type="text" class="form-control" id="serial_motor" name="serial_motor" value="{{ old('serial_motor', $item->serial_motor ?? '') }}">
                                        @error('serial_motor')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="serial_carroceria" class="form-label text-primary">Serial de Carrocería</label>
                                        <input type="text" class="form-control" id="serial_carroceria" name="serial_carroceria" value="{{ old('serial_carroceria', $item->serial_carroceria ?? '') }}">
                                        @error('serial_carroceria')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="transmision" class="form-label text-primary">Transmisión</label>
                                        <input type="text" class="form-control" id="transmision" name="transmision" value="{{ old('transmision', $item->transmision ?? '') }}">
                                        @error('transmision')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="kilometraje" class="form-label text-primary">Kilometraje Inicial</label>
                                        <input type="number" class="form-control" id="kilometraje" name="kilometraje" value="{{ old('kilometraje', $item->kilometraje ?? '') }}">
                                        @error('kilometraje')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="km_mantt" class="form-label text-primary">Kilometraje para Mantenimiento</label>
                                        <input type="number" class="form-control" id="km_mantt" name="km_mantt" value="{{ old('km_mantt', $item->km_mantt ?? '') }}">
                                        @error('km_mantt')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tipo_combustible" class="form-label text-primary">Tipo de Combustible</label>
                                        <input type="text" class="form-control" id="tipo_combustible" name="tipo_combustible" value="{{ old('tipo_combustible', $item->tipo_combustible ?? '') }}">
                                        @error('tipo_combustible')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fuel" class="form-label text-primary">Capacidad de Combustible (L)</label>
                                        <input type="number" step="0.01" class="form-control" id="fuel" name="fuel" value="{{ old('fuel', $item->fuel ?? '') }}">
                                        @error('fuel')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="consumo" class="form-label text-primary">Consumo (L/100km)</label>
                                        <input type="number" step="0.01" class="form-control" id="consumo" name="consumo" value="{{ old('consumo', $item->consumo ?? '') }}">
                                        @error('consumo')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="HP" class="form-label text-primary">Caballos de Fuerza (HP)</label>
                                        <input type="number" class="form-control" id="HP" name="HP" value="{{ old('HP', $item->HP ?? '') }}">
                                        @error('HP')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="CC" class="form-label text-primary">Cilindrada (CC)</label>
                                        <input type="number" class="form-control" id="CC" name="CC" value="{{ old('CC', $item->CC ?? '') }}">
                                        @error('CC')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="largo" class="form-label text-primary">Largo (m)</label>
                                        <input type="number" step="0.01" class="form-control" id="largo" name="largo" value="{{ old('largo', $item->largo ?? '') }}">
                                        @error('largo')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ancho" class="form-label text-primary">Ancho (m)</label>
                                        <input type="number" step="0.01" class="form-control" id="ancho" name="ancho" value="{{ old('ancho', $item->ancho ?? '') }}">
                                        @error('ancho')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="altura" class="form-label text-primary">Altura (m)</label>
                                        <input type="number" step="0.01" class="form-control" id="altura" name="altura" value="{{ old('altura', $item->altura ?? '') }}">
                                        @error('altura')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="carga_max" class="form-label text-primary">Carga Máxima (kg)</label>
                                        <input type="number" step="0.01" class="form-control" id="carga_max" name="carga_max" value="{{ old('carga_max', $item->carga_max ?? '') }}">
                                        @error('carga_max')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="oil" class="form-label text-primary">Tipo de Aceite</label>
                                        <input type="text" class="form-control" id="oil" name="oil" value="{{ old('oil', $item->oil ?? '') }}">
                                        @error('oil')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña 3: Póliza y Seguros -->
                            <div class="tab-pane fade" id="poliza-seguros" role="tabpanel" aria-labelledby="poliza-seguros-tab">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="poliza_numero" class="form-label text-primary">Número de Póliza</label>
                                        <input type="text" class="form-control" id="poliza_numero" name="poliza_numero" value="{{ old('poliza_numero', $item->poliza_numero ?? '') }}">
                                        @error('poliza_numero')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="id_poliza" class="form-label text-primary">ID de Póliza</label>
                                        <input type="number" class="form-control" id="id_poliza" name="id_poliza" value="{{ old('id_poliza', $item->id_poliza ?? '') }}">
                                        @error('id_poliza')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tipo_poliza" class="form-label text-primary">Tipo de Póliza</label>
                                        <input type="text" class="form-control" id="tipo_poliza" name="tipo_poliza" value="{{ old('tipo_poliza', $item->tipo_poliza ?? '') }}">
                                        @error('tipo_poliza')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cobertura" class="form-label text-primary">Cobertura</label>
                                        <input type="number" step="0.01" class="form-control" id="cobertura" name="cobertura" value="{{ old('cobertura', $item->cobertura ?? '') }}">
                                        @error('cobertura')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="agencia" class="form-label text-primary">Agencia</label>
                                        <input type="text" class="form-control" id="agencia" name="agencia" value="{{ old('agencia', $item->agencia ?? '') }}">
                                        @error('agencia')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="poliza_fecha_in" class="form-label text-primary">Fecha de Inicio de Póliza</label>
                                        <input type="date" class="form-control" id="poliza_fecha_in" name="poliza_fecha_in" value="{{ old('poliza_fecha_in', $item->poliza_fecha_in ?? '') }}">
                                        @error('poliza_fecha_in')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="poliza_fecha_out" class="form-label text-primary">Fecha de Fin de Póliza</label>
                                        <input type="date" class="form-control" id="poliza_fecha_out" name="poliza_fecha_out" value="{{ old('poliza_fecha_out', $item->poliza_fecha_out ?? '') }}">
                                        @error('poliza_fecha_out')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="certif_reg" class="form-label text-primary">Certificado de Registro</label>
                                        <input type="text" class="form-control" id="certif_reg" name="certif_reg" value="{{ old('certif_reg', $item->certif_reg ?? '') }}">
                                        @error('certif_reg')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña 4: Información Adicional -->
                            <div class="tab-pane fade" id="informacion-adicional" role="tabpanel" aria-labelledby="informacion-adicional-tab">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="id_usuario" class="form-label text-primary">Usuario Asignado</label>
                                        <select name="id_usuario" id="id_usuario" class="form-control">
                                            <option value="">Seleccione un usuario</option>
                                            @foreach($clientes as $id => $usuario)
                                                <option value="{{ $id }}" {{ old('id_usuario', $item->id_usuario ?? '') == $id ? 'selected' : '' }}>{{ $usuario }}</option>
                                            @endforeach
                                        </select>
                                        @error('id_usuario')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="id_tipo_vehiculo" class="form-label text-primary">Tipo de Vehículo</label>
                                        <select name="id_tipo_vehiculo" id="id_tipo_vehiculo" class="form-control">
                                            <option value="">Seleccione un tipo</option>
                                            @foreach($tiposVehiculo as $id => $tipo)
                                                <option value="{{ $id }}" {{ old('id_tipo_vehiculo', $item->id_tipo_vehiculo ?? '') == $id ? 'selected' : '' }}>{{ $tipo }}</option>
                                            @endforeach
                                        </select>
                                        @error('id_tipo_vehiculo')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="estatus" class="form-label text-primary">Estatus</label>
                                        <select name="estatus" id="estatus" class="form-control">
                                            <option value="1" {{ old('estatus', $item->estatus ?? '') == '1' ? 'selected' : '' }}>Disponible</option>
                                            <option value="2" {{ old('estatus', $item->estatus ?? '') == '2' ? 'selected' : '' }}>En Mantenimiento</option>
                                            <option value="3" {{ old('estatus', $item->estatus ?? '') == '3' ? 'selected' : '' }}>Inactivo</option>
                                        </select>
                                        @error('estatus')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="condicion" class="form-label text-primary">Condición</label>
                                        <input type="text" class="form-control" id="condicion" name="condicion" value="{{ old('condicion', $item->condicion ?? '') }}">
                                        @error('condicion')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ubicacion" class="form-label text-primary">Ubicación</label>
                                        <input type="number" class="form-control" id="ubicacion" name="ubicacion" value="{{ old('ubicacion', $item->ubicacion ?? '') }}">
                                        @error('ubicacion')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ubicacion_1" class="form-label text-primary">Ubicación Adicional</label>
                                        <input type="text" class="form-control" id="ubicacion_1" name="ubicacion_1" value="{{ old('ubicacion_1', $item->ubicacion_1 ?? '') }}">
                                        @error('ubicacion_1')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sucursal" class="form-label text-primary">Sucursal</label>
                                        <input type="number" class="form-control" id="sucursal" name="sucursal" value="{{ old('sucursal', $item->sucursal ?? '') }}">
                                        @error('sucursal')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label for="observacion" class="form-label text-primary">Observaciones</label>
                                        <textarea class="form-control" id="observacion" name="observacion">{{ old('observacion', $item->observacion ?? '') }}</textarea>
                                        @error('observacion')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary d-flex align-items-center">
                                <i class="fa-solid fa-save me-2"></i> {{ isset($item) ? 'Guardar Cambios' : 'Guardar Vehículo' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Carga de jQuery para la funcionalidad del formulario, si fuera necesario -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Cargar modelos según la marca seleccionada
            $('#marca').change(function() {
                var marcaId = $(this).val();
                if (marcaId) {
                    $.ajax({
                        url: '{{ route('marcas.getModelos') }}',
                        method: 'GET',
                        data: { marca_id: marcaId },
                        success: function(data) {
                            $('#modelo').empty().append('<option value="">Seleccione un modelo</option>');
                            $.each(data, function(id, modelo) {
                                $('#modelo').append('<option value="' + id + '">' + modelo + '</option>');
                            });
                            
                            // Si estamos en modo edición, seleccionamos el modelo actual
                            @if(isset($item))
                                $('#modelo').val("{{ old('modelo', $item->modelo ?? '') }}");
                            @endif
                        }
                    });
                } else {
                    $('#modelo').empty().append('<option value="">Seleccione una marca primero</option>');
                }
            });

            // Disparar el evento change al cargar la página si ya hay una marca seleccionada
            @if(isset($item) && $item->marca)
                $('#marca').trigger('change');
            @endif
        });
    </script>
@endpush
