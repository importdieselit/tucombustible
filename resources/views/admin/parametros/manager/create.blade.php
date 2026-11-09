@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 mt-4">
    
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('parametros.manager.index') }}" class="btn btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h3 class="mb-0 text-secondary fw-bold">Nuevo Esquema de Parámetro</h3>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-dark text-white pt-3 pb-2 rounded-top-3">
                    <h5 class="mb-0"><i class="fas fa-hammer me-2"></i> Constructor (Builder)</h5>
                </div>
                
                <div class="card-body bg-light p-4">
                    <form action="{{ route('parametros.manager.store') }}" method="POST" id="builderForm">
                        @csrf
                        
                        <!-- Sección 1: Metadatos -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-4">1. Identificación y Metadatos</h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-secondary">Grupo Lógico</label>
                                        <input type="text" name="grupo" class="form-control text-uppercase bg-light" placeholder="Ej: OPERACIONES" required>
                                        <small class="text-muted">Define la pestaña donde aparecerá.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-secondary">Clave Única del Sistema</label>
                                        <input type="text" name="clave" class="form-control text-uppercase font-monospace bg-light" placeholder="Ej: TARIFA_MINIMA" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-secondary">Etiqueta Visual (UI)</label>
                                        <input type="text" name="label" class="form-control" placeholder="Ej: Tarifa Mínima de Bunkering" required>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <label class="form-label fw-bold text-secondary">Texto de Ayuda / Descripción</label>
                                        <input type="text" name="help" class="form-control" placeholder="Instrucciones para el usuario que editará este valor...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: UI & Base de Datos -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-4">2. Estructura de Datos (UI Type)</h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary">Tipo de Control</label>
                                        <select name="ui_type" id="ui_type" class="form-select border-primary bg-light" required>
                                            <option value="text">Texto Corto (String)</option>
                                            <option value="number">Numérico (Decimal/Entero)</option>
                                            <option value="group">Grupo de Campos Estáticos</option>
                                            <option value="repeater" disabled>Repetidor (Requiere JSON avanzado)</option>
                                            <option value="db_select">Selector Dinámico (Desde Base de Datos)</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Bloque Dinámico DB --}}
                                <div id="db_config_block" class="row g-3 mt-3 p-3 bg-white rounded border border-info d-none transition-all">
                                    <div class="col-12">
                                        <p class="mb-2 fw-bold text-info"><i class="fas fa-database me-2"></i>Enlace a Tabla del Sistema</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Tabla Origen</label>
                                        <select name="db_table" id="db_table" class="form-select form-select-sm">
                                            <option value="">-- Seleccione tabla --</option>
                                            @foreach($tablas as $tabla)
                                                <option value="{{ $tabla }}">{{ $tabla }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Campo Valor (Value)</label>
                                        <select name="db_value_field" id="db_value_field" class="form-select form-select-sm" disabled>
                                            <option value="">Cargando...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Campo Etiqueta (Label)</label>
                                        <select name="db_label_field" id="db_label_field" class="form-select form-select-sm" disabled>
                                            <option value="">Cargando...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success btn-lg px-5 shadow-sm fw-bold">
                                <i class="fas fa-check-circle me-2"></i> Generar Esquema
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// El mismo JS que tenías antes para el manejo del DOM[cite: 1], funciona bien. 
// No hay necesidad de reinventar la rueda aquí.
document.addEventListener('DOMContentLoaded', function () {
    const uiTypeSelect = document.getElementById('ui_type');
    const dbConfigBlock = document.getElementById('db_config_block');
    const dbTableSelect = document.getElementById('db_table');
    const valueFieldSelect = document.getElementById('db_value_field');
    const labelFieldSelect = document.getElementById('db_label_field');

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

    dbTableSelect.addEventListener('change', function () {
        const tabla = this.value;
        valueFieldSelect.innerHTML = '<option value="">Cargando...</option>';
        labelFieldSelect.innerHTML = '<option value="">Cargando...</option>';
        valueFieldSelect.disabled = true;
        labelFieldSelect.disabled = true;

        if (tabla) {
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
                });
        }
    });
});
</script>
@endsection