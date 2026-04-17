{{-- Este parcial asume que usa un framework CSS de 12 columnas (ej. Bootstrap) --}}
@foreach ($items as $item)
    @php
        // Usa el ancho definido en el JSON, por defecto 12 (ancho completo)
        $colWidth = $item['col_width'] ?? 12;
        // Asignar color según el estatus (solo para booleanos/compuestos)
        $statusClass = '';
        if ($item['response_type'] === 'boolean' || ($item['response_type'] === 'composite' && isset($item['value']['status']))) {
            $status = $item['response_type'] === 'boolean' ? $item['value'] : $item['value']['status'];
            $statusClass = $status === true ? 'text-success' : 'text-danger';
        }

        // Formato del valor
        $displayValue = $item['value'];
        if ($item['response_type'] === 'boolean') {
            $displayValue = $item['value'] ? 'SÍ' : 'NO';
        } elseif ($item['response_type'] === 'composite') {
            $statusText = $item['value']['status'] ? 'SÍ (OK)' : 'NO (Falla)';
            $vigencia = $item['value']['vigencia'] ?? 'N/A';
            $displayValue = "Estado: {$statusText} / Vigencia: {$vigencia}";
        }
    @endphp

    <div class="col-md-{{ $colWidth }} mb-3">
        <strong class="d-block">{{ $item['label'] }}:</strong>
        <p class="{{ $statusClass }}">{{ $displayValue }}</p>
    </div>
@endforeach