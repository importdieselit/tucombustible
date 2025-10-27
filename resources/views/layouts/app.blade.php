<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard - TuCombustible')</title>
<!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2C22gB7Fz2i4M8c9tU8vQ+I6bLwK6z+a6D+Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    @stack('styles')
</head>
<body>
    @include('layouts.header')

    <div class="container-fluid">
           @if (!Request::routeIs(['login', 'logout', 'register', 'password.*']))
            <div class="container-fluid">
                <div class="row">
                    @php($user = Auth::user())
                    @if($user->id_perfil == 3)
                        @include('layouts.sidebar-cliente')
                    @elseif($user->id_perfil == 2)
                        @include('layouts.sidebar-cliente')
                    @else
                        @include('layouts.sidebar')
                    @endif
                    <main class="col ms-sm-auto col-lg-10 px-md-4 py-4 z-1">
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

    @include('layouts.footer')
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js" xintegrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf198Ytg5eI4Nkz5q+0Ukn" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" xintegrity="sha512-rpLll8u6Jj6XvYhJ2kZlD+0ZlH2d9e6o4j5Yp7v0s3Ue5z7N46u5v6Z5q+0Ukn" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" xintegrity="sha384-I7E8VVD/ismYTF4y589a1H3eO/bpcGkYIId1D9g7aFwWp8zU2E/2lV8A4M4Z/B" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" xintegrity="sha384-0m1rA4j8C5bT2XqP2r2k8sH2wJ5xK0xL9Q9z9n0B4E8G5J/B7V7P" crossorigin="anonymous"></script>
    
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="{{ asset('js/alerts.js') }}" defer></script>
    <script src="{{ asset('js/jquery.PrintArea.js') }}"></script>
    
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
    @stack('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
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
       
    });

    </script>
</body>
</html>
