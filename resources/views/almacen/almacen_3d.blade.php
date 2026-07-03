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

<div class="position-absolute top-2 end-2 bg-dark bg-opacity-75 text-light p-2 rounded small border border-secondary text-center" style="z-index: 100; pointer-events: auto;">
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
    const alturaNivel = 1.5;      
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

    // Función auxiliar para estandarizar las etiquetas flotantes
    function agregarEtiquetaFlotante(info, x, z, alturaY) {
        const canvas = document.createElement('canvas');
        canvas.width = 512; canvas.height = 256;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = 'rgba(20,20,20,0.85)'; ctx.fillRect(0,0,512,256);
        ctx.fillStyle = '#0dcaf0'; ctx.fillRect(0,0,512,12);
        ctx.fillStyle = '#ffffff'; ctx.textAlign = 'center';
        ctx.font = 'Bold 42px Arial'; ctx.fillText(info.sku, 256, 75);
        ctx.font = 'Bold 36px Arial'; ctx.fillStyle = '#0dcaf0'; ctx.fillText(`Stock: ${info.stock} / ${info.capacidad}`, 256, 135);
        ctx.font = '26px Arial'; ctx.fillStyle = '#cccccc'; ctx.fillText(info.producto.substring(0,35), 256, 195);

        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(canvas) }));
        sprite.scale.set(cellSize * 1.5, cellSize * 0.75, 1);
        sprite.position.set(x + cellSize/2, alturaY, z + cellSize/2);
        scene.add(sprite);
    }

    // --- FUNCIONES DE RENDERIZADO VOLUMÉTRICO PROPORCIONAL ---

    function crearBloqueEstructural(posX, posY, posZ, infoSub, index, numSubdivisiones) {
    const anchoTotal = cellSize - espacioEstructura;
    const prof = cellSize - espacioEstructura;
    const altoTotal = alturaNivel - espacioEstructura;

    // Calcular el ancho de esta subdivisión específica
    const anchoSub = anchoTotal / numSubdivisiones;

    // Coordenadas centrales exactas en el mapa 3D
    const zCentro = posZ + cellSize/2;
    // La X central desplaza la caja para alinearla lado a lado (A, B, C...)
    const xCentro = posX + (espacioEstructura/2) + (index * anchoSub) + (anchoSub/2);

    // 1. CASO: CELDA VACÍA
    if (!infoSub || !infoSub.ocupado) {
        const geometry = new THREE.BoxGeometry(anchoSub, altoTotal, prof);
        const edges = new THREE.EdgesGeometry(geometry);
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0xb0bec5, linewidth: 2 }));
        line.position.set(xCentro, posY + alturaNivel/2, zCentro);
        scene.add(line);
        return;
    }

    // 2. CASO: CELDA OCUPADA
    const ratio = (infoSub.capacidad && infoSub.capacidad > 0) ? Math.min(infoSub.stock / infoSub.capacidad, 1) : 1;
    const altoLleno = Math.max(altoTotal * ratio, 0.05); 
    const altoVacio = altoTotal - altoLleno;

    // Volumen Lleno (Stock)
    const matLleno = new THREE.MeshStandardMaterial({ color: 0x0d6efd, roughness: 0.4 });
    const meshLleno = new THREE.Mesh(new THREE.BoxGeometry(anchoSub, altoLleno, prof), matLleno);
    meshLleno.position.set(xCentro, posY + altoLleno/2, zCentro);
    meshLleno.castShadow = true;
    scene.add(meshLleno);

    // Volumen Vacío (Transparente)
    if (altoVacio > 0) {
        const matVacio = new THREE.MeshStandardMaterial({ color: 0x0d6efd, transparent: true, opacity: 0.15 });
        const meshVacio = new THREE.Mesh(new THREE.BoxGeometry(anchoSub, altoVacio, prof), matVacio);
        meshVacio.position.set(xCentro, posY + altoLleno + (altoVacio/2), zCentro);
        scene.add(meshVacio);
    }

    // Jaula Wireframe negra
    const edges = new THREE.EdgesGeometry(new THREE.BoxGeometry(anchoSub, altoTotal, prof));
    const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x000000 }));
    line.position.set(xCentro, posY + altoTotal/2, zCentro);
    scene.add(line);

    // --- ETIQUETAS DE IDENTIFICACIÓN ---
    const offsetZ = prof / 2 + 0.01;
    const offsetXEtq = anchoSub / 2 + 0.01;
    const etiqAncho = anchoSub * 0.85;
    const etiqAlto = altoTotal * 0.4;

    // Etiqueta Frontal
    const etiqZPos = crearEtiquetaPlana(infoSub.sku, infoSub.stock, infoSub.capacidad, infoSub.producto, '#0d6efd', etiqAncho, etiqAlto);
    etiqZPos.position.set(xCentro, posY + altoTotal/2, zCentro + offsetZ);
    scene.add(etiqZPos);

    // Etiqueta Trasera
    const etiqZNeg = crearEtiquetaPlana(infoSub.sku, infoSub.stock, infoSub.capacidad, infoSub.producto, '#0d6efd', etiqAncho, etiqAlto);
    etiqZNeg.rotation.y = Math.PI; 
    etiqZNeg.position.set(xCentro, posY + altoTotal/2, zCentro - offsetZ);
    scene.add(etiqZNeg);

    // Etiqueta Derecha
    const etiqXPos = crearEtiquetaPlana(infoSub.sku, infoSub.stock, infoSub.capacidad, infoSub.producto, '#0d6efd', etiqAncho, etiqAlto);
    etiqXPos.rotation.y = Math.PI / 2;
    etiqXPos.position.set(xCentro + offsetXEtq, posY + altoTotal/2, zCentro);
    scene.add(etiqXPos);

    // Etiqueta Izquierda
    const etiqXNeg = crearEtiquetaPlana(infoSub.sku, infoSub.stock, infoSub.capacidad, infoSub.producto, '#0d6efd', etiqAncho, etiqAlto);
    etiqXNeg.rotation.y = -Math.PI / 2;
    etiqXNeg.position.set(xCentro - offsetXEtq, posY + altoTotal/2, zCentro);
    scene.add(etiqXNeg);
}

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
     // Definimos las dimensiones y el offset para que sobresalgan un poco de la caja
        const offsetZ = cellSize / 2 + prof / 2 + 0.01;
        const offsetX = cellSize / 2 + ancho / 2 + 0.01; // Ajusta 'ancho' si tu caja tiene un ancho distinto

        // 1. Cara Z Positiva (Frente)
        const etiqZPos = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', ancho * 0.85, altoTotal * 0.4);
        etiqZPos.position.set(x + cellSize/2, y + altoTotal/2, z + offsetZ);
        scene.add(etiqZPos);

        // 2. Cara Z Negativa (Atrás)
        const etiqZNeg = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', ancho * 0.85, altoTotal * 0.4);
        etiqZNeg.rotation.y = Math.PI; 
        etiqZNeg.position.set(x + cellSize/2, y + altoTotal/2, z - offsetZ + cellSize);
        scene.add(etiqZNeg);

        // 3. Cara X Positiva (Derecha)
        const etiqXPos = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', ancho * 0.85, altoTotal * 0.4);
        etiqXPos.rotation.y = Math.PI / 2; // Rotación de 90 grados
        etiqXPos.position.set(x + offsetX, y + altoTotal/2, z + cellSize/2);
        scene.add(etiqXPos);

        // 4. Cara X Negativa (Izquierda)
        const etiqXNeg = crearEtiquetaPlana(sku, stock, capacidad, producto, '#0d6efd', ancho * 0.85, altoTotal * 0.4);
        etiqXNeg.rotation.y = -Math.PI / 2; // Rotación de -90 grados
        etiqXNeg.position.set(x - offsetX + cellSize, y + altoTotal/2, z + cellSize/2);
        scene.add(etiqXNeg);
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

    function crearPalletPiso(x, z, infoPallet, CxF = 4, CxC = 4, nivelesMax = 5) {
        // Base de madera (Paleta real)
        const planoMat = new THREE.MeshStandardMaterial({ color: 0x8b5a2b, roughness: 1.0 });
        const pisoCelda = new THREE.Mesh(new THREE.BoxGeometry(cellSize - 0.2, 0.1, cellSize - 0.2), planoMat);
        pisoCelda.position.set(x + cellSize/2, 0.05, z + cellSize/2);
        pisoCelda.receiveShadow = true;
        scene.add(pisoCelda);

        if (infoPallet && infoPallet.ocupado) {
            // Configuraciones de la estiba
            const cajasPorFila = CxF;
            const cajasPorColumna = CxC;
            const nivelesMaximos = nivelesMax;
            const totalCajasMaximas = cajasPorFila * cajasPorColumna * nivelesMaximos; // 80 cajas

            // Cálculo de cuántas cajas dibujar basado en el % de stock
            const ratio = (infoPallet.capacidad && infoPallet.capacidad > 0) ? Math.min(infoPallet.stock / infoPallet.capacidad, 1) : 1;
            const cajasADibujar = Math.floor(ratio * totalCajasMaximas);

            // Dimensiones de cada cajita
            const anchoCaja = (cellSize - 0.4) / cajasPorFila;
            const profCaja = (cellSize - 0.4) / cajasPorColumna;
            const altoCaja = 1.4 / nivelesMaximos;

            const boxGeom = new THREE.BoxGeometry(anchoCaja - 0.02, altoCaja - 0.02, profCaja - 0.02); // -0.02 para crear separación visual
            const boxMat = new THREE.MeshStandardMaterial({ color: 0xc2a878, roughness: 0.9 }); // Color cartón corrugado

            let cajasDibujadas = 0;
            const startX = x + 0.2 + (anchoCaja / 2);
            const startZ = z + 0.2 + (profCaja / 2);

            // Bucle anidado para construir la paleta capa por capa
            dibujadoLoop:
            for (let nivel = 0; nivel < nivelesMaximos; nivel++) {
                const posY = 0.1 + (nivel * altoCaja) + (altoCaja / 2);
                for (let fila = 0; fila < cajasPorFila; fila++) {
                    for (let col = 0; col < cajasPorColumna; col++) {
                        if (cajasDibujadas >= cajasADibujar) break dibujadoLoop;
                        
                        const mallaCaja = new THREE.Mesh(boxGeom, boxMat);
                        mallaCaja.position.set(startX + (fila * anchoCaja), posY, startZ + (col * profCaja));
                        mallaCaja.castShadow = true;
                        scene.add(mallaCaja);
                        cajasDibujadas++;
                    }
                }
            }

            // Etiqueta flotante reutilizable
            agregarEtiquetaFlotante(infoPallet, x, z, 1.8);
        }

       
    }

     function crearTamboresPiramide(x, z, infoTambores) {
            // Configuraciones de la base
            const tamboresEnBase = 10;
            const totalTamboresMax = (tamboresEnBase * (tamboresEnBase + 1)) / 2; // Fórmula matemática (10+9+8...+1) = 55 tambores
            
            // El diámetro de cada tambor debe ajustarse al tamaño de la celda
            const diametro = (cellSize - 0.2) / tamboresEnBase;
            const radio = diametro / 2;
            const largo = cellSize - 0.4;

            if (infoTambores && infoTambores.ocupado) {
                const ratio = (infoTambores.capacidad && infoTambores.capacidad > 0) ? Math.min(infoTambores.stock / infoTambores.capacidad, 1) : 1;
                const tamboresADibujar = Math.floor(ratio * totalTamboresMax);

                const matTambor = new THREE.MeshStandardMaterial({ color: 0x1f618d, roughness: 0.5, metalness: 0.3 }); // Azul acero
                const cylGeom = new THREE.CylinderGeometry(radio, radio, largo, 16);
                cylGeom.rotateX(Math.PI / 2); // Acostar el tambor horizontalmente

                let tamboresDibujados = 0;
                let tamboresEsteNivel = tamboresEnBase;
                let nivelActual = 0;

                // Bucle para apilar (Desplazamiento geométrico en las juntas)
                construccionPiramide:
                while (tamboresEsteNivel > 0 && tamboresDibujados < tamboresADibujar) {
                    // Cálculo de la altura: Raíz cuadrada de (2r)^2 - r^2 = r * 1.732 (Apilamiento hexagonal)
                    const posY = radio + (nivelActual * (radio * 1.732)); 
                    
                    // Desplazamiento en X: Cada nivel se corre hacia adentro el valor de 1 radio
                    const startX = x + 0.1 + (nivelActual * radio) + radio;

                    for (let i = 0; i < tamboresEsteNivel; i++) {
                        if (tamboresDibujados >= tamboresADibujar) break construccionPiramide;
                        
                        const mallaTambor = new THREE.Mesh(cylGeom, matTambor);
                        mallaTambor.position.set(startX + (i * diametro), posY, z + cellSize/2);
                        mallaTambor.castShadow = true;
                        scene.add(mallaTambor);
                        
                        tamboresDibujados++;
                    }
                    nivelActual++;
                    tamboresEsteNivel--;
                }

                agregarEtiquetaFlotante(infoTambores, x, z, radio + (10 * radio * 1.732) + 0.5);
            }
        }

    // --- PROCESAMIENTO E ITERACIÓN DEL LAYOUT MATRIZ ---
    dataAlmacen.forEach(item => {
    const posX = (item.x * cellSize) - offsetX - (cellSize);
    const posZ = (item.z * cellSize) - offsetZ - (cellSize);

    if (item.tipo === 'ESTANTE') {
        for (let n = 1; n <= item.niveles; n++) {
            const posY = (n - 1) * alturaNivel;

            // Extraemos TODAS las subdivisiones que pertenecen a este nivel
            let subsEnNivel = item.inventario.filter(i => i.nivel === n);
            const numSubdivisiones = subsEnNivel.length > 0 ? subsEnNivel.length : 1;

            if (subsEnNivel.length > 0) {
                // Ordenamos alfabéticamente por código (Ej: -A, -B, -C) para pintar en orden de izquierda a derecha
                subsEnNivel.sort((a, b) => a.codigo_completo.localeCompare(b.codigo_completo));
                
                // Dibujamos cada subdivisión recalculando su X interna
                subsEnNivel.forEach((subInfo, index) => {
                    crearBloqueEstructural(posX, posY, posZ, subInfo, index, numSubdivisiones);
                });
            } else {
                // Nivel completamente vacío (Sin subdivisiones guardadas en BD)
                crearBloqueEstructural(posX, posY, posZ, null, 0, 1);
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
    } else if (item.tipo === 'TAMBORES_PIRAMIDE') {
        const infoTambores = item.inventario.find(i => i.nivel === 1);
        crearTamboresPiramide(posX, posZ, infoTambores);
    }
});

    // --- 4. CÁMARAS Y CONTROLES ---
    const orbitControls = new THREE.OrbitControls(camera, renderer.domElement);
    orbitControls.enableDamping = true;
    orbitControls.dampingFactor = 0.05;
    orbitControls.maxPolarAngle = Math.PI / 2 - 0.05; 
    orbitControls.screenSpacePanning = false;
    
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