@extends('layouts.app')
@section('title', 'TuCombustible - '.Auth::user()->name)

@push('styles')
        <style>
        :root {
            --bg-light: #f4f6f8;
            --bg-card: #ffffff;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --secondary-color: #10b981;
            --secondary-dark: #059669;
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
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15), 0 4px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary-custom:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-secondary-custom {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary-custom:hover {
            background-color: var(--secondary-dark);
            border-color: var(--secondary-dark);
        }

        .progress-bar-custom {
            background-color: var(--primary-color);
        }

        .progress-bar-danger {
            background-color: #ef4444;
        }

        .stat-card-icon {
            font-size: 2rem;
            color: #495057;
        }

        .main-hero-card {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            color: var(--text-dark);
        }

        .main-hero-card .text-muted {
            color: #6c757d !important;
        }

        /* Estilos para la visualización de los depósitos de sucursal */
        .tank-container {
            position: relative;
            width: 100%;
            height: 150px;
            background-color: #e9ecef;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .tank-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            transition: height 0.5s ease-in-out, background-color 0.3s;
        }

        .tank-fill.normal {
            background: linear-gradient(to top, #63b3ed, #4299e1);
        }

        .tank-fill.alert {
            background: linear-gradient(to top, #fc8181, #e53e3e);
        }

        .tank-level-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--text-dark);
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 10;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
        }

        .tank-capacity-label {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .card-hover:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-5px);
            transition: all 0.3s ease-in-out;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="display-8 fw-bold mb-5 text-center text-black">{{ ucword(Auth::user()->name)}}</h1>
        {{-- En una aplicación real, esta información vendría del usuario autenticado --}}
         <p class="text-center text-sm mb-5" id="user-role-info">  </p>

        <!-- Sección de Visualización de Capacidad Total o de Sucursal -->
        <div class="card p-4 mb-5 main-hero-card">
            @php
                // Simulación de datos pasados desde el controlador
                $currentUserRole = 'principal'; // 'principal' o 'sucursal'
                $currentUserBranchId = 'branch-A';
                $depositos = [
                    ['id' => '1', 'serial' => 'D-001', 'producto' => 'Gasolina', 'capacidad_litros' => 10000, 'nivel_actual_litros' => 8500, 'nivel_alerta_litros' => 1000, 'branch_id' => 'branch-A'],
                    ['id' => '2', 'serial' => 'D-002', 'producto' => 'Diésel', 'capacidad_litros' => 15000, 'nivel_actual_litros' => 1200, 'nivel_alerta_litros' => 1500, 'branch_id' => 'branch-A'],
                    ['id' => '3', 'serial' => 'D-003', 'producto' => 'Kerosene', 'capacidad_litros' => 5000, 'nivel_actual_litros' => 4500, 'nivel_alerta_litros' => 500, 'branch_id' => 'branch-B'],
                    ['id' => '4', 'serial' => 'D-004', 'producto' => 'Gas Natural', 'capacidad_litros' => 8000, 'nivel_actual_litros' => 600, 'nivel_alerta_litros' => 800, 'branch_id' => 'branch-B'],
                ];
                $pedidos = [['id' => 'p1', 'estado' => 'En proceso'], ['id' => 'p2', 'estado' => 'Pendiente']];
                $solicitudes = [['id' => 's1', 'estado' => 'Pendiente'], ['id' => 's2', 'estado' => 'Aprobada']];
                $notificaciones = [['id' => 'n1', 'leido' => false], ['id' => 'n2', 'leido' => true]];
                //$sucursales = [['id' => 'branch-A', 'nombre' => 'Sucursal Principal'], ['id' => 'branch-B', 'nombre' => 'Sucursal Sur']];

                $filteredDeposits = collect($depositos)->filter(function ($deposito) use ($currentUserRole, $currentUserBranchId) {
                    return $currentUserRole === 'principal' || $deposito['branch_id'] === $currentUserBranchId;
                });

                $totalCapacity = $filteredDeposits->sum('capacidad_litros');
                $totalCurrent = $filteredDeposits->sum('nivel_actual_litros');
                $percentage = $totalCapacity > 0 ? ($totalCurrent / $totalCapacity) * 100 : 0;
                $isAlert = $totalCurrent <= $filteredDeposits->sum('nivel_alerta_litros');
            @endphp
            <div class="d-flex align-items-center">
                <i class="fas fa-warehouse text-info me-3" style="font-size: 3rem;"></i>
                <div>
                    <h2 class="h4 fw-bold text-black mb-0" id="main-title">
                        @if ($currentUserRole == 'principal')
                            Estado de Cupo General
                        @else
                            Estado de tu Sucursal
                        @endif
                    </h2>
                    <p class="text-sm text-muted mb-0" id="main-subtitle">
                        @if ($currentUserRole == 'principal')
                            Resumen de todas las sucursales
                        @else
                            {{ collect($sucursales)->firstWhere('id', $currentUserBranchId)['nombre'] }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <p class="fw-bold mb-2">Total Disponible / Capacidad</p>
                <div class="d-flex align-items-center mb-2">
                    <h3 class="fw-bold mb-0 me-2">{{ number_format($totalCurrent, 2) }} L</h3>
                    <p class="text-muted mb-0">/ {{ $totalCapacity }} L</p>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar {{ $isAlert ? 'progress-bar-danger' : 'progress-bar-custom' }}"
                         role="progressbar"
                         style="width: {{ $percentage }}%;"
                         aria-valuenow="{{ $percentage }}"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        <!-- Secciones de Acciones y Vistas -->
        <div class="row g-4 mb-5">
            <!-- Tarjeta de Pedidos -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                    <i class="fas fa-truck-ramp-box stat-card-icon mb-2 text-warning"></i>
                    <h5 class="fw-bold mb-1">Pedidos</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($pedidos, fn($p) => $p['estado'] == 'En proceso' || $p['estado'] == 'Pendiente')) }} en proceso</p>
                </div>
            </div>
            <!-- Tarjeta de Solicitudes -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                    <i class="fas fa-clipboard-list stat-card-icon mb-2 text-primary"></i>
                    <h5 class="fw-bold mb-1">Solicitudes</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($solicitudes, fn($s) => $s['estado'] == 'Pendiente')) }} pendientes</p>
                </div>
            </div>
            <!-- Tarjeta de Notificaciones -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                    <i class="fas fa-bell stat-card-icon mb-2 text-danger"></i>
                    <h5 class="fw-bold mb-1">Notificaciones</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($notificaciones, fn($n) => !$n['leido'])) }} nuevas</p>
                </div>
            </div>
            <!-- Tarjeta de Sucursales -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center" id="sucursales-card">
                    <i class="fas fa-sitemap stat-card-icon mb-2 text-success"></i>
                    <h5 class="fw-bold mb-1">Sucursales</h5>
                    <p class="text-muted mb-0">{{ count($sucursales) }} activas</p>
                </div>
            </div>
        </div>
        
        <!-- Botón para Hacer Pedido -->
        <div class="text-center my-5">
            <button class="btn btn-primary-custom btn-lg rounded-pill px-5 py-3 shadow-lg fs-5">
                <i class="fas fa-plus-circle me-2"></i> Hacer Pedido
            </button>
        </div>

        <!-- Secciones de Contenido Dinámico (Listados) -->
        <div id="content-sections">
            @if ($currentUserRole == 'principal')
                <!-- Vista para cliente principal: resumen por sucursal -->
                <div class="card p-4 mb-4">
                    <h4 class="fw-bold mb-3">Resumen de Sucursales</h4>
                    <div class="row g-4" id="branches-list">
                        @foreach ($sucursales as $sucursal)
                            @php
                                $branchDeposits = collect($depositos)->filter(fn($d) => $d['branch_id'] === $sucursal['id']);
                                $branchTotalCapacity = $branchDeposits->sum('capacidad_litros');
                                $branchTotalCurrent = $branchDeposits->sum('nivel_actual_litros');
                                $fillPercentage = $branchTotalCapacity > 0 ? ($branchTotalCurrent / $branchTotalCapacity) * 100 : 0;
                                $isAlert = $branchTotalCurrent <= $branchDeposits->sum('nivel_alerta_litros');
                            @endphp
                            <div class="col-12 col-md-6 col-lg-4 card-hover">
                                <div class="card p-4 rounded-3 shadow-lg h-100">
                                    <div class="card-body p-0">
                                        <h5 class="fw-bold text-white mb-2">{{ $sucursal['nombre'] }}</h5>
                                        <div class="tank-container rounded-lg">
                                            <div class="tank-fill {{ $isAlert ? 'alert' : 'normal' }}" style="height: {{ $fillPercentage }}%;"></div>
                                            <div class="tank-level-text">{{ $branchTotalCurrent }} L</div>
                                            <div class="tank-capacity-label">Capacidad: {{ $branchTotalCapacity }} L</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <!-- Vista para usuario de sucursal: listado de depósitos -->
                <div class="card p-4 mb-4">
                    <h4 class="fw-bold mb-3">Inventario por Depósito</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-dark mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Serial</th>
                                    <th scope="col">Producto</th>
                                    <th scope="col">Nivel Actual (L)</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="depositos-listado">
                                @php
                                    $branchDeposits = collect($depositos)->filter(fn($d) => $d['branch_id'] === $currentUserBranchId);
                                @endphp
                                @foreach ($branchDeposits as $deposito)
                                    @php
                                        $isAlert = $deposito['nivel_actual_litros'] <= $deposito['nivel_alerta_litros'];
                                    @endphp
                                    <tr>
                                        <td>{{ $deposito['serial'] }}</td>
                                        <td>{{ $deposito['producto'] }}</td>
                                        <td>{{ $deposito['nivel_actual_litros'] }} L</td>
                                        <td><span class="badge {{ $isAlert ? 'bg-danger' : 'bg-success' }}">{{ $isAlert ? 'Alerta' : 'Normal' }}</span></td>
                                        <td><button type="button" class="btn btn-sm btn-info text-white ajustar-btn" data-id="{{ $deposito['id'] }}">Ajustar</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>

    <!-- Modales de Bootstrap (la lógica del JS se mantiene) -->
    <div class="modal fade" id="ajustarNivelModal" tabindex="-1" aria-labelledby="ajustarNivelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-card text-light rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="ajustarNivelModalLabel">Ajustar Nivel del Depósito</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="ajustarNivelForm">
                        <input type="hidden" id="deposito-id">
                        <p class="text-sm"><strong>Nivel Actual:</strong> <span id="modal-nivel-actual"></span> L</p>
                        <div class="mb-3">
                            <label for="nuevo_nivel" class="form-label">Nuevo Nivel (Litros)</label>
                            <input type="number" step="0.01" class="form-control bg-secondary text-light border-0" id="nuevo_nivel" name="nuevo_nivel" required>
                        </div>
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación</label>
                            <textarea class="form-control bg-secondary text-light border-0" id="observacion" name="observacion" rows="3" required placeholder="Describe el motivo del ajuste."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-primary-custom" id="btn-submit-ajuste">Guardar Ajuste</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-card text-light rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmación de Ajuste</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p id="confirm-message"></p>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                    <button type="button" class="btn btn-danger" id="btn-confirm-ok">Sí</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Este JavaScript ahora se enfoca únicamente en la interacción con el usuario y las llamadas a la API
        // asumiendo que el backend de Laravel proveerá la URL correcta para la API.
        
        let ajustarNivelModal;
        let confirmModal;
        let currentDeposito = null;

        // Se usa la URL de la página para obtener el rol, o se define por defecto.
        const urlParams = new URLSearchParams(window.location.search);
        const currentUserRole = urlParams.get('role') || 'principal';
        const currentUserBranchId = urlParams.get('branch') || 'branch-A';
        const depositosData = {!! json_encode($depositos) !!};
        
        function openAjusteModal(id) {
            currentDeposito = depositosData.find(d => d.id === id);
            if (currentDeposito) {
                document.getElementById('deposito-id').value = currentDeposito.id;
                document.getElementById('modal-nivel-actual').textContent = currentDeposito.nivel_actual_litros;
                document.getElementById('nuevo_nivel').value = currentDeposito.nivel_actual_litros;
                document.getElementById('observacion').value = '';
                ajustarNivelModal.show();
            }
        }
        
        async function guardarAjuste(nuevoNivel, observacion) {
            const variacion = Math.abs(nuevoNivel - currentDeposito.nivel_actual_litros);

            try {
                // Esta URL sería un endpoint real en Laravel, como '/api/depositos/update'
                const response = await fetch('/api/depositos/update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: currentDeposito.id,
                        nivel_actual_litros: nuevoNivel,
                        observacion: observacion,
                        variacion: variacion
                    })
                });
                const result = await response.json();
                
                if (response.ok && result.success) {
                    // En una aplicación real, se recargaría la página o se actualizaría la vista dinámicamente
                    // para reflejar los cambios.
                    window.location.reload(); 
                } else {
                    console.error("Error al guardar el ajuste: ", result.message);
                }
            } catch (error) {
                console.error("Error en la llamada a la API: ", error);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Instancias de los modales
            const btnSubmitAjuste = document.getElementById('btn-submit-ajuste');
            const btnConfirmOk = document.getElementById('btn-confirm-ok');
            ajustarNivelModal = new bootstrap.Modal(document.getElementById('ajustarNivelModal'));
            confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            const inputNuevoNivel = document.getElementById('nuevo_nivel');
            const inputObservacion = document.getElementById('observacion');
            const confirmMessage = document.getElementById('confirm-message');

            btnSubmitAjuste.addEventListener('click', () => {
                const nuevoNivel = parseFloat(inputNuevoNivel.value);
                const observacion = inputObservacion.value;

                if (isNaN(nuevoNivel) || nuevoNivel < 0 || nuevoNivel > currentDeposito.capacidad_litros) {
                    ajustarNivelModal.hide();
                    confirmMessage.textContent = 'El nuevo nivel debe ser un valor entre 0 y la capacidad del tanque.';
                    btnConfirmOk.style.display = 'none';
                    confirmModal.show();
                    return;
                }

                const diferencia = Math.abs(nuevoNivel - currentDeposito.nivel_actual_litros);
                const esAjusteGrande = diferencia > (currentDeposito.nivel_actual_litros * 0.1);

                if (esAjusteGrande) {
                    ajustarNivelModal.hide();
                    confirmMessage.textContent = 'El ajuste de nivel es superior al 10% del nivel actual. ¿Estás seguro de que quieres continuar?';
                    btnConfirmOk.style.display = 'inline-block';
                    confirmModal.show();
                } else {
                    guardarAjuste(nuevoNivel, observacion);
                }
            });

            btnConfirmOk.addEventListener('click', () => {
                const nuevoNivel = parseFloat(inputNuevoNivel.value);
                const observacion = inputObservacion.value;
                guardarAjuste(nuevoNivel, observacion);
                confirmModal.hide();
            });

            // Asigna los eventos a los botones de ajuste de forma dinámica
            document.querySelectorAll('.ajustar-btn').forEach(button => {
                button.addEventListener('click', (e) => openAjusteModal(e.target.dataset.id));
            });
        });
    </script>
@endpush
