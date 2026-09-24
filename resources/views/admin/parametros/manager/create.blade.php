@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white pt-3 pb-2">
            <h5 class="mb-0"><i class="fas fa-tools me-2"></i> Constructor de Parámetros</h5>
        </div>
        
        <div class="card-body bg-light">
            <form action="{{ route('parametros.manager.store') }}" method="POST" id="builderForm">
                @csrf
                
                <div class="row bg-white p-4 rounded border shadow-sm mb-4">
                    <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">1. Datos Generales</h6>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Grupo</label>
                        <input type="text" name="grupo" class="form-control text-uppercase" placeholder="Ej: COMBUSTIBLES" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Clave Única (Código)</label>
                        <input type="text" name="clave" class="form-control text-uppercase" placeholder="Ej: PRECIO_MGO" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Etiqueta UI (Label)</label>
                        <input type="text" name="label" class="form-control" placeholder="Ej: Precio Base MGO" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Descripción (Uso interno)</label>
                        <input type="text" name="descripcion" class="form-control">
                    </div>
                </div>

                <div class="row bg-white p-4 rounded border shadow-sm mb-4">
                    <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">2. Comportamiento (UI Type)</h6>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipo de Control</label>
                        <select name="ui_type" id="ui_type" class="form-select" required>
                            <option value="text">Texto Simple</option>
                            <option value="number">Número</option>
                            <option value="repeater">Repetidor (Tabla Libre)</option>
                            <option value="db_select">Selector desde Base de Datos</option>
                            <option value="db_matrix">Matriz basada en Base de Datos</option>
                        </select>
                    </div>
                </div>

                {{-- BLOQUE DINÁMICO: Solo se muestra si elige un tipo DB --}}
                <div id="db_config_block" class="row bg-white p-4 rounded border shadow-sm mb-4 d-none border-primary">
                    <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">
                        <i class="fas fa-database me-2"></i> Configuración de Relación a BD
                    </h6>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Tabla Origen</label>
                        <select name="db_table" id="db_table" class="form-select">
                            <option value="">-- Seleccione una tabla --</option>
                            @foreach($tablas as $tabla)
                                <option value="{{ $tabla }}">{{ $tabla }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Campo para Valor (ID)</label>
                        <select name="db_value_field" id="db_value_field" class="form-select" disabled>
                            <option value="">Seleccione tabla primero...</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Campo para Mostrar (Label)</label>
                        <select name="db_label_field" id="db_label_field" class="form-select" disabled>
                            <option value="">Seleccione tabla primero...</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-success px-4 fw-bold">
                    <i class="fas fa-save me-2"></i> Guardar Nuevo Esquema
                </button>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uiTypeSelect = document.getElementById('ui_type');
    const dbConfigBlock = document.getElementById('db_config_block');
    const dbTableSelect = document.getElementById('db_table');
    const valueFieldSelect = document.getElementById('db_value_field');
    const labelFieldSelect = document.getElementById('db_label_field');

    // 1. Mostrar/Ocultar bloque de BD dependiendo del tipo seleccionado
    uiTypeSelect.addEventListener('change', function () {
        if (this.value === 'db_select' || this.value === 'db_matrix') {
            dbConfigBlock.classList.remove('d-none');
            dbTableSelect.setAttribute('required', 'required');
            valueFieldSelect.setAttribute('required', 'required');
            labelFieldSelect.setAttribute('required', 'required');
        } else {
            dbConfigBlock.classList.add('d-none');
            dbTableSelect.removeAttribute('required');
            valueFieldSelect.removeAttribute('required');
            labelFieldSelect.removeAttribute('required');
        }
    });

    // 2. Fetch dinámico de columnas cuando se selecciona una tabla
    dbTableSelect.addEventListener('change', function () {
        const tabla = this.value;
        
        valueFieldSelect.innerHTML = '<option value="">Cargando...</option>';
        labelFieldSelect.innerHTML = '<option value="">Cargando...</option>';
        valueFieldSelect.disabled = true;
        labelFieldSelect.disabled = true;

        if (tabla) {
            // Petición AJAX (Vanilla JS) a nuestra API interna
            fetch(`/admin/parametros/manager/columnas/${tabla}`)
                .then(response => response.json())
                .then(columnas => {
                    let options = '<option value="">-- Seleccione campo --</option>';
                    columnas.forEach(col => {
                        options += `<option value="${col}">${col}</option>`;
                    });
                    
                    valueFieldSelect.innerHTML = options;
                    labelFieldSelect.innerHTML = options;
                    valueFieldSelect.disabled = false;
                    labelFieldSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error obteniendo columnas:', error);
                    valueFieldSelect.innerHTML = '<option value="">Error</option>';
                    labelFieldSelect.innerHTML = '<option value="">Error</option>';
                });
        }
    });
});
</script>
@endpush

@endsection