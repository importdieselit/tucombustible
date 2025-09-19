@extends('layouts.app')

@section('title', 'Checklist de Salida de Camiones')

@push('styles')
    <style>
        :root {
            --bg-light: #f4f6f8;
            --bg-card: #ffffff;
            --text-dark: #333333;
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --secondary-color: #10b981;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .card {
            background-color: var(--bg-card);
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s ease-in-out;
        }

        .form-check-input:checked {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary-custom:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card p-4">
                    <h1 class="h3 fw-bold mb-4 text-center">Checklist de Salida de Camión</h1>
                    <p class="text-muted text-center mb-4">
                        Por favor, verifique cada uno de los puntos antes de autorizar la salida del vehículo.
                    </p>
                    <form id="checklistForm" method="POST" action="{{ route('checklist.process') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="camion_id" class="form-label fw-bold">Seleccione el Camión</label>
                            <select class="form-select" id="camion_id" name="camion_id" required>
                                <option value="">Seleccione un camión...</option>
                                @foreach ($camiones as $camion)
                                    <option value="{{ $camion['id'] }}">{{ $camion['placa'] }} - {{ $camion['marca'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="conductor_id" class="form-label fw-bold">Seleccione el Conductor</label>
                            <select class="form-select" id="conductor_id" name="conductor_id" required>
                                <option value="">Seleccione un conductor...</option>
                                @foreach ($conductores as $conductor)
                                    <option value="{{ $conductor['id'] }}">{{ $conductor['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <h2 class="h5 fw-bold mb-3">Puntos de Verificación</h2>
                            <ul class="list-group">
                                @foreach ($itemsChecklist as $item)
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="form-check w-100">
                                        <input class="form-check-input me-2" type="checkbox" name="items_verificados[]" value="{{ $item }}" id="item-{{ $loop->index }}" required>
                                        <label class="form-check-label" for="item-{{ $loop->index }}">
                                            {{ $item }}
                                        </label>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="mb-4">
                            <label for="observaciones" class="form-label fw-bold">Observaciones (Opcional)</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Añade cualquier observación relevante aquí."></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-save me-2"></i> Guardar Checklist
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script para SweetAlert2 y manejo del formulario con AJAX -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('checklistForm');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                // Convertir los checkboxes a un array de valores
                const checkedItems = Array.from(form.querySelectorAll('input[name="items_verificados[]"]:checked')).map(cb => cb.value);
                data['items_verificados'] = checkedItems;
                
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: result.message,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            // Opcional: limpiar el formulario o redirigir
                            form.reset();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al guardar el checklist.',
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor. Por favor, intente de nuevo.',
                    });
                });
            });
            });
        </script>
@endsection
