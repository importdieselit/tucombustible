<div class="modal fade" id="modalDetalleChequeo" tabindex="-1" aria-labelledby="modalDetalleChequeoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title h6 text-uppercase fw-black" id="modalDetalleChequeoLabel">
                    <i class="fas fa-gas-pump text-orange me-2"></i> Detalles del Chequeo
                </h5>
                <button type="button" class="btn btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4">
                {{-- Meta-información rápida del chequeo --}}
                <div class="row g-2 mb-4 text-center">
                    <div class="col-md-4">
                        <div class="bg-white rounded p-2 border border-start border-3 border-orange shadow-sm">
                            <span class="text-muted d-block small text-uppercase fw-bold" style="font-size: 10px;">Sede</span>
                            <strong id="modal-meta-sede" class="text-dark small">-</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white rounded p-2 border border-start border-3 border-dark shadow-sm">
                            <span class="text-muted d-block small text-uppercase fw-bold" style="font-size: 10px;">Fecha</span>
                            <strong id="modal-meta-fecha" class="text-dark small">-</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white rounded p-2 border border-start border-3 border-secondary shadow-sm">
                            <span class="text-muted d-block small text-uppercase fw-bold" style="font-size: 10px;">Turno</span>
                            <strong id="modal-meta-turno" class="text-dark small">-</strong>
                        </div>
                    </div>
                </div>

                {{-- Contenedor donde se inyectará la tabla procesada --}}
                <div id="contenedor-detalle-tanques">
                    <div class="text-center py-4">
                        <div class="spinner-border text-orange" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="text-muted small mt-2 mb-0 fw-bold text-uppercase">Buscando mediciones en la base de datos...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2">
                <button type="button" class="btn btn-sm btn-secondary fw-bold text-uppercase" data-bs-dismiss="modal" style="font-size: 12px;">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }

    /* =========================================================================
       FORZAR LÍNEAS DIVISORIAS DE COLUMNAS Y FILAS EN EL CONTENIDO INYECTADO
       ========================================================================= */
    #contenedor-detalle-tanques table {
        border-collapse: collapse !important;
        border: 1px solid #dee2e6 !important;
    }
    #contenedor-detalle-tanques th, 
    #contenedor-detalle-tanques td {
        border: 1px solid #dee2e6 !important; /* Añade las líneas verticales y horizontales */
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        const boton = e.target.closest('.btn-ver-detalle');
        if (!boton) return;

        const modalElement = document.getElementById('modalDetalleChequeo');
        if (!modalElement) return;

        const modalBootstrap = bootstrap.Modal.getOrCreateInstance(modalElement);
        const contenedorTanques = document.getElementById('contenedor-detalle-tanques');

        const metaSede = document.getElementById('modal-meta-sede');
        const metaFecha = document.getElementById('modal-meta-fecha');
        const metaTurno = document.getElementById('modal-meta-turno');

        const chequeoId = boton.getAttribute('data-id');
        
        metaSede.textContent = boton.getAttribute('data-sede');
        metaFecha.textContent = boton.getAttribute('data-fecha');
        metaTurno.textContent = boton.getAttribute('data-turno');

        contenedorTanques.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-orange" role="status"><span class="visually-hidden">Cargando...</span></div>
                <p class="text-muted small mt-2 mb-0 fw-bold text-uppercase">Buscando cubicaciones en la base de datos...</p>
            </div>
        `;

        modalBootstrap.show();

        fetch(`/combustibles/chequeos_depositos/${chequeoId}`)
            .then(response => {
                if (!response.ok) throw new Error('Error de red');
                return response.text();
            })
            .then(html => {
                contenedorTanques.innerHTML = html;
            })
            .catch(error => {
                console.error(error);
                contenedorTanques.innerHTML = `
                    <div class="alert alert-danger text-center p-3 mb-0 small fw-bold text-uppercase" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> No se pudo cargar el desglose de los tanques.
                    </div>
                `;
            });
    });
});
</script>