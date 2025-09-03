@extends('layouts.app')

@section('title', 'Gestión de Depósitos de Combustible')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Depósitos de Combustible</h1>
        <div>
            <!-- Botón para abrir el modal de creación de depósito -->
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#depositoModal">
                <i class="bi bi-plus-circle"></i> Crear Depósito
            </button>
        </div>
    </div>
    <div class="col-12">
        <p class="text-muted">Gestiona la información y el estado de los tanques de combustible.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Inventario de Depósitos</h5>
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

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo Producto</th>
                        <th>Ubicación</th>
                        <th>Nivel Actual</th>
                        <th>Capacidad</th>
                        <th class="text-center">Porcentaje</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $deposito)
                        <tr>
                            <td>{{ $deposito->serial }}</td>
                            <td>{{ $deposito->producto }}</td>
                            <td>{{ $deposito->ubicacion ?? 'N/A' }}</td>
                            <td>{{ number_format($deposito->nivel_actual_litros, 2, ',', '.') }} Lt</td>
                            <td>{{ number_format($deposito->capacidad_litros, 2, ',', '.') }} Lt</td>
                            <td class="text-center">
                                @php
                                    $porcentaje = ($deposito->nivel_actual_litros / $deposito->capacidad_litros) * 100;
                                    $clase_barra = 'bg-success';
                                    if ($porcentaje < 25) {
                                        $clase_barra = 'bg-danger';
                                    } elseif ($porcentaje < 50) {
                                        $clase_barra = 'bg-warning';
                                    }
                                @endphp
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar {{ $clase_barra }}" role="progressbar" style="width: {{ $porcentaje }}%;" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($porcentaje, 0) }}%</div>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-warning edit-btn"
                                    data-id="{{ $deposito->id }}"
                                    data-nombre="{{ $deposito->serial }}"
                                    data-capacidad="{{ $deposito->capacidad_litros }}"
                                    data-nivel="{{ $deposito->nivel_actual_litros }}"
                                    data-ubicacion="{{ $deposito->ubicacion }}">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <form action="{{ route('depositos.destroy', $deposito->id) }}" method="POST" class="d-inline-block delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay depósitos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Depósito -->
<div class="modal fade" id="depositoModal" tabindex="-1" aria-labelledby="depositoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="depositoModalLabel">Crear Depósito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="depositoForm" action="{{ route('depositos.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="_method" value="POST" id="method-field">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Identificador del Depósito</label>
                        <input type="text" class="form-control" id="serial" name="serial" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacidad_litros" class="form-label">Capacidad (Litros)</label>
                        <input type="number" class="form-control" id="capacidad_litros" name="capacidad_litros" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="nivel_actual_litros" class="form-label">Nivel Actual (Litros)</label>
                        <input type="number" class="form-control" id="nivel_actual_litros" name="nivel_actual_litros" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="ubicacion" class="form-label">Ubicación (Opcional)</label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion">
                    </div>
                    <div class="mb-3">
                        @php
                            use App\Models\Deposito;
                            $producto = Deposito::select('producto')->distinct()->get();
                        @endphp
                        <label for="producto" class="form-label">Tipo de Producto</label>
                        <select class="form-select" id="producto" name="producto" required>
                            @foreach ($producto as $prod) 
                                <option value="{{ $prod->producto }}">{{ $prod->producto }}</option>
                                
                            @endforeach
                            <option value="0">Otro</option>
                        </select>
                </div>
                <!-- Nuevo campo para "Otro" producto -->
                        <div class="mb-3" id="nuevo-producto-block" style="display:none;">
                            <label for="nuevo_producto" class="form-label">Nombre del Nuevo Producto</label>
                            <input type="text" class="form-control" id="nuevo_producto" name="nuevo_producto">
                        </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success" id="submitBtn">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script defer src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const createModal = document.getElementById('depositoModal');
        const form = document.getElementById('depositoForm');
        const modalTitle = document.getElementById('depositoModalLabel');
        const methodField = document.getElementById('method-field');
        
            const nuevoProductoBlock = $('#nuevo-producto-block');
            const nuevoProductoInput = $('#nuevo_producto');
        $('#producto').on('change', function() {
            
            if (this.value == 0) {
                nuevoProductoBlock.show();
                nuevoProductoInput.attr('required', 'required');
            } else {
                nuevoProductoBlock.hide();
                nuevoProductoInput.removeAttr('required');
                nuevoProductoInput.val(''); // Limpiar el valor cuando se oculta
            }
        });
        createModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const isEdit = button && button.classList.contains('edit-btn');
            
            form.reset();
            form.action = "{{ route('depositos.store') }}";
            methodField.value = "POST";
            modalTitle.textContent = "Crear Depósito";
            
            if (isEdit) {
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                const capacidad = button.getAttribute('data-capacidad');
                const nivel = button.getAttribute('data-nivel');
                const ubicacion = button.getAttribute('data-ubicacion');

                modalTitle.textContent = "Editar Depósito";
                form.action = `/depositos/${id}`; // URL de actualización
                methodField.value = "PUT";

                document.getElementById('nombre').value = nombre;
                document.getElementById('capacidad_litros').value = capacidad;
                document.getElementById('nivel_actual_litros').value = nivel;
                document.getElementById('ubicacion').value = ubicacion;

            }
        });

         
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "No podrás revertir esto!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    });

</script>
@endpush
