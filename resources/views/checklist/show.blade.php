@extends('layouts.app') 

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Detalle de Inspección #{{ $inspeccion->id }} - Vehículo: {{ $inspeccion->vehiculo->placa ?? 'N/A' }}</h2>
        <div>
            <a href="{{ route('inspeccion.pdf', $inspeccion->id) }}" class="btn btn-danger" target="_blank">
                <i class="fa fa-file-pdf me-2"></i> Imprimir PDF
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left me-2"></i> Volver
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-{{ $inspeccion->estatus_general === 'OK' ? 'success' : 'warning' }}">
            <h6 class="m-0 font-weight-bold text-white">
                Estatus General: {{ $inspeccion->estatus_general }}
                <span class="float-end">Inspeccionado por: {{ $inspeccion->usuario->name ?? 'Sistema' }} el {{ $inspeccion->created_at->format('d/m/Y H:i') }}</span>
            </h6>
        </div>
        <div class="card-body">

            @foreach ($respuesta['sections'] as $section)
                <div class="mt-4 border-bottom pb-2">
                    <h4 class="text-primary">{{ $section['section_title'] }}</h4>
                </div>
                
                @if (isset($section['items']))
                    <div class="row">
                        @include('checklist.partials.render_items', ['items' => $section['items']])
                    </div>
                @elseif (isset($section['subsections']))
                    @foreach ($section['subsections'] as $subsection)
                        <h5 class="mt-3 text-secondary">{{ $subsection['subsection_title'] }}</h5>
                        <div class="row">
                            @include('checklist.partials.render_items', ['items' => $subsection['items']])
                        </div>
                    @endforeach
                @endif
            @endforeach

        </div>
    </div>
</div>
@endsection