@extends('layouts.app')

@section('content')
<style>
    body { overflow-x: hidden; }
    #canvas-container {
        width: 100%;
        height: 80vh;
        background-color: #1a1a1a;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        cursor: grab;
    }
    #canvas-container:active { cursor: grabbing; }
    #hud-overlay {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 10;
        pointer-events: none;
    }
    .hud-controls {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        background: rgba(0,0,0,0.7);
        padding: 10px 20px;
        border-radius: 30px;
        color: white;
        backdrop-filter: blur(5px);
    }
    .btn-3d { pointer-events: auto; }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 text-dark"><i class="fas fa-cube text-primary me-2"></i> Gemelo Digital 3D: {{ $almacen->nombre }}</h5>
        <a href="{{ route('almacen.layout.disenar', $almacen->id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a Planta 2D
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div id="canvas-container">
            <div id="hud-overlay" class="text-white">
                <h6 class="font-weight-bold text-uppercase mb-1" style="letter-spacing: 2px;">WMS Engine</h6>
                <div style="font-size: 11px;">
                    <span class="badge bg-primary me-1">ESTANTES</span>
                    <span class="badge bg-warning text-dark">GRANEL</span>
                    <span class="badge bg-info text-dark">PISO PALLET</span>
                </div>
            </div>

            <div class="hud-controls d-flex gap-3 align-items-center">
                <button class="btn btn-sm btn-primary btn-3d" id="btnOrbit">
                    <i class="fas fa-globe me-1"></i> Vista Dron (Mouse)
                </button>
                <button class="btn btn-sm btn-outline-light btn-3d" id="btnDoom">
                    <i class="fas fa-walking me-1"></i> Modo Recorrido (WASD)
                </button>
                <div class="border-start border-secondary ps-3 ms-2 small d-none" id="doomInstrucciones">
                    Usa <b>W A S D</b> para caminar. Mouse para girar cabeza.
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/PointerLockControls.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/TransformControls.js"></script>

<div class="position-absolute top-5 end-2 bg-dark bg-opacity-75 text-light p-2 rounded small border border-secondary text-center" style="z-index: 100; pointer-events: auto;">
    <div class="font-weight-bold mb-1" style="font-size: 11px; color: #38bdf8;">MODO EDICIÓN 3D</div>
    <div class="btn-group btn-group-sm w-100 mb-1" role="group">
        <button type="button" class="btn btn-outline-info active" id="btnModoMover"><i class="fas fa-arrows-alt"></i> Mover</button>
        <button type="button" class="btn btn-outline-info" id="btnModoRotar"><i class="fas fa-sync-alt"></i> Girar</button>
    </div>
    <div class="text-muted" style="font-size: 9px;">Haz click en un objeto para seleccionarlo.<br>Presiona [Esc] para deseleccionar.</div>
</div>

