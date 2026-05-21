     
        <div class="bg-dark p-4 text-white d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 fw-bold">TUCOMBUSTIBLE</h3>
            </div>
            <div>
                <h1 class="fw-bold">SALA DE CONTROL</h1>
            </div>
            <div class="text-end">
                <div class="h4 mb-0">{{ $today->translatedFormat('d M, Y') }}</div>
            </div>
            
        </div>
<div class="row w-100 align-items-center bg-light p-0 m-0 border noPrint no-print">
                <div class="col-4 text-muted small">
                    <i class="fas fa-clock me-1"></i> Última actualización: 
                    <span id="last-sync-time" class="fw-bold">--:--:--</span>
                </div>
                <div class="col-4 text-muted small border-start">
                    <i class="fas fa-hourglass-half me-1"></i> Próxima en: 
                    <span id="countdown-timer" class="badge bg-dark">45s</span>
                </div>
                <div class="col-4 text-end">
                    <button onclick="manualRefresh()" class="btn btn-sm btn-primary ms-2" id="btn-refresh">
                        <i class="fas fa-sync-alt" id="refresh-icon"></i> Actualizar Ahora
                    </button>
                </div>
            </div>
        <div class="row g-0 border-bottom">
            <div class="col-md-3 p-4 text-center border-end">
                <div class="display-5 fw-bold text-primary">{{ $total }}</div>
                <div class="text-uppercase small fw-bold text-muted">Total Flota</div>
            </div>
            <div class="col-md-2 p-4 text-center border-end">
                <div class="display-5 fw-bold text-success">{{ $operativosCount }}</div>
                <div class="text-uppercase small fw-bold text-muted">Activas</div>
            </div>
            <div class="col-md-2 p-4 text-center border-end">
                <div class="display-5 fw-bold text-danger">{{ $fallaCount }}</div>
                <div class="text-uppercase small fw-bold text-muted">Inoperativas</div>
            </div>
            <div class="col-md-2 p-4 text-center border-end">
                <div class="display-5 fw-bold text-warning">{{ $enRuta }}</div>
                <div class="text-uppercase small fw-bold text-muted">En Ruta</div>
            </div>
            <div class="col-md-3 p-4 text-center">
                <div class="h2 mb-1 fw-bold {{ $porcentajeDisponibilidad > 80 ? 'text-success' : 'text-warning' }}">
                    {{ $porcentajeDisponibilidad }}%
                </div>
                <div class="progress mx-auto" style="height: 8px; width: 100px;">
                    <div class="progress-bar bg-success" style="width: {{ $porcentajeDisponibilidad }}%"></div>
                </div>
                <div class="text-uppercase small fw-bold text-muted mt-2">Disponibilidad</div>
            </div>
        </div>

        {{-- SECCIÓN GERENCIAL --}}

        <div class="row mb-4 no-print">
            <div class="col-lg-5">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div id="chart-disponibilidad" style="width:100%; height:300px;"></div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
                    <div class="card-body">
                        <h6 class="fw-black text-uppercase small text-muted mb-4">Análisis de Flota por Segmento</h6>
                        <div id="chart-segmentos" style="width:100%; height:250px;"></div>
                        
                
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">

                        <div class="row g-3 mb-4">
                            <!--
                            <h3 class="w-100 text-center "><strong>  UNIDADES OPERATIVAS</strong></h3>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm  border-chutos border-0 border-top border-4">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-truck-pickup me-1 text-corporate"></i> Chutos</span>
                                        <span class="badge bg-chutos rounded-pill">{{ $chutosOperativos->count() }} de {{ $totalChutos }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($chutosOperativos as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fa-solid fa-truck-pickup text-muted"></i> {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-camiones">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-truck me-1 text-warning"></i> Camiones</span>
                                        <span class="badge bg-camiones rounded-pill">{{ $camionesOperativos->count() }} de {{ $totalCamiones }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionesOperativos as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-truck text-muted"></i> {{ $v->flota }} <span class="font-weight-bold text-muted">|</span> {{ $v->placa }}
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-cisternas">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-trailer me-1 text-success"></i> Cisternas</span>
                                        <span class="badge bg-cisternas rounded-pill">{{ $cisternasOperativas->count() }} de {{ $totalCisternas }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($cisternasOperativas as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-trailer text-muted"></i> {{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-camionetas">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center align-middle py-2">
                                        <span class="w-100 fw-bold small text-uppercase align-middle">
                                            <i class="fas fa-car me-1 text-secondary m-0 p-0"></i> Livianos
                                        </span>
                                            <span class="badge bg-secondary rounded-pill">{{ $camionetasOperativas->count() }} de {{ $totalLivianos }}</span>
                                        
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionetasOperativas as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-car text-muted"></i>{{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        -->
                            <h3 class="w-100 text-center"><strong>  UNIDADES CON FALLA</strong></h3>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-chutos">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-truck-pickup me-1 text-corporate"></i> Chutos</span>
                                        <span class="badge bg-chutos rounded-pill">{{ $chutosFalla->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($chutosFalla as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fa-solid fa-truck-pickup text-muted"></i> {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                                <span class="badge {{ $v->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    {{ $v->dias_fuera_servicio }} DÍAS
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-warning">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-truck me-1 text-warning"></i> Camiones</span>
                                        <span class="badge bg-warning rounded-pill">{{ $camionesFalla->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionesFalla as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-truck text-muted"></i> {{ $v->flota }} <span class="font-weight-bold text-muted">|</span> {{ $v->placa }}
                                                </span>
                                                <span class="badge {{ $v->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    {{ $v->dias_fuera_servicio }} DÍAS
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-success">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-trailer me-1 text-success"></i> Cisternas</span>
                                        <span class="badge bg-success rounded-pill">{{ $cisternasFalla->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($cisternasFalla as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-trailer text-muted"></i> {{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                                <span class="badge {{ $v->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    {{ $v->dias_fuera_servicio }} DÍAS
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-secondary">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-car me-1 text-secondary"></i> Livianos</span>
                                        <span class="badge bg-secondary text-dark rounded-pill">{{ $camionetasFalla->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionetasFalla as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-car text-muted"></i>{{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                                <span class="badge {{ $v->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    <i class="bi bi-clock-history"></i> {{ $v->dias_fuera_servicio }} DÍAS
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>


                                    <h3 class="w-100 text-center"><strong>  UNIDADES EN RUTA</strong></h3>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-chutos">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-truck-pickup me-1 text-corporate"></i> Chutos</span>
                                        <span class="badge bg-chutos rounded-pill">{{ $chutosEnRuta->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($chutosEnRuta as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fa-solid fa-truck-pickup text-muted"></i> {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                                <span class="badge {{ $v->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    {{ $v->dias_fuera_servicio }} DÍAS
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-warning">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-truck me-1 text-warning"></i> Camiones</span>
                                        <span class="badge bg-warning rounded-pill">{{ $camionesEnRuta->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionesEnRuta as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-truck text-muted"></i> {{ $v->flota }} <span class="font-weight-bold text-muted">|</span> {{ $v->placa }}
                                                </span>
                                                <span class="badge {{ $v->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    {{ $v->dias_fuera_servicio }} DÍAS
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-success">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-trailer me-1 text-success"></i> Cisternas</span>
                                        <span class="badge bg-success rounded-pill">{{ $cisternasEnRuta->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($cisternasEnRuta as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-trailer text-muted"></i> {{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                                <span class="badge {{ $v->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    {{ $v->dias_fuera_servicio }} DÍAS
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-secondary">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"><i class="fas fa-car me-1 text-secondary"></i> Livianos</span>
                                        <span class="badge bg-secondary text-dark rounded-pill">{{ $camionetasEnRuta->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionetasEnRuta as $v)
                                                <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                                    <i class="fas fa-car text-muted"></i>{{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                                </span>
                                                <span class="badge {{ $v->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    <i class="bi bi-clock-history"></i> {{ $v->dias_fuera_servicio }} DÍAS
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>     
                </div>
            </div>
            <div class="col-12 d-none">
                <h3 class="w-100 text-center"><strong>  PLANIFICACION DEL DIA </strong></h3>
                <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
                    <div class="card-body">
                
                        <div class="mt-3 bg-white rounded shadow-sm border-start border-4 border-orange overflow-hidden">
                            {{-- Encabezado del Cuadro --}}
                            <div class="p-3 bg-light d-flex justify-content-between align-items-center border-bottom">
                                <div>
                                    <span class="text-uppercase fw-bold mb-0" style="font-size: 11px; color: #666; letter-spacing: 1px;">Planificacion del Dia</span>
                                    <h4 class="fw-black mb-0 text-dark">{{ $despachosHoy->count() ?? 0 }} <small class="text-muted small" style="font-size: 14px;">Viajes Totales</small></h4>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-dark text-orange fw-black px-3 py-2" style="border-radius: 20px;">
                                        CAPACIDAD UTILIZADA: {{ $utilizacionFlota ?? 0 }}%
                                    </span>
                                </div>
                            </div>

                            {{-- Tabla de Movimientos --}}
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="py-2 px-3 text-uppercase small text-muted" style="width: 120px;">Tipo</th>
                                            <th class="py-2 px-3 text-uppercase small text-muted" style="width: 240px;">Unidad</th>
                                            <th class="py-2 px-3 text-uppercase small text-muted">Detalle de Operación</th>
                                            <th class="py-2 px-3 text-uppercase small text-muted text-end" style="width: 150px;">Total Litros</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($despachosHoy as $viaje)
                                            @php
                                                $destinoRaw = $viaje->destino_ciudad;
                                                $esFlete = str_contains(strtoupper($destinoRaw), 'FLETE');
                                                $totalLitros = 0;
                                                // Limpiamos la palabra "FLETE", las flechas "->" y espacios sobrantes
                                                $destinoLimpio = trim(str_ireplace(['FLETE', ' ->'], ['', ''], $destinoRaw));
                                                
                                                // Si es flete, asignamos colores y estilos únicos (un tono Púrpura o Gris Oscuro)
                                                if ($esFlete) {
                                                    $badgeClass = 'bg-dark text-white'; // O un color que prefieras para fletes
                                                    $lineColor = '#6f42c1'; // Púrpura para fletes
                                                    $tipoEtiqueta = 'Flete';
                                                    $iconClass = 'fa-truck-loading';
                                                } else {
                                                    $esDespacho = is_null($viaje->litros);
                                                    $detallesDespacho = $esDespacho ? $viaje->despachos : null;
                                                
                                                    $totalLitros = $esDespacho 
                                                        ? ($detallesDespacho->sum('litros') ?? 0) 
                                                        : $viaje->litros;
                                                    $lineColor = $esDespacho ? '#28a745' : '#17a2b8';
                                                    $badgeClass = $esDespacho ? 'bg-success' : 'bg-info text-dark';
                                                    $tipoEtiqueta = $esDespacho ? 'Despacho' : 'Carga';
                                                    $iconClass = $esDespacho ? 'fa-arrow-up' : 'fa-arrow-down';
                                                }
                                            @endphp

                                            <tr class="viaje-row" style="border-left: 5px solid {{ $lineColor }};">
                                                <td class="align-middle px-3">
                                                    <span class="badge {{ $badgeClass }} text-uppercase w-100" style="font-size: 9px; letter-spacing: 0.5px;">
                                                        <i class="fas {{ $iconClass }} me-1"></i>
                                                        {{ $tipoEtiqueta }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center align-middle">
                                                        <div class="me-2">
                                                            <i class="fas fa-truck text-muted"></i>
                                                        </div>
                                                        <div>
                                                            <span class="fw-bold text-dark" style="font-size: 13px;">{{ $viaje->vehiculo ? '['.$viaje->vehiculo->flota.'] '.$viaje->vehiculo->placa :$viaje->otro_vehiculo}} 
                                                                @if($viaje->cisternaAcoplada) 
                                                                
                                                                    @php($cisterna=$viaje->cisternaAcoplada)
                                                                
                                                                    <br>
                                                                    <i class="fas fa-link text-muted opacity-50" style="font-size"></i>
                                                                    [{{ $cisterna->flota}}] <span class="text-success">{{ $cisterna->placa }}</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="align-middle px-3">
                                                    {{-- Si es Flete, mostramos el destino limpio --}}
                                                    @if($esFlete)
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2">
                                                                <i class="fas fa-map-marker-alt text-muted"></i>
                                                            </div>
                                                            <div>
                                                                <span class="fw-bold text-dark" style="font-size: 13px;">{{ $destinoLimpio }}</span>
                                                            </div>
                                                        </div>
                                                    @elseif($esDespacho && $detallesDespacho && $detallesDespacho->count() > 0)
                                                        {{-- (Mantienes tu lógica anterior de desglose de clientes aquí) --}}
                                                        <div>
                                                            <span class="fw-black text-dark" style="font-size: 15px;">{{ $destinoLimpio }}</span>
                                                        </div>
                                                        <div class="py-1">

                                                            @foreach($detallesDespacho as $d)
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="text-dark fw-bold" style="font-size: 13px;">{{ $d->cliente->alias?? $d->cliente->nombre ?? $d->otro_cliente }}</span>
                                                                    <span class="badge bg-light text-dark border">{{ number_format($d->litros, 2) }} Lts</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        {{-- Caso Carga --}}
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <span class="fw-black text-dark" style="font-size: 15px;">{{ $destinoLimpio }}</span>
                                                            </div>
                                                            <i class="fas fa-gas-pump text-muted opacity-50"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                
                                                <td class="text-end align-middle px-3 fw-black text-dark" style="font-size: 16px;">
                                                    {{ number_format($totalLitros, 2) }} Ltrs
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="chart-data-meta" class="d-none"
            data-operativos="{{ $operativosCount }}"
            data-enruta="{{ $enRuta }}"
            data-fallas="{{ $fallaCount }}"
            data-chutos="{{ $totalChutos }}"
            data-camiones="{{ $totalCamiones }}"
            data-tanques="{{ $totalCisternas }}"
            data-livianos="{{ $totalLivianos }}">
        </div>