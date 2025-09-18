@extends('layouts.app')

@section('title', 'Dashboard de Combustible')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Dashboard de Combustible</h1>
        <p class="text-muted">Información clave y monitoreo en tiempo real para la gestión de combustible.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3 text-primary">Clientes con Sucursales (con Desglose)</h2>
            <canvas id="chartConSucursales"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h5 mb-3 text-primary">Clientes sin Sucursales</h2>
            <canvas id="chartSinSucursales"></canvas>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card p-3 h-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle-icon bg-warning me-3 p-3">
                     <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-clock-fill text-white" viewBox="0 0 16 16">
                         <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                     </svg>
                </div>
                <div class="text-center">
                    <h5 class="mb-1 text-secondary-emphasis">Próximas Vencimientos</h5>
                    <p class="text-muted mb-0">9 Clientes</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-3 h-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle-icon bg-success me-3 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-droplet-half text-white" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M7.646 4.97A.5.5 0 0 1 8 4.5v1.5H7.5a.5.5 0 0 1 0-1zM9 10.74V13.5a1.5 1.5 0 0 1-3 0v-2.768a1.5 1.5 0 0 1 1.5-1.5c.29 0 .546.166.75.385l.75.76V10.74z"/>
                        <path fill-rule="evenodd" d="M4.5 9a.5.5 0 0 1-.5-.5V3a.5.5 0 0 1 1 0v5.5a.5.5 0 0 1-.5.5zm1.5 0a.5.5 0 0 1-.5-.5V3a.5.5 0 0 1 1 0v5.5a.5.5 0 0 1-.5.5zm1.5 0a.5.5 0 0 1-.5-.5V3a.5.5 0 0 1 1 0v5.5a.5.5 0 0 1-.5.5z"/>
                        <path d="M8 1.5a1.5 1.5 0 0 0-1.5 1.5v2.5a.5.5 0 0 0 1 0v-2.5A.5.5 0 0 1 8 2.5a.5.5 0 0 0 .5-.5v-2.5a.5.5 0 0 0-1 0V.5a.5.5 0 0 0 1 0v1z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h5 class="mb-1 text-secondary-emphasis">Depósitos Activos</h5>
                    <p class="text-muted mb-0">3 Depósitos</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-3 h-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle-icon bg-info me-3 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-box-seam-fill text-white" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15.22.25l-2.75 1.75c-.88-.56-1.92-.81-3-.71-.85-.08-1.7-.01-2.5.21-.83-.22-1.68-.29-2.53-.21-1.08-.1-2.07.16-2.92.71L.78.25A.5.5 0 0 0 0 .61v14.78a.5.5 0 0 0 .78.36l2.9-1.93c.8-.53 1.7-.76 2.6-.78.93-.03 1.8.14 2.6.46.8-.32 1.67-.49 2.6-.46.9.02 1.8.25 2.6.78l2.9 1.93a.5.5 0 0 0 .78-.36V.61a.5.5 0 0 0-.78-.36z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h5 class="mb-1 text-secondary-emphasis">Stock Disponible</h5>
                    <p class="text-muted mb-0">12,500 Lts.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-3 h-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle-icon bg-danger me-3 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-clipboard-data-fill text-white" viewBox="0 0 16 16">
                        <path d="M6.5 0h3a.5.5 0 0 1 .5.5v2.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V.5a.5.5 0 0 1 .5-.5zM8 1.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 1 0v-1a.5.5 0 0 0-.5-.5z"/>
                        <path fill-rule="evenodd" d="M0 4.5A1.5 1.5 0 0 1 1.5 3h13A1.5 1.5 0 0 1 16 4.5v10.5h-16V4.5zM3 8a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h.5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H3zm2.5 0a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h.5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H5.5zm2.5 0a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h.5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H8zm2.5 0a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h.5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H10.5zm2.5 0a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h.5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H13z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h5 class="mb-1 text-secondary-emphasis">Reportes en Espera</h5>
                    <p class="text-muted mb-0">5 Reportes</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ejemplo de datos, deberías obtenerlos desde tu backend
            // El campo `sucursales` se utiliza para la separación
            const data = [
                { id: 1, nombre: 'Cliente A', disponible: 85, cupo: 100, sucursales: ['Sucursal A1', 'Sucursal A2'] },
                { id: 2, nombre: 'Cliente B', disponible: 95, cupo: 100, sucursales: [] },
                { id: 3, nombre: 'Cliente C', disponible: 70, cupo: 100, sucursales: ['Sucursal C1'] },
                { id: 4, nombre: 'Cliente D', disponible: 90, cupo: 100, sucursales: [] },
                { id: 5, nombre: 'Cliente E', disponible: 60, cupo: 100, sucursales: ['Sucursal E1', 'Sucursal E2', 'Sucursal E3'] },
            ];

            // 1. Separar los datos en dos grupos
            const dataConSucursales = data.filter(d => d.sucursales && d.sucursales.length > 0);
            const dataSinSucursales = data.filter(d => !d.sucursales || d.sucursales.length === 0);

            // 2. Gráfico para clientes con sucursales (con drilldown)
            const ctxConSucursales = document.getElementById('chartConSucursales').getContext('2d');
            const labelsConSucursales = dataConSucursales.map(d => d.nombre);
            const disponiblesConSucursales = dataConSucursales.map(d => d.disponible);

            new Chart(ctxConSucursales, {
                type: 'bar',
                data: {
                    labels: labelsConSucursales,
                    datasets: [{
                        label: '% Disponibles',
                        data: disponiblesConSucursales,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Disponibilidad de Clientes con Sucursales'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '%'
                            }
                        }
                    },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const firstElement = elements[0];
                            const index = firstElement.index;
                            const clienteSeleccionado = dataConSucursales[index];
                            
                            // Aquí iría la lógica del "drilldown".
                            // Por ejemplo, podrías mostrar un modal con las sucursales del cliente,
                            // o redirigir a una nueva página con información detallada.
                            // Puedes usar el ID del cliente para buscar más datos en tu backend.
                            console.log('Cliente seleccionado para drilldown:', clienteSeleccionado);
                            
                            // Ejemplo: Alerta con las sucursales
                            const sucursales = clienteSeleccionado.sucursales.join(', ');
                            const message = `El cliente ${clienteSeleccionado.nombre} tiene las siguientes sucursales: ${sucursales}.`;
                            showCustomModal('Detalle de Sucursales', message);
                        }
                    }
                }
            });

            // 3. Gráfico para clientes sin sucursales
            const ctxSinSucursales = document.getElementById('chartSinSucursales').getContext('2d');
            const labelsSinSucursales = dataSinSucursales.map(d => d.nombre);
            const disponiblesSinSucursales = dataSinSucursales.map(d => d.disponible);

            new Chart(ctxSinSucursales, {
                type: 'bar',
                data: {
                    labels: labelsSinSucursales,
                    datasets: [{
                        label: '% Disponibles',
                        data: disponiblesSinSucursales,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Disponibilidad de Clientes sin Sucursales'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '%'
                            }
                        }
                    }
                }
            });

            // Función para un modal personalizado, ya que alert() no se recomienda.
            function showCustomModal(title, message) {
                const modalHtml = `
                    <div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="customAlertModalLabel">${title}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    ${message}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('customAlertModal'));
                modal.show();
                document.getElementById('customAlertModal').addEventListener('hidden.bs.modal', function () {
                    this.remove();
                });
            }
        });
    </script>
@endpush
