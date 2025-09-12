@extends('layouts.app')

@push('styles')
{{-- Incluye Tailwind CSS desde CDN para este ejemplo. En un proyecto real, se usaría npm. --}}
<script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnhtA4E5D4j3iX30T7b2s4B03aFz4g1ZzR2b4zN7c5" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9f6ZgR23E3E5gR3y3nK3c0vJzR24F7r5E1N3N7f6zO8C9yC1z6" crossorigin="anonymous"></script>

<style>
    body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: #1a202c; /* Color de fondo similar a slate-900 */
            color: #e2e8f0; /* Color de texto similar a slate-100 */
        }
        .bg-custom-dark {
            background-color: #2d3748; /* Color similar a slate-800 */
        }
        .bg-custom-lighter-dark {
            background-color: #4a5568; /* Color similar a slate-700 */
        }
        .text-custom-light {
            color: #cbd5e0; /* Color similar a slate-400 */
        }
        .rounded-4 { border-radius: 1.5rem !important; }

        /* Estilos personalizados para la visualización de los depósitos */
        .tank-container {
            position: relative;
            width: 100%;
            height: 150px;
            background-color: #334155;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.6);
        }

        .tank-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            transition: height 0.5s ease-in-out, background-color 0.3s;
        }

        .tank-fill.normal {
            background: linear-gradient(to top, #38bdf8, #0ea5e9);
        }

        .tank-fill.alert {
            background: linear-gradient(to top, #f87171, #ef4444);
        }

        .tank-level-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 10;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .tank-capacity-label {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }

        /* Hover effect for cards */
        .card-hover:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-5px);
            transition: all 0.3s ease-in-out;
        }
</style>
@endpush