<script>    
window.addEventListener('load', function() {
    
    // --- 1. CONFIGURACIÓN INICIAL DEL MOTOR ---
    const container = document.getElementById('canvas-container');
    const width = container.clientWidth;
    const height = container.clientHeight;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x222222); 
    scene.fog = new THREE.FogExp2(0x222222, 0.015); 

    const camera = new THREE.PerspectiveCamera(60, width / height, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    
    renderer.setSize(width, height);
    renderer.shadowMap.enabled = true; 
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.appendChild(renderer.domElement);

    // --- 2. ILUMINACIÓN INDUSTRIAL PRO INDUSTRIAL PBR ---
    const luzAmbiental = new THREE.AmbientLight(0xffffff, 0.4); 
    scene.add(luzAmbiental);

    const luzDirecional = new THREE.DirectionalLight(0xffffff, 0.8);
    luzDirecional.position.set(50, 100, 50);
    luzDirecional.castShadow = true; 

    luzDirecional.shadow.mapSize.width = 2048;
    luzDirecional.shadow.mapSize.height = 2048;
    luzDirecional.shadow.camera.near = 0.5;
    luzDirecional.shadow.camera.far = 300;
    const d = 80;
    luzDirecional.shadow.camera.left = -d;
    luzDirecional.shadow.camera.right = d;
    luzDirecional.shadow.camera.top = d;
    luzDirecional.shadow.camera.bottom = -d;
    scene.add(luzDirecional);

    // --- 3. CONSTRUCCIÓN DEL ALMACÉN (GEMELO DIGITAL) ---
    const configFilas = {{ $almacen->total_filas_grid }};
    const configColumnas = {{ $almacen->total_columnas_grid }};
    
    const cellSize = 3;           
    const alturaNivel = 2.2;      
    const espacioEstructura = 0.2; 
    
    const dataAlmacen = @json($mapa3d);
    const objetosInteractivos = []; // Registro de elementos editables en 3D

    // Suelo Industrial de Concreto Pulido
    const sueloGeo = new THREE.PlaneGeometry(configColumnas * cellSize * 2, configFilas * cellSize * 2);
    const sueloMat = new THREE.MeshStandardMaterial({ 
        color: 0x22252a, 
        roughness: 0.4, 
        metalness: 0.2 
    });
    const suelo = new THREE.Mesh(sueloGeo, sueloMat);
    suelo.rotation.x = -Math.PI / 2;
    suelo.receiveShadow = true; 
    scene.add(suelo);

    // Grilla Técnica (AutoCAD style)
    const gridHelper = new THREE.GridHelper(Math.max(configFilas, configColumnas) * cellSize, Math.max(configFilas, configColumnas), 0x888888, 0x444444);
    gridHelper.position.y = 0.01; 
    scene.add(gridHelper);

    const offsetX = (configColumnas * cellSize) / 2;
    const offsetZ = (configFilas * cellSize) / 2;

    // --- FUNCIONES AUXILIARES: ETIQUETAS LEGIBLES ---
    function crearEtiquetaPlana(sku, stock, cap, producto, colorHex, w, h) {
        const canvas = document.createElement('canvas');
        canvas.width = 512; canvas.height = 256;
        const ctx = canvas.getContext('2d');
        
        ctx.fillStyle = 'rgba(20, 20, 20, 0.85)'; 
        ctx.fillRect(0, 0, 512, 256);
        ctx.fillStyle = colorHex;
        ctx.fillRect(0, 0, 512, 12); 

        ctx.fillStyle = '#ffffff'; ctx.textAlign = 'center';
        ctx.font = 'Bold 42px Arial'; ctx.fillText(sku, 256, 75);
        ctx.font = 'Bold 36px Arial'; ctx.fillStyle = colorHex; ctx.fillText(`Stock: ${stock} / ${cap}`, 256, 135);
        ctx.font = '26px Arial'; ctx.fillStyle = '#cccccc'; ctx.fillText(producto.substring(0, 35), 256, 195);

        const texture = new THREE.CanvasTexture(canvas);
        texture.minFilter = THREE.LinearFilter; 
        const mat = new THREE.MeshBasicMaterial({ map: texture, transparent: true, side: THREE.DoubleSide });
        return new THREE.Mesh(new THREE.PlaneGeometry(w, h), mat);
    }

    // --- FUNCIONES DE RENDERIZADO MODULAR MEDIANTE GRUPOS EN EL CENTRO LOCAL (0,0,0) ---

    function agregarNivelEstanteVacio(grupoPadre, y) {
        const ancho = cellSize - espacioEstructura;
        const alto = alturaNivel - espacioEstructura;
        const prof = cellSize - espacioEstructura;

        const geometry = new THREE.BoxGeometry(ancho, alto, prof);
        const edges = new THREE.EdgesGeometry(geometry);
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0xb0bec5, linewidth: 2 }));
        
        line.position.set(0, y + alturaNivel / 2, 0);
        grupoPadre.add(line);
    }

    function agregarNivelEstanteOcupado(grupoPadre, y, sku, stock, producto, capacidad) {
        const ancho = cellSize - espacioEstructura;
        const altoTotal = alturaNivel - espacioEstructura;
        const prof = cellSize - espacioEstructura;

        const ratio = (capacidad && capacidad > 0) ? Math.min(stock / capacidad, 1) : 1;
        const altoLleno = Math.max(altoTotal * ratio, 0.05); 
        const altoVacio = altoTotal - altoLleno;

        // 1. Parte Solida (Stock)
        const matLleno = new THREE.MeshStandardMaterial({ color: 0x0d6efd, roughness: 0.4 });
        const meshLleno = new THREE.Mesh(new THREE.BoxGeometry(ancho, altoLleno, prof), matLleno);
        meshLleno.position.set(0, y + altoLleno / 2, 0);
        meshLleno.castShadow = true;
        meshLleno.receiveShadow = true;
        grupoPadre.add(meshLleno);

        // 2. Parte Transparente (Vacío)
        if (altoVacio > 0) {
            const matVacio = new THREE.MeshStandardMaterial({ color: 0x0d6efd, transparent: true, opacity: 0.15 });
            const meshVacio = new THREE.Mesh(new THREE.BoxGeometry(ancho, altoVacio, prof), matVacio);
            meshVacio.position.set(0, y + altoLleno + (altoVacio / 2), 0);
            grupoPadre.add(meshVacio);
        }

        // 3. Contenedor de Alambre / Jaula
        const edges = new THREE.EdgesGeometry(new THREE.BoxGeometry(ancho, altoTotal, prof));
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x000000 }));
        line.position.set(0, y + altoTotal / 2, 0);
        grupoPadre.add(line);

        // 4. Inyección de Etiquetas Periféricas (4 Caras)
        const offZ = prof / 2 + 0.01;
        const offX = ancho / 2 + 0.01;

        const etiqZPos = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', ancho * 0.85, altoTotal * 0.4);
        etiqZPos.position.set(0, y + altoTotal / 2, offZ);
        grupoPadre.add(etiqZPos);

        const etiqZNeg = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', ancho * 0.85, altoTotal * 0.4);
        etiqZNeg.rotation.y = Math.PI; 
        etiqZNeg.position.set(0, y + altoTotal / 2, -offZ);
        grupoPadre.add(etiqZNeg);

        const etiqXPos = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', prof * 0.85, altoTotal * 0.4);
        etiqXPos.rotation.y = Math.PI / 2;
        etiqXPos.position.set(offX, y + altoTotal / 2, 0);
        grupoPadre.add(etiqXPos);

        const etiqXNeg = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', prof * 0.85, altoTotal * 0.4);
        etiqXNeg.rotation.y = -Math.PI / 2;
        etiqXNeg.position.set(-offX, y + altoTotal / 2, 0);
        grupoPadre.add(etiqXNeg);
    }

    function construirTanqueGranel(estaOcupado, sku, stock, producto, capacidad, orientacion) {
        const grupo = new THREE.Group();
        const radio = (cellSize - espacioEstructura) / 2;
        const altoTotal = alturaNivel * 1.5; 

        let altoLleno = altoTotal;
        let altoVacio = 0;

        if (estaOcupado && capacidad > 0) {
            const ratio = Math.min(stock / capacidad, 1);
            altoLleno = Math.max(altoTotal * ratio, 0.1);
            altoVacio = altoTotal - altoLleno;
        }

        // Configuración estructural base del Cilindro
        const matLleno = new THREE.MeshStandardMaterial({ color: 0xffc107, roughness: 0.3, metalness: 0.4 });
        const meshLleno = new THREE.Mesh(new THREE.CylinderGeometry(radio, radio, estaOcupado ? altoLleno : 0.01, 32), matLleno);
        meshLleno.castShadow = true;
        meshLleno.receiveShadow = true;

        const matVacio = new THREE.MeshStandardMaterial({ color: 0xffc107, transparent: true, opacity: 0.15 });
        const meshVacio = new THREE.Mesh(new THREE.CylinderGeometry(radio, radio, estaOcupado ? altoVacio : altoTotal, 32), matVacio);

        const edges = new THREE.EdgesGeometry(new THREE.CylinderGeometry(radio, radio, altoTotal, 16));
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0xffc107, opacity: 0.5, transparent: true }));

        // ADAPTACIÓN DE ORIENTACIÓN (Horizontal 'H' vs Vertical 'V')
        if (orientacion === 'H') {
            meshLleno.rotation.z = Math.PI / 2;
            meshVacio.rotation.z = Math.PI / 2;
            line.rotation.z = Math.PI / 2;

            meshLleno.position.set(estaOcupado ? (-altoTotal / 2 + altoLleno / 2) : 0, radio, 0);
            meshVacio.position.set(estaOcupado ? (altoLleno / 2 + altoVacio / 2) : 0, radio, 0);
            line.position.set(0, radio, 0);
        } else {
            meshLleno.position.set(0, estaOcupado ? (altoLleno / 2) : 0, 0);
            meshVacio.position.set(0, estaOcupado ? (altoLleno + (altoVacio / 2)) : (altoTotal / 2), 0);
            line.position.set(0, altoTotal / 2, 0);
        }

        if (estaOcupado) grupo.add(meshLleno);
        grupo.add(meshVacio);
        grupo.add(line);

        // Cartel Flotante del Tanque
        if (estaOcupado) {
            const canvas = document.createElement('canvas');
            canvas.width = 512; canvas.height = 256;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = 'rgba(20,20,20,0.85)'; ctx.fillRect(0,0,512,256);
            ctx.fillStyle = '#ffc107'; ctx.fillRect(0,0,512,12);
            ctx.fillStyle = '#ffffff'; ctx.textAlign = 'center';
            ctx.font = 'Bold 42px Arial'; ctx.fillText(sku, 256, 75);
            ctx.font = 'Bold 36px Arial'; ctx.fillStyle = '#ffc107'; ctx.fillText(`Lts: ${stock} / ${capacidad}`, 256, 135);
            ctx.font = '26px Arial'; ctx.fillStyle = '#cccccc'; ctx.fillText(producto.substring(0,35), 256, 195);
            
            const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(canvas) }));
            sprite.scale.set(cellSize * 1.5, cellSize * 0.75, 1);
            sprite.position.set(0, (orientacion === 'H' ? radio * 2 : altoTotal) + 0.8, 0);
            grupo.add(sprite);
        }

        return grupo;
    }

    function construirPalletPiso(infoPallet) {
        const grupo = new THREE.Group();
        
        const planoMat = new THREE.MeshStandardMaterial({ color: 0x0dcaf0, side: THREE.DoubleSide, roughness: 0.8 });
        const pisoCelda = new THREE.Mesh(new THREE.PlaneGeometry(cellSize, cellSize), planoMat);
        pisoCelda.rotation.x = -Math.PI / 2;
        pisoCelda.position.set(0, 0.01, 0);
        grupo.add(pisoCelda);

        if (infoPallet && infoPallet.ocupado) {
            const anchoBox = cellSize - 0.4;
            const altoTotal = 1.6; 
            const prof = cellSize - 0.4;

            const ratio = (infoPallet.capacidad && infoPallet.capacidad > 0) ? Math.min(infoPallet.stock / infoPallet.capacidad, 1) : 1;
            const altoLleno = Math.max(altoTotal * ratio, 0.05);
            const altoVacio = altoTotal - altoLleno;

            const meshLleno = new THREE.Mesh(new THREE.BoxGeometry(anchoBox, altoLleno, prof), new THREE.MeshStandardMaterial({ color: 0x17a2b8, roughness: 0.5 }));
            meshLleno.position.set(0, altoLleno / 2, 0);
            meshLleno.castShadow = true;
            meshLleno.receiveShadow = true;
            grupo.add(meshLleno);

            if (altoVacio > 0) {
                const meshVacio = new THREE.Mesh(new THREE.BoxGeometry(anchoBox, altoVacio, prof), new THREE.MeshStandardMaterial({ color: 0x17a2b8, transparent: true, opacity: 0.15 }));
                meshVacio.position.set(0, altoLleno + (altoVacio / 2), 0);
                grupo.add(meshVacio);
            }

            const edges = new THREE.EdgesGeometry(new THREE.BoxGeometry(anchoBox, altoTotal, prof));
            const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x000000 }));
            line.position.set(0, altoTotal / 2, 0);
            grupo.add(line);

            // Cartel Informativo
            const canvas = document.createElement('canvas');
            canvas.width = 512; canvas.height = 256;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = 'rgba(20,20,20,0.85)'; ctx.fillRect(0,0,512,256);
            ctx.fillStyle = '#17a2b8'; ctx.fillRect(0,0,512,12);
            ctx.fillStyle = '#ffffff'; ctx.textAlign = 'center';
            ctx.font = 'Bold 42px Arial'; ctx.fillText(infoPallet.sku, 256, 75);
            ctx.font = 'Bold 36px Arial'; ctx.fillStyle = '#17a2b8'; ctx.fillText(`Stock: ${infoPallet.stock} / ${infoPallet.capacidad}`, 256, 135);
            ctx.font = '26px Arial'; ctx.fillStyle = '#cccccc'; ctx.fillText(infoPallet.producto.substring(0,35), 256, 195);

            const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(canvas) }));
            sprite.scale.set(cellSize * 1.5, cellSize * 0.75, 1);
            sprite.position.set(0, altoTotal + 0.8, 0);
            grupo.add(sprite);
        }

        return grupo;
    }

    // --- ITERACIÓN INTEGRAL DE ELEMENTOS ---
    dataAlmacen.forEach(item => {
        const posX = (item.x * cellSize) - offsetX - (cellSize) + (cellSize / 2);
        const posZ = (item.z * cellSize) - offsetZ - (cellSize) + (cellSize / 2);

        let grupoCelda = null;

        if (item.tipo === 'ESTANTE') {
            grupoCelda = new THREE.Group();
            for (let n = 1; n <= item.niveles; n++) {
                const infoNivel = item.inventario.find(i => i.nivel === n);
                const posY = (n - 1) * alturaNivel;

                if (infoNivel && infoNivel.ocupado) {
                    agregarNivelEstanteOcupado(grupoCelda, posY, infoNivel.sku, infoNivel.stock, infoNivel.producto, infoNivel.capacidad);
                } else {
                    agregarNivelEstanteVacio(grupoCelda, posY);
                }
            }
        } 
        else if (item.tipo === 'GRANEL_LUBRICANTE') {
            const ubiOcupada = item.inventario.find(i => i.ocupado);
            grupoCelda = construirTanqueGranel(
                !!ubiOcupada, 
                ubiOcupada ? ubiOcupada.sku : '', 
                ubiOcupada ? ubiOcupada.stock : 0, 
                ubiOcupada ? ubiOcupada.producto : '', 
                item.capacidad_max || (ubiOcupada ? ubiOcupada.capacidad : 0),
                item.orientacion || 'V'
            );
        } 
        else if (item.tipo === 'PISO_PALLET') {
            const infoPallet = item.inventario.find(i => i.nivel === 1);
            grupoCelda = construirPalletPiso(infoPallet);
        }

        // Asignación de Transformaciones y Metadata Persistente
        if (grupoCelda) {
            grupoCelda.position.set(posX, 0, posZ);
            grupoCelda.rotation.y = item.rotacion_radianes || 0; // Carga rotación original de la DB
            
            grupoCelda.userData = {
                id: item.id,
                tipo: item.tipo,
                codigo: item.codigo
            };

            scene.add(grupoCelda);
            objetosInteractivos.push(grupoCelda);
        }
    });

    // --- 4. CÁMARAS Y CONTROLES PRO ---
    const orbitControls = new THREE.OrbitControls(camera, renderer.domElement);
    orbitControls.enableDamping = true;
    orbitControls.dampingFactor = 0.05;
    orbitControls.maxPolarAngle = Math.PI / 2 - 0.05; 
    orbitControls.screenSpacePanning = false;
    
    camera.position.set(offsetX * 1.5, Math.max(configFilas, configColumnas) * 1.5, offsetZ * 1.5);
    orbitControls.target.set(0, 0, 0);

    const doomControls = new THREE.PointerLockControls(camera, document.body);
    let modoDoomActivo = false;

    // INTERRUPTOR DE MANDOS DE TRANSFORMACIÓN (Mover/Girar en 3D)
    const transformControls = new THREE.TransformControls(camera, renderer.domElement);
    scene.add(transformControls);

    transformControls.addEventListener('change', () => renderer.render(scene, camera));
    transformControls.addEventListener('dragging-changed', function (event) {
        orbitControls.enabled = !event.value;
    });

    $('#btnModoMover').on('click', function() {
        transformControls.setMode('translate');
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
    });

    $('#btnModoRotar').on('click', function() {
        transformControls.setMode('rotate');
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
    });

    // Raycasting para Selección Tactil/Raton en la Escena 3D
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2();

    renderer.domElement.addEventListener('pointerdown', (event) => {
        if (modoDoomActivo) return;

        const rect = renderer.domElement.getBoundingClientRect();
        mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects(objetosInteractivos, true);

        if (intersects.length > 0) {
            let objBase = intersects[0].object;
            while (objBase.parent && objBase.parent !== scene) {
                objBase = objBase.parent;
            }
            transformControls.attach(objBase);
        }
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            transformControls.detach();
        }
    });

    // SINCRONIZACIÓN AUTOMÁTICA HACIA LARAVEL POR MEDIO DE AJAX
    transformControls.addEventListener('objectChange', function () {
        const obj = transformControls.object;
        if (!obj) return;

        // Ecuación Inversa Perfecta del Grid para Sincronizar Coordenadas
        const calculadoGridX = Math.round((obj.position.x + offsetX + (cellSize / 2)) / cellSize);
        const calculadoGridY = Math.round((obj.position.z + offsetZ + (cellSize / 2)) / cellSize);
        const anguloRotacion = obj.rotation.y;

        $.ajax({
            url: "{{ route('almacen.layout.actualizar_posicion_3d') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                estructura_id: obj.userData.id,
                coord_x: calculadoGridX,
                coord_y: calculadoGridY,
                rotacion: anguloRotacion
            },
            success: function(res) {
                if(res.success) {
                    console.log(`Estructura ${obj.userData.codigo} resincronizada en DB [X:${calculadoGridX}, Y:${calculadoGridY}].`);
                }
            },
            error: function(err) {
                console.error("Fallo de comunicación en persistencia espacial remota.", err);
            }
        });
    });

    // --- INTERRUPTORES HUD ORBIT / DOOM ---
    $('#btnOrbit').on('click', function() {
        modoDoomActivo = false;
        doomControls.unlock();
        transformControls.detach();
        orbitControls.enabled = true;
        
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        $('#btnDoom').removeClass('btn-light').addClass('btn-outline-light');
        $('#doomInstrucciones').addClass('d-none');
        
        camera.position.set(offsetX * 1.5, Math.max(configFilas, configColumnas) * 1.5, offsetZ * 1.5);
        orbitControls.target.set(0, 0, 0);
    });

    $('#btnDoom').on('click', function() {
        modoDoomActivo = true;
        orbitControls.enabled = false;
        transformControls.detach();
        
        $(this).removeClass('btn-outline-light').addClass('btn-light');
        $('#btnOrbit').removeClass('btn-primary').addClass('btn-outline-primary');
        $('#doomInstrucciones').removeClass('d-none');

        camera.position.set(0, 1.7, Math.max(configFilas, configColumnas) * 0.8);
        camera.lookAt(0, 1.7, 0);
        doomControls.lock(); 
    });

    let moveForward = false, moveBackward = false, moveLeft = false, moveRight = false;
    const velocity = new THREE.Vector3();
    const direction = new THREE.Vector3();

    document.addEventListener('keydown', (event) => {
        if(!modoDoomActivo) return;
        switch (event.code) {
            case 'ArrowUp': case 'KeyW': moveForward = true; break;
            case 'ArrowLeft': case 'KeyA': moveLeft = true; break;
            case 'ArrowDown': case 'KeyS': moveBackward = true; break;
            case 'ArrowRight': case 'KeyD': moveRight = true; break;
        }
    });

    document.addEventListener('keyup', (event) => {
        if(!modoDoomActivo) return;
        switch (event.code) {
            case 'ArrowUp': case 'KeyW': moveForward = false; break;
            case 'ArrowLeft': case 'KeyA': moveLeft = false; break;
            case 'ArrowDown': case 'KeyS': moveBackward = false; break;
            case 'ArrowRight': case 'KeyD': moveRight = false; break;
        }
    });

    // --- 5. BUCLE DE ANIMACIÓN ---
    const clock = new THREE.Clock();

    function animate() {
        requestAnimationFrame(animate);
        const delta = clock.getDelta();

        if (modoDoomActivo && doomControls.isLocked) {
            velocity.x -= velocity.x * 10.0 * delta;
            velocity.z -= velocity.z * 10.0 * delta;

            direction.z = Number(moveForward) - Number(moveBackward);
            direction.x = Number(moveRight) - Number(moveLeft);
            direction.normalize(); 

            const speed = 35.0; 
            if (moveForward || moveBackward) velocity.z -= direction.z * speed * delta;
            if (moveLeft || moveRight) velocity.x -= direction.x * speed * delta;

            doomControls.moveRight(-velocity.x * delta);
            doomControls.moveForward(-velocity.z * delta);
            camera.position.y = 1.7; 
        } else {
            orbitControls.update(); 
        }

        renderer.render(scene, camera);
    }
    animate();

    window.addEventListener('resize', () => {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    });
});
</script>
@endsection