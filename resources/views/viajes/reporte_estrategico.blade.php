@extends('layouts.app')

@section('content')
<style>
    @media print {
        .no-print, nav, .sidebar, form, .btn, footer, .navbar {
            display: none !important;
        }
        .no-print-chart {
            display: none !important;
        }
        body { background-color: #fff !important; font-size: 11pt; }
        .container-fluid { width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; margin-bottom: 15px !important; page-break-inside: avoid; }
        .table-responsive { overflow: visible !important; }
        .print-header { display: block !important; margin-bottom: 15px; text-align: center; }
    }
    .print-header { display: none; }
</style>

<div class="container-fluid">
    
    <div class="print-header">
        <h2>Reporte Estratégico de Operaciones de Carga</h2>
        <p>Rango: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
        <hr>
    </div>

    <!-- Barra Superior -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="h3 text-gray-800">Dashboard Estratégico de Operaciones</h2>
        <button onclick="window.print()" class="btn btn-primary shadow-sm">
            <i class="fas fa-print fa-sm text-white-50"></i> Imprimir / Exportar PDF
        </button>
    </div>

    <!-- Filtros Dinámicos -->
    <div class="card shadow mb-4 no-print">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filtros y Criterios</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('viajes.reporte_estrategico') }}" class="row g-3">
                <div class="col-md-2">
                    <label>Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
                </div>
                <div class="col-md-2">
                    <label>Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
                </div>
                <div class="col-md-2">
                    <label>Tipo Operación</label>
                    <select name="tipo_operacion" class="form-control">
                        <option value="">Todas</option>
                        <option value="ventas" {{ $tipoOperacion == 'ventas' ? 'selected' : '' }}>Todas las Ventas (MGO e Ind.)</option>
                        <option value="1" {{ $tipoOperacion == '1' ? 'selected' : '' }}>Venta MGO</option>
                        <option value="2" {{ $tipoOperacion == '2' ? 'selected' : '' }}>Venta Industrial</option>
                        <option value="3" {{ $tipoOperacion == '3' ? 'selected' : '' }}>Fletes</option>
                        <option value="4" {{ $tipoOperacion == '4' ? 'selected' : '' }}>Compras</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="cliente_id">Cliente</label>
                    <select name="cliente_id" id="cliente_id" class="form-control">
                        <option value="">Todos los clientes</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}" {{ $clienteId == $cli->id ? 'selected' : '' }}>
                                @if($cli->parent == 0 || is_null($cli->parent))
                                    [Principal] {{ $cli->nombre }}
                                @else
                                    &#160;&#160;&#160;&#160;↳ {{ $cli->nombre }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Chofer</label>
                    <select name="chofer_id" class="form-control">
                        <option value="">Todos</option>
                        @foreach($choferes as $chofer)
                            <option value="{{ $chofer->id }}" {{ $choferId == $chofer->id ? 'selected' : '' }}>
                                {{ $chofer->persona->nombre }} {{ $chofer->persona->apellido }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Destino</label>
                    <select name="destino_ciudad" class="form-control">
                        <option value="">Todos</option>
                        @foreach($destinos as $dest)
                            <option value="{{ $dest }}" {{ $destino == $dest ? 'selected' : '' }}>{{ $dest }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Agrupar Tabla</label>
                    <select name="agrupar_por" class="form-control border-primary fw-bold">
                        <option value="ninguno" {{ $agruparPor == 'ninguno' ? 'selected' : '' }}>Sin Agrupar (Detalle)</option>
                        <option value="tipo_operacion" {{ $agruparPor == 'tipo_operacion' ? 'selected' : '' }}>Por Tipo de Operación</option>
                        <option value="cliente" {{ $agruparPor == 'cliente' ? 'selected' : '' }}>Por Cliente</option>
                        <option value="chofer" {{ $agruparPor == 'chofer' ? 'selected' : '' }}>Por Chofer</option>
                        <option value="destino" {{ $agruparPor == 'destino' ? 'selected' : '' }}>Por Destino</option>
                    </select>
                </div>
                <div class="col-md-10 d-flex justify-content-end align-items-end">
                    <button type="submit" class="btn btn-success px-4"><i class="fas fa-search"></i> Aplicar Filtros</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TARJETA DE RESUMEN DE CLIENTE PRINCIPAL (EN CASO DE FILTRAR POR CLIENTE) -->
    @if($clienteSeleccionado)
        <div class="card border-left-info shadow mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Resumen de Despachos | Cliente {{ $esClientePrincipal ? 'Principal (Incluye Sucursales)' : 'Sucursal' }}
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $clienteSeleccionado->nombre }}
                        </div>
                        @if(!$esClientePrincipal && $clienteSeleccionado->padre)
                            <div class="small text-muted">Pertenece a: <strong>{{ $clienteSeleccionado->padre->nombre }}</strong></div>
                        @endif
                    </div>
                    <div class="col-auto text-center border-start px-4">
                        <div class="text-xs font-weight-bold text-uppercase text-muted">Total Despachos</div>
                        <div class="h4 mb-0 font-weight-bold text-primary">{{ number_format($totalDespachos) }}</div>
                    </div>
                    <div class="col-auto text-center border-start px-4">
                        <div class="text-xs font-weight-bold text-uppercase text-muted">Volumen Total Despachado</div>
                        <div class="h4 mb-0 font-weight-bold text-success">{{ number_format($totalLitros, 2) }} Lts</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- KPIs GENERALES -->
        <div class="row mb-4">
            <div class="col-md-2 mb-2">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Viajes Procesados</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($totalViajes) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Despachos Realizados</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($totalDespachos) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Volumen Total Ventas</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($totalLitros, 2) }} Lts</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Compras Realizadas</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($totalCompras) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Volumen Total Compras</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($totalLitrosCompras, 2) }} Lts</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- GRILLA DE GRÁFICOS -->
    <div class="row">
        <div class="col-md-6 mb-4 chart-container" id="cardChartChoferes">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Viajes por Chofer</h6>
                    <div class="form-check form-switch no-print">
                        <input class="form-check-input print-toggle" type="checkbox" id="printChoferes" checked data-target="cardChartChoferes">
                        <label class="form-check-label small" for="printChoferes">Imprimir</label>
                    </div>
                </div>
                <div class="card-body"><div id="choferesChart" style="width:100%; min-height:280px;"></div></div>
            </div>
        </div>

        <div class="col-md-6 mb-4 chart-container" id="cardChartAyudantes">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">Viajes por Ayudante</h6>
                    <div class="form-check form-switch no-print">
                        <input class="form-check-input print-toggle" type="checkbox" id="printAyudantes" checked data-target="cardChartAyudantes">
                        <label class="form-check-label small" for="printAyudantes">Imprimir</label>
                    </div>
                </div>
                <div class="card-body"><div id="ayudantesChart" style="width:100%; min-height:280px;"></div></div>
            </div>
        </div>

        <div class="col-md-6 mb-4 chart-container" id="cardChartDestinos">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Top Destinos</h6>
                    <div class="form-check form-switch no-print">
                        <input class="form-check-input print-toggle" type="checkbox" id="printDestinos" checked data-target="cardChartDestinos">
                        <label class="form-check-label small" for="printDestinos">Imprimir</label>
                    </div>
                </div>
                <div class="card-body"><div id="destinosChart" style="width:100%; min-height:280px;"></div></div>
            </div>
        </div>

        <div class="col-md-6 mb-4 chart-container" id="cardChartStatus">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-warning">Distribución por Estatus</h6>
                    <div class="form-check form-switch no-print">
                        <input class="form-check-input print-toggle" type="checkbox" id="printStatus" checked data-target="cardChartStatus">
                        <label class="form-check-label small" for="printStatus">Imprimir</label>
                    </div>
                </div>
                <div class="card-body"><div id="statusChart" style="width:100%; min-height:280px;"></div></div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE TABLAS -->
    @if($agruparPor === 'tipo_operacion')
        <!-- TABLA RESUMEN POR TIPO DE OPERACIÓN -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-dark text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-list-alt"></i> Resumen General por Tipo de Operación</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Tipo de Operación</th>
                                <th class="text-center">Total Viajes</th>
                                <th class="text-end">Volumen Total (Lts)</th>
                                <th class="text-center">% Part. Viajes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tablaAgrupada as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $item['criterio'] }}</td>
                                    <td class="text-center">{{ $item['total_viajes'] }}</td>
                                    <td class="text-end">{{ number_format($item['total_litros'], 2) }}</td>
                                    <td class="text-center">
                                        {{ $totalViajes > 0 ? number_format(($item['total_viajes'] / $totalViajes) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">Sin datos de operaciones.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($tablasPorTipo)
            @foreach($tablasPorTipo as $nombreTipo => $viajesTipo)
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-light border-left-primary">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-truck-loading"></i> Detalle de Operación: {{ $nombreTipo }}
                            <span class="badge bg-primary ms-2">{{ $viajesTipo->count() }} Viajes</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" width="100%">
                                <thead>
                                    <tr>
                                        <th>ID Viaje</th>
                                        <th>Fecha</th>
                                        <th>Destino</th>
                                        <th>Chofer</th>
                                        <th>Ayudante</th>
                                        <th class="text-end">Volumen (Lts)</th>
                                        <th>Cliente(s) Despachado(s)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($viajesTipo as $viaje)
                                        <tr>
                                            <td>#{{ $viaje->id }}</td>
                                            <td>{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y H:i') }}</td>
                                            <td>{{ $viaje->destino_ciudad ?? 'N/A' }}</td>
                                            <td>{{ $viaje->chofer->persona->nombre ?? 'N/A' }} {{ $viaje->chofer->persona->apellido ?? '' }}</td>
                                            <td>{{ $viaje->ayudante_chofer->persona->nombre ?? 'N/A' }} {{ $viaje->ayudante_chofer->persona->apellido ?? '' }}</td>
                                            <td class="text-end">{{ number_format($viaje->litros_filtrados, 2) }}</td>
                                            <td>{{ $viaje->clientes_despachados }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

    @elseif($agruparPor === 'cliente')
        <!-- TABLA RESUMEN POR CLIENTE PRINCIPAL -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-dark text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-user-tag"></i> Resumen Agrupado por Cliente Principal</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Cliente Principal</th>
                                <th class="text-center">Total Viajes</th>
                                <th class="text-center">Total Despachos</th>
                                <th class="text-end">Volumen Total (Lts)</th>
                                <th class="text-center">% Part. Volumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tablaAgrupada as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $item['criterio'] }}</td>
                                    <td class="text-center">{{ $item['total_viajes'] }}</td>
                                    <td class="text-center">{{ $item['total_despachos'] }}</td>
                                    <td class="text-end">{{ number_format($item['total_litros'], 2) }}</td>
                                    <td class="text-center">
                                        {{ $totalLitros > 0 ? number_format(($item['total_litros'] / $totalLitros) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">Sin información de clientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABLA CON EL DETALLE DE TODOS LOS DESPACHOS A PRINCIPALES Y SUCURSALES -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 border-left-info">
                <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-list"></i> Detalle General de Despachos a Clientes y Sucursales</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%">
                        <thead>
                            <tr>
                                <th>ID Viaje</th>
                                <th>Fecha</th>
                                <th>Cliente Destinatario</th>
                                <th>Relación Jerárquica</th>
                                <th>Chofer</th>
                                <th>Destino</th>
                                <th class="text-end">Volumen Despachado (Lts)</th>
                                <th>Tipo Operación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($despachosDetalle as $d)
                                <tr>
                                    <td>#{{ $d['viaje_id'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y H:i') }}</td>
                                    <td class="fw-bold">{{ $d['cliente_nombre'] }}</td>
                                    <td>
                                        @if($d['es_sucursal'])
                                            <span class="badge bg-warning text-dark">Sucursal de: {{ $d['padre_nombre'] }}</span>
                                        @else
                                            <span class="badge bg-success">Cliente Principal</span>
                                        @endif
                                    </td>
                                    <td>{{ $d['chofer'] }}</td>
                                    <td>{{ $d['destino'] ?? 'N/A' }}</td>
                                    <td class="text-end font-weight-bold">{{ number_format($d['litros'], 2) }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $d['tipo_operacion'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No hay despachos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($agruparPor === 'chofer')
        <!-- TABLA CHOFERES -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Resumen Agrupado por Chofer</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Chofer</th>
                                <th class="text-center">Total Viajes</th>
                                <th class="text-end">Volumen Total (Lts)</th>
                                <th class="text-center">% Part. Viajes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tablaAgrupada as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $item['criterio'] }}</td>
                                    <td class="text-center">{{ $item['total_viajes'] }}</td>
                                    <td class="text-end">{{ number_format($item['total_litros'], 2) }}</td>
                                    <td class="text-center">
                                        {{ $totalViajes > 0 ? number_format(($item['total_viajes'] / $totalViajes) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">Sin información de choferes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABLA AYUDANTES -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">Resumen Agrupado por Ayudante</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%">
                        <thead class="table-info">
                            <tr>
                                <th>#</th>
                                <th>Ayudante</th>
                                <th class="text-center">Total Viajes</th>
                                <th class="text-end">Volumen Total (Lts)</th>
                                <th class="text-center">% Part. Viajes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tablaAgrupadaAyudantes as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $item['criterio'] }}</td>
                                    <td class="text-center">{{ $item['total_viajes'] }}</td>
                                    <td class="text-end">{{ number_format($item['total_litros'], 2) }}</td>
                                    <td class="text-center">
                                        {{ $totalViajes > 0 ? number_format(($item['total_viajes'] / $totalViajes) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">Sin información de ayudantes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($agruparPor === 'destino')
        <!-- TABLA DESTINOS -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Resumen Agrupado por Destino</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Destino</th>
                                <th class="text-center">Total Viajes</th>
                                <th class="text-end">Volumen Total (Lts)</th>
                                <th class="text-center">% Part. Viajes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tablaAgrupada as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $item['criterio'] }}</td>
                                    <td class="text-center">{{ $item['total_viajes'] }}</td>
                                    <td class="text-end">{{ number_format($item['total_litros'], 2) }}</td>
                                    <td class="text-center">
                                        {{ $totalViajes > 0 ? number_format(($item['total_viajes'] / $totalViajes) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">Sin información de destinos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @else
        <!-- TABLA DETALLADA GENERAL -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Detalle General de Viajes</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%">
                        <thead>
                            <tr>
                                <th>ID Viaje</th>
                                <th>Fecha</th>
                                <th>Tipo Operación</th>
                                <th>Destino</th>
                                <th>Chofer</th>
                                <th>Ayudante</th>
                                <th class="text-end">Volumen Despachado (Lts)</th>
                                <th>Cliente(s) Despachado(s)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($viajes as $viaje)
                                <tr>
                                    <td>#{{ $viaje->id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            {{ $mapaTipos[$viaje->tipo_planificacion] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $viaje->destino_ciudad }}</td>
                                    <td>{{ $viaje->chofer->persona->nombre ?? 'N/A' }}</td>
                                    <td>{{ $viaje->ayudante_chofer->persona->nombre ?? 'N/A' }}</td>
                                    <td class="text-end fw-bold">{{ number_format($viaje->litros_filtrados, 2) }}</td>
                                    <td>{{ $viaje->clientes_despachados }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No se encontraron registros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@push('scripts')
<!-- HIGHCHARTS Y SCRIPT -->
<script src="https://code.highcharts.com/highcharts.js"></script>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        new TomSelect('#cliente_id', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            placeholder: "Buscar cliente..."
        });
    });
</script>
<script>
    (function() {
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('print-toggle')) {
                const targetId = e.target.getAttribute('data-target');
                const targetEl = document.getElementById(targetId);
                if (targetEl) {
                    if (e.target.checked) {
                        targetEl.classList.remove('no-print-chart');
                    } else {
                        targetEl.classList.add('no-print-chart');
                    }
                }
            }
        });

        function initDashboardCharts() {
            if (typeof Highcharts === 'undefined') return;

            const dataChoferes = @json($viajesPorChofer);
            const dataAyudantes = @json($viajesPorAyudante);
            const dataDestinos = @json($viajesPorDestino);
            const dataStatus = @json($viajesPorStatus);

            const formatPieData = (obj) => Object.keys(obj).map(key => ({ name: key, y: obj[key] }));

            const commonColumnOptions = {
                chart: { type: 'column' },
                title: { text: null },
                yAxis: { min: 0, title: { text: 'Cantidad' } },
                credits: { enabled: false }
            };

            const commonPieOptions = {
                chart: { type: 'pie' },
                title: { text: null },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: { enabled: true, format: '<b>{point.name}</b>: {point.y}' }
                    }
                },
                credits: { enabled: false }
            };

            if (document.getElementById('choferesChart')) {
                Highcharts.chart('choferesChart', Highcharts.merge(commonColumnOptions, {
                    xAxis: { categories: Object.keys(dataChoferes) },
                    series: [{ name: 'Viajes', data: Object.values(dataChoferes), color: '#4e73df' }]
                }));
            }

            if (document.getElementById('ayudantesChart')) {
                Highcharts.chart('ayudantesChart', Highcharts.merge(commonColumnOptions, {
                    xAxis: { categories: Object.keys(dataAyudantes) },
                    series: [{ name: 'Viajes', data: Object.values(dataAyudantes), color: '#36b9cc' }]
                }));
            }

            if (document.getElementById('destinosChart')) {
                Highcharts.chart('destinosChart', Highcharts.merge(commonPieOptions, {
                    plotOptions: { pie: { innerSize: '50%' } },
                    series: [{ name: 'Viajes', data: formatPieData(dataDestinos) }]
                }));
            }

            if (document.getElementById('statusChart')) {
                Highcharts.chart('statusChart', Highcharts.merge(commonPieOptions, {
                    series: [{ name: 'Viajes', data: formatPieData(dataStatus) }]
                }));
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardCharts);
        } else {
            initDashboardCharts();
        }
    })();
</script>
@endpush
@endsection