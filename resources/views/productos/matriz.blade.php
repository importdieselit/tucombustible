@extends('layouts.app') <!-- Cambia esto por tu layout principal -->

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Matriz de Configuración de Productos</h2>
            <p class="text-muted">Haz clic en cualquier celda para editar. Las filas modificadas se resaltarán. Recuerda guardar al final.</p>
        </div>
        <!-- El botón principal arriba, siempre visible y claro -->
        <button type="button" class="btn btn-primary btn-lg shadow-sm" onclick="document.getElementById('matriz-form').submit();">
            <i class="fas fa-save"></i> Guardar Todos los Cambios
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <form id="matriz-form" action="{{ route('productos.matriz.update') }}" method="POST">
                @csrf
                <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                    <table class="table table-bordered table-hover excel-table mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 50px;">ID</th>
                                <th>Combustible (Nombre)</th>
                                <th>Precio</th>
                                <th>Litros/TM</th>
                                <th>Categoría ISO</th>
                                <th>Densidad a 15°C</th>
                                <th>Viscosidad</th>
                                <th>Azufre (%)</th>
                                <th style="min-width: 300px;">Aplicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productos as $p)
                            <tr class="row-excel" id="row-{{ $p->id }}">
                                <td class="text-center align-middle bg-light fw-bold text-muted">
                                    {{ $p->id }}
                                </td>
                                
                                <td>
                                    <input type="text" name="productos[{{ $p->id }}][nombre]" value="{{ $p->nombre }}" class="excel-input" onchange="markChanged({{ $p->id }})">
                                </td>
                                <td>
                                    <input type="text" name="productos[{{ $p->id }}][precio]" value="{{ $p->precio }}" class="excel-input" onchange="markChanged({{ $p->id }})">
                                </td>
                                <td>
                                    <input type="text" name="productos[{{ $p->id }}][litros_tm]" value="{{ $p->litros_tm }}" class="excel-input" onchange="markChanged({{ $p->id }})">
                                </td>
                                <td>
                                    <input type="text" name="productos[{{ $p->id }}][categoria_iso]" value="{{ $p->categoria_iso }}" class="excel-input" onchange="markChanged({{ $p->id }})">
                                </td>
                                <td>
                                    <input type="text" name="productos[{{ $p->id }}][densidad]" value="{{ $p->densidad }}" class="excel-input" onchange="markChanged({{ $p->id }})">
                                </td>
                                <td>
                                    <input type="text" name="productos[{{ $p->id }}][viscosidad]" value="{{ $p->viscosidad }}" class="excel-input" onchange="markChanged({{ $p->id }})">
                                </td>
                                <td>
                                    <input type="text" name="productos[{{ $p->id }}][azufre]" value="{{ $p->azufre }}" class="excel-input" onchange="markChanged({{ $p->id }})">
                                </td>
                                <td>
                                    <!-- Usamos input en lugar de textarea para mantener la estética de cuadrícula de Excel -->
                                    <input type="text" name="productos[{{ $p->id }}][aplicacion]" value="{{ $p->aplicacion }}" class="excel-input" onchange="markChanged({{ $p->id }})">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Estilos mágicos para que parezca Excel y no un formulario feo de los 90s */
    .excel-table th {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #555;
        border-bottom: 2px solid #ddd;
        white-space: nowrap;
    }
    
    .excel-table td {
        padding: 0 !important; /* Quitamos padding para que el input ocupe todo */
        vertical-align: middle;
        position: relative;
    }

    .excel-input {
        width: 100%;
        height: 100%;
        min-height: 40px;
        border: 2px solid transparent;
        padding: 8px 12px;
        background: transparent;
        color: #333;
        font-size: 14px;
        transition: all 0.2s;
        outline: none;
    }

    /* Efecto hover y focus estilo celda de Excel */
    .excel-input:hover {
        background-color: rgba(0,0,0,0.02);
    }
    .excel-input:focus {
        background-color: #fff;
        border: 2px solid #0d6efd; /* Borde azul característico de selección */
        box-shadow: 0 0 5px rgba(13, 110, 253, 0.3);
        z-index: 10;
        position: relative;
    }

    /* Clase que se aplica por JS cuando modifican algo */
    .row-changed {
        background-color: #e8f4f8 !important; 
    }
    .row-changed td:first-child {
        border-left: 4px solid #0d6efd;
    }
</style>

<script>
    // Un poco de JS básico para mejorar la experiencia UX de tus queridos usuarios
    function markChanged(id) {
        let row = document.getElementById('row-' + id);
        if (row) {
            row.classList.add('row-changed');
        }
    }
</script>
@endsection