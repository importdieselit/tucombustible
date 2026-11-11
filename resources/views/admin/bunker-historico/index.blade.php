@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 text-secondary fw-bold"><i class="fas fa-chart-line me-2"></i> Histórico de Precios Bunker</h3>
            <p class="text-muted mb-0">Precios referenciales extraídos de Ship & Bunker.</p>
        </div>
        <div>
            <button class="btn btn-outline-primary shadow-sm" onclick="cargarHistorico(1)">
                <i class="fas fa-sync-alt me-1"></i> Refrescar Datos
            </button>

            <button id="btn-run-scraper" class="btn btn-warning shadow-sm fw-bold" onclick="ejecutarScraperWeb()">
                <i class="fas fa-spider me-1"></i> Extraer Precios Web Ahora
            </button>
        </div>
    </div>

    <!-- Contenedor de Notificaciones -->
    <div id="alert-container"></div>

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        
        <div class="row w-100 flex-nowrap">
           <div class="col-10 bg-white text-dark">
                <h5 class="m-0 fw-bold">Mis Productos </h5>
            </div>
        </div>
        <div class="row w-100 flex-nowrap overflow-auto p-0" id="banner" style="scrollbar-width: thin; scroll-behavior: smooth;">

            @foreach ($productos as $producto)
            
                <div class="col-12 col-md-4 col-lg-3 mb-2">
                    <!-- La Card -->
                    <div class="card border-0 h-100" style="min-width: 260px; border-radius: 5px; ">
                        <div class="card-body p-1 text-center d-flex flex-column justify-content-center">
                            
                            <!-- Nombre del Producto -->
                            <h6 class="card-title mb-1 fw-bold text-dark text-uppercase text-truncate" title="{{ $producto->nombre }}">
                                {{ $producto->nombre }}
                                <small class="text-muted text-truncate" style="font-size: 0.8rem;">
                                ({{ $producto->proveedor ?? 'Sin Proveedor' }})
                                </small>
                                 <span id="precio-{{ $producto->nombre }}" class="fs-5 ml-2 fw-bolder text-primary">
                                    <!-- Formateamos el número para que no parezca que lo hizo un practicante -->
                                    ${{ number_format($producto->precio, 2, ',', '.') }}
                                </span>
                            </h6>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card-body p-0">
            <div class="table-responsive position-relative">
                
                <!-- Loader que se muestra mientras carga el AJAX -->
                <div id="loader" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center bg-white" style="z-index: 10; opacity: 0.8;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>

               <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" colspan="2" style="width: 10%;">Historico Precios Internacionales</th>
                        </tr>
                        <tr>
                            <th class="ps-4" style="width: 15%;">Fecha</th>
                            <th>Precios y Sincronización</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-body">
                        <!-- Las filas se inyectan aquí con JS -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Paginación Dinámica -->
        <div class="card-footer bg-white border-0 pt-3 pb-3 d-flex justify-content-center" id="paginacion-container">
            <!-- Los botones de página se inyectan aquí con JS -->
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, iniciando carga de histórico...');
        cargarHistorico(1); // Carga la primera página al entrar
    });

    // 1. CARGAR DATOS (Fetch)
    function cargarHistorico(page) {
        mostrarLoader(true);
        fetch(`/admin/bunker-historico/datos?page=${page}`)
            .then(response => response.json())
            .then(data => {
                renderizarTabla(data.paginacion.data, data.ultima_fecha);
                renderizarPaginacion(data.paginacion);
                mostrarLoader(false);
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al cargar los datos del servidor.', 'danger');
                mostrarLoader(false);
            });
    }

    // 2. DIBUJAR LA TABLA AGRUPADA
    function renderizarTabla(fechasAgrupadas, ultimaFecha) {
        const tbody = document.getElementById('tabla-body');
        tbody.innerHTML = '';

        if (!fechasAgrupadas || fechasAgrupadas.length === 0) {
            tbody.innerHTML = `<tr><td colspan="2" class="text-center py-4 text-muted">No hay registros históricos.</td></tr>`;
            return;
        }

        fechasAgrupadas.forEach(grupo => {
            const esReciente = (grupo.fecha === ultimaFecha);
            
            // Construimos los bloques de cada combustible
            let htmlCombustibles = '<div class="d-flex flex-wrap gap-3">';
            
            if (grupo.combustibles) {
                grupo.combustibles.forEach(item => {
                    const btnClass = esReciente ? 'btn-success' : 'btn-outline-secondary';
                    const btnText = esReciente ? '<i class="fas fa-sync-alt"></i> Sync' : 'Sync';
                    
                    htmlCombustibles += `
                        <div class="border rounded p-2 bg-white shadow-sm d-flex align-items-center gap-3" style="min-width: 220px;">
                            <span class="badge bg-primary fs-6">${item.combustible}</span>
                            <span class="fw-bold fs-5 text-dark ms-auto">$${parseFloat(item.precio).toFixed(2)}</span>
                            <button class="btn btn-sm ${btnClass} py-1 px-2 btn-sync" data-id="${item.id}" onclick="sincronizarPrecio(this)">
                                <i class="fas fa-sync-alt"></i> Sync
                            </button>
                        </div>
                    `;
                });
            }
            htmlCombustibles += '</div>';

            const tr = document.createElement('tr');
            if(esReciente) tr.classList.add('table-light');
            
            tr.innerHTML = `
                <td class="ps-4 fw-bold text-muted align-middle fs-6">${grupo.fecha}</td>
                <td class="py-3">${htmlCombustibles}</td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    // 3. DIBUJAR LA PAGINACIÓN SIN RECARGAR PÁGINA
    function renderizarPaginacion(paginacion) {
        const container = document.getElementById('paginacion-container');
        let html = '<nav><ul class="pagination mb-0 shadow-sm">';

        // Botón Anterior
        html += `<li class="page-item ${paginacion.current_page === 1 ? 'disabled' : ''}">
                    <button class="page-link" onclick="cargarHistorico(${paginacion.current_page - 1})">&laquo; Ant</button>
                 </li>`;

        // Iterar páginas (Mostramos un rango simple para no saturar si hay 100 páginas)
        let startPage = Math.max(1, paginacion.current_page - 2);
        let endPage = Math.min(paginacion.last_page, paginacion.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${paginacion.current_page === i ? 'active' : ''}">
                        <button class="page-link" onclick="cargarHistorico(${i})">${i}</button>
                     </li>`;
        }

        // Botón Siguiente
        html += `<li class="page-item ${paginacion.current_page === paginacion.last_page ? 'disabled' : ''}">
                    <button class="page-link" onclick="cargarHistorico(${paginacion.current_page + 1})">Sig &raquo;</button>
                 </li>`;
        
        html += '</ul></nav>';
        container.innerHTML = html;
    }

    // 4. SINCRONIZAR A PRODUCTOS (POST con Fetch)
    function sincronizarPrecio(buttonElement) {
        const id = buttonElement.getAttribute('data-id');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content'); // REQUIERE METATAG CSRF EN EL LAYOUT

        // Cambiar botón a estado cargando
        const originalHtml = buttonElement.innerHTML;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        buttonElement.disabled = true;

        fetch(`/admin/bunker-historico/sincronizar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ historico_id: id })
        })
        .then(response => response.json().then(data => ({status: response.status, body: data})))
        .then(res => {
            if (res.status === 200 && res.body.success) {
                console.log('Sincronización exitosa:', res.body);
                mostrarAlerta(res.body.message, 'success');
                // Efecto visual de éxito
                document.getElementById(`precio-${res.body.producto}`).textContent = `$${res.body.precio}`;
                buttonElement.innerHTML = '<i class="fas fa-check"></i> ¡Listo!';
                buttonElement.classList.replace('btn-outline-secondary', 'btn-success');
            } else {
                mostrarAlerta(res.body.message, 'warning');
                buttonElement.innerHTML = originalHtml;
                buttonElement.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Ocurrió un error en el servidor al sincronizar.', 'danger');
            buttonElement.innerHTML = originalHtml;
            buttonElement.disabled = false;
        });
    }

    // Utilidades
    function mostrarLoader(show) {
        const loader = document.getElementById('loader');
        if (show) {
            loader.classList.remove('d-none');
            loader.classList.add('d-flex');
        } else {
            loader.classList.remove('d-flex');
            loader.classList.add('d-none');
        }
    }

    function mostrarAlerta(mensaje, tipo) {
        const alertContainer = document.getElementById('alert-container');
        alertContainer.innerHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show shadow-sm border-0" role="alert">
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        // Auto ocultar a los 5 segundos
        setTimeout(() => { alertContainer.innerHTML = ''; }, 5000);
    }

    // 5. EJECUTAR EL SCRAPER MANUALMENTE
    function ejecutarScraperWeb() {
        const btn = document.getElementById('btn-run-scraper');
        const originalHtml = btn.innerHTML;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Protegemos el botón y avisamos al usuario (esto tarda unos 3 a 8 segundos)
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Extrayendo de Ship&Bunker...';
        btn.disabled = true;
        btn.classList.replace('btn-warning', 'btn-secondary');
        
        mostrarLoader(true); // Encendemos el loader de la tabla por si acaso

        fetch(`/admin/bunker-historico/ejecutar-scraper`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json().then(data => ({status: response.status, body: data})))
        .then(res => {
            if (res.status === 200 && res.body.success) {
                mostrarAlerta('¡Magia! ' + res.body.message, 'success');
                // IMPORTANTE: Como se extrajeron datos nuevos, ordenamos a la tabla que se recargue
                cargarHistorico(1); 
            } else {
                mostrarAlerta(res.body.message, 'danger');
                console.error("Log del Scraper:", res.body.log);
                mostrarLoader(false);
            }
        })
        .catch(error => {
            console.error('Error Fatal:', error);
            mostrarAlerta('El servidor tardó demasiado o colapsó al intentar raspar la web.', 'danger');
            mostrarLoader(false);
        })
        .finally(() => {
            // Devolver el botón a su estado normal sea cual sea el resultado
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.replace('btn-secondary', 'btn-warning');
        });
    }
</script>
@endsection