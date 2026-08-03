<div class="table-responsive bg-white rounded shadow-sm border" style="max-height: 400px; overflow-y: auto; overflow-x: auto;">
    {{-- El min-width evita que las columnas con anchos fijos se aplasten en pantallas chicas --}}
    <table class="table table-sm table-hover align-middle mb-0" style="min-width: 1000px;">
        {{-- Cabecera pegajosa (Sticky) para que no se pierda al bajar el scroll --}}
        <thead class="bg-dark text-white sticky-top" style="font-size: 12px; top: 0; z-index: 10;">
            <tr class="text-uppercase">
                <th class="py-2 text-center">Nombre</th>
                <th class="py-2 text-center">Forma Geométrica</th>
                <th class="py-2 text-center">Capacidad Máxima</th>
                <th class="py-2 text-center" style="width: 180px;">Combustible Actual</th>
                <th class="py-2 text-center" style="width: 200px;">Centimetros Medidos</th>
                <th class="py-2 text-center" style="width: 180px;">Litros Calculados</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detalles as $detalle)
                @php
                    $geometrias = [
                        'CH' => 'Cil. Horizontal',
                        'CV' => 'Cil. Vertical',
                        'OH' => 'Oval Horizontal',
                        'OV' => 'Oval Vertical',
                        'R'  => 'Rectangular',
                        'C'  => 'Cúbico',
                        'E'  => 'Esférico'
                    ];
                    $textoGeometria = $geometrias[$detalle->tanque_forma] ?? 'No definida';

                    // RESOLUCIÓN DE COLOR DE ALTO CONTRASTE PARA EL BADGE
                    $isDiesel = Str::contains(Str::lower($detalle->combustible_nombre), 'diesel');
                    $badgeStyle = $isDiesel 
                        ? 'background-color: #ffa500; color: #000000;' 
                        : 'background-color: #00a8ff; color: #ffffff;';
                @endphp
                <tr style="font-size: 13px;">
                    {{-- Nombre --}}
                    <td class="text-center fw-black text-dark py-3">
                        <i class="fas fa-database text-secondary me-2"></i> {{ $detalle->tanque_serial }}
                    </td>
                    
                    {{-- Forma Geométrica --}}
                    <td class="text-center text-muted fw-bold text-uppercase" style="font-size: 11px;">
                        {{ $textoGeometria }}
                    </td>
                    
                    {{-- Capacidad Máxima --}}
                    <td class="text-center fw-bold text-dark">
                        {{ number_format($detalle->capacidad_max, 2, ',', '.') }} Lts
                    </td>
                    
                    {{-- Combustible Actual --}}
                    <td class="text-center">
                        <span class="badge text-uppercase fw-black px-2 py-1 shadow-sm" style="font-size: 11px; letter-spacing: 0.3px; {{ $badgeStyle }}">
                            <i class="fas fa-gas-pump me-1 small"></i> {{ $detalle->combustible_nombre }}
                        </span>
                    </td>
                    
                    {{-- Centimetros Medidos --}}
                    <td class="text-center fw-black text-secondary font-monospace" style="font-size: 14px;">
                        {{ number_format($detalle->centimetros_medidos, 2, ',', '.') }} cm
                    </td>
                    
                    {{-- Litros Calculados --}}
                    <td class="text-center fw-black font-monospace" style="font-size: 15px; color: #ff6600 !important;">
                        {{ number_format($detalle->litros_calculados, 2, ',', '.') }} Lts
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted fw-bold small text-uppercase">
                        <i class="fas fa-info-circle me-1 text-warning"></i> No se encontraron registros de tanques para este chequeo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>