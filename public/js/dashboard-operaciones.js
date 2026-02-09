console.log('Dashboard Operaciones cargado');
        console.log('Pedidos:', @json($pedidos));
        console.log('Solicitudes:', @json($solicitudes));    
        console.log('Notificaciones:', @json($notificaciones));
const pedidos = @json($pedidos);
// const solicitudes = @json($solicitudes);
// const notificaciones = @json($notificaciones);

//    const pedidos = [
//             { id: 1, cliente: 'Empresa Alfa', cantidad: 5000, estado: 'Pendiente', fecha: '2024-10-26' },
//             { id: 2, cliente: 'Transportes Delta', cantidad: 800, estado: 'Pendiente', fecha: '2024-10-25' },
//             { id: 3, cliente: 'Distribuidora Beta', cantidad: 1000, estado: 'En Ruta', fecha: '2024-10-25' },
//             { id: 4, cliente: 'Empresa Alfa', cantidad: 2000, estado: 'Pendiente', fecha: '2024-10-24' },
//         ];

        const solicitudes = [
            { id: 1, cliente: 'Empresa Alfa', tipo: 'Mantenimiento', descripcion: 'Revisión de bomba de sucursal centro.', estado: 'En Proceso' },
            { id: 2, cliente: 'Transportes Delta', tipo: 'Consulta', descripcion: 'Duda sobre la facturación de octubre.', estado: 'Pendiente' },
            { id: 3, cliente: 'Distribuidora Beta', tipo: 'Actualización', descripcion: 'Actualizar contacto de gerencia.', estado: 'Completado' },
        ];
        
        const notificaciones = [
            { id: 1, cliente: 'Transportes Delta', mensaje: 'El depósito de la sucursal 1 está por debajo del 10% de su capacidad.', fecha: '2024-10-26 09:30' },
            { id: 2, cliente: 'Empresa Alfa', mensaje: 'Cambio de horario en el envío programado de hoy.', fecha: '2024-10-26 08:15' },
        ];
 
        let detailSections = [
                
           document.getElementById('pedidos-details'),
                document.getElementById('solicitudes-details'),
                document.getElementById('notificaciones-details')

                 ];
               // Funciones para manejar la vista del dashboard y los detalles
        function hideAllDetails() {
            detailSections.forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById('clientes-list-container').classList.add('hidden');
            document.getElementById('sucursal-details-container').classList.add('hidden');
        }

        function showDetails(sectionId,event) {
            event.preventDefault(); 

            document.getElementById('dashboard-main-view').style.display = 'none';
            hideAllDetails();
            document.getElementById(sectionId).style.display = 'block';

            // Lógica para renderizar los datos de cada sección
            if (sectionId === 'pedidos-details') {
                renderizarPedidos();
            } else if (sectionId === 'solicitudes-details') {
                renderizarSolicitudes();
            } else if (sectionId === 'notificaciones-details') {
                renderizarNotificaciones();
            }
        }

        function showDashboard() {
            hideAllDetails();
           document.getElementById('dashboard-main-view').style.display = 'block';
        }



// Agrega los listeners a los botones y selectores
function agregarListeners() {
    // Escucha los clics en los botones "Aprobar"
    document.querySelectorAll('.aprobar-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const pedidoId = parseInt(e.target.dataset.id);
            aprobarPedido(pedidoId);
        });
    });

    // Escucha los cambios en los selectores de vehículos
    document.querySelectorAll('.vehiculo-select').forEach(select => {
        select.addEventListener('change', (e) => {
            const pedidoId = parseInt(e.target.dataset.id);
            const vehiculoAsignado = e.target.value;
            asignarVehiculo(pedidoId, vehiculoAsignado);
        });
    });

    // Puedes agregar un listener para el botón "Ver" aquí
    document.querySelectorAll('.ver-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const pedidoId = parseInt(e.target.dataset.id);
            mostrarDetallesPedido(pedidoId);
        });
    });
}

function aprobarPedido(id) {
    const pedido = pedidos.find(p => p.id === id);
    if (pedido) {
        // Lógica para cambiar el estado
        pedido.estado = 'Aprobado';
        
        // Re-renderizar la tabla para reflejar el cambio
        renderizarPedidos();
        
        // Mostrar una alerta de éxito con SweetAlert
        Swal.fire('Aprobado', `El pedido #${id} ha sido aprobado.`, 'success');
    }
}

