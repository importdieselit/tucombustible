<!DOCTYPE html>
<html>
<head>
    <title>{{ $titulo }} - {{ $inspeccion->vehiculo->placa ?? 'N/A' }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 14px; }
        .section-title { background-color: #f2f2f2; padding: 5px; margin-top: 15px; border-bottom: 2px solid #333; font-weight: bold; font-size: 11px; }
        .subsection-title { margin-top: 10px; font-weight: bold; font-size: 10px; color: #555; }
        .checklist-table { width: 100%; border-collapse: collapse; }
        .checklist-table td { padding: 4px; vertical-align: top; }
        .field-label { font-weight: bold; display: block; margin-bottom: 2px; }
        .field-value.ok { color: green; font-weight: bold; }
        .field-value.warning { color: red; font-weight: bold; }
        /* Clases de columna para DomPDF - Simulación de Grid */
        .col-12 { width: 100%; }
        .col-6 { width: 49%; display: inline-block; margin-right: 1%; }
        .col-4 { width: 32.33%; display: inline-block; margin-right: 1%; }
    </style>
</head>
<body>

    <div class="header">
        <h1>FORMATO CHECK LIST - INSPECCION DE SALIDA</h1>
        <p>No. Inspección: **{{ $inspeccion->id }}** | Estatus: **{{ $inspeccion->estatus_general }}**</p>
        <p style="font-size: 8px;">Generado por: {{ $inspeccion->usuario->name ?? 'Sistema' }} el {{ $inspeccion->created_at->format('d/m/Y H:i') }}</p>
    </div>

    @foreach ($respuesta['sections'] as $section)
        <div class="section-title">{{ $section['section_title'] }}</div>
        
        @php
            // Función simple para renderizar ítems con formato de columna en PDF
            $renderPdfItems = function ($items) {
                $html = '<div style="margin-top: 5px;">';
                foreach ($items as $item) {
                    $colWidth = $item['col_width'] ?? 12;
                    $colClass = 'col-' . $colWidth;
                    
                    // Lógica de valores y estatus
                    $statusClass = '';
                    $displayValue = $item['value'];

                    if ($item['response_type'] === 'boolean') {
                        $displayValue = $item['value'] ? 'SÍ' : 'NO';
                        $statusClass = $item['value'] ? 'ok' : 'warning';
                    } elseif ($item['response_type'] === 'composite') {
                        $statusText = $item['value']['status'] ? 'SÍ (OK)' : 'NO (Falla)';
                        $vigencia = $item['value']['vigencia'] ?? 'N/A';
                        $displayValue = "Estado: {$statusText} / Vigencia: {$vigencia}";
                        $statusClass = $item['value']['status'] ? 'ok' : 'warning';
                    }
                    
                    $html .= "<div class='{$colClass}'>";
                    $html .= "<span class='field-label'>{$item['label']}:</span>";
                    $html .= "<span class='field-value {$statusClass}'>{$displayValue}</span>";
                    $html .= "</div>";
                }
                $html .= '</div>';
                return $html;
            };
        @endphp

        @if (isset($section['items']))
            {!! $renderPdfItems($section['items']) !!}
        @elseif (isset($section['subsections']))
            @foreach ($section['subsections'] as $subsection)
                <div class="subsection-title">{{ $subsection['subsection_title'] }}</div>
                {!! $renderPdfItems($subsection['items']) !!}
            @endforeach
        @endif
    @endforeach

</body>
</html>