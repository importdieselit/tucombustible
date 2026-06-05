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
    container.appendChild(renderer.domElement);

    // --- 2. ILUMINACIÓN EJECUTIVA ---
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6); 
    scene.add(ambientLight);

    const dirLight = new THREE.DirectionalLight(0xffffff, 0.8); 
    dirLight.position.set(20, 40, 20);
    dirLight.castShadow = true;
    dirLight.shadow.mapSize.width = 2048;
    dirLight.shadow.mapSize.height = 2048;
    scene.add(dirLight);

    // --- 3. CONSTRUCCIÓN DEL ALMACÉN (GEMELO DIGITAL 3D REAL) ---
    const configFilas = {{ $almacen->total_filas_grid }};
    const configColumnas = {{ $almacen->total_columnas_grid }};
    
    const cellSize = 3;           // Escala base en metros del cubo contenedor
    const alturaNivel = 2.2;      // Distancia de separación vertical entre niveles
    const espacioEstructura = 0.2; // Tolerancia visual interna por celda
    
    const dataAlmacen = @json($mapa3d);

    // Piso del Almacén
    const planeGeo = new THREE.PlaneGeometry(configColumnas * cellSize, configFilas * cellSize);
    const planeMat = new THREE.MeshStandardMaterial({ color: 0x444444, roughness: 0.8 });
    const floor = new THREE.Mesh(planeGeo, planeMat);
    floor.rotation.x = -Math.PI / 2; 
    floor.receiveShadow = true;
    scene.add(floor);

    // Grilla Técnica (AutoCAD style)
    const gridHelper = new THREE.GridHelper(Math.max(configFilas, configColumnas) * cellSize, Math.max(configFilas, configColumnas), 0x888888, 0x444444);
    gridHelper.position.y = 0.01; 
    scene.add(gridHelper);

    // Offsets de centrado dinámico tridimensional
    const offsetX = (configColumnas * cellSize) / 2;
    const offsetZ = (configFilas * cellSize) / 2;

    // --- FUNCIONES INTERNAS ADAPTADAS AL LAYOUT DE OPERACIONES ---

    // 1-A. ESTANTE DISPONIBLE: Muestra solo estructura vacía (reja/jaula con bordes claros)
    function crearEstructuraVacia(x, y, z) {
        const ancho = cellSize - espacioEstructura;
        const alto = alturaNivel - espacioEstructura;
        const prof = cellSize - espacioEstructura;

        const geometry = new THREE.BoxGeometry(ancho, alto, prof);
        const edges = new THREE.EdgesGeometry(geometry);
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0xb0bec5, linewidth: 2 }));
        
        line.position.set(x + cellSize/2, y + alturaNivel/2, z + cellSize/2);
        scene.add(line);
    }

    // 1-B. ESTANTE OCUPADO: Muestra bloque sólido corporativo con SKU y Stock pintados en textura
    function crearCajaSolida(x, y, z, sku, stock,producto) {
        const ancho = cellSize - espacioEstructura;
        const alto = alturaNivel - espacioEstructura;
        const prof = cellSize - espacioEstructura;

        const geometry = new THREE.BoxGeometry(ancho, alto, prof);
        
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 256;
        const ctx = canvas.getContext('2d');
        
        ctx.fillStyle = '#0d6efd'; // bg-primary de Bootstrap
        ctx.fillRect(0, 0, 256, 256);
        
        ctx.fillStyle = '#ffffff';
        ctx.font = 'Bold 28px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(sku, 128, 110);
        ctx.font = '22px Arial';
        ctx.fillText(`Cant: ${stock}`, 128, 160);
        ctx.fillText(`Producto: ${producto}`, 128, 200);

        const texture = new THREE.CanvasTexture(canvas);
        const material = new THREE.MeshStandardMaterial({ map: texture, roughness: 0.4 });
        const mesh = new THREE.Mesh(geometry, material);
        
        mesh.position.set(x + cellSize/2, y + alturaNivel/2, z + cellSize/2);
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        scene.add(mesh);

        const edges = new THREE.EdgesGeometry(geometry);
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x000000 }));
        line.position.copy(mesh.position);
        scene.add(line);
    }

    // 2. LUBRICANTE / GRANEL: Transparencia total si está vacío, cilindro sólido amarillo si tiene stock
    function crearTanqueGranel(x, z, estaOcupado, sku, stock, producto) {
        const radio = (cellSize - espacioEstructura) / 2;
        const alturaTanque = alturaNivel * 1.5; 

        const geometry = new THREE.CylinderGeometry(radio, radio, alturaTanque, 16);
        let material;

        if (estaOcupado) {
            const canvas = document.createElement('canvas');
            canvas.width = 256;
            canvas.height = 256;
            const ctx = canvas.getContext('2d');
            
            ctx.fillStyle = '#ffc107'; // bg-warning de Bootstrap
            ctx.fillRect(0, 0, 256, 256);
            ctx.fillStyle = '#212529'; // Texto oscuro contrastante
            ctx.font = 'Bold 28px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(sku, 128, 110);
            ctx.font = '22px Arial';
            ctx.fillText(`Lts: ${stock}`, 128, 160);
            ctx.fillText(`Producto: ${producto}`, 128, 200);
            const texture = new THREE.CanvasTexture(canvas);
            material = new THREE.MeshStandardMaterial({ map: texture, roughness: 0.3 });
        } else {
            material = new THREE.MeshStandardMaterial({
                color: 0xffc107,
                transparent: true,
                opacity: 0.15
            });
        }

        const mesh = new THREE.Mesh(geometry, material);
        mesh.position.set(x + cellSize/2, alturaTanque/2, z + cellSize/2);
        mesh.castShadow = estaOcupado;
        scene.add(mesh);

        // Al estar vacío le fijamos una jaula exterior para denotar la reserva de espacio
        if (!estaOcupado) {
            const edges = new THREE.EdgesGeometry(geometry);
            const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0xffc107, opacity: 0.4, transparent: true }));
            line.position.copy(mesh.position);
            scene.add(line);
        }
    }

    // 3. PISO PALLET: Siempre cambia el color del suelo. Si está ocupado dibuja caja (pallet) arriba
    function crearPalletPiso(x, z, infoPallet) {
        const planoGeo = new THREE.PlaneGeometry(cellSize, cellSize);
        const planoMat = new THREE.MeshStandardMaterial({
            color: 0x0dcaf0, // bg-info de Bootstrap
            side: THREE.DoubleSide,
            roughness: 0.8
        });
        const pisoCelda = new THREE.Mesh(planoGeo, planoMat);
        pisoCelda.rotation.x = Math.PI / 2;
        pisoCelda.position.set(x + cellSize/2, 0.01, z + cellSize/2);
        scene.add(pisoCelda);

        if (infoPallet && infoPallet.ocupado) {
            const anchoBox = cellSize - 0.4;
            const altoBox = 1.3; 
            
            const boxGeo = new THREE.BoxGeometry(anchoBox, altoBox, anchoBox);
            const canvas = document.createElement('canvas');
            canvas.width = 256;
            canvas.height = 256;
            const ctx = canvas.getContext('2d');
            
            ctx.fillStyle = '#17a2b8'; 
            ctx.fillRect(0, 0, 256, 256);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'Bold 26px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(infoPallet.sku, 128, 110);
            ctx.font = '22px Arial';
            ctx.fillText(`Pallet: ${infoPallet.stock}`, 128, 160);
            ctx.fillText(`Producto: ${infoPallet.producto}`, 128, 200);

            const texture = new THREE.CanvasTexture(canvas);
            const boxMat = new THREE.MeshStandardMaterial({ map: texture, roughness: 0.5 });
            const palletMesh = new THREE.Mesh(boxGeo, boxMat);
            
            palletMesh.position.set(x + cellSize/2, altoBox / 2, z + cellSize/2);
            palletMesh.castShadow = true;
            scene.add(palletMesh);
        }
    }

    // --- PROCESAMIENTO E ITERACIÓN DEL LAYOUT MATRIZ ---
    dataAlmacen.forEach(item => {
        const posX = (item.x * cellSize) - offsetX - (cellSize);
        const posZ = (item.z * cellSize) - offsetZ - (cellSize);

        if (item.tipo === 'ESTANTE') {
            for (let n = 1; n <= item.niveles; n++) {
                const infoNivel = item.inventario.find(i => i.nivel === n);
                const posY = (n - 1) * alturaNivel;

                if (infoNivel && infoNivel.ocupado) {
                    crearCajaSolida(posX, posY, posZ, infoNivel.sku, infoNivel.stock);
                } else {
                    crearEstructuraVacia(posX, posY, posZ);
                }
            }
        } 
        else if (item.tipo === 'GRANEL_LUBRICANTE') {
            const ubiOcupada = item.inventario.find(i => i.ocupado);
            if (ubiOcupada) {
                crearTanqueGranel(posX, posZ, true, ubiOcupada.sku, ubiOcupada.stock, ubiOcupada.producto);
            } else {
                crearTanqueGranel(posX, posZ, false, '', 0, '');
            }
        } 
        else if (item.tipo === 'PISO_PALLET') {
            const infoPallet = item.inventario.find(i => i.nivel === 1);
            crearPalletPiso(posX, posZ, infoPallet);
        }
    });

    // --- 4. CÁMARAS Y CONTROLES (ÓRBITA vs DOOM) ---
    const orbitControls = new THREE.OrbitControls(camera, renderer.domElement);
    orbitControls.enableDamping = true;
    orbitControls.dampingFactor = 0.05;
    orbitControls.maxPolarAngle = Math.PI / 2 - 0.05; 
    
    camera.position.set(offsetX * 1.5, Math.max(configFilas, configColumnas) * 1.5, offsetZ * 1.5);
    orbitControls.target.set(0, 0, 0);

    const doomControls = new THREE.PointerLockControls(camera, document.body);
    let modoDoomActivo = false;

    $('#btnOrbit').on('click', function() {
        modoDoomActivo = false;
        doomControls.unlock();
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

    // --- 5. BUCLE DE ANIMACIÓN CORE ---
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
            
            camera.position.y = 1.7; // Altura estándar del ojo técnico humano
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