@extends('layouts.app')

@section('title', "Resultados de Búsqueda: " . $query)

@section('content')
<div class="container mt-5">
    <h1 class="mb-4 text-primary"><i class="bi bi-search me-2"></i> Resultados de Búsqueda</h1>
    
    <div class="alert alert-info">
        Mostrando resultados para: <strong>"{{ $query }}"</strong>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light fw-bold">
            Elementos Encontrados ({{ count($results) }})
        </div>
        <ul class="list-group list-group-flush">
            @forelse ($results as $item)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <!-- Icono basado en el tipo -->
                        @php
                            $icon_class = 'bi-question-circle';
                            if ($item['icon'] === 'truck') $icon_class = 'bi-truck';
                            if ($item['icon'] === 'person') $icon_class = 'bi-person-badge';
                            if ($item['icon'] === 'people') $icon_class = 'bi-building';
                        @endphp
                        
                        <i class="bi {{ $icon_class }} fs-4 me-3 text-secondary"></i>
                        
                        <div>
                            <span class="badge bg-primary me-2">{{ $item['type'] }}</span>
                            <span class="fw-bold">{{ $item['description'] }}</span>
                        </div>
                    </div>
                    
                    <!-- Botón para ver el detalle -->
                    <a href="{{ $item['details_link'] }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-eye"></i> Ver Detalle
                    </a>
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-4">
                    <i class="bi bi-emoji-frown fs-3 d-block mb-2"></i>
                    No se encontraron resultados para su búsqueda. Intente con una palabra clave diferente.
                </li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
