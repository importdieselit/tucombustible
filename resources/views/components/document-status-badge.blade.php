{{-- resources/views/components/document-status-badge.blade.php --}}
@props(['status', 'label'])

{{-- Asegura que $status sea un array y tenga los valores por defecto --}}
@php
    $status = array_merge([
        'class' => 'bg-secondary',
        'icon' => 'bi-question-circle',
        'title' => 'Sin Informacion',
    ], (array) $status);
@endphp

<div class="d-flex align-items-center mb-1">
    {{-- Etiqueta del Documento --}}
    <span class="fw-bold me-2">{{ $label }}:</span>
    
    {{-- Badge Dinámico --}}
    <span class="badge {{ $status['class'] }}" title="{{ $status['title'] }}">
        <i class="bi {{ $status['icon'] }} me-1"></i>
    </span>
</div>