<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4e73df">
    
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('img/icon-192x192.png') }}">
    <link rel="shortcut icon" href="{{ asset('img/icon-192x192.png') }}" type="image/png">  
    <title>@yield('title', 'Dashboard - TuCombustible')</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <!-- CSS de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" />

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


<style>
    @media (max-width: 767.98px) {
    .search-form-header.active {
        display: flex !important;
        position: absolute;
        top: 60px;
        left: 0;
        width: 100%;
        background: white;
        padding: 10px 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10;
    }
}



/* Mejora estética del input */
.search-form-header input {
    border-radius: 20px 0 0 20px !important;
}
.search-form-header button {
    border-radius: 0 20px 20px 0 !important;
}#offline-toast {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #323232;
    color: white;
    padding: 12px 24px;
    border-radius: 50px;
    display: none; /* Oculto por defecto */
    z-index: 10000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    align-items: center;
    gap: 10px;
    font-size: 0.8rem;
}
    /* FIX CRÍTICO: Evitar que Tailwind resetee los botones de la marca */
        .bg-orange-impordiesel {
            background-color: #FF6B00 !important;
            color: white !important;
        }
        .bg-gray-industrial {
            background-color: #4C474F !important;
            color: white !important;
        }
        /* Asegurar que los botones tipo enlace se vean como botones */
        .btn-orange, .btn-dark {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            border: none !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        {{-- Estilos para simetría con el compañero --}}
        .text-orange-impordiesel { color: #FF6B00 !important; }
        .border-orange-impordiesel { border-color: #FF6B00 !important; }
    

#offline-toast.show {
    display: flex;
    animation: fadeInOut 0.5s ease;
}

@keyframes fadeInOut {
    from { bottom: 0; opacity: 0; }
    to { bottom: 20px; opacity: 1; }
}
</style>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />



    
    @stack('styles')
</head>
<body class="bg-gray-50">

    <div class="container-fluid">
    @php($isAuthPage = Request::routeIs(['login', 'logout', 'register', 'password.*']))

    @if (!$isAuthPage && Auth::check())
            <div class="container-fluid">
                <div class="row">
                    @php($user = Auth::user())
                    @if($user->id_perfil !=4)
                        @php($sidebar = 'admin_sidebar')
                    @endif
                    @include('layouts.sidebar')
            @include('layouts.header')

                    <main class="col ms-sm-auto col-lg-12 px-md-4 py-4 z-1">
                        @yield('content')
                    </main>
                </div>
            </div>
            @else 

                <!-- Si la ruta es login, logout, etc., solo se muestra el contenido principal -->
                <main class="container-fluid py-4 z-1">
                    @yield('content')
                </main>
             @endif 

    {{-- Sistema de Notificaciones Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
        @foreach (['success', 'error', 'warning', 'info'] as $msg)
            @if(session($msg))
                <div class="toast align-items-center text-bg-{{ $msg == 'error' ? 'danger' : $msg }} border-0 show" role="alert">
                    <div class="d-flex">
                        <div class="toast-body font-bold text-uppercase small">{{ session($msg) }}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @if(session('error'))
        <div class="toast align-items-center text-bg-danger border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('error') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="toast align-items-center text-bg-warning border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('warning') }}
                </div>
                <button type="button" class="btn-close me-2 m-auto"
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="toast align-items-center text-bg-info border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('info') }}
                </div>
                <button type="button" class="btn-close me-2 m-auto"
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    @endif
</div>


    @include('layouts.footer')

<div id="offline-toast">
    <i class="fa fa-wifi-off text-warning"></i>
    <span>Sin conexión. Trabajando en modo local.</span>
</div>    

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js"></script>


    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/alerts.js') }}" defer></script>
    <script src="{{ asset('js/jquery.PrintArea.js') }}"></script>
    <script src="{{ asset('js/pwa-notifications.js') }}"></script>
    <!-- Popper.js para dropdowns y tooltips de Bootstrap -->

{{-- Scripts para Highcharts --}}
<script src="{{ asset('js/highcharts.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    
    <!-- Script de DataTables -->
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toastElList = [].slice.call(document.querySelectorAll('.toast'))
            toastElList.map(function (toastEl) {
                new bootstrap.Toast(toastEl, { delay: 4500 }).show()
            })
        });

    /**
     * Motor de Inicialización Universal Impordiesel
     * Este script asegura que los componentes de Bootstrap funcionen 
     * independientemente de otras librerías o errores externos.
     */
    const BootstrapManager = (function() {
        const init = () => {
            // 1. Verificar si Bootstrap está cargado
            const bs = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
            
            if (!bs) {
                console.error("Bootstrap JS no detectado. Verifique la carga de la librería.");
                return;
            }

            // 2. Inicializar todos los Dropdowns de forma masiva
            // Usamos un selector genérico para no ensuciar las vistas
            const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            dropdownElementList.map(function (el) {
                // Evitamos duplicar la instancia si ya existe
                if (!bs.Dropdown.getInstance(el)) {
                    new bs.Dropdown(el, {
                        boundary: 'viewport',
                        display: 'dynamic'
                    });
                }
            });
        };

        return {
            boot: function() {
                // Ejecutar al cargar el DOM
                document.addEventListener('DOMContentLoaded', init);
                // Ejecutar después de cualquier cambio en el DOM (Útil para AJAX)
                const observer = new MutationObserver(init);
                observer.observe(document.body, { childList: true, subtree: true });
            }
        };
    })();

    BootstrapManager.boot();

    document.addEventListener("DOMContentLoaded", function () {

        const toastElList = [].slice.call(document.querySelectorAll('.toast'))
        toastElList.map(function (toastEl) {
            new bootstrap.Toast(toastEl, { delay: 4500 }).show()
        })
            
        // Toggle Sidebar
        $('#sidebarCollapse').on('click', function() {
            $('.sidebar, .sidebar-overlay').toggleClass('active');
            // Cambiar icono de barras a X (opcional)
            $(this).find('i').toggleClass('bi-list bi-x-lg');
        });

                // Toggle Buscador en Móviles
                $('#toggleMobileSearch').on('click', function() {
                    $('#globalSearchForm').toggleClass('active');
                    // Si se abre, poner foco en el input
                    if($('#globalSearchForm').hasClass('active')) {
                        $('#globalSearchForm input').focus();
                    }
                });

                // Cerrar buscador si se hace click fuera
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#globalSearchContainer').length) {
                        $('#globalSearchForm').removeClass('active');
                    }
                });
            
            if ($('.sidebar-overlay').length === 0) {
                $('body').append('<div class="sidebar-overlay"></div>');
            }

            $('#sidebarCollapse').on('click', function(e) {
                e.preventDefault();
                $('.sidebar').addClass('active');
                $('.sidebar-overlay').addClass('active');
                $('body').css('overflow', 'hidden'); // Evita scroll al estar abierto
            });

            // 3. Función para CERRAR el menú (al dar click al overlay)
            $('.sidebar-overlay').on('click', function() {
                cerrarMenuMovil();
            });

            // 4. Cierre automático al hacer click en una opción que NO sea un desplegable
            $('.sidebar .nav-link').on('click', function() {
                // Si no es un padre con submenú, cerramos al navegar
                if (!$(this).hasClass('dropdown-toggle')) {
                    cerrarMenuMovil();
                }
            });


            $("#print").on("click", function () {
                var mode = 'iframe'; //popup
                var close = mode == "popup";
                var options = {
                mode: mode,
                popClose: close
                };
                $(".noPrint").hide();
                $(".siPrint").show();
                $("div.printableArea").printArea(options);
                $(".noPrint").show();
                $(".siPrint").hide();
            });


           $('.datatable').each(function() {
            let $tabla = $(this);

            let isEmpty = $tabla.find('tbody td[colspan]').length > 0;

            if (isEmpty) {
                $tabla.removeClass('datatable');
                console.log('Tabla vacía detectada: Saltando inicialización para evitar warning.');
            } else {
                $tabla.DataTable({
                    language: {
                        "decimal": "",
                        "emptyTable": "No hay información",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
                        "infoEmpty": "Mostrando 0 a 0 de 0 Entradas",
                        "infoFiltered": "(Filtrado de _MAX_ total entradas)",
                        "infoPostFix": "",
                        "thousands": ",",
                        "lengthMenu": "Mostrar _MENU_ Entradas",
                        "loadingRecords": "Cargando...",
                        "processing": "Procesando...",
                        "search": "Buscar:",
                        "zeroRecords": "Sin resultados encontrados",
                        "paginate": {
                            "first": "Primero",
                            "last": "Ultimo",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    },
                    layout: {
                        topStart: {
                            buttons: ['csv', 'excel', 'pdf', 'print']
                        }
                    },
                    "order": [
                        [ 0, 'desc' ] 
                    ]
                });
            }
        });
       
    });

    function cerrarMenuMovil() {
        $('.sidebar').removeClass('active');
        $('.sidebar-overlay').removeClass('active');
        $('body').css('overflow', 'auto'); // Restaurar scroll
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            // 1. Verificamos si ya existe un SW activo para no molestar
            if (navigator.serviceWorker.controller) {
              //  console.log('SW: Ya se encuentra activo y controlando la página.');
            }

            navigator.serviceWorker.register("{{ asset('sw.js') }}")
                .then(registration => {
                    // 2. Detectar si hay una actualización esperando
                    registration.onupdatefound = () => {
                        const installingWorker = registration.installing;
                        installingWorker.onstatechange = () => {
                            if (installingWorker.state === 'installed') {
                                if (navigator.serviceWorker.controller) {
                                   // console.log('SW: Nueva versión lista. Por favor recarga.');
                                } else {
                                    console.log('SW: Instalado por primera vez.');
                                }
                            }
                        };
                    };
                })
                .catch(error => console.error('SW Error:', error));
        });
        if ('PushManager' in window) {
            window.addEventListener('load', () => {
                // Pasamos la llave desde el entorno de Laravel de forma segura
                const publicKey = @json(config('webpush.vapid.public_key')); 

                if (!publicKey) {
                    alert("Error: La llave pública no llegó al navegador. Revisa tu config/webpush.php");
                    return;
                }
                if(publicKey) {
                    // Podemos llamar a la suscripción automáticamente 
                    // o después de una interacción del usuario
                    suscribirUsuario(publicKey);
                }
            });
        }
    }

    function persistirDataOffline(key, data) {
        localStorage.setItem('cache_data_' + key, JSON.stringify(data));
    }

    $(document).ready(function() {
        const toast = $('#offline-toast');

         // Función para actualizar el contador visual
        function updateSyncBadge() {
            const pending = JSON.parse(localStorage.getItem('pending_sync') || '[]');
            const $container = $('#sync-status-container');
            const $count = $('#sync-count');

            if (pending.length > 0) {
                $container.removeClass('d-none').addClass('d-inline-block');
                $count.text(pending.length);
            } else {
                $container.addClass('d-none');
            }
        }

   
        function updateOnlineStatus() {
            const toast = $('#offline-toast');
            
            // Si el navegador dice offline, esperamos 2 segundos para confirmar
            // que no es un simple cambio de celda o carga lenta
            if (!navigator.onLine) {
                setTimeout(() => {
                    if (!navigator.onLine) { // Si 2 segundos después sigue offline...
                        toast.addClass('show');
                    }
                }, 10000);
            } else {
                toast.removeClass('show');
            }
        }

        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);

        // DEBEMOS esperar un poco antes de la primera validación
        setTimeout(() => {
            if (!navigator.onLine) {
                toast.addClass('show');
            }
        }, 1500); // 1.5 segundos de gracia para que el SW y la red se estabilice


       
        if (!navigator.onLine) toast.addClass('show');
    
        $('.offline-form').on('submit', function(e) {
            if (!navigator.onLine) {
                e.preventDefault(); // Detenemos el envío real
                
                const $form = $(this);
                const formData = $form.serializeArray();
                const formId = $form.attr('id') || 'form_' + Date.now();

                // Guardamos en LocalStorage
                saveForLater(formId, $form.attr('action'), formData);

                // Feedback visual
                alert('Sin conexión: Los datos se guardaron localmente y se enviarán automáticamente al recuperar la señal.');
                $form[0].reset(); // Limpiamos para el siguiente registro
            }
        });

        function saveForLater(id, url, data) {
            let pending = JSON.parse(localStorage.getItem('pending_sync') || '[]');
            pending.push({ id, url, data, timestamp: new Date().getTime() });
            localStorage.setItem('pending_sync', JSON.stringify(pending));
            updateSyncBadge(); 
        }   
        updateSyncBadge();

        
        window.addEventListener('online', function() {
            const pending = JSON.parse(localStorage.getItem('pending_sync') || '[]');
            
            if (pending.length > 0) {
               // console.log('Sincronizando datos pendientes...');
                
                pending.forEach((item, index) => {
                    $.ajax({
                        url: item.url,
                        method: 'POST',
                        data: item.data,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function() {
                        //    console.log('Sincronizado con éxito:', item.id);
                            // Eliminar de la cola si se envió bien
                            removerDePendientes(index);
                        },
                        error: function() {
                            console.error('Fallo al sincronizar item:', item.id);
                        }
                    });
                });
            }
            updateSyncBadge();
        });
    });


    function removerDePendientes(index) {
        let pending = JSON.parse(localStorage.getItem('pending_sync') || '[]');
        pending.splice(index, 1);
        localStorage.setItem('pending_sync', JSON.stringify(pending));
    }

    // Ejemplo aplicado a tu fetch de vehículos que hicimos antes
    function cargarVehiculos(clienteId) {
        const cacheKey = `vehiculos_cliente_${clienteId}`;
        
        // Si estamos offline, cargar inmediatamente de LocalStorage
        if (!navigator.onLine) {
            const cached = localStorage.getItem('cache_data_' + cacheKey);
            if (cached) renderizarVehiculos(JSON.parse(cached));
            return;
        }

        fetch(`/api/clientes/${clienteId}/vehiculos`)
            .then(r => r.json())
            .then(data => {
                persistirDataOffline(cacheKey, data); // Guardar para la próxima vez que esté offline
                renderizarVehiculos(data);
            });
    }
        // FUNCIÓN GLOBAL PARA RECHAZAR DOCUMENTOS
        function rechazarDocumento(id) {
            Swal.fire({
                title: '¿Rechazar documento?',
                text: "Indica el motivo para que el cliente pueda corregirlo:",
                input: 'textarea',
                inputPlaceholder: 'Ej: El documento no es legible...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, rechazar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    document.getElementById('form-rechazo-' + id).submit();
                } else if (result.isConfirmed && !result.value) {
                    Swal.fire('Error', 'Debes indicar un motivo', 'error');
                }
            })
        }

        function suscribirANotificaciones() {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    // Lógica para registrar el dispositivo en tu base de datos Laravel
                    console.log("TuCombustible: Notificaciones activadas");
                }
            });
        }
        

        
let deferredPrompt;
    const installButton = document.getElementById('install-button');

    window.addEventListener('beforeinstallprompt', (e) => {
        // 1. Prevenir que el navegador muestre su propio aviso automático
        e.preventDefault();
        
        // 2. Guardar el evento para dispararlo luego
        deferredPrompt = e;
        
        // 3. Mostrar tu botón personalizado con estilo corporativo
        installButton.style.display = 'inline-block';
        
        console.log('TuCombustible: Modo PWA detectado y listo para instalar.');
    });

    installButton.addEventListener('click', async () => {
        if (deferredPrompt) {
            // 4. Mostrar el prompt de instalación nativo
            deferredPrompt.prompt();
            
            // 5. Esperar la respuesta del usuario
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`Usuario respondió a la instalación: ${outcome}`);
            
            // 6. Limpiar y ocultar el botón si aceptó
            if (outcome === 'accepted') {
                installButton.style.display = 'none';
            }
            
            deferredPrompt = null;
        }
    });

    // Opcional: Detectar si ya está instalada
    window.addEventListener('appinstalled', (evt) => {
        console.log('TuCombustible: Aplicación instalada con éxito.');
        installButton.style.display = 'none';
    });
    </script>
</body>
</html>