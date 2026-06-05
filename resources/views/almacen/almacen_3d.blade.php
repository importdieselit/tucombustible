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
    
    const cellSize = 3;           
    const alturaNivel = 2.2;      
    const espacioEstructura = 0.2; 
    
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

    const offsetX = (configColumnas * cellSize) / 2;
    const offsetZ = (configFilas * cellSize) / 2;

    // --- FUNCIONES AUXILIARES: ETIQUETAS LEGIBLES INDEPENDIENTES ---
    
    // Etiqueta Plana: Ideal para pegar en el frente de los Racks sin chocar con los niveles superiores
    function crearEtiquetaPlana(sku, stock, cap, producto, colorHex, w, h) {
        const canvas = document.createElement('canvas');
        canvas.width = 512; canvas.height = 256;
        const ctx = canvas.getContext('2d');
        
        ctx.fillStyle = 'rgba(20, 20, 20, 0.85)'; // Fondo oscuro semitransparente
        ctx.fillRect(0, 0, 512, 256);
        ctx.fillStyle = colorHex;
        ctx.fillRect(0, 0, 512, 12); // Línea de acento superior

        ctx.fillStyle = '#ffffff'; ctx.textAlign = 'center';
        ctx.font = 'Bold 42px Arial'; ctx.fillText(sku, 256, 75);
        ctx.font = 'Bold 36px Arial'; ctx.fillStyle = colorHex; ctx.fillText(`Stock: ${stock} / ${cap}`, 256, 135);
        ctx.font = '26px Arial'; ctx.fillStyle = '#cccccc'; ctx.fillText(producto.substring(0, 35), 256, 195);

        const texture = new THREE.CanvasTexture(canvas);
        texture.minFilter = THREE.LinearFilter; // Nitidez
        const mat = new THREE.MeshBasicMaterial({ map: texture, transparent: true });
        return new THREE.Mesh(new THREE.PlaneGeometry(w, h), mat);
    }

    // --- FUNCIONES DE RENDERIZADO VOLUMÉTRICO PROPORCIONAL ---

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

    function crearCajaSolida(x, y, z, sku, stock, producto, capacidad) {
        const ancho = cellSize - espacioEstructura;
        const altoTotal = alturaNivel - espacioEstructura;
        const prof = cellSize - espacioEstructura;

        // Matemática de Porcentaje de Ocupación
        const ratio = (capacidad && capacidad > 0) ? Math.min(stock / capacidad, 1) : 1;
        const altoLleno = Math.max(altoTotal * ratio, 0.05); // Mínimo visible
        const altoVacio = altoTotal - altoLleno;

        // 1. Parte Solida (Stock Real)
        const matLleno = new THREE.MeshStandardMaterial({ color: 0x0d6efd, roughness: 0.4 });
        const meshLleno = new THREE.Mesh(new THREE.BoxGeometry(ancho, altoLleno, prof), matLleno);
        meshLleno.position.set(x + cellSize/2, y + altoLleno/2, z + cellSize/2);
        meshLleno.castShadow = true;
        scene.add(meshLleno);

        // 2. Parte Transparente (Espacio Libre)
        if (altoVacio > 0) {
            const matVacio = new THREE.MeshStandardMaterial({ color: 0x0d6efd, transparent: true, opacity: 0.15 });
            const meshVacio = new THREE.Mesh(new THREE.BoxGeometry(ancho, altoVacio, prof), matVacio);
            meshVacio.position.set(x + cellSize/2, y + altoLleno + (altoVacio/2), z + cellSize/2);
            scene.add(meshVacio);
        }

        // 3. Jaula exterior negra para definir el volumen completo
        const edges = new THREE.EdgesGeometry(new THREE.BoxGeometry(ancho, altoTotal, prof));
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x000000 }));
        line.position.set(x + cellSize/2, y + altoTotal/2, z + cellSize/2);
        scene.add(line);

        // 4. Carteles identificadores (Frontal y Trasero para visibilidad total)
        const etiquetaZPlus = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', ancho * 0.85, altoTotal * 0.4);
        etiquetaZPlus.position.set(x + cellSize/2, y + altoTotal/2, z + cellSize/2 + prof/2 + 0.01);
        scene.add(etiquetaZPlus);

        const etiquetaZMinus = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', ancho * 0.85, altoTotal * 0.4);
        etiquetaZMinus.rotation.y = Math.PI; 
        etiquetaZMinus.position.set(x + cellSize/2, y + altoTotal/2, z + cellSize/2 - prof/2 - 0.01);
        scene.add(etiquetaZMinus);
    }

    function crearTanqueGranel(x, z, estaOcupado, sku, stock, producto, capacidad) {
        const radio = (cellSize - espacioEstructura) / 2;
        const altoTotal = alturaNivel * 1.5; 

        if (estaOcupado) {
            const ratio = (capacidad && capacidad > 0) ? Math.min(stock / capacidad, 1) : 1;
            const altoLleno = Math.max(altoTotal * ratio, 0.1);
            const altoVacio = altoTotal - altoLleno;

            // Fluido Sólido
            const matLleno = new THREE.MeshStandardMaterial({ color: 0xffc107, roughness: 0.3 });
            const meshLleno = new THREE.Mesh(new THREE.CylinderGeometry(radio, radio, altoLleno, 32), matLleno);
            meshLleno.position.set(x + cellSize/2, altoLleno/2, z + cellSize/2);
            meshLleno.castShadow = true;
            scene.add(meshLleno);

            // Vacío Transparente
            if (altoVacio > 0) {
                const matVacio = new THREE.MeshStandardMaterial({ color: 0xffc107, transparent: true, opacity: 0.15 });
                const meshVacio = new THREE.Mesh(new THREE.CylinderGeometry(radio, radio, altoVacio, 32), matVacio);
                meshVacio.position.set(x + cellSize/2, altoLleno + (altoVacio/2), z + cellSize/2);
                scene.add(meshVacio);
            }

            // Etiqueta Sprite Flotante (Se usa Sprite porque siempre mirará a la cámara, ideal para cilindros)
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
            sprite.position.set(x + cellSize/2, altoTotal + 0.8, z + cellSize/2);
            scene.add(sprite);
        } else {
            // Cilindro 100% Vacío
            const matVacio = new THREE.MeshStandardMaterial({ color: 0xffc107, transparent: true, opacity: 0.15 });
            const meshVacio = new THREE.Mesh(new THREE.CylinderGeometry(radio, radio, altoTotal, 32), matVacio);
            meshVacio.position.set(x + cellSize/2, altoTotal/2, z + cellSize/2);
            scene.add(meshVacio);
        }

        // Jaula de bordes para contener visualmente el cilindro
        const edges = new THREE.EdgesGeometry(new THREE.CylinderGeometry(radio, radio, altoTotal, 16));
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0xffc107, opacity: 0.5, transparent: true }));
        line.position.set(x + cellSize/2, altoTotal/2, z + cellSize/2);
        scene.add(line);
    }

    function crearPalletPiso(x, z, infoPallet) {
        const planoMat = new THREE.MeshStandardMaterial({ color: 0x0dcaf0, side: THREE.DoubleSide, roughness: 0.8 });
        const pisoCelda = new THREE.Mesh(new THREE.PlaneGeometry(cellSize, cellSize), planoMat);
        pisoCelda.rotation.x = -Math.PI / 2;
        pisoCelda.position.set(x + cellSize/2, 0.01, z + cellSize/2);
        scene.add(pisoCelda);

        if (infoPallet && infoPallet.ocupado) {
            const anchoBox = cellSize - 0.4;
            const altoTotal = 1.6; 
            const prof = cellSize - 0.4;

            const ratio = (infoPallet.capacidad && infoPallet.capacidad > 0) ? Math.min(infoPallet.stock / infoPallet.capacidad, 1) : 1;
            const altoLleno = Math.max(altoTotal * ratio, 0.05);
            const altoVacio = altoTotal - altoLleno;

            // Sólido
            const meshLleno = new THREE.Mesh(new THREE.BoxGeometry(anchoBox, altoLleno, prof), new THREE.MeshStandardMaterial({ color: 0x17a2b8, roughness: 0.5 }));
            meshLleno.position.set(x + cellSize/2, altoLleno/2, z + cellSize/2);
            meshLleno.castShadow = true;
            scene.add(meshLleno);

            // Transparente
            if (altoVacio > 0) {
                const meshVacio = new THREE.Mesh(new THREE.BoxGeometry(anchoBox, altoVacio, prof), new THREE.MeshStandardMaterial({ color: 0x17a2b8, transparent: true, opacity: 0.15 }));
                meshVacio.position.set(x + cellSize/2, altoLleno + (altoVacio/2), z + cellSize/2);
                scene.add(meshVacio);
            }

            // Bordes
            const edges = new THREE.EdgesGeometry(new THREE.BoxGeometry(anchoBox, altoTotal, prof));
            const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x000000 }));
            line.position.set(x + cellSize/2, altoTotal/2, z + cellSize/2);
            scene.add(line);

            // Cartel Flotante
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
            sprite.position.set(x + cellSize/2, altoTotal + 0.8, z + cellSize/2);
            scene.add(sprite);
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
                    crearCajaSolida(posX, posY, posZ, infoNivel.sku, infoNivel.stock, infoNivel.producto, infoNivel.capacidad);
                } else {
                    crearEstructuraVacia(posX, posY, posZ);
                }
            }
        } 
        else if (item.tipo === 'GRANEL_LUBRICANTE') {
            const ubiOcupada = item.inventario.find(i => i.ocupado);
            if (ubiOcupada) {
                crearTanqueGranel(posX, posZ, true, ubiOcupada.sku, ubiOcupada.stock, ubiOcupada.producto, ubiOcupada.capacidad);
            } else {
                crearTanqueGranel(posX, posZ, false, '', 0, '', 0);
            }
        } 
        else if (item.tipo === 'PISO_PALLET') {
            const infoPallet = item.inventario.find(i => i.nivel === 1);
            crearPalletPiso(posX, posZ, infoPallet);
        }
    });

    // --- 4. CÁMARAS Y CONTROLES ---
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