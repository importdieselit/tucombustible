@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Configuración del Sistema</h2>
        <button type="submit" form="formParametros" class="btn btn-primary fw-bold">
            <i class="fas fa-save me-1"></i> Guardar Cambios
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('parametros.update') }}" method="POST" id="formParametros">
        @csrf
        @method('PUT')

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pt-3 pb-0 border-bottom">
                {{-- NAVEGACIÓN DE PESTAÑAS --}}
                <ul class="nav nav-tabs border-bottom-0" id="configTabs" role="tablist">
                    @foreach($grupos as $nombreGrupo =>$parametros)
                        @php $tabId = 'tab-' . Str::slug($nombreGrupo); @endphp
                        <li class="nav-item" role="presentation">
                            <button class="nav-link custom-tab-btn fw-bold {{ $loop->first ? 'active text-primary' : 'text-muted' }}" 
                                    type="button" 
                                    data-target="#{{ $tabId }}">
                                {{ $nombreGrupo }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body bg-light pt-4">
                {{-- CONTENIDO DE LAS PESTAÑAS --}}
                <div class="tab-content-container">
                    @foreach($grupos as $nombreGrupo =>$parametros)
                        @php $tabId = 'tab-' . Str::slug($nombreGrupo); @endphp
                        
                        <div id="{{ $tabId }}" class="custom-tab-pane {{ $loop->first ? 'd-block' : 'd-none' }}">
                            <div class="row bg-white p-4 rounded border shadow-sm">
                                
                                @foreach($parametros as$param)
                                    @php
                                        $valorArray = is_array($param->valor) ?$param->valor : [];
                                        $esquema =$valorArray['schema'] ?? [];
                                        $dato =$valorArray['content'] ?? null;
                                        
                                        $type = $esquema['ui_type'] ?? 'text';$label = $esquema['label'] ?? $param->clave;
                                    @endphp

                                    <div class="col-12 mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <label class="form-label fw-bold text-dark fs-5">{{ $label }}</label>
                                        
                                        @if(isset($esquema['help']))
                                            <p class="text-muted small mb-2">{{ $esquema['help'] }}</p>
                                        @endif

                                        {{-- 1. VALORES TEXTO / NUMERO --}}
                                        @if(in_array($type, ['text', 'number']))
                                            <input type="{{ $type }}" 
                                                   name="parametros[{{ $param->id }}]" 
                                                   value="{{ old('parametros.'.$param->id, is_scalar($dato) ?$dato : '') }}" 
                                                   class="form-control" style="max-width: 400px;">

                                        {{-- 2. DESPLEGABLE DE BASE DE DATOS (DB_SELECT) --}}
                                        @elseif($type === 'db_select' && isset($esquema['source']))
                                            @php
                                                $src =$esquema['source'];
                                                $registros = \Illuminate\Support\Facades\Schema::hasTable($src['table'] ?? '')
                                                    ? \Illuminate\Support\Facades\DB::table($src['table'])
                                                        ->select($src['value_field'],$src['label_field'])
                                                        ->get()
                                                    : collect();
                                            @endphp
                                            <select name="parametros[{{ $param->id }}]" class="form-select" style="max-width: 400px;">
                                                <option value="">-- Seleccionar de {{ $src['table'] }} --</option>
                                                @foreach($registros as$reg)
                                                    <option value="{{ $reg->{$src['value_field']} }}" 
                                                        {{ (string)$dato === (string)$reg->{$src['value_field']} ? 'selected' : '' }}>
                                                        {{ $reg->{$src['label_field']} }}
                                                    </option>
                                                @endforeach
                                            </select>

                                        {{-- 3. MATRIZ DINÁMICA DESDE BASE DE DATOS (DB_MATRIX) --}}
                                        @elseif($type === 'db_matrix' && isset($esquema['source']))
                                            @php
                                                $src =$esquema['source'];
                                                $filasBD = \Illuminate\Support\Facades\Schema::hasTable($src['table'] ?? '')
                                                    ? \Illuminate\Support\Facades\DB::table($src['table'])
                                                        ->select($src['value_field'],$src['label_field'])
                                                        ->get()
                                                    : collect();
                                            @endphp
                                            <div class="table-responsive border rounded">
                                                <table class="table table-sm table-hover mb-0 align-middle">
                                                    <thead class="table-primary">
                                                        <tr>
                                                            <th>Registro ({{ $src['table'] }})</th>
                                                            <th style="width: 250px;">Valor Asignado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($filasBD as$reg)
                                                            @php
                                                                $valId =$reg->{$src['value_field']};$valLabel = $reg->{$src['label_field']};
                                                                $valorGuardado = is_array($dato) ? ($dato[$valId] ?? '') : '';
                                                            @endphp
                                                            <tr>
                                                                <td class="fw-bold">{{ $valLabel }}</td>
                                                                <td>
                                                                    <input type="text" 
                                                                           name="parametros[{{ $param->id }}][{{$valId }}]" 
                                                                           value="{{ old('parametros.'.$param->id.'.'.$valId,$valorGuardado) }}" 
                                                                           class="form-control form-control-sm">
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                        {{-- 4. TABLA REPETIDORA LIBRE (REPEATER) --}}
                                        @elseif($type === 'repeater')
                                            <div class="table-responsive border rounded mb-2">
                                                <table class="table table-sm table-striped table-hover mb-0" id="tabla-repeater-{{ $param->id }}">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th>Clave / Parámetro</th>
                                                            <th>Valor</th>
                                                            <th style="width: 50px;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @if(is_array($dato) && count($dato) > 0)
                                                            @foreach($dato as $index =>$row)
                                                                <tr>
                                                                    <td>
                                                                        <input type="text" 
                                                                               name="parametros[{{ $param->id }}][{{$index }}][clave]" 
                                                                               value="{{ $row['clave'] ?? '' }}" 
                                                                               class="form-control form-control-sm">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" 
                                                                               name="parametros[{{ $param->id }}][{{$index }}][valor]" 
                                                                               value="{{ $row['valor'] ?? '' }}" 
                                                                               class="form-control form-control-sm">
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">&times;</button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @else
                                                            {{-- Fila inicial por defecto si está vacío --}}
                                                            <tr>
                                                                <td><input type="text" name="parametros[{{ $param->id }}][0][clave]" class="form-control form-control-sm" placeholder="Clave"></td>
                                                                <td><input type="text" name="parametros[{{ $param->id }}][0][valor]" class="form-control form-control-sm" placeholder="Valor"></td>
                                                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">&times;</button></td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="agregarFilaRepeater({{ $param->id }})">
                                                <i class="fas fa-plus me-1"></i> Agregar Fila
                                            </button>
                                        @endif

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Manejo de pestañas Vanilla JS
        const tabButtons = document.querySelectorAll('.custom-tab-btn');
        const tabPanes = document.querySelectorAll('.custom-tab-pane');

        tabButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'text-primary');
                    btn.classList.add('text-muted');
                });

                tabPanes.forEach(pane => {
                    pane.classList.remove('d-block');
                    pane.classList.add('d-none');
                });

                this.classList.add('active', 'text-primary');
                this.classList.remove('text-muted');

                const targetId = this.getAttribute('data-target');
                const targetPane = document.querySelector(targetId);
                if (targetPane) {
                    targetPane.classList.remove('d-none');
                    targetPane.classList.add('d-block');
                }
            });
        });
    });

    // Función global para agregar filas dinámicas al Repeater
    function agregarFilaRepeater(paramId) {
        const tbody = document.querySelector(`#tabla-repeater-${paramId} tbody`);
        const index = Date.now();
        const nuevaFila = `
            <tr>
                <td><input type="text" name="parametros[${paramId}][${index}][clave]" class="form-control form-control-sm" placeholder="Clave"></td>
                <td><input type="text" name="parametros[${paramId}][${index}][valor]" class="form-control form-control-sm" placeholder="Valor"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">&times;</button></td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', nuevaFila);
    }
</script>
@endpush
@endsection