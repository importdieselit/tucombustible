@extends('layouts.app')
@section('title', 'Auditoría de Varillaje')

@section('content')
@php
    $formasGeometricas = [
        'CH' => 'Cilíndrico Horizontal',
        'CV' => 'Cilíndrico Vertical',
        'OH' => 'Oval Horizontal',
        'OV' => 'Oval Vertical',
        'R'  => 'Rectangular',
        'C'  => 'Cúbico',
        'E'  => 'Esférico'
    ];
@endphp

<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex justify-content-between align-items-end">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-eye-dropper text-orange me-2"></i> Auditoría de Varillaje de Tanques
            </h2>
            <p class="text-muted small mb-0">Control físico, cubicación instantánea y validación de existencias en tiempo real.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-dark text-white p-2 text-uppercase fw-bold" style="font-size: 12px;">
                <i class="fas fa-user-shield text-orange me-1"></i> Auditor: {{ auth()->user()->name }}
            </span>
        </div>
    </div>

    {{-- FILTROS POR SEDE (CONTROLADOR DE FLUJO GET) --}}
    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Ubicación a Evaluar</label>
            <select name="id_sede" class="form-select form-select-sm fw-bold" style="font-size: 13px;" onchange="this.form.submit()">
                <option value="">SELECCIONE UNA SEDE PARA AUDITAR</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id }}" {{ request('id_sede') == $sede->id ? 'selected' : '' }}>
                        {{ $sede->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        
        @if(request('id_sede'))
            <div class="col-md-1">
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold text-uppercase" style="font-size: 12px; padding: 5px 0;">
                    Limpiar
                </a>
            </div>
        @endif
    </form>

    {{-- ESTADO ACTIVO: SEDE SELECCIONADA --}}
    @if(request('id_sede'))
        
        {{-- ALERTA DE SEGURIDAD DE TIEMPO INMUTABLE --}}
        <div class="alert bg-white shadow-sm d-flex align-items-center mb-4 py-3" style="border-left: 4px solid #ff6600; border-radius: 4px;">
            <i class="fas fa-clock text-orange fa-lg me-3"></i>
            <div>
                <strong class="text-dark d-block" style="font-size: 14px;">Sello de Auditoría Blindado</strong>
                <span class="text-muted small">Este registro se guardará automáticamente con la fecha y hora exacta del servidor. No se permiten modificaciones extemporáneas.</span>
            </div>
        </div>

        {{-- CONTENEDOR DEL GEMELO DIGITAL 3D --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-black text-uppercase small text-dark">
                    <i class="fas fa-cube text-orange me-2"></i> Monitoreo Volumétrico (Gemelo Digital 3D)
                </h6>
            </div>
            <div class="card-body p-0 bg-dark position-relative">
                <div id="canvas-3d" style="width: 100%; height: 400px;"></div>
                <div id="3d-tooltip" class="position-absolute p-3 bg-white rounded shadow-lg border" style="display: none; z-index: 100; pointer-events: none; min-width: 200px; border-left: 4px solid #ff6600 !important;"></div>
                
                {{-- LEYENDA --}}
                <div class="position-absolute bottom-0 start-0 m-3 text-white p-2 rounded d-flex align-items-center gap-3" style="z-index: 10; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: #ffa500;"></span>
                        <span class="fw-bold text-light" style="font-size: 11px;">DIESEL</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: #00a8ff;"></span>
                        <span class="fw-bold text-light" style="font-size: 11px;">MGO</span>
                    </div>
                </div>
            </div>
        </div>


        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i> Por favor corrige los siguientes errores:</div>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- FORMULARIO PRINCIPAL DE ENVÍO POST --}}
        <form action="{{ route('combustibles.chequeos_depositos.store') }}" method="POST">
            @csrf
            <input type="hidden" name="id_sede" value="{{ request('id_sede') }}">

            {{-- 1. SELECCIÓN DE TURNO --}}
            <div class="card shadow-sm border-0 mb-3 bg-light">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label class="small fw-black text-uppercase text-muted mb-0">
                                <i class="fas fa-sun text-orange me-1"></i> Turno Operativo
                            </label>
                        </div>
                        <div class="col-md-4">
                            <select name="turno" class="form-select form-select-sm fw-bold" required>
                                <option value="">Seleccione...</option>
                                <option value="Matutino" {{ old('turno') == 'Matutino' ? 'selected' : '' }}>Matutino</option>
                                <option value="Nocturno" {{ old('turno') == 'Nocturno' ? 'selected' : '' }}>Nocturno</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABLA DE REGISTRO OBLIGATORIO --}}
            <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-black text-uppercase small text-dark">
                        <i class="fas fa-list-check text-orange me-2"></i> Captura Forzosa de Medidas en Patio
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                                    <th class="ps-4">Tanque / Serial</th>
                                    <th>Geometría</th>
                                    <th>Capacidad Máxima</th>
                                    <th style="width: 280px;">Combustible Presente</th>
                                    <th style="width: 200px;">Medición Vara (CM)</th>
                                    <th class="text-end pe-4" style="width: 180px;">Litros Cubitados</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($depositos as $deposito)
                                    @php
                                        $altoMaximoTanque = ($deposito->forma == 'R' || $deposito->forma == 'OV' || $deposito->forma == 'C') ? $deposito->alto : $deposito->diametro;
                                    @endphp

                                    <tr data-tanque-id="{{ $deposito->id }}" class="fila-tanque">
                                        <td class="ps-4">
                                            <span class="fw-black text-dark d-block" style="font-size: 15px;">{{ $deposito->serial }}</span>
                                            <small class="text-muted text-uppercase font-monospace" style="font-size: 11px;">ID: #{{ $deposito->id }}</small>
                                            <input type="hidden" name="detalles[{{ $loop->index }}][id_deposito]" value="{{ $deposito->id }}">
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                                {{ $formasGeometricas[$deposito->forma] ?? $deposito->forma }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-secondary" style="font-size: 14px;">
                                                {{ number_format($deposito->capacidad_maxima, 2, ',', '.') }} Lts
                                            </span>
                                        </td>
                                        <td>
                                            {{-- SELECT DINÁMICO DE COMBUSTIBLE CON ATRIBUTOS DATA PARA THREE.JS --}}
                                            <select name="detalles[{{ $loop->index }}][id_tipos_combustible]" class="form-select form-select-sm select-combustible" data-tanque-id="{{ $deposito->id }}">
                                                @foreach($tiposCombustible as $tipo)
                                                    @php
                                                        // Evaluamos la persistencia: 1. Datos enviados fallidos (old) -> 2. Campo singular -> 3. Campo plural (por si acaso)
                                                        $selectedId = old("detalles.{$loop->index}.id_tipos_combustible")  
                                                            ?? $deposito->ultima_medicion->id_tipos_combustible 
                                                            ?? '';
                                                    @endphp
                                                    <option value="{{ $tipo->id }}" 
                                                        data-nombre="{{ strtoupper($tipo->nombre) }}"
                                                        {{ $selectedId == $tipo->id ? 'selected' : '' }}>
                                                        {{ $tipo->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       name="detalles[{{ $loop->index }}][centimetros_medidos]" 
                                                       class="form-select form-select-sm fw-black text-center input-centimetros" 
                                                       step="0.01" 
                                                       min="0" 
                                                       max="{{ $altoMaximoTanque ?? 500 }}"
                                                       value="{{ $deposito->ultima_medicion->centimetros_medidos ?? '' }}"
                                                       placeholder="0.00"
                                                       data-tanque-id="{{ $deposito->id }}"
                                                       data-alto-max="{{ $altoMaximoTanque ?? 250 }}"
                                                       required 
                                                       style="font-size: 14px; border-radius: 4px 0 0 4px;">
                                                <span class="input-group-text bg-light fw-bold text-muted" style="font-size: 11px;">CM</span>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <span class="fw-black text-orange visor-litros" id="visor_litros_{{ $deposito->id }}" style="font-size: 16px;">
                                                {{ isset($deposito->ultima_medicion->litros_calculados) ? number_format($deposito->ultima_medicion->litros_calculados, 2, ',', '.') : '0,00' }}
                                            </span>
                                            <span class="text-muted small fw-bold">Lts</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted small fw-bold">
                                            <i class="fas fa-info-circle me-1 text-warning"></i> Seleccione una Sede Operativa para listar los tanques asociados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- BLOQUE DE NOVEDADES Y CIERRE --}}
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <label class="small fw-black text-uppercase text-muted mb-2 d-block" style="font-size: 12px;">
                                <i class="fas fa-comment-alt me-1 text-orange"></i> Observaciones de Patio e Incidencias
                            </label>
                            <textarea name="observaciones" rows="3" class="form-control" placeholder="Escriba aquí novedades (Ej. Variaciones climáticas, estado físico de la vara, condiciones de seguridad del patio)..." style="font-size: 13px; border-radius: 6px;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-stretch">
                    <div class="card shadow-sm border-0 w-100 bg-dark text-white">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                            <i class="fas fa-file-signature text-orange fa-2x mb-2"></i>
                            <h5 class="fw-black text-uppercase mb-1" style="font-size: 14px; letter-spacing: 0.5px;">Cierre de Lote Seguro</h5>
                            <p class="text-muted small mb-3">Al procesar, se guardará la estructura completa de manera obligatoria.</p>
                            <button type="submit" class="btn btn-warning w-100 fw-black text-uppercase py-2 shadow" style="color: #000; font-size: 13px; background-color: #ff6600; border-color: #ff6600;">
                                <i class="fas fa-check-circle me-1"></i> Registrar y Firmar Varillaje
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    @else
        {{-- ESTADO VACÍO: ADVERTENCIA INICIAL --}}
        <div class="card-body py-5 text-center bg-light border border-dashed rounded shadow-sm">
            <div class="py-5">
                <i class="fas fa-clipboard-check text-muted fa-3x mb-3 opacity-50"></i>
                <h5 class="fw-bold text-secondary text-uppercase mb-1" style="font-size: 14px;">Formulario de Varillaje no Iniciado</h5>
                <p class="text-muted small mb-0">Seleccione una Sede operativa específica en el panel superior para desplegar los tanques y registrar las lecturas.</p>
            </div>
         </div>
    @endif
</div>

{{-- CARGA SCRIPT DE RENDERIZADO 3D (ARQUITECTURA PADRE-HIJO OPTIMIZADA) --}}
@if(request('id_sede'))
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tanquesRaw = @json($depositos instanceof \Illuminate\Pagination\LengthAwarePaginator ? $depositos->items() : $depositos);
            const tanquesData = tanquesRaw.sort((a, b) => a.serial.localeCompare(b.serial));

            const container = document.getElementById('canvas-3d');
            const tooltip = document.getElementById('3d-tooltip');
            const fluidMeshes = {};
            const clippingPlanes = {};

            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0x111116);

            const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.set(0, 10, 15);
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            renderer.localClippingEnabled = true; // Fundamental para el nivel de líquido
            container.appendChild(renderer.domElement);

            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.maxPolarAngle = Math.PI / 2 - 0.05;
            controls.minDistance = 3;
            controls.maxDistance = 30;
            
            scene.add(new THREE.AmbientLight(0xffffff, 0.5));
            const dirLight = new THREE.DirectionalLight(0xffffff, 0.8);
            dirLight.position.set(10, 25, 15);
            scene.add(dirLight);
            const gridHelper = new THREE.GridHelper(50, 50, 0xff6600, 0x222226);
            scene.add(gridHelper);

            const objetoTanquesMeshes = [];
            let filaEjeX = -(tanquesData.length * 3.5) / 2;

            tanquesData.forEach(tanque => {
                let geometry, fluidGeometry;
                
                const largo = (tanque.longitud || tanque.largo || 300) / 100;
                const ancho = (tanque.ancho || 200) / 100;
                const alto = (tanque.alto || tanque.alto_total || 250) / 100;
                const diametro = (tanque.diametro || 200) / 100;
                const radio = diametro / 2;

                // 1. GEOMETRÍAS CON SOPORTE TOTAL (INCLUYENDO ÓVALOS)
                if (tanque.forma === 'R' || tanque.forma === 'C') {
                    geometry = new THREE.BoxGeometry(ancho, alto, largo);
                    fluidGeometry = new THREE.BoxGeometry(ancho * 0.99, alto * 0.99, largo * 0.99);
                } else if (tanque.forma === 'CV' || tanque.forma === 'OV') {
                    geometry = new THREE.CylinderGeometry(radio, radio, alto, 32);
                    fluidGeometry = new THREE.CylinderGeometry(radio * 0.98, radio * 0.98, alto * 0.98, 32);
                } else if (tanque.forma === 'CH' || tanque.forma === 'OH') {
                    geometry = new THREE.CylinderGeometry(radio, radio, largo, 32);
                    fluidGeometry = new THREE.CylinderGeometry(radio * 0.98, radio * 0.98, largo * 0.98, 32);
                } else {
                    geometry = new THREE.SphereGeometry(radio, 32, 32);
                    fluidGeometry = new THREE.SphereGeometry(radio * 0.98, 32, 32);
                }

                // 2. DETECTAR COMBUSTIBLE ACTIVO DESDE EL DOM
                const selectElement = document.querySelector(`.select-combustible[data-tanque-id="${tanque.id}"]`);
                let colorFluido = 0xffa500; // Diesel por defecto (Naranja)
                if (selectElement) {
                    const opt = selectElement.options[selectElement.selectedIndex];
                    const nombreComb = opt ? (opt.getAttribute('data-nombre') || '') : '';
                    if (nombreComb && !nombreComb.includes('DIESEL')) {
                        colorFluido = 0x00a8ff; // MGO (Azul)
                    }
                }

                // 3. CREAR CONTENEDOR (PADRE)
                const materialContenedor = new THREE.MeshStandardMaterial({
                    color: 0xffffff,
                    roughness: 0.2,
                    metalness: 0.4,
                    transparent: true,
                    opacity: 0.35,
                    side: THREE.DoubleSide
                });
                const tankMesh = new THREE.Mesh(geometry, materialContenedor);

                let yFija = alto / 2;
                let altoVisual = alto;

                if (tanque.forma === 'CH') {
                    tankMesh.rotation.z = Math.PI / 2;
                    yFija = radio;
                    altoVisual = diametro;
                } else if (tanque.forma === 'OV') {
                    tankMesh.scale.set(ancho / diametro, 1, 1);
                } else if (tanque.forma === 'OH') {
                    tankMesh.rotation.z = Math.PI / 2;
                    tankMesh.scale.set(1, 1, ancho / diametro);
                    yFija = radio;
                    altoVisual = diametro;
                }

                // 4. CREAR LÍQUIDO (HIJO)
                // Inicializamos al 0% (se actualizará en el loop de sincronización final si hay datos)
                const clipPlane = new THREE.Plane(new THREE.Vector3(0, -1, 0), yFija - (altoVisual / 2));
                clippingPlanes[tanque.id] = clipPlane;

                const materialLiquido = new THREE.MeshStandardMaterial({
                    color: colorFluido, 
                    roughness: 0.1,
                    metalness: 0.1,
                    clippingPlanes: [clipPlane],
                    side: THREE.DoubleSide
                });
                const fluidMesh = new THREE.Mesh(fluidGeometry, materialLiquido);
                fluidMesh.raycast = function() {}; // Desactiva colisiones directas al líquido

                // APLICAR JERARQUÍA NATIVA
                tankMesh.add(fluidMesh);

                const inicialX = tanque.orden_x !== null ? parseFloat(tanque.orden_x) : filaEjeX;
                const inicialZ = tanque.orden_z !== null ? parseFloat(tanque.orden_z) : 0;

                tankMesh.position.set(inicialX, yFija, inicialZ);
                
                tankMesh.userData = {
                    id: tanque.id,
                    serial: tanque.serial,
                    capacidad: parseFloat(tanque.capacidad_maxima).toLocaleString('es-VE'),
                    altoMax: altoVisual
                };

                scene.add(tankMesh);
                objetoTanquesMeshes.push(tankMesh);
                fluidMeshes[tanque.id] = fluidMesh;

                if (tanque.orden_x === null) {
                    filaEjeX += Math.max(ancho, diametro, largo) + 2.0;
                }
            });

            // FUNCIÓN DE ACTUALIZACIÓN DE FLUIDO
            function actualizarVolumenFisico(tanqueId, cms, altoMaxCms) {
                const tanqueData = tanquesData.find(t => t.id == tanqueId);
                if (!tanqueData) return;

                let porcentaje = cms / altoMaxCms;
                if (porcentaje > 1) porcentaje = 1;
                if (porcentaje < 0 || isNaN(porcentaje)) porcentaje = 0;

                const litrosTotales = parseFloat(tanqueData.capacidad_maxima);
                const litrosCalculados = litrosTotales * porcentaje;

                document.getElementById(`visor_litros_${tanqueId}`).innerText = litrosCalculados.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                const meshPadre = objetoTanquesMeshes.find(m => m.userData.id == tanqueId);
                if (meshPadre) {
                    const altoRealMesh = meshPadre.userData.altoMax;
                    const yBase = meshPadre.position.y;
                    const alturaCorteY = (yBase - (altoRealMesh / 2)) + (altoRealMesh * porcentaje);
                    clippingPlanes[tanqueId].constant = alturaCorteY;
                }
            }

            // LISTENERS DE INPUTS
            document.querySelectorAll('.input-centimetros').forEach(input => {
                input.addEventListener('input', function() {
                    const id = this.getAttribute('data-tanque-id');
                    const cms = parseFloat(this.value);
                    const altoMax = parseFloat(this.getAttribute('data-alto-max'));
                    actualizarVolumenFisico(id, cms, altoMax);
                });
            });

            // LISTENER CORRECTO PARA EL SELECT DE COMBUSTIBLE
            document.querySelectorAll('.select-combustible').forEach(select => {
                select.addEventListener('change', function() {
                    const id = this.getAttribute('data-tanque-id');
                    const optionSeleccionada = this.options[this.selectedIndex];
                    const nombreCombustible = optionSeleccionada ? (optionSeleccionada.getAttribute('data-nombre') || '') : '';
                    
                    const fluidMeshRef = fluidMeshes[id];
                    if (fluidMeshRef) {
                        fluidMeshRef.material.color.setHex(nombreCombustible.includes('DIESEL') ? 0xffa500 : 0x00a8ff);
                    }
                });
            });

            // RAYCASTER PARA EL TOOLTIP (CLIC DIRECTO EN EL TANQUE)
            const raycaster = new THREE.Raycaster();
            const mouse = new THREE.Vector2();

            window.addEventListener('click', function(event) {
                if (event.target.tagName !== 'CANVAS') return;

                const rect = renderer.domElement.getBoundingClientRect();
                mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
                mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

                raycaster.setFromCamera(mouse, camera);
                const intersects = raycaster.intersectObjects(objetoTanquesMeshes);

                if (intersects.length > 0) {
                    const tanqueSeleccionado = intersects[0].object;
                    const data = tanqueSeleccionado.userData;

                    const inputCms = document.querySelector(`.input-centimetros[data-tanque-id="${data.id}"]`).value || '0';
                    
                    // Leer directamente desde el Select Dinámico
                    const selectComb = document.querySelector(`.select-combustible[data-tanque-id="${data.id}"]`);
                    const optionActiva = selectComb ? selectComb.options[selectComb.selectedIndex] : null;
                    const tipoActivo = optionActiva ? (optionActiva.getAttribute('data-nombre') || 'DIESEL') : 'DIESEL';
                    
                    const litrosVisor = document.getElementById(`visor_litros_${data.id}`).innerText;

                    tooltip.innerHTML = `
                        <div class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Panel de Auditoría</div>
                        <h6 class="fw-black text-dark text-uppercase mb-2" style="font-size: 14px;">
                            <i class="fas fa-gas-pump me-1" style="color: #ff6600;"></i> ${data.serial}
                        </h6>
                        <div class="mb-1" style="font-size: 12px;">
                            <strong class="text-secondary">Líquido Declarado:</strong> 
                            <span class="badge px-2 py-0.5 fw-bold text-uppercase ${tipoActivo.includes('DIESEL') ? 'bg-warning text-dark' : 'bg-info text-white'}" style="font-size: 10px;">${tipoActivo}</span>
                        </div>
                        <div class="mb-1" style="font-size: 12px;">
                            <strong class="text-secondary">Medida de Vara:</strong> 
                            <span class="fw-black text-dark font-monospace">${inputCms} CM</span>
                        </div>
                        <div style="font-size: 12px;">
                            <strong class="text-secondary">Volumen Actual:</strong> 
                            <span class="fw-black text-orange">${litrosVisor} Lts</span> / <span class="text-muted text-xs">${data.capacidad} Lts Máx</span>
                        </div>
                    `;

                    const xPos = event.clientX - rect.left + 15;
                    const yPos = event.clientY - rect.top + 15;
                    tooltip.style.left = `${xPos}px`;
                    tooltip.style.top = `${yPos}px`;
                    tooltip.style.display = 'block';
                } else {
                    tooltip.style.display = 'none';
                }
            });

            // SINCRONIZACIÓN INICIAL DE NIVELES (Si hay un varillaje previo en BD)
            setTimeout(() => {
                document.querySelectorAll('.input-centimetros').forEach(input => {
                    const id = input.getAttribute('data-tanque-id');
                    const cms = parseFloat(input.value);
                    const altoMax = parseFloat(input.getAttribute('data-alto-max'));
                    
                    if (!isNaN(cms) && cms > 0) {
                        actualizarVolumenFisico(id, cms, altoMax);
                    }
                });
            }, 300);

            // ANIMATION LOOP
            function animate() {
                requestAnimationFrame(animate);
                controls.update();
                renderer.render(scene, camera);
            }
            animate();

            // RESPONSIVE RESIZE
            window.addEventListener('resize', function () {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
        });
    </script>
@endif

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection