@extends('layouts.app')

@section('title', isset($item) ? 'Editar Vehículo' : 'Crear Vehículo')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        input[type="text"], textarea {
        text-transform: uppercase;
    }
    
    /* Opcional: si quieres que el placeholder también sea mayúscula */
    input[type="text"]::placeholder {
        text-transform: uppercase;
    }
    </style>
@endpush

@section('content')
<div class="container-fluid py-4">
    {{-- ENCABEZADO DE PÁGINA --}}
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm border-start border-4 border-orange">
        <div>
            <h4 class="fw-bold text-uppercase mb-0">
                {{ isset($item) ? 'Actualizar Registro' : 'Nuevo Ingreso de Vehículo' }}
            </h4>
            <small class="text-muted">Gestión de Flota - Módulo de Activos</small>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vehiculos.index') }}" class="text-decoration-none">Vehículos</a></li>
                <li class="breadcrumb-item active">{{ isset($item) ? 'Edición' : 'Creación' }}</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-step shadow-sm border-0">
                <div class="card-body p-0">
                    <form action="{{ isset($item) ? route('vehiculos.update', $item->id) : route('vehiculos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($item)) @method('PUT') @endif
                        
                        <div class="bg-light border-bottom">
                            <ul class="nav nav-tabs border-0" id="vehicleTab" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab"><i class="fa-solid fa-car me-2"></i>Básica</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="tech-tab" data-bs-toggle="tab" data-bs-target="#tech" type="button" role="tab"><i class="fa-solid fa-gear me-2"></i>Técnica</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="legal-tab" data-bs-toggle="tab" data-bs-target="#legal" type="button" role="tab"><i class="fa-solid fa-shield-halved me-2"></i>Documentación</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="extra-tab" data-bs-toggle="tab" data-bs-target="#extra" type="button" role="tab"><i class="fa-solid fa-circle-info me-2"></i>Adicional</button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content p-4" id="vehicleTabContent">
                            
                            <div class="tab-pane fade show active" id="info" role="tabpanel">
                                <div class="row g-4">
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Número de Flota</label>
                                        <input type="text" class="form-control fw-bold border-orange" name="flota" value="{{ old('flota', $item->flota ?? '') }}" placeholder="Ej. T-01">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Placa / Matrícula</label>
                                        <input type="text" class="form-control fw-bold" name="placa" value="{{ old('placa', $item->placa ?? '') }}" placeholder="AAA000">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Marca</label>
                                        <select name="marca" id="marca" class="form-control select2">
                                            <option value="">Seleccione...</option>
                                            @foreach($marcas as $id => $marca)
                                                <option value="{{ $id }}" {{ old('marca', $item->marca ?? '') == $id ? 'selected' : '' }}>{{ $marca }}</option>
                                            @endforeach
                                            <option value="otro">-- OTRA (NUEVA) --</option>
                                        </select>
                                        <div id="nueva_marca_group" class="mt-2" style="display: none;">
                                            <input type="text" name="nueva_marca" id="nueva_marca" class="form-control form-control-sm border-primary" placeholder="Nombre de la marca">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Modelo</label>
                                        <select name="modelo" id="modelo" class="form-control select2">
                                            <option value="">Seleccione...</option>
                                            @foreach($modelos as $id => $modelo)
                                                <option value="{{ $id }}" {{ old('modelo', $item->modelo ?? '') == $id ? 'selected' : '' }}>{{ $modelo }}</option>
                                            @endforeach
                                            <option value="otro">-- OTRO (NUEVO) --</option>
                                        </select>
                                        <div id="nuevo_modelo_group" class="mt-2" style="display: none;">
                                            <input type="text" name="nuevo_modelo" id="nuevo_modelo" class="form-control form-control-sm border-primary" placeholder="Nombre del modelo">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label-corp">Año</label>
                                        <input type="number" class="form-control" name="anno" value="{{ old('anno', $item->anno ?? '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label-corp">Color</label>
                                        <input type="text" class="form-control" name="color" value="{{ old('color', $item->color ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Fecha de Ingreso</label>
                                        <input type="date" class="form-control" name="fecha_in" value="{{ old('fecha_in', $item->fecha_in ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Disponibilidad Inmediata</label>
                                        <select name="disp" class="form-select border-start border-4 border-success">
                                            <option value="1" {{ old('disp', $item->disp ?? '') == '1' ? 'selected' : '' }}>DISPONIBLE</option>
                                            <option value="0" {{ old('disp', $item->disp ?? '') == '0' ? 'selected' : '' }}>FUERA DE SERVICIO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tech" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Serial del Motor</label>
                                        <input type="text" class="form-control" name="serial_motor" value="{{ old('serial_motor', $item->serial_motor ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Serial de Carrocería (VIN)</label>
                                        <input type="text" class="form-control" name="serial_carroceria" value="{{ old('serial_carroceria', $item->serial_carroceria ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Transmisión</label>
                                        <input type="text" class="form-control" name="transmision" value="{{ old('transmision', $item->transmision ?? '') }}" placeholder="Manual / Automática">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">KM Inicial</label>
                                        <input type="number" class="form-control border-orange fw-bold" name="kilometraje" value="{{ old('kilometraje', $item->kilometraje ?? '') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">KM para Mantenimiento</label>
                                        <input type="number" class="form-control" name="km_mantt" value="{{ old('km_mantt', $item->km_mantt ?? '') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Tipo Combustible</label>
                                        <input type="text" class="form-control" name="tipo_combustible" value="{{ old('tipo_combustible', $item->tipo_combustible ?? '') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Capacidad Tanque (L)</label>
                                        <input type="number" step="0.01" class="form-control" name="fuel" value="{{ old('fuel', $item->fuel ?? '') }}">
                                    </div>
                                    <div class="col-md-12">
                                        <div class="bg-light p-3 rounded mt-2 border">
                                            <h6 class="small fw-bold text-uppercase mb-3">Especificaciones de Carga y Aceite</h6>
                                            <div class="row g-3">
                                                <div class="col-md-3"><label class="x-small fw-bold text-muted">CARGA MÁX (KG)</label><input type="number" step="0.01" class="form-control form-control-sm" name="carga_max" value="{{ old('carga_max', $item->carga_max ?? '') }}"></div>
                                                <div class="col-md-3"><label class="x-small fw-bold text-muted">TIPO DE ACEITE</label><input type="text" class="form-control form-control-sm" name="oil" value="{{ old('oil', $item->oil ?? '') }}"></div>
                                                <div class="col-md-2"><label class="x-small fw-bold text-muted">LARGO (M)</label><input type="number" step="0.01" class="form-control form-control-sm" name="largo" value="{{ old('largo', $item->largo ?? '') }}"></div>
                                                <div class="col-md-2"><label class="x-small fw-bold text-muted">ANCHO (M)</label><input type="number" step="0.01" class="form-control form-control-sm" name="ancho" value="{{ old('ancho', $item->ancho ?? '') }}"></div>
                                                <div class="col-md-2"><label class="x-small fw-bold text-muted">ALTO (M)</label><input type="number" step="0.01" class="form-control form-control-sm" name="altura" value="{{ old('altura', $item->altura ?? '') }}"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="legal" role="tabpanel">
                                {{-- SECCIÓN: DOCUMENTACIÓN DEL VEHÍCULO --}}

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Número de Póliza</label>
                                        <input type="text" class="form-control" name="poliza_numero" value="{{ old('poliza_numero', $item->poliza_numero ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Vigencia Desde</label>
                                        <input type="date" class="form-control" name="poliza_fecha_in" value="{{ old('poliza_fecha_in', $item->poliza_fecha_in ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-corp text-danger">Vigencia Hasta</label>
                                        <input type="date" class="form-control border-danger" name="poliza_fecha_out" value="{{ old('poliza_fecha_out', $item->poliza_fecha_out ?? '') }}">
                                    </div>
                                    
                                    <div class="col-12"><hr class="my-3 text-muted"></div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Venc. RCV</label>
                                        <input type="date" class="form-control" name="rcv" value="{{ old('rcv', $item->rcv ?? '') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Venc. RACDA</label>
                                        <input type="date" class="form-control" name="racda" value="{{ old('racda', $item->racda ?? '') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Venc. ROTC</label>
                                        <input type="date" class="form-control" name="rotc_venc" value="{{ old('rotc_venc', $item->rotc_venc ?? '') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-corp">Permiso INTT</label>
                                        <input type="date" class="form-control" name="permiso_intt" value="{{ old('permiso_intt', $item->permiso_intt ?? '') }}">
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <div class="form-check form-switch bg-light p-3 rounded border">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" id="semcamer" name="semcamer" value="NO VENCE" {{ old('semcamer', $item->semcamer ?? '') ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-uppercase small" for="semcamer">Registro SEMCAMER (No vence)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mt-4">
                                        <div class="form-check form-switch bg-light p-3 rounded border">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" id="homologacion" name="homologacion_intt" value="NO VENCE" {{ old('homologacion_intt', $item->homologacion_intt ?? '') ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-uppercase small" for="homologacion">Homologación INTT (No vence)</label>
                                        </div>
                                    </div>
                                </div>
                                                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-start border-4 border-orange shadow-sm">
                                    <div class="card-header bg-dark py-3">
                                        <h6 class="text-white mb-0 fw-bold text-uppercase small">
                                            <i class="fas fa-file-pdf me-2 text-orange"></i> Documentación Digital (PDF / Imágenes)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            @foreach($documentosRequeridos as $doc)
                                                <div class="col-md-4">
                                                    <label class="form-label fw-black text-uppercase small text-muted">
                                                        {{ $doc->nombre }} ({{ $doc->abreviatura }})
                                                    </label>
                                                    <input type="file" 
                                                        name="documentos[{{ $doc->id }}]" 
                                                        class="form-control form-control-sm border-2" 
                                                        accept=".pdf,.jpg,.png">
                                                    <div class="form-text" style="font-size: 9px;">Formatos permitidos: PDF, JPG, PNG</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            </div>

                            <div class="tab-pane fade" id="extra" role="tabpanel">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label-corp text-orange">Asignación de Usuario</label>
                                        <select name="id_usuario" class="form-control select2">
                                            <option value="">Sin Asignar</option>
                                            @foreach($clientes as $id => $usuario)
                                                <option value="{{ $id }}" {{ old('id_usuario', $item->id_usuario ?? '') == $id ? 'selected' : '' }}>{{ $usuario }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Categoría Vehículo</label>
                                        <select name="id_tipo_vehiculo" class="form-control">
                                            <option value="">Seleccione...</option>
                                            @foreach($tiposVehiculo as $id => $tipo)
                                                <option value="{{ $id }}" {{ old('id_tipo_vehiculo', $item->id_tipo_vehiculo ?? '') == $id ? 'selected' : '' }}>{{ $tipo }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-corp">Estatus Operativo</label>
                                        <select name="estatus" class="form-select border-start border-4 border-info">
                                            <option value="1" {{ old('estatus', $item->estatus ?? '') == '1' ? 'selected' : '' }}>ACTIVO / OPERATIVO</option>
                                            <option value="2" {{ old('estatus', $item->estatus ?? '') == '2' ? 'selected' : '' }}>EN MANTENIMIENTO</option>
                                            <option value="3" {{ old('estatus', $item->estatus ?? '') == '3' ? 'selected' : '' }}>INACTIVO / DESINCORPORADO</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label-corp text-uppercase">Observaciones de la Unidad</label>
                                        <textarea class="form-control" name="observacion" rows="4" placeholder="Detalles sobre siniestros anteriores, condiciones especiales, etc.">{{ old('observacion', $item->observacion ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer bg-white p-4 text-end border-top">
                            <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-secondary text-uppercase fw-bold me-2 px-4">Cancelar</a>
                            <button type="submit" class="btn bg-corporate text-white text-uppercase fw-bold px-5">
                                <i class="fa-solid fa-save me-2"></i> {{ isset($item) ? 'Actualizar Registro' : 'Confirmar y Guardar' }}
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

            // Lógica dinámica de Marca/Modelo
            $('#marca').change(function() {
                var marcaId = $(this).val();
                if (marcaId === 'otro') {
                    $('#nueva_marca_group').fadeIn();
                    $('#nueva_marca').attr('required', 'required');
                    $('#modelo').empty().append('<option value="otro">-- Otro (Agregar nuevo) --</option>');
                    $('#nuevo_modelo_group').fadeIn();
                } else {
                    $('#nueva_marca_group').hide();
                    $('#nueva_marca').removeAttr('required').val('');
                    if (marcaId) {
                        $.ajax({
                            url: '{{ route('marcas.getModelos') }}',
                            method: 'GET',
                            data: { marca_id: marcaId },
                            success: function(data) {
                                $('#modelo').empty().append('<option value="">Seleccione...</option>');
                                $.each(data, function(id, modelo) {
                                    $('#modelo').append('<option value="' + id + '">' + modelo + '</option>');
                                });
                                $('#modelo').append('<option value="otro">-- Otro (Agregar nuevo) --</option>');
                            }
                        });
                    }
                }
            });

            $('#modelo').change(function() {
                if ($(this).val() === 'otro') {
                    $('#nuevo_modelo_group').fadeIn();
                    $('#nuevo_modelo').attr('required', 'required');
                } else {
                    $('#nuevo_modelo_group').hide();
                    $('#nuevo_modelo').removeAttr('required').val('');
                }
            });

            @if(isset($item) && $item->marca)
                $('#marca').trigger('change');
            @endif

            $(document).on('input', 'input[type="text"], textarea', function() {
                // Convertir el valor a mayúsculas físicamente
                this.value = this.value.toUpperCase();
            });

            // Por seguridad, procesar todos los campos antes de enviar el formulario
            $('form').on('submit', function() {
                $(this).find('input[type="text"], textarea').each(function() {
                    this.value = this.value.toUpperCase();
                });
            });

        });
    </script>
@endpush