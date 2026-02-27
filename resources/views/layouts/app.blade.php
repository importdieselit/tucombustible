<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard - TuCombustible')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    @livewireStyles
    @stack('styles')
</head>
<body class="bg-gray-50">

    @php($isAuthPage = Request::routeIs(['login', 'logout', 'register', 'password.*']))

    @if (!$isAuthPage && Auth::check())
        <x-layouts.sidebar />

        <div class="md:ml-64 flex flex-col min-h-screen">
            
            @include('layouts.header')

            <main class="flex-grow p-4">
                @yield('content')
                {{ $slot ?? '' }} {{-- Para componentes Livewire que usen layouts --}}
            </main>

            @include('layouts.footer')
        </div>
    @else
        <main class="container-fluid py-4">
            @yield('content')
        </main>
    @endif

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
        @foreach (['success', 'error', 'warning', 'info'] as $msg)
            @if(session($msg))
                <div class="toast align-items-center text-bg-{{ $msg == 'error' ? 'danger' : $msg }} border-0 show" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">{{ session($msg) }}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @livewireScripts
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/alerts.js') }}" defer></script>
    @stack('scripts')

    <script>
        // Inicializar Toasts
        document.addEventListener('DOMContentLoaded', function () {
            const toastElList = [].slice.call(document.querySelectorAll('.toast'))
            toastElList.map(function (toastEl) {
                new bootstrap.Toast(toastEl, { delay: 4500 }).show()
            })
        });

        // FUNCIÓN GLOBAL PARA RECHAZAR DOCUMENTOS
        function rechazarDocumento(id) {
            Swal.fire({
                title: '¿Rechazar documento?',
                text: "Indica el motivo para que el cliente pueda corregirlo:",
                input: 'textarea',
                inputPlaceholder: 'Ej: El documento no es legible o está vencido...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, rechazar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    // Aquí envías el formulario de rechazo (puedes crear un form oculto o usar fetch)
                    document.getElementById('form-rechazo-' + id).submit();
                } else if (result.isConfirmed && !result.value) {
                    Swal.fire('Error', 'Debes indicar un motivo', 'error');
                }
            })
        }
    </script>
</body>
</html>