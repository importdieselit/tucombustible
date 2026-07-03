@extends('layouts.app')
@section('title', 'Módulo de Combustibles')

@section('content')
<div class="container py-5">
    
    {{-- ENCABEZADO DE MÓDULO --}}
    <div class="text-center mb-5">
        <span class="badge text-uppercase fw-bold p-2 mb-2 shadow-sm" style="background-color: #ff6600; color: #000; font-size: 11px; letter-spacing: 1px;">
            Sistemas ImporDiesel
        </span>
        <h1 class="display-5 fw-black text-dark text-uppercase mb-2">
            Módulo de Combustibles
        </h1>
        <p class="text-muted mx-auto" style="max-width: 600px; font-size: 15px;">
            Bienvenido al panel central de control de carburantes. Seleccione una de las áreas operativas listadas a continuación para comenzar.
        </p>
    </div>

    {{-- MENÚ DE ACCESO EN TARJETAS --}}
    <div class="row g-4 justify-content-center">
        
        {{-- OPCIÓN 1: INFRAESTRUCTURA DE TANQUES --}}
        <div class="col-md-5">
            <div class="card h-100 shadow-sm border-0 card-modulo transition-all" style="border-top: 4px solid #6c757d !important;">
                <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                    <div class="bg-light rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center shadow-inner" style="width: 70px; height: 70px;">
                        <i class="fas fa-boxes text-secondary fa-2x"></i>
                    </div>
                    <h5 class="fw-black text-uppercase text-dark mb-2" style="font-size: 16px; letter-spacing: 0.5px;">
                        Control de Depósitos
                    </h5>
                    <p class="text-muted small mb-4 flex-grow-1">
                        Configuración geométrica, registro técnico, dimensiones métricas de aforo y parametrización de la infraestructura de tanques en sedes.
                    </p>
                    <a href="{{ route('combustibles.depositos.index') }}" class="btn btn-outline-secondary w-100 fw-bold text-uppercase py-2" style="font-size: 12px; letter-spacing: 0.5px;">
                        <i class="fas fa-sliders-h me-1"></i> Gestionar Tanques
                    </a>
                </div>
            </div>
        </div>

        {{-- OPCIÓN 2: AUDITORÍA DE VARILLAJE (EL GEMELO 3D) --}}
        <div class="col-md-5">
            <div class="card h-100 shadow-sm border-0 card-modulo transition-all" style="border-top: 4px solid #ff6600 !important;">
                <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                    <div class="bg-light rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center shadow-inner" style="width: 70px; height: 70px;">
                        <i class="fas fa-eye-dropper text-orange fa-2x"></i>
                    </div>
                    <h5 class="fw-black text-uppercase text-dark mb-2" style="font-size: 16px; letter-spacing: 0.5px;">
                        Auditoría de Varillaje
                    </h5>
                    <p class="text-muted small mb-4 flex-grow-1">
                        Captura forzosa de medidas físicas en patio, cubicación reactiva e inspección visual en tiempo real mediante el gemelo digital 3D.
                    </p>
                    <a href="{{ route('combustibles.chequeos_depositos.create') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 shadow-sm text-dark" style="background-color: #ff6600; border-color: #ff6600; font-size: 12px; letter-spacing: 0.5px;">
                        <i class="fas fa-clipboard-check me-1"></i> Abrir Varillaje
                    </a>
                </div>
            </div>
        </div>

        {{-- OPCIÓN 3: LLENADOS CON CUPOS PREPGADOS --}}
        <div class="col-md-5">
            <div class="card h-100 shadow-sm border-0 card-modulo transition-all" style="border-top: 4px solid #ff6600 !important;">
                <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                    <div class="bg-light rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center shadow-inner" style="width: 70px; height: 70px;">
                        <i class="fas fa-eye-dropper text-orange fa-2x"></i>
                    </div>
                    <h5 class="fw-black text-uppercase text-dark mb-2" style="font-size: 16px; letter-spacing: 0.5px;">
                        Llenados con Cupos Prepagados
                    </h5>
                    <p class="text-muted small mb-4 flex-grow-1">
                        Llenados de vehiculos de clientes con cupos prepagados.
                    </p>
                    <a href="{{ route('combustibles.llenados_prepagados.index') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 shadow-sm text-dark" style="background-color: #ff6600; border-color: #ff6600; font-size: 12px; letter-spacing: 0.5px;">
                        <i class="fas fa-clipboard-check me-1"></i> Abrir Llenado
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .fw-black { font-weight: 900; }
    .text-orange { color: #ff6600 !important; }
    .card-modulo {
        border-radius: 8px;
        background-color: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-modulo:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,.08) !important;
    }
    .shadow-inner {
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.04);
    }
</style>
@endsection