function asignarVehiculo(id, vehiculo) {
    const pedido = pedidos.find(p => p.id === id);
    if (pedido) {
        // Lógica para asignar el vehículo
        pedido.vehiculo = vehiculo;
        
        // Opcional: Re-renderizar para mostrar el vehículo asignado si es necesario
        // renderizarPedidos(); 
        
        // Mostrar una alerta de confirmación
        Swal.fire('Vehículo Asignado', `El vehículo ${vehiculo} ha sido asignado al pedido #${id}.`, 'success');
    }
}

function mostrarDetallesPedido(id) {
    // Lógica para mostrar los detalles del pedido
    const pedido = pedidos.find(p => p.id === id);
    if (pedido) {
        // Aquí puedes abrir un modal, por ejemplo, para mostrar más información
        console.log('Detalles del pedido:', pedido);
        Swal.fire({
            title: `Detalles del Pedido #${pedido.id}`,
            html: `
                <p><strong>Cliente:</strong> ${pedido.cliente}</p>
                <p><strong>Cantidad:</strong> ${pedido.cantidad} L</p>
                <p><strong>Estado:</strong> <span class="badge ${pedido.estado === 'Pendiente' ? 'bg-danger' : 'bg-success'}">${pedido.estado}</span></p>
                <p><strong>Fecha:</strong> ${pedido.fecha}</p>
                <p><strong>Vehículo:</strong> ${pedido.vehiculo || 'No asignado'}</p>
            `
        });
    }
}


        function renderizarPedidos() {
            const tbody = document.getElementById('pedidos-table-body');
            tbody.innerHTML = '';
            pedidos.forEach(pedido => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${pedido.id}</td>
                    <td>${pedido.cliente}</td>
                    <td>${pedido.cantidad} L</td>
                    <td><span class="badge ${pedido.estado === 'Pendiente' ? 'bg-danger' : 'bg-success'}">${pedido.estado}</span></td>
                    <td>${pedido.fecha}</td>
                    <td>
                          <button class="btn btn-sm btn-info ver-btn" data-id="${pedido.id}">Ver</button>
                
                        ${pedido.estado === 'Pendiente' ? `
                            <button class="btn btn-sm btn-success aprobar-btn" data-id="${pedido.id}">Aprobar</button>
                            <select class="form-select form-select-sm vehiculo-select mt-1" data-id="${pedido.id}">
                                <option value="">Asignar Vehículo</option>
                                ${vehiculosDisponibles.map(vehiculo => `<option value="${vehiculo}">${vehiculo}</option>`).join('')}
                            </select>
                        ` : ''}
                        </td>
                `;
                tbody.appendChild(row);
            });
            agregarListeners()
             document.getElementById('pedidos-details').focus();
            
        }

        function renderizarSolicitudes() {
            const tbody = document.getElementById('solicitudes-table-body');
            tbody.innerHTML = '';
            solicitudes.forEach(solicitud => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${solicitud.id}</td>
                    <td>${solicitud.cliente}</td>
                    <td>${solicitud.tipo}</td>
                    <td>${solicitud.descripcion}</td>
                    <td><span class="badge ${solicitud.estado === 'Pendiente' ? 'bg-warning text-dark' : (solicitud.estado === 'Completado' ? 'bg-success' : 'bg-primary')}">${solicitud.estado}</span></td>
                `;
                tbody.appendChild(row);
            });
                document.getElementById('solicitudes-details').focus();
            }
        
        function renderizarNotificaciones() {
            const listGroup = document.querySelector('#notificaciones-details .list-group');
            listGroup.innerHTML = '';
            notificaciones.forEach(notificacion => {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
                item.innerHTML = `
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">${notificacion.cliente}</div>
                        ${notificacion.mensaje}
                    </div>
                    <span class="badge bg-secondary rounded-pill">${notificacion.fecha}</span>
                `;
                listGroup.appendChild(item);
            });
            document.getElementById('notificaciones-details').focus();
        }



        document.addEventListener('DOMContentLoaded', function () {
            // Datos simulados pasados desde PHP
            const clientes = {!! json_encode($clientes) !!};
            const chartData = {!! json_encode($chartData) !!};
            const drilldownSeries = {!! json_encode($drilldownSeries) !!};
            
            // Referencias a los contenedores
            const clientesListContainer = document.getElementById('clientes-list-container');
            const sucursalDetailsContainer = document.getElementById('sucursal-details-container');
            const dashboardMainView = document.getElementById('dashboard-main-view');

            detailSections = [
                document.getElementById('pedidos-details'),
                document.getElementById('solicitudes-details'),
                document.getElementById('notificaciones-details')
            ];


           

            // Botones de navegación
            const verClientesBtn = document.getElementById('sucursales-card');
            const backToDashboardBtn = document.getElementById('back-to-dashboard-btn');
            const backToListBtn = document.getElementById('back-to-list-btn');

            // Ocultar vistas por defecto
            clientesListContainer.classList.add('hidden');
            sucursalDetailsContainer.classList.add('hidden');

            // Lógica para el gráfico de Highcharts con drilldown
            Highcharts.chart('chart-container', {
                chart: {
                    type: 'bar'
                },
                title: {
                    text: 'Consumo y Disponibilidad por Clientes'
                },
                xAxis: {
                    categories: chartData.map(d => d.name)
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Porcentaje de Cupo'
                    },
                    labels: {
                        formatter: function () {
                            return this.value + '%';
                        }
                    }
                },
                tooltip: {
                    formatter: function () {
                        // Encuentra el dato original para obtener los valores en litros
                        const pointData = chartData.find(d => d.name === this.x);
                        const cupo = pointData ? pointData.cupo : null;
                        const disponible = pointData ? pointData.disponible : null;
                        const consumido = cupo - disponible;
                        
                        return `<b>${this.x}</b><br/>
                                Consumido: <b>${(consumido / cupo * 100).toFixed(2)}%</b> (${consumido.toFixed(2)} L)<br/>
                                Disponible: <b>${(disponible / cupo * 100).toFixed(2)}%</b> (${disponible.toFixed(2)} L)<br/>
                                Cupo Total: ${cupo.toFixed(2)} L`;
                    }
                },
                plotOptions: {
                    series: {
                        stacking: 'percent' // Apilamiento en porcentaje
                    }
                },
                series: [{
                    name: 'Consumido',
                    color: 'rgb(204, 74, 58)', // N
                    data: chartData.map(d => ({
                        y: d.cupo - d.disponible,
                        drilldown: d.drilldown
                    }))
                }, {
                    name: 'Disponible',
                    color: 'rgb(69, 155, 100)', // Nuevo color para el disponible
                    data: chartData.map(d => ({
                        y: d.disponible,
                        drilldown: d.drilldown
                    }))
                }],
                drilldown: {
                    series: drilldownSeries.map(s => ({
                        id: s.id,
                        name: s.name,
                        data: s.data.map(d => {
                            const consumido = d.cupo - d.disponible;
                            return [d.name, consumido];
                        })
                    }))
                }
            });

            // Manejadores de eventos de navegación
            if (verClientesBtn) {
                verClientesBtn.addEventListener('click', () => {
                    document.getElementById('dashboard-main-view').classList.add('hidden');
                    clientesListContainer.classList.remove('hidden');
                });
            }

            if (backToDashboardBtn) {
                backToDashboardBtn.addEventListener('click', () => {
                    clientesListContainer.classList.add('hidden');
                    document.getElementById('dashboard-main-view').classList.remove('hidden');
                });
            }

            if (backToListBtn) {
                backToListBtn.addEventListener('click', () => {
                    sucursalDetailsContainer.classList.add('hidden');
                    clientesListContainer.classList.remove('hidden');
                });
            }

            // Lógica para mostrar los detalles de la sucursal al hacer clic en la tarjeta
            document.querySelectorAll('.sucursal-card-container').forEach(card => {
                card.addEventListener('click', (e) => {
                    const sucursalId = parseInt(e.currentTarget.dataset.sucursalId, 10); 
                    const sucursal = clientes.find(s => parseInt(s.id, 10) === sucursalId);

                    if (sucursal) {
                        clientesListContainer.classList.add('hidden');
                        sucursalDetailsContainer.classList.remove('hidden');

                        document.getElementById('sucursal-details-title').textContent = sucursal.nombre;
                        document.getElementById('details-direccion').textContent = sucursal.direccion;
                        document.getElementById('details-contacto').textContent = sucursal.contacto;
                        document.getElementById('details-telefono').textContent = sucursal.telefono || 'No especificado';
                        document.getElementById('details-disponible').textContent = `${sucursal.disponible} L`;
                        document.getElementById('details-cupo').textContent = `/ ${sucursal.cupo} L`;

                        const percentage = (sucursal.disponible / sucursal.cupo) * 100;
                        const progressBar = document.getElementById('details-progress-bar');
                        progressBar.style.width = `${percentage}%`;
                        progressBar.classList.remove('progress-bar-custom', 'progress-bar-danger');
                        progressBar.classList.add(percentage < 10 ? 'progress-bar-danger' : 'progress-bar-custom');

                        const consumoHistorico = {
                            categorias: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                            data: [1000, 800, 1200, 1500, 900, 1100, 1300]
                        };

                        Highcharts.chart('consumo-chart-container', {
                            chart: {
                                type: 'line'
                            },
                            title: {
                                text: 'Histórico de Consumo Semanal'
                            },
                            xAxis: {
                                categories: consumoHistorico.categorias
                            },
                            yAxis: {
                                title: {
                                    text: 'Litros Consumidos'
                                }
                            },
                            series: [{
                                name: 'Consumo',
                                data: consumoHistorico.data,
                                color: '#3b82f6'
                            }],
                            credits: {
                                enabled: false
                            }
                        });
                        
                        const btnDetailsPedido = document.getElementById('btn-details-pedido');
                        const btnDetailsEdicion = document.getElementById('btn-details-edicion');
                        
                        btnDetailsPedido.setAttribute('data-sucursal-id', sucursalId);
                        
                        btnDetailsEdicion.setAttribute('data-id', sucursalId);
                        btnDetailsEdicion.setAttribute('data-nombre', sucursal.nombre);
                        btnDetailsEdicion.setAttribute('data-direccion', sucursal.direccion);
                        btnDetailsEdicion.setAttribute('data-contacto', sucursal.contacto);
                        btnDetailsEdicion.setAttribute('data-telefono', sucursal.telefono);
                    }
                });
            });

            // Lógica para el modal de pedidos
            const pedidoModal = document.getElementById('hacerPedidoModal');
            const btnSubmitPedido = document.getElementById('btn-submit-pedido');
            const hacerPedidoForm = document.getElementById('hacerPedidoForm');
            const sucursalSelect = document.getElementById('sucursalSelect');
            
            // Lógica para preseleccionar la sucursal en el modal de pedidos
            document.querySelectorAll('.make-order-btn, #btn-details-pedido').forEach(button => {
                button.addEventListener('click', (e) => {
                    const sucursalId = e.currentTarget.dataset.sucursalId;
                    if (sucursalSelect) {
                        sucursalSelect.value = sucursalId;
                    }
                });
            });

            // Lógica para el modal de edición de sucursal
            const editarSucursalModal = document.getElementById('editarSucursalModal');
            const btnSubmitEdicion = document.getElementById('btn-submit-edicion');
            const editarSucursalForm = document.getElementById('editarSucursalForm');

            editarSucursalModal.addEventListener('show.bs.modal', (e) => {
                const button = e.relatedTarget;
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                const direccion = button.getAttribute('data-direccion');
                const contacto = button.getAttribute('data-contacto');
                const telefono = button.getAttribute('data-telefono');
                
                document.getElementById('editSucursalId').value = id;
                document.getElementById('editNombreSucursal').value = nombre;
                document.getElementById('editDireccionSucursal').value = direccion;
                document.getElementById('editContactoSucursal').value = contacto;
                document.getElementById('editTelefonoSucursal').value = telefono;
            });

            // Lógica para manejar los backdrops de los modales (si se quedan)
            const allModals = document.querySelectorAll('.modal');
            allModals.forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });
                });
            });
        }); 