@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/noc_tv.css') }}?v={{ filemtime(public_path('css/noc_tv.css')) }}">
@endpush

@section('content')
<div id="noc-viewport">
    <div id="noc-scaler" class="container-fluid p-3">
        <div id="countdown-timer" style="position: absolute; top: 15px; right: 15px; background: #002d72; color: #ffc107; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 14px; z-index: 99999; border: 1px solid #ffc107; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
        APK v: <span id="lbl-apk-version">Detectando...</span>
    </div>
        
        <div id="offline-banner" class="offline-alert">
            ⚠️ ALERTA DE SISTEMA: PÉRDIDA DE SEÑAL DE RED - RECONECTANDO...
        </div>

        <div class="tv-grid-container">
            <div id="map-panel">
                <div id="map"></div>
            </div>

            <div id="dashboard-panel">
                <div id="reporte-container">
                    <div class="text-center mt-5 text-muted">
                        <i class="fas fa-circle-notch fa-spin fa-4x mb-3 text-primary"></i>
                        <h2 style="font-weight: 900; color:#002d72;">INICIALIZANDO CONSOLA NOC...</h2>
                        <p>Estableciendo conexión encriptada con la central...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js"></script>
    
    @php
        // Capturamos las versiones exactas al momento de renderizar la vista
        $cssPath = public_path('css/tv_noc.css');
        $jsPath = public_path('js/tv_noc.js');
        $cssVersion = file_exists($cssPath) ? (string)filemtime($cssPath) : '1';
        $jsVersion = file_exists($jsPath) ? (string)filemtime($jsPath) : '1';
    @endphp

    <script>
        // Le pasamos las versiones iniciales a nuestro archivo JS
        window.NOC_CONFIG = {
            tvToken: "{{ $token ?? '' }}",
            streamUrl: "{{ route('api.sala.control.stream') }}",
            cssVersion: "{{ $cssVersion }}",
            jsVersion: "{{ $jsVersion }}"
        };
    </script>

    <script src="{{ asset('js/tv_noc.js') }}?v={{ $jsVersion }}"></script>
@endpush
@endsection