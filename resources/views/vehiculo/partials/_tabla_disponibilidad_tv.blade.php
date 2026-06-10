     
     <div class="row py-0 bg-white g-0 border-bottom">
            
        <div class="row g-2 mb-4 mx-1">
            <div class="col-md-2 col-sm-6">
                <div class="card border-0 shadow-sm border-start border-4 border-camionetas">
                    <div class="card-body p-2 text-center">
                        <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Disponibles</small>
                        <h5 class="mb-0 fw-bold text-dark">{{ number_format($stats['disponibles'], 0, ',', '.') }} <span class="small">L</span></h5>
                        <i class="fas fa-warehouse text-muted mt-1"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-2 col-sm-6">
                <div class="card border-0 shadow-sm border-start border-4 border-cisternas">
                    <div class="card-body p-2 text-center">
                        <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Despachados</small>
                        <h5 class="mb-0 fw-bold text-success">{{ number_format($stats['despachados'], 0, ',', '.') }} <span class="small">L</span></h5>
                        <i class="fas fa-sign-out-alt text-success mt-1"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-2 col-sm-6">
                <div class="card border-0 shadow-sm border-start border-4 border-camiones">
                    <div class="card-body p-2 text-center">
                        <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Cargas</small>
                        <h5 class="mb-0 fw-bold text-warning">{{ number_format($stats['cargas'], 0, ',', '.') }} <span class="small">L</span></h5>
                        <i class="fas fa-truck-loading text-warning mt-1"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm border-start border-4 border-chutos">
                    <div class="card-body p-2 text-center">
                        <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Despachos Programados</small>
                        <h5 class="mb-0 fw-bold text-chutos">{{ number_format($stats['prog_desp'], 0, ',', '.') }} <span class="small">L</span></h5>
                        <i class="fas fa-calendar-alt text-chutos mt-1"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                
                <div class="card border-0 shadow-sm border-start border-4 border-info">
                    <div class="card-body p-2 text-center">
                        <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Carga Programada</small>
                        <h5 class="mb-0 fw-bold text-info">{{ number_format($stats['prog_carg'], 0, ',', '.') }} <span class="small">L</span></h5>
                        <i class="fas fa-clock text-info mt-1"></i>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <div class="row bg-white g-0 border-bottom">
            <div class="col-md-3 p-3 text-center border-end">
                <div class="display-5 fw-bold text-primary">{{ $total }}</div>
                <div class="text-uppercase small fw-bold text-muted">Total Flota</div>
            </div>
            <div class="col-md-2 p-3 text-center border-end">
                <div class="display-5 fw-bold text-success">{{ $operativosCount }}</div>
                <div class="text-uppercase small fw-bold text-muted">Activas</div>
            </div>
            <div class="col-md-2 p-3 text-center border-end">
                <div class="display-5 fw-bold text-danger">{{ $fallaCount }}</div>
                <div class="text-uppercase small fw-bold text-muted">Inoperativas</div>
            </div>
            <div class="col-md-2 p-3 text-center border-end">
                <div class="display-5 fw-bold text-warning">{{ $enRuta }}</div>
                <div class="text-uppercase small fw-bold text-muted">En Ruta</div>
            </div>
            <div class="col-md-3 p-3 text-center">
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
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-0">

                        <div class="row g-3 mb-4">

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
                        
                          
                            <h3 class="w-100 text-center"><strong>  UNIDADES EN RUTA</strong></h3>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-chutos">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase">Chutos</span>
                                        <span class="badge bg-chutos rounded-pill">{{ $chutosEnRuta->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($chutosEnRuta as $v)
                                                <span class="badge border text-dark fw-bold bg-light" style="font-size: 0.7rem;">
                                                    {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
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
                                        <span class="fw-bold small text-uppercase"> Camiones</span>
                                        <span class="badge bg-warning rounded-pill">{{ $camionesEnRuta->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionesEnRuta as $v)
                                                <span class="badge border text-dark fw-bold bg-light" style="font-size: 0.7rem;">
                                                 {{ $v->flota }} <span class="font-weight-bold text-muted">|</span> {{ $v->placa }}
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
                                        <span class="fw-bold small text-uppercase">Cisternas</span>
                                        <span class="badge bg-success rounded-pill">{{ $cisternasEnRuta->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($cisternasEnRuta as $v)
                                                <span class="badge border text-dark fw-bold bg-light" style="font-size: 0.7rem;">
                                                    {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
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
                                        <span class="fw-bold small text-uppercase"> Livianos</span>
                                        <span class="badge bg-secondary text-dark rounded-pill">{{ $camionetasEnRuta->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionetasEnRuta as $v)
                                                <span class="badge border text-dark fw-bold bg-light" style="font-size: 0.7rem;">
                                                    {{ $v->flota }} <span class="text-dark">|</span> {{ $v->placa }}
                                                </span>
                                            @empty
                                                <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <h3 class="w-100 text-center"><strong>  UNIDADES CON FALLA</strong></h3>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm border-0 border-top border-4 border-chutos">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-bold small text-uppercase"> Chutos</span>
                                        <span class="badge bg-chutos rounded-pill">{{ $chutosFalla->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($chutosFalla as $v)
                                                <span class="badge border text-dark fw-bold bg-light" style="font-size: 0.7rem;">
                                                    {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
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
                                        <span class="fw-bold small text-uppercase">Camiones</span>
                                        <span class="badge bg-warning rounded-pill">{{ $camionesFalla->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionesFalla as $v)
                                                <span class="badge border text-dark fw-bold bg-light" style="font-size: 0.7rem;">
                                                    {{ $v->flota }} <span class="font-weight-bold text-muted">|</span> {{ $v->placa }}
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
                                        <span class="fw-bold small text-uppercase"> Cisternas</span>
                                        <span class="badge bg-success rounded-pill">{{ $cisternasFalla->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($cisternasFalla as $v)
                                                <span class="badge border text-dark fw-bold bg-light" style="font-size: 0.7rem;">
                                                    {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
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
                                        <span class="fw-bold small text-uppercase">Livianos</span>
                                        <span class="badge bg-secondary text-dark rounded-pill">{{ $camionetasFalla->count() }}</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse($camionetasFalla as $v)
                                                <span class="badge border text-dark fw-bold bg-light" style="font-size: 0.7rem;">
                                                    {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
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