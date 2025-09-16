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
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="{{ asset('js/alerts.js') }}"></script>
    @stack('scripts')
</body>
</html>
