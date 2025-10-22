<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">TuCombustible</a>
        
        <!-- INICIO: Buscador Universal -->
        <form class="d-flex me-3" action="{{ route('search.global') }}" method="GET" style="width: 50%; margin-left:50%">
            <div class="input-group">
                <input 
                    class="form-control form-control-sm" 
                    type="search" 
                    placeholder="Buscar por Placa, Chofer, Cliente..."  
                    aria-label="Search" 
                    name="query" 
                    value="{{ request('query') }}" 
                    required
                >
                <button class="btn btn-outline-primary btn-sm" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
        <!-- FIN: Buscador Universal -->

        <div class="d-flex ms-auto align-items-center">
            <!-- Fragmento de tu Cabecera -->

<!-- Notificaciones Dinámicas -->
<div class="me-3 dropdown">
    <!-- Botón de Notificaciones -->
    <a href="#" 
       class="btn btn-outline-secondary position-relative" 
       id="alertsDropdown" 
       data-bs-toggle="dropdown" 
       aria-expanded="false">
        <i class="bi bi-bell"></i>
        
        <!-- Badge Dinámico: Solo se muestra si hay alertas no leídas -->
        @if($unreadAlertsCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger px-1" style="min-width: 20px;">
                {{ $unreadAlertsCount }}
                <span class="visually-hidden">notificaciones nuevas</span>
            </span>
        @endif
    </a>

    <!-- Dropdown del Menú de Alertas -->
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="alertsDropdown" style="min-width: 300px;">
        <li class="dropdown-header text-center">
            @if($unreadAlertsCount > 0)
                Tienes **{{ $unreadAlertsCount }}** alertas sin leer
            @else
                No tienes alertas pendientes.
            @endif
        </li>
        @forelse($unreadAlerts as $alerta)
            <li>
                <a class="dropdown-item" href="{{ url('/alertas/ver', $alerta->id) }}">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> 
                    <!-- Observación truncada para el menú -->
                    {{ Str::limit($alerta->observacion, 40) }}
                    
                </a>
            </li>
        @empty
            <li><span class="dropdown-item text-center text-muted">Todo está tranquilo.</span></li>
        @endforelse
        
        @if($unreadAlertsCount > 0)
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-center text-primary" href="{{ url('/alertas') }}">Ver todas las alertas</a></li>
        @endif
    </ul>
</div>
<!-- Fin Notificaciones Dinámicas -->

            <!-- Menú de usuario básico -->
            <div class="dropdown">
                <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ Auth::user()->name ?? 'Usuario' }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                    <li><a class="dropdown-item" href="{{ route('usuarios.index') }}">Perfil</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item" type="submit">Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