@section('content')
<div class="bg-slate-900 text-slate-100 font-sans p-4 min-h-screen">
    <div class="container mx-auto max-w-7xl">
        <h1 class="text-3xl font-bold mb-6 text-center text-slate-200">Dashboard de Depósitos</h1>

        <!-- Botones de Recargas y Despachos -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6 mb-10">
            <!-- Botón para Recargas -->
            <a href="{{ route('combustible.recarga') }}" class="w-1/2 sm:w-1/2 p-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-md">
                <span class="block mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.485 0-4.5 2.015-4.5 4.5S9.515 17 12 17s4.5-2.015 4.5-4.5S14.485 8 12 8z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 1c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 18a9 9 0 100-18 9 9 0 000 18z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17a5 5 0 100-10 5 5 0 000 10z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13a1 1 0 100-2 1 1 0 000 2z" />
                    </svg>
                </span>
                Recargas
            </a>

            <!-- Botón para Despachos -->
            <a href="{{ route('combustible.despacho') }}" class="w-full sm:w-1/2 p-4 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-md">
                <span class="block mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-3 0a2 2 0 002 2h0a2 2 0 002-2V7m-3 0a2 2 0 002 2h0a2 2 0 002-2V7M9 5a2 2 0 012-2h0a2 2 0 012 2v0m-3 0V3m0 0v2m0-2v2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-4 4m0-4l4 4" />
                    </svg>
                </span>
                Despachos
            </a>

            <!-- Botón para Despachos -->
            <a href="{{ route('depositos.list') }}" class="w-full sm:w-1/2 p-4 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-md">
                <span class="block mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-3 0a2 2 0 002 2h0a2 2 0 002-2V7m-3 0a2 2 0 002 2h0a2 2 0 002-2V7M9 5a2 2 0 012-2h0a2 2 0 012 2v0m-3 0V3m0 0v2m0-2v2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-4 4m0-4l4 4" />
                    </svg>
                </span>
                Listado
            </a>
        </div>
        
        <!-- Sección de Visualización Gráfica -->
        <div id="depositos-grafica" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- Los depósitos se renderizarán aquí dinámicamente con JavaScript -->
        </div>

        <!-- Sección de Listado de Depósitos -->
        <div class="bg-slate-800 p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-4 text-slate-200">Listado de Depósitos</h2>
            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full divide-y divide-slate-700">
                    <thead class="bg-slate-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                Serial
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                Producto
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                Capacidad (L)
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                Nivel Actual (L)
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                Estado
                            </th>
                        </tr>
                    </thead>
                    <tbody id="depositos-listado" class="bg-slate-800 divide-y divide-slate-700">
                        <!-- Las filas de depósitos se renderizarán aquí dinámicamente con JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


    <!-- Modal para Ajuste de Nivel -->
    <div class="modal fade" id="ajustarNivelModal" tabindex="-1" aria-labelledby="ajustarNivelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-custom-dark text-white rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="ajustarNivelModalLabel">Ajustar Nivel del Depósito</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="ajustarNivelForm">
                        <input type="hidden" id="deposito-id">
                        <p class="text-sm"><strong>Nivel Actual:</strong> <span id="modal-nivel-actual"></span> L/ <span id="capacidad-litros"></span> L</p>
                        <div class="mb-3">
                            <label for="nuevo_nivel" class="form-label">Nuevo Nivel (Litros)</label>
                            <input type="number" step="0.01" class="form-control  border-0" id="nuevo_nivel" name="nuevo_nivel" required>
                        </div>
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación</label>
                            <textarea class="form-control  border-0" id="observacion" name="observacion" rows="3" required placeholder="Describe el motivo del ajuste."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-submit-ajuste">Guardar Ajuste</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-custom-lighter-dark text-white rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmación de Ajuste</h5>
                    <button type="button" class="btn-close btn-close-primary" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-custom-lighter-dark text-white text-center">
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
    const depositos = @json($data);

    // Función para renderizar el gráfico visual de los depósitos
    function renderizarGraficos(depositos) {
        const container = document.getElementById('depositos-grafica');
        container.innerHTML = ''; // Limpiar el contenedor

        depositos.forEach(deposito => {
            // Calcular el porcentaje de llenado
            const fillPercentage = (deposito.nivel_actual_litros / deposito.capacidad_litros) * 100;
            // Determinar si está en estado de alerta
            const isAlert = deposito.nivel_actual_litros <= deposito.nivel_alerta_litros;

            // Crear el elemento del depósito
            const depositoCard = `
                <div class="bg-slate-800 p-4 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300">
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <h3 class="text-xl font-medium">${deposito.serial}</h3>
                            <p class="text-sm text-slate-400">${deposito.producto}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${isAlert ? 'bg-red-500 text-white' : 'bg-green-500 text-white'}">
                            ${isAlert ? 'ALERTA' : 'OK'}
                        </span>
                    </div>
                    <div class="tank-container rounded-lg">
                        <div
                            class="tank-fill ${isAlert ? 'alert' : 'normal'}"
                            style="height: ${fillPercentage}%;">
                        </div>
                        <div class="tank-level-text">
                            ${deposito.nivel_actual_litros} L
                        </div>
                        <div class="tank-capacity-label" >
                            Capacidad: ${deposito.capacidad_litros} L
                        </div>
                    </div>
                    <div class="text-end mt-3">
                                <button type="button" class="btn btn-sm btn-info text-white ajustar-btn" data-id="${deposito.id}">
                                    Ajustar Nivel
                                </button>
                            </div>
                </div>
            `;
            container.innerHTML += depositoCard;
        });
                 // Agrega el evento de clic a los nuevos botones
            document.querySelectorAll('.ajustar-btn').forEach(button => {
                button.addEventListener('click', (e) => openAjusteModal(e.target.dataset.id));
            });

    }

    // Función para renderizar el listado de depósitos
    function renderizarListado(depositos) {
        const tableBody = document.getElementById('depositos-listado');
        tableBody.innerHTML = ''; // Limpiar el contenedor

        depositos.forEach(deposito => {
            const isAlert = deposito.nivel_actual_litros <= deposito.nivel_alerta_litros;

            const tableRow = `
                <tr class="hover:bg-slate-700 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-slate-200">${deposito.serial}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-slate-300">${deposito.producto}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-slate-300">${deposito.capacidad_litros} L</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-slate-300">${deposito.nivel_actual_litros} L</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${isAlert ? 'bg-red-500 text-white' : 'bg-green-500 text-white'}">
                            ${isAlert ? 'Alerta' : 'Normal'}
                        </span>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += tableRow;
        });

    }
// Función para abrir el modal de ajuste
        function openAjusteModal(id) {
            currentDeposito = depositos.find(d => d.id == id);
            if (currentDeposito) {
                document.getElementById('deposito-id').value = currentDeposito.id;
                document.getElementById('modal-nivel-actual').textContent = currentDeposito.nivel_actual_litros;
                document.getElementById('capacidad-litros').textContent = currentDeposito.capacidad_litros;
                document.getElementById('nuevo_nivel').value = currentDeposito.nivel_actual_litros;
                document.getElementById('observacion').value = '';
                ajustarNivelModal.show();
            }
        }
         const ajustarNivelModal = new bootstrap.Modal(document.getElementById('ajustarNivelModal'));
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        

    // Llamar a las funciones de renderizado al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        // Se asegura de que la variable `depositos` no esté vacía antes de renderizar
        if (depositos && depositos.length > 0) {
            renderizarGraficos(depositos);
            renderizarListado(depositos);
        
        } else {
            console.error('No se recibieron datos de depósitos.');
        }

         // Instancias de los modales
        
        // Elementos del DOM
        const depositosGrafica = document.getElementById('depositos-grafica');
        const depositosListado = document.getElementById('depositos-listado');
        const modalNivelActual = document.getElementById('modal-nivel-actual');
        const inputNuevoNivel = document.getElementById('nuevo_nivel');
        const inputObservacion = document.getElementById('observacion');
        const confirmMessage = document.getElementById('confirm-message');
        const btnSubmitAjuste = document.getElementById('btn-submit-ajuste');
        const btnConfirmOk = document.getElementById('btn-confirm-ok');

        let currentDeposito = null;

        
        // Evento para el botón de "Guardar Ajuste" en el modal de ajuste
        btnSubmitAjuste.addEventListener('click', () => {
            id=document.getElementById('deposito-id').value;
            currentDeposito = depositos.find(d => d.id == id);
            
            const nuevoNivel = parseFloat(inputNuevoNivel.value);
            const observacion = inputObservacion.value;
            console.log(currentDeposito);
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

        // Evento para el botón de confirmación "Sí"
        btnConfirmOk.addEventListener('click', () => {
            const nuevoNivel = parseFloat(inputNuevoNivel.value);
            const observacion = inputObservacion.value;
            guardarAjuste(nuevoNivel, observacion);
            confirmModal.hide();
        });

         // Función para guardar el ajuste en la base de datos (simulada)
            async function guardarAjuste(nuevoNivel, observacion) {
            try {
                const response = await fetch('{{route('deposito.ajuste')}}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: currentDeposito.id,
                        nivel_actual_litros: nuevoNivel,
                        observacion: observacion
                    })
                });

                const result = await response.json();
                
                if (response.ok && result.success) {
                    console.log("Ajuste guardado exitosamente.");
                    // Actualiza los datos en la memoria para que se reflejen en la interfaz
                    const depositoIndex = depositos.findIndex(d => d.id === currentDeposito.id);
                    if (depositoIndex !== -1) {
                        depositos[depositoIndex].nivel_actual_litros = nuevoNivel;
                        renderizarGraficos();
                        renderizarListado();
                    }
                    ajustarNivelModal.hide();
                } else {
                    console.error("Error al guardar el ajuste: ", result.message);
                }
            } catch (error) {
                console.error("Error en la llamada a la API: ", error);
            }
        }


        // Función para guardar el ajuste (simulando una llamada al backend)
        function guardarAjuste(nuevoNivel, observacion) {
            const depositoIndex = depositos.findIndex(d => d.id === currentDeposito.id);
            if (depositoIndex !== -1) {
                depositos[depositoIndex].nivel_actual_litros = nuevoNivel;
                console.log(`Ajuste guardado para el depósito ${currentDeposito.serial}. Nuevo nivel: ${nuevoNivel} L. Observación: "${observacion}"`);

                ajustarNivelModal.hide();
                renderizarGraficos();
                renderizarListado();
            }
        } 


    });
</script>
@endpush
