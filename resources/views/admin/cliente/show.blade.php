@extends('layouts.app')
@section('title', 'Expediente - ' . $cliente->nombre)

@section('content')
<div class="container-fluid">

    {{-- ALERTA DE HABILITACIÓN ESPECIAL (Mantenida tal cual) --}}
    @if(isset($orden) && $orden->estatus == 2 && $orden->vehiculoBelong->estatus == 1)
        <div class="alert alert-warning border-orange shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle fs-4 me-3 text-orange"></i>
            <div>
                <strong class="text-uppercase">Unidad en Habilitación Especial:</strong> 
                Esta unidad mantiene una Orden abierta por tareas no críticas.
            </div>
        </div>
    @endif

    {{-- ENCABEZADO Y BARRA DE HERRAMIENTAS --}}
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border">
            <div>
                <h3 class="text-orange mb-0 fw-bold"><i class="fas fa-address-card me-2"></i>Expediente de Cliente</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-muted">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}" class="text-muted">Clientes</a></li>
                        <li class="breadcrumb-item active text-orange" aria-current="page">{{ $cliente->nombre }}</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2 no-print">
                <a href="{{ route('clientes.index') }}" class="btn btn-light border btn-sm fw-bold uppercase">
                    <i class="fas fa-chevron-left me-1 text-orange"></i> Volver
                </a>
                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-dark btn-sm fw-bold uppercase">
                    <i class="fas fa-edit me-1 text-orange"></i> Editar
                </a>
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-bold uppercase">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>

    {{-- LÍNEA DE TIEMPO --}}
    <div class="bg-white p-6 rounded-lg shadow-md border-2 border-gray-300 mb-8 no-print">
        <p class="text-[10px] font-black uppercase text-gray-400 mb-6 tracking-widest border-b pb-2">
            Línea de Tiempo del Registro (5 Etapas)
        </p>
        <div class="flex items-start justify-between relative">
            <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 z-0"></div>
            <div class="absolute top-5 left-0 h-1 bg-orange-impordiesel z-0"
                 style="width: {{ $cliente->porcentaje_registro }}%"></div>

            @foreach($tiposCombustible->first() ? \App\Models\RegistroPaso::activos()->get() : collect() as $paso)
                @php
                    $completado = $paso->id < $cliente->registro_paso;
                    $actual     = $paso->id == $cliente->registro_paso;
                @endphp
                <div class="flex flex-col items-center z-10 flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm border-2
                        {{ $completado ? 'bg-orange-impordiesel border-orange-impordiesel text-white'
                                       : ($actual ? 'bg-white border-orange-impordiesel text-orange-impordiesel shadow-lg'
                                                  : 'bg-white border-gray-300 text-gray-400') }}">
                        @if($completado)
                            <i class="fas fa-check text-xs"></i>
                        @else
                            {{ $paso->orden }}
                        @endif
                    </div>
                    <p class="text-[9px] font-black uppercase mt-2 text-center leading-tight max-w-[80px]
                        {{ $actual ? 'text-orange-impordiesel' : ($completado ? 'text-gray-600' : 'text-gray-400') }}">
                        {{ $paso->nombre }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row">
        {{-- COLUMNA IZQUIERDA: DATOS Y LISTADOS --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-orange mb-4 overflow-hidden">
                <div class="card-header bg-dark py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="text-white mb-0 fw-bold uppercase tracking-tighter">{{ $cliente->nombre }}</h5>
                        <span class="badge {{ $cliente->color_status }} text-uppercase">{{ $cliente->label_status }}</span>
                    </div>
                    <small class="text-orange fw-bold">RIF: {{ $cliente->rif }}</small>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="text-muted text-uppercase fw-bold d-block small mb-1">Contacto Principal</label>
                            <span class="fw-bold text-dark uppercase">{{ $cliente->contacto ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted text-uppercase fw-bold d-block small mb-1">Teléfono Principal</label>
                            <span class="fw-bold text-dark">{{ $cliente->telefono ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted text-uppercase fw-bold d-block small mb-1">Contacto Alternativo</label>
                            <span class="fw-bold text-dark uppercase">{{ $cliente->contacto_alt ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted text-uppercase fw-bold d-block small mb-1">Teléfono Alternativo</label>
                            <span class="fw-bold text-dark">{{ $cliente->telefono_alt ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-top pt-3">
                            <label class="text-muted text-uppercase fw-bold d-block small mb-1">Correo Electrónico</label>
                            <span class="fw-bold text-dark">{{ $cliente->email ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-top pt-3">
                            <label class="text-muted text-uppercase fw-bold d-block small mb-1">Ubicación</label>
                            <span class="fw-bold text-dark uppercase">{{ $cliente->estado->nombre ?? 'N/A' }} / {{ $cliente->ciudad->nombre ?? 'N/A' }}</span>
                        </div>
                        <div class="col-12 border-top pt-3">
                            <label class="text-muted text-uppercase fw-bold d-block small mb-1">Dirección Fiscal</label>
                            <span class="fw-bold text-dark uppercase">{{ $cliente->direccion ?? 'N/A' }}</span>
                        </div>
                        <div class="col-12 border-top pt-3 bg-light p-3 rounded">
                            <label class="text-orange text-uppercase fw-bold d-block small mb-1">Dirección Operativa (Despacho)</label>
                            <span class="fw-bold text-dark uppercase">{{ $cliente->direccion_operativa ?? 'N/A' }}</span>
                        </div>
                        <div class="mb-2">
                            @if($cliente->es_aliado_comercial)
                                <span class="badge bg-primary text-white px-3 py-2 rounded-pill fw-bold">
                                    <i class="fas fa-handshake me-1"></i> Aliado Comercial (Aplica para prestamos de combustibles)
                                </span>
                            @else
                                <span class="badge bg-secondary text-white px-3 py-2 rounded-pill fw-bold">
                                    <i class="fas fa-building me-1"></i> No es Aliado Comercial (No aplica para prestamos de combustibles)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- HISTORIAL DE PEDIDOS --}}
            <div class="mb-8">
                <h2 class="text-sm font-black uppercase text-gray-700 tracking-widest mb-4">
                    <span class="text-orange-impordiesel">|</span> Historial de Pedidos Recientes
                </h2>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    {{-- CONTENEDOR CON SCROLL --}}
                    <div class="overflow-y-auto max-h-[400px]">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gray-industrial text-white text-[10px] font-black uppercase tracking-widest">
                                    <th class="px-6 py-3">ID Pedido</th>
                                    <th class="px-6 py-3">Tipo de Combustible</th>
                                    <th class="px-6 py-3 text-center">Litros Solicitados</th>
                                    <th class="px-6 py-3 text-center">Estatus</th>
                                    <th class="px-6 py-3 text-center">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($pedidos as $pedido)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-3 font-black text-gray-700">
                                        #{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-6 py-3 font-bold text-gray-600 uppercase text-[10px]">
                                        Combustible
                                    </td>
                                    <td class="px-6 py-3 text-center font-black text-gray-800">
                                        {{ number_format($pedido->cantidad_solicitada, 0, ',', '.') }} Lts
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="px-2 py-1 rounded text-[9px] font-black uppercase border" 
                                            style="background-color: {{ $pedido->estado_color }}20; color: {{ $pedido->estado_color }}; border-color: {{ $pedido->estado_color }}40;">
                                            {{ $pedido->estado_text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center text-gray-500 font-bold">
                                        {{ $pedido->fecha_solicitud ? $pedido->fecha_solicitud->format('d/m/Y') : 'N/A' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 font-black uppercase text-[10px] tracking-widest">
                                        No se encontraron pedidos.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- PAGINACIÓN --}}
                    <div class="p-3 bg-gray-50 border-t border-gray-200">
                        {{ $pedidos->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>

            {{-- --- INICIO BLOQUE SUCURSALES --- --}}
            @if($cliente->es_padre)
            <div class="mt-4 bg-white rounded border-2 border-gray-300 shadow-md overflow-hidden mb-8">
                <div class="bg-gray-800 p-3">
                    <h5 class="text-[10px] font-black uppercase text-orange-impordiesel italic">
                        <i class="fas fa-sitemap mr-2"></i> Sucursales Vinculadas
                    </h5>
                </div>
                <div class="overflow-y-auto max-h-[300px]">
                    <table class="w-full text-[10px] font-black uppercase border-collapse">
                        <thead class="bg-gray-100 border-b border-gray-300 sticky top-0 z-10">
                            <tr>
                                <th class="p-3 text-left">Razón Social</th>
                                <th class="p-3 text-right">Expediente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sucursales as $sucursal)
                            <tr class="border-b border-gray-200 hover:bg-orange-50 transition">
                                <td class="p-3 text-gray-700">{{ $sucursal->nombre }}</td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('clientes.show', $sucursal) }}" 
                                    class="bg-gray-industrial text-white px-2 py-1 rounded hover:bg-black transition">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-gray-50 border-t border-gray-200">
                    {{ $sucursales->appends(request()->query())->links() }}
                </div>
            </div>
            @endif
            {{-- --- FIN BLOQUE SUCURSALES --- --}}

            {{-- PLACAS Y CHOFERES --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 no-print">

                {{-- PLACAS --}}
                <div class="bg-white rounded border-2 border-gray-300 shadow-md flex flex-col h-[380px]">
                    <div class="bg-gray-800 p-3 flex justify-between items-center">
                        <h5 class="text-[10px] font-black uppercase text-orange-impordiesel italic">
                            <i class="fas fa-truck-moving mr-2"></i> Placas Autorizadas
                        </h5>
                        <span class="bg-orange-impordiesel text-white text-[9px] px-2 py-0.5 rounded-full font-black">
                            {{ $cliente->placas->count() }}
                        </span>
                    </div>
                    <div class="p-2 border-b">
                        <input type="text" id="filterPlacas" onkeyup="filtrarLista('filterPlacas', 'containerPlacas')"
                               class="w-full px-3 py-2 bg-gray-100 border-none rounded text-[10px] font-black uppercase outline-none focus:ring-1 focus:ring-orange-impordiesel"
                               placeholder="Buscar placa...">
                    </div>
                    <div class="flex-1 overflow-y-auto p-2" id="containerPlacas">
                        @forelse($cliente->placas as $placa)
                            <div class="activo-item flex justify-between items-center p-2 mb-1 bg-gray-50 border border-gray-200 rounded hover:border-orange-impordiesel transition">
                                <span class="text-xs font-black text-gray-700 tracking-widest">{{ $placa->placa }}</span>
                                <form action="{{ route('clientes.placas.inactivar', $placa->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-400 hover:text-red-600 text-[10px] font-black uppercase transition"
                                            onclick="return confirm('¿Inactivar esta placa?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-[10px] text-gray-400 text-center mt-10 font-bold uppercase italic">Sin placas registradas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-dark d-flex justify-content-between align-items-center py-2">
                            <span class="text-info fw-bold small text-uppercase"><i class="fas fa-id-card me-2"></i>Personal</span>
                            <span class="badge bg-info rounded-pill">{{ $cliente->choferes->count() }}</span>
                        </div>
                        <div class="p-2">
                            <input type="text" id="filterChoferes" onkeyup="filtrarLista('filterChoferes', 'containerChoferes')"
                                   class="form-control form-control-sm text-uppercase fw-bold border-0 bg-light" placeholder="Buscar personal...">
                        </div>
                        <div class="card-body p-2 overflow-auto" id="containerChoferes" style="max-height: 250px;">
                            @forelse($cliente->choferes as $chofer)
                                <div class="activo-item d-flex justify-content-between align-items-center p-2 mb-1 bg-white border rounded">
                                    <div>
                                        <div class="fw-bold text-dark small uppercase">{{ $chofer->nombre_completo }}</div>
                                        <div class="text-muted" style="font-size: 9px;">C.I: {{ $chofer->cedula }}</div>
                                    </div>
                                    <form action="{{ route('clientes.choferes.inactivar', $chofer->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-link text-danger p-0" onclick="return confirm('¿Inactivar?')"><i class="fas fa-times"></i></button>
                                    </form>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted small italic">Sin personal.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            {{-- ========================================== --}}
            {{-- BLOQUE ARCHIVERO DIGITAL --}}
            {{-- ========================================== --}}
           <div class="card shadow-sm border-0 mt-5 mb-4 no-print">
                <div class="card-header bg-dark py-3 d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0 fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">
                        <i class="fas fa-folder-open text-orange me-2"></i> Archivero Digital del Cliente
                    </h5>
                    <span class="badge bg-orange text-dark fw-black px-3 py-2" style="font-size: 11px;">
                        {{ $espacioUsadoMb }} MB / 120 MB
                    </span>
                </div>
                
                <div class="card-body p-4 bg-light">
                    
                    {{-- SECCIÓN 1: FORMULARIO DE CARGA --}}
                    <div class="bg-white p-3 border rounded shadow-sm mb-4">
                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                            <i class="fas fa-upload text-orange me-2 fs-5"></i>
                            <span class="fw-bold text-uppercase text-dark" style="font-size: 12px; letter-spacing: 0.5px;">
                                Subir nuevo documento PDF al expediente
                            </span>
                        </div>

                        <form action="{{ route('clientes.documentos.store') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
                            @csrf
                            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                            
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-muted text-uppercase mb-1" style="font-size: 10px;">Nombre descriptivo</label>
                                <input type="text" name="nombre_archivo" class="form-control text-uppercase fw-bold" placeholder="Ej: REGISTRO MERCANTIL" style="font-size: 13px;" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-muted text-uppercase mb-1" style="font-size: 10px;">Seleccionar archivo</label>
                                <input type="file" name="archivo" class="form-control" accept="application/pdf" style="font-size: 13px;" required>
                            </div>
                            <div class="col-md-2 mt-md-4 pt-md-2">
                                <button type="submit" class="btn btn-dark w-100 fw-bold text-uppercase" style="font-size: 12px; height: 38px;">
                                    <i class="fas fa-plus-circle me-1 text-orange"></i> Cargar
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- DIVISOR VISUAL FUERTE --}}
                    <div class="my-4 border-top border-2 border-secondary opacity-25"></div>

                    {{-- SECCIÓN 2: LISTADO DE ARCHIVOS GUARDADOS --}}
                    <div class="bg-white border rounded shadow-sm overflow-hidden">
                        <div class="bg-secondary bg-opacity-10 px-3 py-2 border-bottom d-flex align-items-center">
                            <i class="fas fa-file-pdf text-danger me-2 fs-5"></i>
                            <span class="fw-bold text-uppercase text-dark" style="font-size: 12px; letter-spacing: 0.5px;">
                                Documentos resguardados en el sistema
                            </span>
                        </div>

                        <div class="table-responsive" style="max-height: 350px;">
                            <table class="table table-hover table-striped align-middle mb-0" style="font-size: 13px;">
                                <thead class="table-dark text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                    <tr>
                                        <th class="px-4 py-3">Nombre del Documento</th>
                                        <th class="px-4 py-3 text-center" style="width: 150px;">Fecha de Carga</th>
                                        <th class="px-4 py-3 text-end" style="width: 150px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($documentos as $doc)
                                    <tr>
                                        <td class="px-4 py-3 fw-bold text-dark text-uppercase">
                                            <i class="far fa-file-pdf text-danger me-2 fs-5"></i> {{ $doc->nombre_archivo }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-muted fw-bold">
                                            {{ $doc->created_at->format('d/m/Y h:i A') }}
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <div class="d-flex justify-content-end gap-2" role="group">
                                                <a href="{{ route('clientes.documentos.download', $doc->id) }}" 
                                                class="btn btn-sm btn-outline-primary fw-bold text-uppercase px-3" 
                                                style="font-size: 11px;" title="Descargar PDF">
                                                    <i class="fas fa-download me-1"></i> Ver
                                                </a>
                                                
                                                <form action="{{ route('clientes.documentos.destroy', $doc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este archivo permanentemente del expediente del cliente?');">
                                                    @csrf 
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger fw-bold text-uppercase px-3" 
                                                            style="font-size: 11px;" title="Eliminar permanentemente">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-5 text-center text-muted fw-bold text-uppercase" style="font-size: 12px; letter-spacing: 0.5px;">
                                            <i class="fas fa-info-circle me-1 text-info"></i> No hay documentos digitales anexados a este expediente.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA: PANEL DE CONTROL --}}
        <div class="col-lg-4">
            
            {{-- ÁREA DEL TOKEN (AHORA SÍ DENTRO DE UNA CARD) --}}
            @if($cliente->parent == 0)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-uppercase small text-dark mb-3 border-bottom pb-1">
                            <i class="fas fa-link me-2 text-orange"></i>Token de Vinculación
                        </h6>
                        
                        @if($cliente->token_registro)
                            {{-- ESTADO CON TOKEN: Tu diseño original intacto --}}
                            <div class="d-flex align-items-center">
                                <span id="tokenAdmin" class="badge bg-light text-dark border p-2 fw-black fs-6 me-2">
                                    {{ $cliente->token_registro }}
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyTokenAdmin()" title="Copiar Token">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 10px;">
                                Código para registro de sucursales.
                            </small>
                        @else
                            {{-- ESTADO SIN TOKEN: Botón de asignación --}}
                            <form action="{{ route('clientes.generar-token', $cliente->id) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-dark fw-bold px-3 shadow-sm w-100">
                                    <i class="fas fa-key me-1"></i> ASIGNAR TOKEN
                                </button>
                            </form>
                            <small class="text-danger d-block mt-2 fw-bold text-center" style="font-size: 10px;">
                                <i class="fas fa-exclamation-circle"></i> Cliente antiguo sin token generado.
                            </small>
                        @endif
                    </div>
                </div>
            @endif

            {{-- PANEL DE ACCIONES DINÁMICAS --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    
                    {{-- AVANZAR PASO --}}
                    @if($cliente->status == \App\Models\Cliente::STATUS_EN_REGISTRO)
                        <h6 class="fw-bold text-uppercase small text-muted mb-3">Gestión de Registro</h6>
                        <form action="{{ route('clientes.avanzarPaso', $cliente->id) }}" method="POST" class="mb-4">
                            @csrf
                            <div class="mb-2">
                                <select name="paso" class="form-select form-select-sm fw-bold border-orange">
                                    @foreach(\App\Models\RegistroPaso::activos()->get() as $paso)
                                        <option value="{{ $paso->id }}" {{ $cliente->registro_paso == $paso->id ? 'selected' : '' }}>
                                            Paso {{ $paso->orden }}: {{ $paso->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 btn-sm fw-bold">ACTUALIZAR ETAPA</button>
                        </form>

                        {{-- BLOQUE APROBAR --}}
                        @if($cliente->status == \App\Models\Cliente::STATUS_EN_REGISTRO)
                        <div class="p-3 border-2 border-success rounded mb-3 bg-light">
                            <h6 class="text-success fw-bold text-uppercase small mb-3">
                                <i class="fas fa-check-circle me-1"></i>Aprobar Cliente
                            </h6>
                            <p class="text-muted mb-3" style="font-size: 11px;">
                                Al aprobar, el cliente podrá solicitar <strong>MGO</strong> por los canales regulares. 
                                Si requiere <strong>Diesel</strong>, use la sección de "Cupo General" abajo.
                            </p>
                            <form action="{{ route('clientes.aprobar', $cliente->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 btn-sm fw-bold shadow-sm">
                                    CONFIRMAR APROBACIÓN
                                </button>
                            </form>
                        </div>
                        @endif
                    @endif

                    {{-- GESTIÓN DE CUPO GENERAL (SIAVCOM) --}}
                    @if($cliente->status == \App\Models\Cliente::STATUS_APROBADO)
                        <div class="card shadow-sm border-orange mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-uppercase small text-dark mb-3 border-bottom pb-1">
                                    <i class="fas fa-shield-alt me-2 text-orange"></i>Cupo General (SIAVCOM)
                                </h6>
                                
                                {{-- Visualización de Valores Actuales (Simétrico a GASCO) --}}
                                <div class="row mb-3">
                                    <div class="col-6 border-end text-center">
                                        <span class="text-[10px] text-muted text-uppercase fw-bold d-block">Cupo Aprobado</span>
                                        <span class="fs-5 fw-black text-dark">{{ number_format($cliente->cupo ?? 0, 0) }} L</span>
                                    </div>
                                    <div class="col-6 text-center">
                                        <span class="text-[10px] text-muted text-uppercase fw-bold d-block">Saldo Disponible</span>
                                        <span class="fs-5 fw-black text-orange">{{ number_format($cliente->disponible ?? 0, 0) }} L</span>
                                    </div>
                                </div>

                                <form action="{{ route('clientes.ajustarCupo', $cliente->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="small fw-bold text-muted uppercase">Configurar Producto y Cantidad</label>
                                        <div class="row g-1"> {{-- Uso de row pequeña para alinear select e input --}}
                                            <div class="col-5">
                                                <select name="tipo_combustible_id" required class="form-select form-select-sm fw-bold border-orange h-100">
                                                    <option value="" disabled {{ !$cliente->cupo ? 'selected' : '' }}>Producto...</option>
                                                    @foreach($tiposCombustible as $tipo)
                                                        @if($tipo->id != 1) {{-- Excluir MGO --}}
                                                            <option value="{{ $tipo->id }}" {{ ($cliente->cupo > 0 && $tipo->id == 2) ? 'selected' : '' }}>
                                                                {{ $tipo->nombre }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-7">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="litros_aprobados" 
                                                        class="form-control fw-bold border-orange" 
                                                        placeholder="Cant. Litros" 
                                                        value="{{ $cliente->cupo }}"
                                                        required>
                                                    <button class="btn btn-dark fw-bold" type="submit">
                                                        <i class="fas fa-save me-1"></i> GUARDAR
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted italic" style="font-size: 10px;">
                                                <i class="fas fa-info-circle me-1"></i> Este cupo define el límite máximo para la asignación de GASCO.
                                            </small>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- GESTIÓN DE CUPO GASCO --}}
                    @if($cliente->status == \App\Models\Cliente::STATUS_APROBADO)
                        @php
                            $cupoGeneralLimit = $cliente->cupo ?? 0;
                        @endphp

                        {{-- Inicializamos Alpine.js en la Card --}}
                        <div class="card shadow-sm border-orange mb-4" 
                             x-data="{ 
                                 cupoSiavcom: {{ $cupoGeneralLimit }}, 
                                 cupoGasco: {{ $infoGasco['autorizados'] ?? 0 }},
                                 get errorCupo() {
                                     // Solo hay error si SIAVCOM >= 1 Y Gasco lo supera
                                     if (this.cupoSiavcom >= 1) {
                                         return parseFloat(this.cupoGasco) > parseFloat(this.cupoSiavcom);
                                     }
                                     return false; // Si SIAVCOM es 0, es libre
                                 }
                             }">
                            <div class="card-body">
                                <h6 class="fw-bold text-uppercase small text-dark mb-3 border-bottom pb-1">
                                    <i class="fas fa-gas-pump me-2 text-orange"></i>Cupo Mensual GASCO
                                </h6>
                                 
                                {{-- Visualización de Valores Actuales --}}
                                <div class="row mb-3">
                                    <div class="col-6 border-end text-center">
                                        <span class="text-[10px] text-muted text-uppercase fw-bold d-block">Asignado Mes</span>
                                        <span class="fs-5 fw-black text-dark">{{ number_format($infoGasco['autorizados'], 0) }} L</span>
                                    </div>
                                    <div class="col-6 text-center">
                                        <span class="text-[10px] text-muted text-uppercase fw-bold d-block">Disponible</span>
                                        <span class="fs-5 fw-black text-success">{{ number_format($infoGasco['disponible'], 0) }} L</span>
                                    </div>
                                </div>

                                <form action="{{ route('clientes.gasco.asignar', $cliente->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="small fw-bold text-muted uppercase">Actualizar Cantidad</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="litros_autorizados" 
                                                class="form-control fw-bold border-orange @error('litros_autorizados') is-invalid @enderror" 
                                                :class="errorCupo ? 'is-invalid text-danger' : ''"
                                                placeholder="Ej: 5000" 
                                                x-model.number="cupoGasco"
                                                required>
                                            {{-- El botón se desactiva si hay error --}}
                                            <button class="btn btn-dark fw-bold" type="submit" :disabled="errorCupo">
                                                <i class="fas fa-save me-1"></i> GUARDAR
                                            </button>
                                        </div>
                                        
                                        {{-- Alerta en tiempo real de Alpine.js --}}
                                        <template x-if="errorCupo">
                                            <span class="text-danger d-block mt-1 fw-bold" style="font-size: 10px;">
                                                El cupo GASCO no puede superar el Cupo SIAVCOM (<span x-text="cupoSiavcom"></span> L).
                                            </span>
                                        </template>

                                        {{-- Alerta del backend de Laravel (por si acaso) --}}
                                        @error('litros_autorizados')
                                            <span class="invalid-feedback d-block mt-1 fw-bold" style="font-size: 10px;">{{ $message }}</span>
                                        @enderror

                                        <div class="mt-1">
                                            <small class="text-muted italic" style="font-size: 10px;">
                                                Límite: >= 100 
                                                {{-- Texto dinámico para explicar la regla al usuario --}}
                                                <template x-if="cupoSiavcom >= 1">
                                                    <span>y máx. {{ number_format($cupoGeneralLimit, 0) }} L (SIAVCOM)</span>
                                                </template>
                                                <template x-if="cupoSiavcom < 1">
                                                    <span class="text-success fw-bold">(Sin límite superior)</span>
                                                </template>
                                            </small>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- REGISTRO RÁPIDO PLACA/CHOFER --}}
                    @if($cliente->status == \App\Models\Cliente::STATUS_APROBADO)
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="fw-bold text-uppercase small mb-3">Asignación Rápida</h6>
                            <form action="{{ route('clientes.placas.store', $cliente->id) }}" method="POST" class="mb-3">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" name="placa" maxlength="8" required class="form-control text-uppercase" placeholder="Nueva Placa">
                                    <button class="btn btn-dark" type="submit"><i class="fas fa-plus"></i></button>
                                </div>
                            </form>
                            <form action="{{ route('clientes.choferes.store', $cliente->id) }}" method="POST">
                                @csrf
                                <input type="text" name="nombre_completo" required class="form-control form-control-sm text-uppercase mb-1" placeholder="Nombre Chofer">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="cedula" required class="form-control" placeholder="Cédula">
                                    <button class="btn btn-info text-white" type="submit"><i class="fas fa-plus"></i></button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
            {{-- ========================================== --}}
            {{-- BOTONES DE HABILITAR / INHABILITAR CLIENTE --}}
            {{-- ========================================== --}}

            @if($cliente->status == \App\Models\Cliente::STATUS_APROBADO)
                <div class="card shadow-sm border-danger mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-uppercase small text-danger mb-3 border-bottom pb-1">
                            <i class="fas fa-ban me-2"></i>Inhabilitar Cliente
                        </h6>
                        <p class="text-muted mb-3" style="font-size: 11px;">
                            Al inhabilitar, este cliente <strong>{{ $cliente->es_padre ? 'y TODAS sus sucursales vinculadas perderán' : 'perderá' }}</strong> el acceso para realizar nuevos pedidos de combustible.
                        </p>
                        <form action="{{ route('clientes.inactivar', $cliente->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100 btn-sm fw-bold shadow-sm" 
                                    onclick="return confirm('¿Estás seguro de inhabilitar a este cliente?')">
                                INHABILITAR
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($cliente->status == \App\Models\Cliente::STATUS_INACTIVO)
                <div class="card shadow-sm border-success mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-uppercase small text-success mb-3 border-bottom pb-1">
                            <i class="fas fa-check-circle me-2"></i>Reactivar Cliente
                        </h6>
                        <p class="text-muted mb-3" style="font-size: 11px;">
                            Restaura el acceso para que el cliente pueda volver a operar y solicitar pedidos en la plataforma.
                        </p>
                        <form action="{{ route('clientes.reactivar', $cliente->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 btn-sm fw-bold shadow-sm" 
                                    onclick="return confirm('¿Estás seguro de habilitar nuevamente a este cliente?')">
                                HABILITAR / REACTIVAR
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function filtrarLista(inputId, containerId) {
        const filtro = document.getElementById(inputId).value.toUpperCase();
        const items = document.getElementById(containerId).getElementsByClassName('activo-item');
        for (let i = 0; i < items.length; i++) {
            items[i].style.display = (items[i].textContent || items[i].innerText).toUpperCase().includes(filtro) ? '' : 'none';
        }
    }

    function copyTokenAdmin() {
        const t = document.getElementById('tokenAdmin').innerText.trim();
        navigator.clipboard.writeText(t).then(() => {
            alert('Token copiado al portapapeles');
        });
    }
</script>
@endsection