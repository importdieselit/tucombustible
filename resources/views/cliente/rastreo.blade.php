@extends('layouts.app')
@section('title', 'Rastreo de Unidad - Viaje #' . $viaje->id)

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css" rel="stylesheet" />

    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.2);
            z-index: 1;
        }
        .map-marker-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            border: 2px solid #002d72;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        .marker-pulse {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid #002d72;
            border-radius: 50%;
            animation: pulse-animation 1.5s infinite ease-out;
            opacity: 0;
        }
        @keyframes pulse-animation {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(2); opacity: 0; }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border border-gray-300">
            <div>
                <h3 class="text-orange mb-0 fw-bold uppercase italic">
                    <span class="text-orange">|</span> Monitoreo de Despacho en Tiempo Real
                </h3>
                <div class="text-[11px] font-black text-gray-500 uppercase mt-1">
                    Viaje #{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }} — Destino: {{ $viaje->destino_ciudad }}
                </div>
            </div>
            <a href="{{ route('portal.clientes.index') }}" class="bg-gray-800 text-white px-4 py-2 rounded text-xs font-black uppercase hover:bg-black transition shadow-md">
                <i class="fas fa-arrow-left mr-1"></i> Volver a Panel
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-4">
                <div class="p-4 bg-gray-industrial border-b border-gray-200">
                    <h5 class="text-white mb-0 font-black uppercase text-xs tracking-widest">
                        <i class="fas fa-truck text-orange mr-2"></i> Datos de Logística
                    </h5>
                </div>
                <div class="p-4 bg-white">
                    <div class="mb-4 border-b pb-2">
                        <span class="text-[10px] font-black uppercase text-gray-400 d-block">Estatus General</span>
                        <span class="px-3 py-1 rounded text-[10px] font-black uppercase border border-orange-300 shadow-sm inline-block mt-1 bg-orange-100 text-orange">
                            {{ $viaje->status }}
                        </span>
                    </div>

                    <div class="mb-4 border-b pb-2">
                        <span class="text-[10px] font-black uppercase text-gray-400 d-block">Litros Asignados</span>
                        <div class="text-sm font-black text-gray-800">
                            {{ number_format($datosDespachoCliente->litros ?? 0, 0, ',', '.') }}
                            <span class="text-[10px] text-gray-400 uppercase">Lts</span>
                        </div>
                    </div>

                    <div class="mb-4 border-b pb-2">
                        <span class="text-[10px] font-black uppercase text-gray-400 d-block">Unidad de Transporte</span>
                        <div class="font-black text-gray-800 uppercase text-sm leading-tight">
                            {{ $vehiculo->flota ?? 'Unidad' }}
                        </div>
                        <div class="text-xs font-bold text-gray-500 mt-1">
                            Placa: <span class="text-gray-800 font-black">{{ $vehiculo->placa }}</span>
                        </div>
                    </div>

                    @if($ultimoGps)
                        <div class="bg-gray-50 p-3 rounded border border-dashed border-gray-300">
                            <span class="text-[10px] font-black uppercase text-gray-400 d-block mb-1">
                                <i class="fas fa-clock text-orange mr-1"></i> Último Reporte
                            </span>
                            <div class="text-xs font-black text-gray-700 uppercase">
                                {{ \Carbon\Carbon::parse($ultimoGps->created_at)->format('d/m/Y h:i A') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-4 p-2">
                @if($ultimoGps)
                    <div id="map"></div>
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-center text-gray-400 font-black uppercase text-xs tracking-widest" style="height: 600px;">
                        <i class="fas fa-satellite-dish text-4xl text-gray-300 mb-3 text-orange animate-bounce"></i>
                        No hay coordenadas satelitales recientes.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @if($ultimoGps)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.js"></script>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const lat = {{ $ultimoGps->latitud }};
                const lng = {{ $ultimoGps->longitud }};
                const placa = "{{ $vehiculo->placa }}";

                const map = L.map('map', {
                    fullscreenControl: true,
                }).setView([lat, lng], 14);

<<<<<<< HEAD
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
=======
                 L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
>>>>>>> main
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                const customIcon = L.divIcon({
                    html: `
                        <div class="map-marker-container">
                            <div class="marker-pulse"></div>
                            <i class="fa-solid fa-truck" style="color: #002d72; font-size: 14px;"></i>
                        </div>`,
                    className: 'custom-div-icon',
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });

                const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);

                marker.bindPopup(`
                    <div class="text-center font-sans" style="padding: 2px;">
                        <span class="uppercase font-black text-gray-800" style="font-size: 11px; color:#002d72;">Unidad</span><br>
                        <span class="font-bold text-gray-600" style="font-size: 10px;">Placa: ${placa}</span>
                    </div>
                `).openPopup();
            });
        </script>
    @endif
@endpush