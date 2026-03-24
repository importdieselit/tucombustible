@extends('layouts.app')

@section('title', isset($item) ? 'Editar Vehículo' : 'Crear Vehículo')

@push('styles')
<style>
    .border-orange { border-top: 3px solid #e67e22 !important; }
    .text-corporate { color: #2c3e50 !important; }
    .bg-corporate { background-color: #2c3e50 !important; color: white; }
    .btn-corporate { background-color: #e67e22; color: white; font-weight: bold; border: none; }
    .btn-corporate:hover { background-color: #d35400; color: white; }
    .nav-tabs-custom .nav-link { border: none; color: #6c757d; font-weight: 600; padding: 10px 20px; }
    .nav-tabs-custom .nav-link.active { color: #e67e22; border-bottom: 2px solid #e67e22; background: none; }
    .form-label { color: #2c3e50; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 0.5rem; }
    .border-start-orange { border-left: 4px solid #e67e22 !important; }
    .img-preview-container { position: relative; display: inline-block; }
    .overlay-delete { 
        position: absolute; top: 0; right: 0; background: rgba(231, 76, 60, 0.9); 
        color: white; padding: 2px 6px; border-radius: 0 5px 0 5px; cursor: pointer; 
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    {{-- ENCABEZADO Y BREADCRUMBS --}}
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase">
                <i class="fa-solid fa-truck-moving text-orange me-2"></i>
                {{ isset($item) ? 'Modificar Unidad' : 'Registro de Vehículo' }}
            </h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehiculos.index') }}">Vehículos</a></li>
                    <li class="breadcrumb-item active">{{ isset($item) ? 'Editar' : 'Crear' }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Regresar
        </a>
    </div>

    {{-- FORMULARIO PRINCIPAL --}}
    <form action="{{ isset($item) ? route('vehiculos.updatev', $item->id) : route('vehiculos.store') }}" 
          method="POST" enctype="multipart/form-data">
        @csrf
        @if(isset($item)) @method('PUT') @endif

        <div class="row">
            <div class="col-lg-12">
                {{-- NAVEGACIÓN DE PESTAÑAS --}}
                <ul class="nav nav-tabs nav-tabs-custom mb-0 bg-white px-3 pt-2 rounded-top shadow-sm" id="vehiculoTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                            <i class="fa-solid fa-info-circle me-1"></i> Datos Generales
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tecnico-tab" data-bs-toggle="tab" data-bs-target="#tecnico" type="button">
                            <i class="fa-solid fa-gears me-1"></i> Especificaciones
                        </button>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="docs-tab" data-bs-toggle="tab" href="#docs" role="tab">
                            <i class="fas fa-file-pdf me-1"></i> Documentación
                        </a>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="fotos-tab" data-bs-toggle="tab" data-bs-target="#fotos" type="button">
                            <i class="fa-solid fa-camera me-1"></i> Galería y Fotos
                        </button>
                    </li>
                </ul>

                <div class="card shadow-sm border-orange rounded-0 rounded-bottom">
                    <div class="card-body p-4">
                        <div class="tab-content" id="vehiculoTabContent">
                            
                            {{-- TAB 1: DATOS GENERALES --}}
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Placa / Identificador</label>
                                        <input type="text" name="placa" class="form-control border-start-orange" 
                                               value="{{ old('placa', $item->placa ?? '') }}" required placeholder="ABC-123">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nro. Flota</label>
                                        <input type="text" name="flota" class="form-control" 
                                               value="{{ old('flota', $item->flota ?? '') }}" placeholder="Ej: 05">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tipo de Vehículo</label>
                                        <select name="tipo" id="tipo" class="form-select select2-simple" required>
                                            <option value="">Seleccione...</option>
                                            @foreach($tiposVehiculo as $key => $value)
                                                <option value="{{ $key }}" {{ (isset($item) && $item->tipo == $key) ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Marca</label>
                                        
                                        <select name="marca" id="marca" class="form-select select2-simple" required>
                                            <option value="">Seleccione...</option>
                                            
                                            @foreach($marcas as $key => $value)

                                                <option value="{{ $key }}" {{ (isset($item) && $item->marca == $key) ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Modelo</label>
                                        <select name="modelo" id="modelo" class="form-select select2-simple" required>
                                            <option value="">Seleccione marca primero...</option>
                                            @foreach($modelos as $key => $value)
                                                <option value="{{ $key }}" {{ (isset($item) && $item->modelo == $key) ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Año</label>
                                        <input type="number" name="anno" class="form-control" 
                                               value="{{ old('anio', $item->anno ?? '') }}" placeholder="2023">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Color</label>
                                        <input type="text" name="color" class="form-control" 
                                               value="{{ old('color', $item->color ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Serial de Carrocería (VIN)</label>
                                        <input type="text" name="serial_carroceria" class="form-control" 
                                               value="{{ old('serial_carroceria', $item->serial_carroceria ?? '') }}">
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 2: ESPECIFICACIONES TÉCNICAS --}}
                            <div class="tab-pane fade" id="tecnico" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-primary"><i class="fa-solid fa-gauge-high me-1"></i> Kilometraje Actual</label>
                                        <input type="number" name="km_actual" class="form-control fw-bold border-primary" 
                                               value="{{ old('km_actual', $item->km_actual ?? 0) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Horas de Motor</label>
                                        <input type="number" name="hrs_motor" class="form-control fw-bold" 
                                               value="{{ old('hrs_motor', $item->hrs_motor ?? 0) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de Combustible</label>
                                        <select name="combustible" class="form-select">
                                            <option value="DIESEL" {{ (isset($item) && $item->combustible == 'DIESEL') ? 'selected' : '' }}>DIESEL</option>
                                            <option value="GASOLINA" {{ (isset($item) && $item->combustible == 'GASOLINA') ? 'selected' : '' }}>GASOLINA</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Observaciones Técnicas</label>
                                        <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', $item->observaciones ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB: DOCUMENTACIÓN --}}
                            <div class="tab-pane fade" id="docs" role="tabpanel">
                                <div class="row g-4 p-3">
                                    <div class="col-12">
                                        <div class="alert alert-info border-0 shadow-sm mb-4">
                                            <i class="fas fa-info-circle me-2"></i> 
                                            Si subes un archivo nuevo, <strong>reemplazará</strong> al documento actual.
                                        </div>
                                    </div>

                                    @foreach($documentosRequeridos as $doc)
                                        @php
                                            // Verificamos existencia para mostrar preview
                                            $pathBase = "storage/vehiculos/{$item->id}/documentos/{$doc->abreviatura}_{$item->id}";
                                            $extensiones = ['pdf', 'jpg', 'png', 'jpeg'];
                                            $archivoActual = null;
                                            foreach($extensiones as $ext) {
                                                if(file_exists(public_path("{$pathBase}.{$ext}"))) {
                                                    $archivoActual = asset("{$pathBase}.{$ext}");
                                                    break;
                                                }
                                            }
                                        @endphp
                                        
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card h-100 border shadow-sm">
                                                <div class="card-body">
                                                    <label class="form-label d-block fw-black text-uppercase small mb-3">
                                                        {{ $doc->nombre }}
                                                    </label>
                                                    
                                                    {{-- Indicador de estado --}}
                                                    <div class="d-flex align-items-center mb-3">
                                                        @if($archivoActual)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle me-2">
                                                                <i class="fas fa-check me-1"></i> CARGADO
                                                            </span>
                                                            <a href="{{ $archivoActual }}" target="_blank" class="btn btn-link btn-sm p-0 fw-bold text-decoration-none text-orange">
                                                                Ver actual <i class="fas fa-external-link-alt ms-1"></i>
                                                            </a>
                                                        @else
                                                            <span class="badge bg-light text-muted border me-2">
                                                                <i class="fas fa-times me-1"></i> PENDIENTE
                                                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- Input para cargar/reemplazar --}}
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light"><i class="fas fa-upload"></i></span>
                                                        <input type="file" name="documentos[{{ $doc->id }}]" 
                                                            class="form-control" 
                                                            accept=".pdf,.jpg,.png,.jpeg">
                                                    </div>
                                                    <div class="form-text mt-2" style="font-size: 10px;">
                                                        Soporta: PDF, JPG, PNG.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- TAB 3: FOTOS --}}
                            <div class="tab-pane fade" id="fotos" role="tabpanel">
                                <div class="bg-light p-3 rounded mb-3 border">
                                    <label class="form-label d-block"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Subir Imágenes</label>
                                    <input type="file" name="fotos[]" class="form-control" multiple accept="image/*">
                                    <small class="text-muted">Puedes seleccionar varias imágenes a la vez.</small>
                                </div>

                                @if(isset($item) && !is_null($item->fotos) && $item->fotos->count() > 0)
                                <h6 class="fw-bold text-corporate mb-3 small text-uppercase">Fotos Registradas:</h6>
                                <div class="row g-2">
                                    @foreach($item->fotos as $f)
                                        <div class="col-4 col-md-2" id="foto-{{ $f->id }}">
                                            <div class="img-preview-container">
                                                <img src="{{ asset($f->ruta) }}" class="img-thumbnail rounded shadow-sm">
                                                <span class="overlay-delete" onclick="eliminarFoto({{ $f->id }})">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>

                        </div>
                    </div>
                    
                    {{-- PIE DE CARD CON ACCIONES --}}
                    <div class="card-footer bg-light d-flex justify-content-end py-3">
                        <button type="submit" class="btn btn-corporate px-5 shadow-sm">
                            <i class="fa-solid fa-save me-2"></i> {{ isset($item) ? 'GUARDAR CAMBIOS' : 'REGISTRAR UNIDAD' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Inicializar Select2 si lo usas
        $('.select2-simple').select2({ width: '100%', theme: 'bootstrap-5' });

        // Lógica dinámica de Marcas -> Modelos
        $('#marca').change(function() {
            var marcaId = $(this).val();
            var modeloSelect = $('#modelo');
            
            if (marcaId) {
                modeloSelect.prop('disabled', true).html('<option>Cargando...</option>');
                
                $.ajax({
                    url: '{{ route("marcas.getModelos") }}',
                    method: 'GET',
                    data: { marca_id: marcaId },
                    success: function(data) {
                        modeloSelect.prop('disabled', false).empty().append('<option value="">Seleccione modelo...</option>');
                        $.each(data, function(id, nombre) {
                            let selected = (id == "{{ $item->id_modelo ?? '' }}") ? 'selected' : '';
                            modeloSelect.append(`<option value="${id}" ${selected}>${nombre}</option>`);
                        });
                    }
                });
            } else {
                modeloSelect.empty().append('<option value="">Seleccione marca primero...</option>');
            }
        });

        // Disparar carga de modelos si es edición
        @if(isset($item) && $item->id_marca)
            $('#marca').trigger('change');
        @endif
    });

    function eliminarFoto(id) {
        Swal.fire({
            title: '¿Eliminar imagen?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Sí, borrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Aquí harías tu llamada AJAX a la ruta de eliminar foto
                $(`#foto-${id}`).fadeOut();
            }
        });
    }
</script>
@endpush