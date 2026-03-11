@extends('layouts.app')

@section('title', 'Hoja Técnica - Orden #' . $orden->nro_orden)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-fluid py-3">
    
    {{-- BARRA DE HERRAMIENTAS DINÁMICA --}}
    <div class="d-flex justify-content-between align-items-center mb-4 no-print bg-white p-3 rounded shadow-sm">
        <div>
            <a href="{{ route('ordenes.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="window.print();" class="btn btn-sm btn-dark ms-2">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
        <div class="d-flex gap-2">
            @if($orden->estatus == 'ABIERTA' || $orden->estatus == 2)
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTrabajo">
                    <i class="fas fa-tools"></i> + Trabajo
                </button>
                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalInsumo">
                    <i class="fas fa-box"></i> + Insumo
                </button>
                <a href="{{ route('ordenes.edit', $orden->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <button id="cerrar-orden" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-check-double"></i> CERRAR
                </button>
                <button id="anular-orden" class="btn btn-sm btn-danger">
                    <i class="fas fa-times-circle"></i> CANCELAR
                </button>
            @elseif($orden->estatus == 'CERRADA' || $orden->estatus == 4 || $orden->estatus == 1)
                <button id="reactivar-orden" class="btn btn-sm btn-warning fw-bold">
                    <i class="fas fa-undo"></i> REACTIVAR ORDEN
                </button>
            @endif
            @if($orden->estatus == 'ANULADA' || $orden->estatus == 4)
                <button id="eliminar-orden" class="btn btn-sm btn-outline-danger fw-bold">
                    <i class="fas fa-trash"></i> ELIMINAR DEFINITIVAMENTE
                </button>
            @endif
        </div>
    </div>

    {{-- ENCABEZADO DE ORDEN --}}
    <div class="card card-step border-orange shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-8">
                    <h2 class="fw-bold text-uppercase mb-0">Hoja Técnica de Servicio</h2>
                    <p class="text-muted small mb-0">Impordiesel - Gestión de Mantenimiento de Flota</p>
                </div>
                <div class="col-4 text-end">
                    <div class="bg-corporate text-white p-2 rounded">
                        <span class="d-block small text-uppercase">Orden de Trabajo</span>
                        <span class="fs-4 fw-bold">#{{ $orden->nro_orden }}</span>
                    </div>
                    <div class="mt-1">
                        <span class="badge bg-{{ $estatusData->css }}">
                            ESTADO: <i class="fa {{ $estatusData->icon_orden }}"></i> {{ $estatusData->orden ?? 'Desconocido' }}
                        </span>
                    </div>
                </div>
            </div>

            <hr>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <label class="form-label-corp d-block text-uppercase small fw-bold">Vehículo / Unidad</label>
                    <span class="fw-bold fs-5 text-orange">{{ $orden->vehiculoBelong->flota ?? 'N/A' }}</span> <br>
                    <span class="text-muted small">Placa: {{ $orden->vehiculoBelong->placa ?? 'N/A' }}</span>
                </div>
                <div class="col-md-2">
                    <label class="form-label-corp d-block text-uppercase small fw-bold">Kilometraje</label>
                    <span class="fw-bold">{{ number_format($orden->kilometraje) }} KM</span>
                </div>
                <div class="col-md-3">
                    <label class="form-label-corp d-block text-uppercase small fw-bold">Tipo de Servicio</label>
                    <span class="badge bg-secondary">{{ $orden->tipo }}</span>
                </div>
                <div class="col-md-3 text-end">
                    <label class="form-label-corp d-block text-uppercase small fw-bold">Fecha Apertura</label>
                    <span class="fw-bold">{{ \Carbon\Carbon::parse($orden->fecha_in)->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- COLUMNA TRABAJOS --}}
        <div class="col-md-7">
            <div class="card card-step shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-uppercase small"><i class="fas fa-tools text-orange me-2"></i>Trabajos Ejecutados</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-3">Servicio</th>
                                <th>Mecánicos</th>
                                <th>Mano de Obra</th>
                                <th class="text-end pe-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($totalManoObra = 0)
                            @forelse($trabajos as $trabajo)
                            @php($totalManoObra += $trabajo->costo)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold">{{ $trabajo->descripcion }}</div>
                                    <div class="text-muted x-small text-uppercase">{{ $trabajo->tempario->categoria->categoria ?? 'General' }}</div>
                                </td>
                                <td>
                                    @foreach($trabajo->mecanicos_lista as $mec)
                                        <span class="badge border text-dark fw-normal bg-light">{{ $mec->persona->nombre ?? 'N/A' }}</span>
                                    @endforeach
                                </td>
                                <td>${{ number_format($trabajo->costo, 2) }}</td>
                                <td class="text-end pe-3 no-print">
                                    @if($orden->estatus == 2 || $orden->estatus == 'ABIERTA')
                                        <button class="btn btn-sm btn-link text-danger p-0 delete-item" data-type="trabajo" data-id="{{ $trabajo->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No hay trabajos registrados.</td></tr>
                            @endforelse
                            @if(count($trabajos) > 0)  
                            <tr class="total-row">
                                <td colspan="2" class="text-end ps-3">TOTAL SERVICIOS</td>
                                <td colspan="2" class="text-end pe-3 text-orange">${{ number_format($totalManoObra, 2) }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-step shadow-sm">
                <div class="card-body">
                    <label class="form-label-corp fw-bold text-uppercase small">Observaciones Técnicas:</label>
                    <p class="mt-2 p-3 bg-light rounded border" style="min-height: 100px;">
                        {{ $orden->descripcion ?: 'Sin observaciones adicionales.' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- COLUMNA INSUMOS --}}
        <div class="col-md-5">
            <div class="card card-step shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-uppercase small"><i class="fas fa-box-open text-orange me-2"></i>Insumos y Repuestos</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-3">Cant</th>
                                <th>Descripción</th>
                                <th class="text-end">P.U.</th>
                                <th class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($totalInsumos=0)
                            @forelse($orden->suministros as $item)
                            @php($subtotal = $item->cantidad * $item->precio_unitario)
                            @php($totalInsumos += $subtotal)
                            <tr class="small">
                                <td class="ps-3 text-center">{{ $item->cantidad }}</td>
                                <td>{{ $item->inventario->descripcion }}</td>
                                <td class="text-end">${{ number_format($item->precio_unitario, 2) }}</td>
                                <td class="text-end pe-3 fw-bold">${{ number_format($subtotal, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted">Sin insumos cargados.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3" class="text-end ps-3">TOTAL INSUMOS</td>
                                <td class="text-end pe-3 text-orange">${{ number_format($totalInsumos, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card bg-corporate text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>SUBTOTAL SERVICIOS:</span>
                        <span>${{ number_format($totalManoObra, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>SUBTOTAL REPUESTOS:</span>
                        <span>${{ number_format($totalInsumos, 2) }}</span>
                    </div>
                    <hr class="bg-white my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase">TOTAL GENERAL:</span>
                        <span class="fs-3 fw-bold text-orange">${{ number_format($totalManoObra + $totalInsumos, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FIRMAS --}}
    <div class="row mt-5 justify-content-around">
        <div class="col-auto">
            <div class="signature-box">
                <span class="d-block fw-bold text-uppercase"></span>
                Jefe de Taller / Autorizado
            </div>
        </div>
        <div class="col-auto">
            <div class="signature-box">
                <br>
                Técnico Responsable
            </div>
        </div>
        <div class="col-auto">
            <div class="signature-box">
                <br>
                Chofer / Recepción
            </div>
        </div>
    </div>
</div>

{{-- MODALES --}}

<div class="modal fade" id="modalTrabajo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('ordenes.addTrabajo', $orden->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title small text-uppercase">Registrar Trabajo Realizado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small fw-bold">Categoría</label>
                              
                                <select id="select-categoria" name="id_categoria" class="form-select form-select-sm select2 s2">
                                    <option value="">Seleccione...</option>
                                    @foreach ($categorias_tempario as $cat)

                                        
                                        <option value="{{ $cat->id_tempario_categoria }}">[{{ $cat->codigo }}] {{ $cat->categoria }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Servicio / Trabajo</label>
                                <select id="select-servicio" name="id_servicio" class="form-select form-select-sm select2 s2">
                                    <option value="">Seleccione categoría primero</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Mecánico(s)</label>
                                <select id="select-mecanicos" name="mecanicos[]" class="form-select form-select-sm s2" multiple>
                                    @foreach ($personal as $p)
                                        <option value="{{ $p->id_personal }}">{{ $p->persona->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="button" class="btn btn-orange btn-sm px-4 fw-bold" id="btn-agregar-trabajo">
                                    <i class="fas fa-plus me-1"></i> AGREGAR TRABAJO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar Trabajo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalInsumo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('ordenes.addInsumo', $orden->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-corporate text-white">
                    <h5 class="modal-title small text-uppercase fw-bold">Cargar Insumo al Inventario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Seleccionar Artículo</label>
                            <select name="id_inventario" class="form-select select2-insumos" required style="width: 100%">
                                <option value="">Escriba para buscar...</option>
                                @foreach($inventario as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->descripcion }} (Stock: {{ $item->stock }}) - ${{ $item->precio_venta }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cantidad</label>
                            <input type="number" name="cantidad" class="form-control form-control-lg" min="1" value="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-orange text-white fw-bold">AGREGAR INSUMO</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
@php(
    
    $dataTrabajos = $trabajos->map(function($t) {
                return [
                    'id_trabajo' => $t->id_trabajo,
                    'id_tempario' => $t->id_tempario_servicio,
                    'id_categoria' => $t->id_categoria ?? null,
                    'concepto' => $t->descripcion,
                    'mecanicos' => $t->mecanicos_lista->pluck('id_personal')->toArray(),
                    'mecanicos_nombres' => $t->mecanicos_lista->pluck('persona.nombre')->join(', ')
                ];
            })
)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
     $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    document.addEventListener('DOMContentLoaded', function () {
        const orderId = '{{ $orden->id }}';

        

        // Ahora la variable se renderiza sin conflictos
        let trabajosAsignados = {!! $dataTrabajos->toJson() !!};

        // Inicializar Select2
        $('.select2-insumos').select2({ dropdownParent: $('#modalInsumo') });
        $('.select2-mecanicos').select2({ dropdownParent: $('#modalTrabajo') });

        // Función genérica para Fetch
        async function apiCall(url, method = 'POST', body = null) {
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: body ? JSON.stringify(body) : null
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Operación realizada.', 'success').then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Error en el servidor', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        }

        // Eventos de Botones de Estado
        if(document.getElementById('cerrar-orden')){
            document.getElementById('cerrar-orden').addEventListener('click', () => {
                Swal.fire({
                    title: '¿Cerrar Orden?',
                    text: "Se generará la hoja técnica final.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cerrar'
                }).then((r) => r.isConfirmed && apiCall(`/ordenes/${orderId}/cerrar`));
            });
        }

        if(document.getElementById('anular-orden')){
            document.getElementById('anular-orden').addEventListener('click', () => {
                Swal.fire({
                    title: 'Anular Orden',
                    text: 'Escriba el motivo (se devolverán los insumos al stock):',
                    input: 'text',
                    showCancelButton: true,
                    inputValidator: (v) => !v && '¡Motivo obligatorio!'
                }).then((r) => r.isConfirmed && apiCall(`/ordenes/${orderId}/anular`, 'POST', { anulacion: r.value }));
            });
        }

        if(document.getElementById('reactivar-orden')){
            document.getElementById('reactivar-orden').addEventListener('click', () => {
                apiCall(`/ordenes/${orderId}/reactivar`);
            });
        }

        if(document.getElementById('eliminar-orden')){
            document.getElementById('eliminar-orden').addEventListener('click', () => {
                Swal.fire({
                    title: '¿Eliminar de la DB?',
                    icon: 'error',
                    showCancelButton: true
                }).then((r) => r.isConfirmed && apiCall(`/ordenes/${orderId}/destroy`, 'DELETE'));
            });
        }

            // 1. CARGA DINÁMICA DE TEMPARIO
        $('#select-categoria').on('change', function() {
            const catId = $(this).val();
            if (!catId) return;

            $.post('{{ route("get.tempario_servicios") }}', { catemp: catId }, function(data) {
                $('#select-servicio').html(data);
            });
        });

        // 2. AGREGAR TRABAJO A LA LISTA
        $('#btn-agregar-trabajo').on('click', function() {
            const servicioId = $('#select-servicio').val();
            const categoriaId = $('#select-categoria').val();
            const servicioTexto = $('#select-servicio option:selected').text();
            const mecanicosIds = $('#select-mecanicos').val();
            const mecanicosNombres = $('#select-mecanicos option:selected').map(function(){ return $(this).text(); }).get();

            if (!servicioId || mecanicosIds.length === 0) {
                Swal.fire('Error', 'Debe seleccionar un servicio y al menos un mecánico', 'error');
                return;
            }

            const nuevoTrabajo = {
                id_tempario: servicioId,
                id_categoria: categoriaId,
                concepto: servicioTexto,
                mecanicos: mecanicosIds,
                mecanicos_nombres: mecanicosNombres.join(', ')
            };

            trabajosAsignados.push(nuevoTrabajo);
            renderTrabajos();
            
            // Limpiar selectores
            $('#select-mecanicos').val(null).trigger('change');
        });

        function renderTrabajos() {
            let html = '';
            if (trabajosAsignados.length === 0) {
                html = '<tr><td colspan="3" class="text-center text-muted py-4 small">No hay trabajos asignados</td></tr>';
            } else {
                trabajosAsignados.forEach((t, index) => {
                    html += `
                    <tr class="small">
                        <td class="ps-3 fw-bold">${t.concepto}</td>
                        <td><span class="text-muted">${t.mecanicos_nombres}</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-link btn-sm text-danger btn-remove-trabajo" data-index="${index}">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </td>
                    </tr>`;
                });
            }
            $('#tabla-trabajos-body').html(html);
            $('#trabajos_json').val(JSON.stringify(trabajosAsignados));
        }

        $(document).on('click', '.btn-remove-trabajo', function() {
            const index = $(this).data('index');
            trabajosAsignados.splice(index, 1);
            renderTrabajos();
        });

        $('#btn-limpiar-trabajos').on('click', function() {
            if(confirm('¿Desea limpiar todos los trabajos?')) {
                trabajosAsignados = [];
                renderTrabajos();
            }
        });
    });
</script>
@endpush