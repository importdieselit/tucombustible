@extends('layouts.app')

@section('title', 'Listado de Inventario')

@push('styles')
    <!-- CSS de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" />
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Inventario</h1>
        <p class="text-muted">Gestión de ítems, consulta de stock y estado del inventario.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0">Lista de Ítems</h5>
        <div>
            <a href="{{ route('inventario.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Crear Nuevo Ítem
            </a>
        </div>
    </div>
    <div class="card-body">
        {{-- Mensajes de sesión (success/error) --}}
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
            <table id="inventarioTable" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th>ID de Ítem</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Existencia Actual</th>
                        <th>Existencia Mínima</th>
                        <th>Estado</th>
                        <th>Fecha de Registro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $index => $item)
                    <tr class="clickable-row" data-id="{{ $item->id }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->codigo ?? 'N/A' }}</td>
                        <td>{{ $item->descripcion ?? 'N/A' }}</td>
                        <td>{{ $item->grupo ?? 'N/A' }}</td>
                        <td>{{ number_format($item->existencia ?? 0, 0, ',', '.') }}</td>
                        <td>{{ number_format($item->existencia_minima ?? 0, 0, ',', '.') }}</td>
                        <td>
                             @if ($item->existencia < $item->existencia_minima)
                                <span class="badge bg-danger">Bajo Stock</span>
                            @else
                                <span class="badge bg-success">Stock Óptimo</span>
                            @endif
                        </td>
                        <td>{{ !is_null($item->fecha_in)?$item->fecha_in: 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Script de jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Script de DataTables -->
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#inventarioTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json"
                },
                layout: {
                    topStart: {
                        buttons: ['csv', 'excel', 'pdf', 'print']
                    }
                }
            });

            // Lógica para redirigir al hacer clic en una fila
            $('#inventarioTable tbody').on('click', 'tr', function() {
                var id = $(this).data('id');
                if (id) {
                    // Reemplaza 'inventario' con tu ruta real de detalle de inventario
                    window.location.href = '/inventario/' + id;
                }
            });
        });
    </script>
@endpush
