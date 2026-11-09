@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h2 class="text-secondary fw-bold"><i class="fas fa-cogs me-2"></i> Configuración del Sistema</h2>
        <!-- Botón solo para SuperAdmin que lleva a tu Manager -->
        @if(auth()->user()->id_perfil === 1)
        <a href="{{ route('parametros.manager.index') }}" class="btn btn-outline-dark">
            <i class="fas fa-tools"></i> Constructor de Parámetros
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
            <!-- Pestañas dinámicas basadas en los grupos de parámetros -->
            <ul class="nav nav-tabs" id="paramTabs" role="tablist">
                @php $first = true; @endphp
                @foreach($parametrosAgrupados as $grupo => $params)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-uppercase {{ $first ? 'active' : '' }}" 
                                id="tab-{{ Str::slug($grupo) }}" 
                                data-bs-toggle="tab" 
                                data-bs-target="#pane-{{ Str::slug($grupo) }}" 
                                type="button" role="tab">
                            {{ $grupo }}
                        </button>
                    </li>
                    @php $first = false; @endphp
                @endforeach
            </ul>
        </div>

        <div class="card-body bg-light">
            <div class="card-body bg-light">
                <form action="{{ route('parametros.update') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="tab-content" id="paramTabsContent">
                        @php $first = true; @endphp
                        @foreach($parametrosAgrupados as $grupo => $params)
                            <div class="tab-pane fade {{ $first ? 'show active' : '' }}" id="pane-{{ Str::slug($grupo) }}" role="tabpanel">
                                
                                <div class="row">
                                    @foreach($params as $param)
                                        @php 
                                            $schema = $param->esquema; 
                                            // Extraemos el contenido. Si es array, lo volvemos string JSON para que Blade no explote.
                                            $contenidoSeguro = is_array($param->contenido) ? json_encode($param->contenido) : $param->contenido;
                                        @endphp
                                        <div class="col-md-6 mb-4">
                                            <div class="card h-100 border-0 shadow-sm">
                                                <div class="card-body">
                                                    <h6 class="card-title text-primary fw-bold border-bottom pb-2">
                                                        {{ is_array($schema['label'] ?? null) ? 'Label Inválido' : ($schema['label'] ?? $param->clave) }}
                                                    </h6>
                                                    
                                                    @if(!empty($schema['help']) && !is_array($schema['help']))
                                                        <p class="text-muted small mb-3"><i class="fas fa-info-circle me-1"></i>{{ $schema['help'] }}</p>
                                                    @endif

                                                    <!-- Renderizado Dinámico de Inputs -->
                                                    @if(isset($schema['ui_type']))
                                                        
                                                        @if($schema['ui_type'] === 'number')
                                                            <div class="input-group">
                                                                <input type="number" 
                                                                    name="parametros[{{ $param->id }}]" 
                                                                    class="form-control" 
                                                                    value="{{ $contenidoSeguro }}"
                                                                    {{ isset($schema['attributes']['step']) ? 'step='.$schema['attributes']['step'] : '' }}
                                                                    {{ isset($schema['attributes']['min']) ? 'min='.$schema['attributes']['min'] : '' }}>
                                                            </div>

                                                        @elseif($schema['ui_type'] === 'text')
                                                            <input type="text" 
                                                                name="parametros[{{ $param->id }}]" 
                                                                class="form-control" 
                                                                value="{{ $contenidoSeguro }}">

                                                        @elseif($schema['ui_type'] === 'group' && isset($schema['fields']))
                                                            <!-- Grupos de campos (como las tarifas de viáticos) -->
                                                            <div class="bg-light p-3 rounded border">
                                                                @foreach($schema['fields'] as $key => $field)
                                                                    @php
                                                                        $valorCampo = $param->contenido[$key] ?? '';
                                                                        $valorCampoSeguro = is_array($valorCampo) ? json_encode($valorCampo) : $valorCampo;
                                                                    @endphp
                                                                    <div class="mb-2">
                                                                        <label class="form-label small fw-bold text-secondary">{{ $field['label'] ?? $key }}</label>
                                                                        <input type="{{ $field['ui_type'] ?? 'text' }}" 
                                                                            name="parametros[{{ $param->id }}][{{ $key }}]" 
                                                                            class="form-control form-control-sm" 
                                                                            value="{{ $valorCampoSeguro }}"
                                                                            {{ isset($field['attributes']['step']) ? 'step='.$field['attributes']['step'] : '' }}>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        
                                                        @elseif($schema['ui_type'] === 'db_select' && isset($schema['source']))
                                                            @php
                                                                // Magia negra: consultamos la tabla dinámica directamente desde Blade
                                                                try {
                                                                    $dbOptions = \Illuminate\Support\Facades\DB::table($schema['source']['table'])
                                                                        ->select($schema['source']['value_field'], $schema['source']['label_field'])
                                                                        ->get();
                                                                } catch (\Exception $e) {
                                                                    // Si estalla (ej. borraron la tabla o los campos), evitamos que la página muera
                                                                    $dbOptions = collect([]); 
                                                                }
                                                            @endphp
                                                            
                                                            <select name="parametros[{{ $param->id }}]" class="form-select">
                                                                <option value="">-- Seleccione {{ $schema['label'] ?? 'opción' }} --</option>
                                                                @foreach($dbOptions as $opt)
                                                                    @php
                                                                        $val = $opt->{$schema['source']['value_field']};
                                                                        $lbl = $opt->{$schema['source']['label_field']};
                                                                    @endphp
                                                                    <option value="{{ $val }}" {{ (string)$contenidoSeguro === (string)$val ? 'selected' : '' }}>
                                                                        {{ $lbl }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @elseif($schema['ui_type'] === 'repeater' && isset($schema['fields']))
                                                            <!-- Módulo Repeater: Anti-brutos -->
                                                            <div class="repeater-container border rounded p-2 bg-white" data-param-id="{{ $param->id }}">
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm table-bordered mb-2">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                @foreach($schema['fields'] as $fKey => $fDef)
                                                                                    <th>{{ $fDef['label'] ?? $fKey }}</th>
                                                                                @endforeach
                                                                                <th style="width: 40px;" class="text-center"><i class="fas fa-cog"></i></th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="repeater-body">
                                                                            @php
                                                                                $items = is_array($param->contenido) ? $param->contenido : json_decode($param->contenido, true) ?? [];
                                                                            @endphp
                                                                            @forelse($items as $index => $item)
                                                                                <tr>
                                                                                    @foreach($schema['fields'] as $fKey => $fDef)
                                                                                        <td>
                                                                                            <input type="{{ ($fDef['ui_type'] ?? 'text') === 'number' ? 'number' : 'text' }}"
                                                                                                name="parametros[{{ $param->id }}][{{ $index }}][{{ $fKey }}]"
                                                                                                class="form-control form-control-sm"
                                                                                                value="{{ $item[$fKey] ?? '' }}" required>
                                                                                        </td>
                                                                                    @endforeach
                                                                                    <td class="text-center align-middle">
                                                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fas fa-times"></i></button>
                                                                                    </td>
                                                                                </tr>
                                                                            @empty
                                                                            @endforelse
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                <button type="button" class="btn btn-sm btn-secondary btn-add-row" 
                                                                        data-fields="{{ json_encode($schema['fields']) }}" 
                                                                        data-param-id="{{ $param->id }}">
                                                                    <i class="fas fa-plus"></i> Agregar Fila
                                                                </button>
                                                            </div>

                                                        @elseif($schema['ui_type'] === 'product_matrix' && isset($schema['fields']))
                                                            <!-- Módulo Matriz de Productos -->
                                                            <div class="table-responsive border rounded bg-white p-2">
                                                                <table class="table table-sm table-hover table-bordered mb-0">
                                                                    <thead class="table-dark text-center">
                                                                        <tr>
                                                                            <th style="width: 20%;">ID Prod.</th>
                                                                            @foreach($schema['fields'] as $fKey => $fDef)
                                                                                <th>{{ $fDef['label'] ?? $fKey }}</th>
                                                                            @endforeach
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @php
                                                                            $matrix = is_array($param->contenido) ? $param->contenido : json_decode($param->contenido, true) ?? [];
                                                                        @endphp
                                                                        @foreach($matrix as $prodId => $niveles)
                                                                            <tr>
                                                                                <td class="align-middle text-center bg-light fw-bold">{{ $prodId }}</td>
                                                                                @foreach($schema['fields'] as $fKey => $fDef)
                                                                                    <td>
                                                                                        <input type="number" step="0.01"
                                                                                            name="parametros[{{ $param->id }}][{{ $prodId }}][{{ $fKey }}]"
                                                                                            class="form-control form-control-sm text-end"
                                                                                            value="{{ $niveles[$fKey] ?? '' }}">
                                                                                    </td>
                                                                                @endforeach
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                                <small class="text-muted d-block mt-2">
                                                                    * Esta matriz se genera en base a los IDs de los productos ya guardados en este parámetro.
                                                                </small>
                                                            </div>
                                                        @elseif($schema['ui_type'] === 'db_matrix' && isset($schema['source']))
                                                            <!-- Módulo Matriz de Base de Datos Dinámica -->
                                                            @php
                                                                try {
                                                                    // Consultamos la tabla que pide el JSON
                                                                    $dbRows = \Illuminate\Support\Facades\DB::table($schema['source']['table'])
                                                                        ->select($schema['source']['value_field'], $schema['source']['label_field'])
                                                                        ->get();
                                                                } catch (\Exception $e) {
                                                                    $dbRows = collect([]);
                                                                }

                                                                // --- MAGIA NIVEL DIOS: Campos dinámicos desde otro parámetro ---
                                                                if (isset($schema['dynamic_fields'])) {
                                                                    $targetParam = \App\Models\Parametro::where('clave', $schema['dynamic_fields']['source_parameter'])->first();
                                                                    $schema['fields'] = []; // Inicializamos vacío
                                                                    
                                                                    if ($targetParam && is_array($targetParam->contenido)) {
                                                                        foreach ($targetParam->contenido as $item) {
                                                                            $k = $item[$schema['dynamic_fields']['key_field']] ?? null;
                                                                            $lbl = $item[$schema['dynamic_fields']['label_field']] ?? $k;
                                                                            
                                                                            if ($k) {
                                                                                $schema['fields'][$k] = [
                                                                                    'label' => $lbl,
                                                                                    'ui_type' => $schema['dynamic_fields']['ui_type'] ?? 'text'
                                                                                ];
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                // ---------------------------------------------------------------

                                                                $matrizData = is_array($param->contenido) ? $param->contenido : json_decode($param->contenido, true) ?? [];
                                                            @endphp
                                                            
                                                            @if(!empty($schema['fields']))
                                                                <div class="table-responsive border rounded bg-white p-2">
                                                                    <table class="table table-sm table-hover table-bordered mb-0">
                                                                        <thead class="table-dark text-center">
                                                                            <tr>
                                                                                <th class="text-start text-uppercase">{{ $schema['source']['table'] }}</th>
                                                                                @foreach($schema['fields'] as $fKey => $fDef)
                                                                                    <th>{{ $fDef['label'] ?? $fKey }}</th>
                                                                                @endforeach
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @forelse($dbRows as $row)
                                                                                @php
                                                                                    $rowVal = $row->{$schema['source']['value_field']};
                                                                                    $rowLbl = $row->{$schema['source']['label_field']};
                                                                                @endphp
                                                                                <tr>
                                                                                    <td class="align-middle fw-bold bg-light">{{ $rowLbl }}</td>
                                                                                    @foreach($schema['fields'] as $fKey => $fDef)
                                                                                        <td class="text-center align-middle">
                                                                                            @if(($fDef['ui_type'] ?? '') === 'checkbox')
                                                                                                <input type="hidden" name="parametros[{{ $param->id }}][{{ $rowVal }}][{{ $fKey }}]" value="0">
                                                                                                <input class="form-check-input border-secondary" type="checkbox" 
                                                                                                    name="parametros[{{ $param->id }}][{{ $rowVal }}][{{ $fKey }}]" 
                                                                                                    value="1" style="transform: scale(1.3);"
                                                                                                    {{ !empty($matrizData[$rowVal][$fKey]) ? 'checked' : '' }}>
                                                                                            @else
                                                                                                <input type="{{ $fDef['ui_type'] ?? 'text' }}" 
                                                                                                    name="parametros[{{ $param->id }}][{{ $rowVal }}][{{ $fKey }}]" 
                                                                                                    class="form-control form-control-sm text-center" 
                                                                                                    value="{{ $matrizData[$rowVal][$fKey] ?? '' }}">
                                                                                            @endif
                                                                                        </td>
                                                                                    @endforeach
                                                                                </tr>
                                                                            @empty
                                                                                <tr><td colspan="100%" class="text-center text-muted py-3">No se encontraron registros en la tabla origen.</td></tr>
                                                                            @endforelse
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @else
                                                                <div class="alert alert-warning small py-2 mb-0">
                                                                    <i class="fas fa-exclamation-triangle me-2"></i> No se pudieron generar las columnas. Verifica que el parámetro enlazado exista y contenga datos.
                                                                </div>
                                                            @endif
                                                        @else
                                                            <div class="alert alert-warning small py-2 mb-0 border-warning bg-light">
                                                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                                                Tipo de control (<code>{{ is_array($schema['ui_type']) ? 'Array' : $schema['ui_type'] }}</code>) no soportado visualmente.
                                                            </div>
                                                        @endif

                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                            @php $first = false; @endphp
                        @endforeach
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                            <i class="fas fa-save me-2"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Delegación de eventos para agregar y eliminar filas
    document.addEventListener('click', function(e) {
        
        // 1. Agregar Fila
        let btnAdd = e.target.closest('.btn-add-row');
        if (btnAdd) {
            let container = btnAdd.closest('.repeater-container');
            let tbody = container.querySelector('.repeater-body');
            let paramId = btnAdd.getAttribute('data-param-id');
            let fields = JSON.parse(btnAdd.getAttribute('data-fields'));
            
            let tr = document.createElement('tr');
            let html = '';
            
            for (let key in fields) {
                let type = fields[key].ui_type === 'number' ? 'number' : 'text';
                // El índice temporal es 999, luego se reindexa
                html += `<td><input type="${type}" name="parametros[${paramId}][999][${key}]" class="form-control form-control-sm" required></td>`;
            }
            html += `<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fas fa-times"></i></button></td>`;
            
            tr.innerHTML = html;
            tbody.appendChild(tr);
            
            reindexRepeater(tbody, paramId);
        }

        // 2. Eliminar Fila
        let btnRemove = e.target.closest('.btn-remove-row');
        if (btnRemove) {
            let tbody = btnRemove.closest('.repeater-body');
            let paramId = btnRemove.closest('.repeater-container').getAttribute('data-param-id');
            btnRemove.closest('tr').remove();
            
            reindexRepeater(tbody, paramId);
        }
    });

    // Función mágica que asegura que el array enviado a PHP sea secuencial [0, 1, 2...]
    function reindexRepeater(tbody, paramId) {
        let rows = tbody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            let inputs = row.querySelectorAll('input');
            inputs.forEach(input => {
                // Reemplazamos cualquier número que esté en la posición del índice por el índice real
                // Patrón: parametros[ID][numero][llave] -> parametros[ID][nuevoIndex][llave]
                let regex = new RegExp(`name="parametros\\[${paramId}\\]\\[\\d+\\]`);
                let regexDirect = new RegExp(`parametros\\[${paramId}\\]\\[\\d+\\]`);
                input.name = input.name.replace(regexDirect, `parametros[${paramId}][${index}]`);
            });
        });
    }
});
</script>
@endpush
@endsection