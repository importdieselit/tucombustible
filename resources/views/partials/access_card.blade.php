{{-- Componente reusable para las tarjetas de acceso rápido --}}
<a href="{{ $route }}" class="text-decoration-none" target="{{ $target ?? '_self' }}">
    <div class="card shadow-sm border-0 text-center access-card" 
         style="min-height: 150px; background-color: {{ $bg_opacity ?? 'rgba(0,0,0,0.1)' }};">
        <div class="card-body d-flex flex-column justify-content-center align-items-center p-3">
            
            {{-- Ícono Grande y Colorido --}}
            <i class="fa-solid {{ $icon }} fa-3x {{ Str::after($color, 'bg-') }} mb-3"></i> 
            
            {{-- Título del Módulo --}}
            <h6 class="card-title fw-bold text-dark mb-0">{{ $title }}</h6>
        </div>
    </div>
</a>

{{-- Estilos simples para mejorar el aspecto visual --}}
@push('styles')
<style>
    .access-card {
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }
    .access-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .access-card .fa-3x {
        /* Fuerza la opacidad alta en el ícono */
        opacity: 0.9; 
    }
</style>
@endpush