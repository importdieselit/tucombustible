<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard - TuCombustible')</title>
<!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2C22gB7Fz2i4M8c9tU8vQ+I6bLwK6z+a6D+Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <!-- CSS de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" />


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
        z-index: 1000;
    }
}

/* Mejora estética del input */
.search-form-header input {
    border-radius: 20px 0 0 20px !important;
}
.search-form-header button {
    border-radius: 0 20px 20px 0 !important;
}
</style>

    @stack('styles')
</head>
<body>
    @include('layouts.header')

    <div class="container-fluid">
           @if (!Request::routeIs(['login', 'logout', 'register', 'password.*']))
            <div class="container-fluid">
                <div class="row">
                    @php($user = Auth::user())
                    @include('layouts.sidebar')
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
    </div>
<!-- TOAST CONTAINER -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
    @if(session('success'))
        <div class="toast align-items-center text-bg-success border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    @endif

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
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js" xintegrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf198Ytg5eI4Nkz5q+0Ukn" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" xintegrity="sha512-rpLll8u6Jj6XvYhJ2kZlD+0ZlH2d9e6o4j5Yp7v0s3Ue5z7N46u5v6Z5q+0Ukn" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" xintegrity="sha384-I7E8VVD/ismYTF4y589a1H3eO/bpcGkYIId1D9g7aFwWp8zU2E/2lV8A4M4Z/B" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" xintegrity="sha384-0m1rA4j8C5bT2XqP2r2k8sH2wJ5xK0xL9Q9z9n0B4E8G5J/B7V7P" crossorigin="anonymous"></script>
    
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/alerts.js') }}" defer></script>
    <script src="{{ asset('js/jquery.PrintArea.js') }}"></script>
    
    <!-- Script de DataTables -->
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
    @stack('scripts')
    <script>

        
    // Activar automáticamente todos los toasts
    document.addEventListener('DOMContentLoaded', function () {
        const toastElList = [].slice.call(document.querySelectorAll('.toast'))
        toastElList.map(function (toastEl) {
            new bootstrap.Toast(toastEl, { delay: 4500 }).show()
        })
    });

    

        document.addEventListener("DOMContentLoaded", function () {
            
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

    </script>
</body>
</html>
