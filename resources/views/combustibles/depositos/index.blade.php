@extends('layouts.app')
@section('title', 'Infraestructura de Tanques')

@section('content')
{{-- DICCIONARIO DE FORMAS GEOMÉTRICAS --}}
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
                <i class="fas fa-database text-orange me-2"></i> Infraestructura de Tanques
            </h2>
            <p class="text-muted small mb-0">Gestión, cubicidad y control físico de almacenamiento de combustibles de ImporDiesel.</p>
        </div>
        <div>
            <a href="{{ route('combustibles.depositos.create') }}" class="btn btn-sm btn-dark fw-bold text-uppercase shadow-sm py-2 px-3" style="font-size: 12px;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Tanque
            </a>
        </div>
    </div>

    {{-- FILTROS POR SEDE --}}
    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Ubicación / Sede</label>
            <select name="id_sede" class="form-select form-select-sm fw-bold" style="font-size: 13px;" onchange="this.form.submit()">
                <option value="">SELECCIONE UNA SEDE</option>
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

    {{-- SECCIÓN DEL PLANO INTERACTIVO 3D --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-cubes text-orange me-2"></i> Diseñador de Patio de Tanques (3D Interactivo)
            </h6>
            @if(request('id_sede'))
                <span class="badge bg-light text-dark border px-3 py-2 text-uppercase fw-bold" style="font-size: 11px;">
                    <i class="fas fa-hand-rock text-orange me-1"></i> Arrastra los tanques para ubicarlos en el patio real
                </span>
            @endif
        </div>
        
        @if(request('id_sede'))
            <div class="card-body p-0 bg-dark position-relative" id="contenedor-render-3d">
                <div id="canvas-3d" style="width: 100%; height: 500px; cursor: default;"></div>
                
                {{-- Tooltip flotante --}}
                <div id="3d-tooltip" class="position-absolute p-3 bg-white rounded shadow-lg border" style="display: none; z-index: 100; min-width: 200px; border-left: 4px solid #ff6600 !important;"></div>
                
                {{-- BOTÓN FLOTANTE PARA GUARDAR MAPA --}}
                <div class="position-absolute top-0 end-0 m-3" id="wrapper-boton-guardar" style="display: none;">
                    <button type="button" id="btn-guardar-patio" class="btn btn-success fw-bold text-uppercase shadow-lg py-2 px-3" style="font-size: 12px; border-radius: 30px;">
                        <i class="fas fa-save me-1"></i> Guardar Distribución
                    </button>
                </div>

                {{-- Leyenda estática --}}
                <div class="position-absolute bottom-0 start-0 m-3 text-white p-2 rounded d-flex align-items-center gap-3 shadow-sm" 
                    style="z-index: 10; background-color: rgba(0,0,0,0.4); backdrop-filter: blur(2px);">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: #ffa500;"></span>
                        <span class="fw-bold text-light" style="font-size: 11px; letter-spacing: 0.5px;">DIESEL</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: #00a8ff;"></span>
                        <span class="fw-bold text-light" style="font-size: 11px; letter-spacing: 0.5px;">MGO</span>
                    </div>
                </div>
            </div>
        @else
            <div class="card-body py-5 text-center bg-light border border-dashed rounded-bottom">
                <div class="py-4">
                    <i class="fas fa-map-marked-alt text-muted fa-3x mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-secondary text-uppercase mb-1" style="font-size: 14px;">Plano Tridimensional no cargado</h5>
                    <p class="text-muted small mb-0">Seleccione una **Sede específica** para visualizar y ordenar su patio de distribución.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- TABLA DE TANQUES --}}
    <div class="card shadow-sm border-0" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-gas-pump text-orange me-2"></i> Historial de Tanques Registrados
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top" style="z-index: 10;">
                        <tr class="text-uppercase text-muted" style="font-size: 13px;">
                            <th class="ps-4">Nombre / Serial</th>
                            <th>Sede</th>
                            <th>Tipo de Combustible</th>
                            <th class="text-center">Capacidad Máxima</th>
                            <th class="text-center">Forma Geométrica</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($depositos as $deposito)
                            <tr>
                                <td class="ps-4 fw-black text-dark" style="font-size: 15px;">{{ $deposito->serial }}</td>
                                <td class="text-muted fw-bold" style="font-size: 14px;"><i class="fas fa-map-marker-alt text-secondary me-1"></i> {{ $deposito->sedes->nombre ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-dark text-white text-uppercase" style="font-size: 12px;">
                                        {{-- Primero intenta buscar en la última medición, si es null, busca en la configuración base --}}
                                        {{ $deposito->ultimaMedicion->tipoCombustible->nombre ?? ($deposito->tipoCombustible->nombre ?? 'Sin Asignar') }}
                                    </span>
                                </td>
                                <td class="text-center fw-black text-orange" style="font-size: 16px;">{{ number_format($deposito->capacidad_maxima, 2, ',', '.') }} <span class="text-muted fw-bold" style="font-size: 12px;">Lts</span></td>
                                <td class="text-center"><span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 12px;">{{ $formasGeometricas[$deposito->forma] ?? $deposito->forma }}</span></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('combustibles.depositos.edit', $deposito->id) }}" class="btn btn-sm btn-light border shadow-sm" title="Editar Geometría">
                                            <i class="fas fa-edit text-warning"></i>
                                        </a>
                                        
                                        <form action="{{ route('combustibles.depositos.destroy', $deposito->id) }}" 
                                            method="POST" 
                                            class="m-0" 
                                            onsubmit="return confirm('¿Estás seguro de que deseas eliminar el tanque de manera permanente? Esta acción borrará todas sus configuraciones asociadas.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border shadow-sm" title="Eliminar Tanque permanentemente">
                                                <i class="fas fa-trash-alt text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4">No hay tanques registrados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if(request('id_sede'))
    {{-- DEPENDENCIAS DE THREE.JS --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/DragControls.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tanquesRaw = @json($depositos instanceof \Illuminate\Pagination\LengthAwarePaginator ? $depositos->items() : $depositos);
            const tanquesData = tanquesRaw.sort((a, b) => a.serial.localeCompare(b.serial));

            const container = document.getElementById('canvas-3d');
            const tooltip = document.getElementById('3d-tooltip');
            const wrapperBoton = document.getElementById('wrapper-boton-guardar');
            const btnGuardar = document.getElementById('btn-guardar-patio');

            // ESCENA Y CÁMARA
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0x111116);

            const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.set(0, 10, 15);

            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            renderer.localClippingEnabled = true; // Habilita el recorte de fluido
            container.appendChild(renderer.domElement);

            // CONTROL DE ÓRBITA (CÁMARA)
            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.maxPolarAngle = Math.PI / 2 - 0.05;
            controls.minDistance = 3;
            controls.maxDistance = 30;

            // ILUMINACIÓN Y PISO
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

            // 1. CONSTRUCCIÓN DE GEOMETRÍAS SOPORTANDO TODAS LAS FORMAS
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

            // =========================================================================
            // RESOLUCIÓN DE COMBUSTIBLE EN TIEMPO REAL (IGUAL QUE LA TABLA HTML)
            // =========================================================================
            // Accedemos de forma segura tanto a snake_case como camelCase por si acaso
            const ultimaMedicion = tanque.ultima_medicion || tanque.ultimaMedicion;
            let tipoCombustibleReal = null;

            if (ultimaMedicion) {
                tipoCombustibleReal = ultimaMedicion.tipo_combustible || ultimaMedicion.tipoCombustible;
            }

            // Si no hay última medición, hacemos el fallback al combustible base de la configuración
            if (!tipoCombustibleReal) {
                tipoCombustibleReal = tanque.tipo_combustible || tanque.tipoCombustible;
            }

            // Extraemos el nombre definitivo
            const nombreCombustible = (tipoCombustibleReal && tipoCombustibleReal.nombre) 
                ? tipoCombustibleReal.nombre 
                : 'Sin Asignar';

            // Asignación dinámica del color del fluido basado en el nombre real
            let colorFluido = 0x00a8ff; // Por defecto: Azul MGO
            if (nombreCombustible.toUpperCase().includes('DIESEL')) {
                colorFluido = 0xffa500; // Naranja: Diesel
            }
            // =========================================================================

            // 2. CREACIÓN DE LA MALLA PADRE (El Metal del Tanque)
            const materialContenedor = new THREE.MeshStandardMaterial({
                color: 0xffffff,
                roughness: 0.2,
                metalness: 0.4,
                transparent: true,
                opacity: 0.35,
                side: THREE.DoubleSide
            });
            const tankMesh = new THREE.Mesh(geometry, materialContenedor);

            // Elevación base
            let yFija = alto / 2;
            let altoVisual = alto;
            let rotacionInicial = tanque.rotacion ? parseFloat(tanque.rotacion) : 0;

            // ROTACIONES Y ESCALAS PARA TANQUES ACOSTADOS U OVALADOS
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

            // 3. CREACIÓN DE LA MALLA HIJA (El Líquido)
            const nivelSimulado = 0.70; 
            const alturaCorteY = (yFija - (altoVisual / 2)) + (altoVisual * nivelSimulado);
            const clipPlane = new THREE.Plane(new THREE.Vector3(0, -1, 0), alturaCorteY);

            const materialLiquido = new THREE.MeshStandardMaterial({
                color: colorFluido, // <--- Aquí ya aplica el color corregido
                roughness: 0.1,
                metalness: 0.1,
                clippingPlanes: [clipPlane],
                side: THREE.DoubleSide
            });
            const fluidMesh = new THREE.Mesh(fluidGeometry, materialLiquido);

            fluidMesh.raycast = function() {}; 
            tankMesh.add(fluidMesh);

            // 4. POSICIONAR EL TANQUE MAESTRO EN EL PATIO
            const inicialX = tanque.orden_x !== null ? parseFloat(tanque.orden_x) : filaEjeX;
            const inicialZ = tanque.orden_z !== null ? parseFloat(tanque.orden_z) : 0;
            tankMesh.position.set(inicialX, yFija, inicialZ);
            tankMesh.rotation.y = THREE.MathUtils.degToRad(rotacionInicial);

            let medidasTexto = '';
            if (tanque.forma === 'R' || tanque.forma === 'C') {
                medidasTexto = `${tanque.ancho || 0} x ${tanque.alto || 0} x ${tanque.longitud || 0} cm`;
            } else if (tanque.forma === 'CV' || tanque.forma === 'OV') {
                medidasTexto = `Diámetro: ${tanque.diametro || 0} cm | Alto: ${tanque.alto || 0} cm`;
            } else if (tanque.forma === 'CH' || tanque.forma === 'OH') {
                medidasTexto = `Diámetro: ${tanque.diametro || 0} cm | Largo: ${tanque.longitud || 0} cm`;
            } else {
                medidasTexto = `${tanque.diametro || 0} cm`;
            }

            // Guardar datos en el tanque principal
            tankMesh.userData = {
                id: tanque.id,
                serial: tanque.serial,
                capacidad: parseFloat(tanque.capacidad_maxima).toLocaleString('es-VE'),
                combustible: nombreCombustible,
                medidas: medidasTexto,
                yFija: yFija,
                rotacion: rotacionInicial 
            };

            scene.add(tankMesh);
            objetoTanquesMeshes.push(tankMesh);

            if (tanque.orden_x === null) {
                filaEjeX += Math.max(ancho, diametro, largo) + 2.0;
            }
        });

            // -------------------------------------------------------------
            // ARRASTRE LIMPIO Y NATIVO
            // -------------------------------------------------------------
            const dragControls = new THREE.DragControls(objetoTanquesMeshes, camera, renderer.domElement);

            dragControls.addEventListener('dragstart', function () {
                controls.enabled = false;
                tooltip.style.display = 'none';
            });

            dragControls.addEventListener('drag', function (event) {
                // Bloqueamos nativamente el movimiento hacia arriba/abajo (Eje Y)
                event.object.position.y = event.object.userData.yFija;
                wrapperBoton.style.display = 'block';
            });

            dragControls.addEventListener('dragend', function () {
                controls.enabled = true;
            });

            // -------------------------------------------------------------
            // RAYCASTERS LIMPIOS (DOBLE CLIC Y CLIC)
            // -------------------------------------------------------------
            const raycaster = new THREE.Raycaster();
            const mouse = new THREE.Vector2();

            window.addEventListener('dblclick', function(event) {
                const rect = renderer.domElement.getBoundingClientRect();
                mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
                mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

                raycaster.setFromCamera(mouse, camera);
                // Ya no necesitamos recursividad porque DragControls solo evalúa el parent
                const intersects = raycaster.intersectObjects(objetoTanquesMeshes); 

                if (intersects.length > 0) {
                    const tanqueSeleccionado = intersects[0].object; 
                    controls.target.copy(tanqueSeleccionado.position);
                    camera.position.set(
                        tanqueSeleccionado.position.x, 
                        tanqueSeleccionado.position.y + 3, 
                        tanqueSeleccionado.position.z + 5  
                    );
                    controls.update();
                } else {
                    controls.target.set(0, 0, 0);
                    camera.position.set(0, 10, 15);
                    controls.update();
                }
            });
            
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

                    const esDiesel = data.combustible.toUpperCase().includes('DIESEL');
                    const badgeColor = esDiesel ? 'background-color: #ffa500; color: #000;' : 'background-color: #00a8ff; color: #fff;';

                    tooltip.innerHTML = `
                        <div class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Ficha de Infraestructura</div>
                        <h6 class="fw-black text-dark text-uppercase mb-2" style="font-size: 14px;">
                            <i class="fas fa-gas-pump me-1" style="color: #ff6600;"></i> ${data.serial}
                        </h6>
                        <div class="mb-1" style="font-size: 12px;">
                            <strong class="text-secondary">Combustible:</strong> 
                            <span class="badge rounded-sm px-2 py-0.5 fw-bold text-uppercase" style="${badgeColor} font-size: 10px;">${data.combustible}</span>
                        </div>
                        <div class="mb-1" style="font-size: 12px;">
                            <strong class="text-secondary">Capacidad Máx:</strong> 
                            <span class="fw-black text-dark">${data.capacidad} Lts</span>
                        </div>
                        <div class="mb-2" style="font-size: 12px;">
                            <strong class="text-secondary">Medidas:</strong> 
                            <span class="text-muted font-monospace fw-bold">${data.medidas}</span>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-dark w-100 fw-bold btn-girar-tanque-3d" data-id="${data.id}" style="font-size: 11px; padding: 2px 5px;">
                            <i class="fas fa-sync-alt me-1 text-orange"></i> GIRAR 90°
                        </button>
                    `;

                    const contenedorRect = container.getBoundingClientRect();
                    const xPos = event.clientX - contenedorRect.left + 15;
                    const yPos = event.clientY - contenedorRect.top + 15;

                    tooltip.style.left = `${xPos}px`;
                    tooltip.style.top = `${yPos}px`;
                    tooltip.style.display = 'block';
                } else {
                    tooltip.style.display = 'none';
                }
            });

            controls.addEventListener('start', function() {
                tooltip.style.display = 'none';
            });

            // ENVIAR NUEVAS COORDENADAS POR AJAX
            btnGuardar.addEventListener('click', function () {
                const posiciones = objetoTanquesMeshes.map(mesh => {
                    return {
                        id: mesh.userData.id,
                        x: mesh.position.x,
                        z: mesh.position.z,
                        rotacion: mesh.userData.rotacion
                    };
                });

                btnGuardar.disabled = true;
                btnGuardar.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Guardando...`;

                fetch("{{ route('combustibles.depositos.update-layout') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ tanques: posiciones })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('¡Distribución del patio actualizada exitosamente!');
                        wrapperBoton.style.display = 'none';
                    } else {
                        alert('Error al guardar el mapa.');
                    }
                })
                .catch(() => alert('Error de conexión con el servidor.'))
                .finally(() => {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = `<i class="fas fa-save me-1"></i> Guardar Distribución`;
                });
            });

            // ESCUCHAR EL GIRO DESDE EL TOOLTIP (Delegación de eventos)
            tooltip.addEventListener('click', function (event) {
                const botonGirar = event.target.closest('.btn-girar-tanque-3d');
                if (!botonGirar) return;

                const tanqueId = parseInt(botonGirar.getAttribute('data-id'));
                // Buscamos el objeto 3D correspondiente en el array global de mallas
                const meshTanque = objetoTanquesMeshes.find(mesh => mesh.userData.id === tanqueId);

                if (meshTanque) {
                    // 1. Sumar 90 grados y mantenerlo en el rango de 0-360
                    meshTanque.userData.rotacion = (meshTanque.userData.rotacion + 90) % 360;

                    // 2. Aplicar la rotación física en el entorno 3D (Eje Y)
                    meshTanque.rotation.y = THREE.MathUtils.degToRad(meshTanque.userData.rotacion);

                    // 3. Mostrar el botón flotante superior de "Guardar Distribución"
                    wrapperBoton.style.display = 'block';
                    
                    console.log(`Rotando tanque ID ${tanqueId} a ${meshTanque.userData.rotacion}°`);
                }
            });

            function animate() {
                requestAnimationFrame(animate);
                controls.update();
                renderer.render(scene, camera);
            }
            animate();

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