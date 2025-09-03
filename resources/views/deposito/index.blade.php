@extends('layouts.app')

@push('styles')
{{-- Incluye Tailwind CSS desde CDN para este ejemplo. En un proyecto real, se usaría npm. --}}
<script src="https://cdn.tailwindcss.com"></script>
<style>
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
@endsection

@push('scripts')
<script defer src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
                        <div class="tank-capacity-label">
                            Capacidad: ${deposito.capacidad_litros} L
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML += depositoCard;
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

    // Llamar a las funciones de renderizado al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        // Se asegura de que la variable `depositos` no esté vacía antes de renderizar
        if (depositos && depositos.length > 0) {
            renderizarGraficos(depositos);
            renderizarListado(depositos);
        } else {
            console.error('No se recibieron datos de depósitos.');
        }
    });

</script>
@endpush
