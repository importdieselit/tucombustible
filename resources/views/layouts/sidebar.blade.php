@php
    use App\Models\Acceso;
    use Illuminate\Support\Facades\Auth;
    use App\Models\Modulo;

    $user = Auth::user();

    // 1. Lógica de Permisos (Centralizada)
    if ($user && $user->id_perfil == 1) {
        $modulos = Modulo::where('id_padre', 0)
            ->where('visible', 1)
            ->orderBy('orden')
            ->get();
    } else {
        $modulosPermitidosIds = Acceso::where('id_usuario', Auth::id())
            ->where('read', 1)
            ->pluck('id_modulo');

        $modulos = Modulo::where('id_padre', 0)
            ->where('visible', 1)
            ->whereIn('id', $modulosPermitidosIds)
            ->orderBy('orden')
            ->get();
    }
@endphp

<style>
/* Sidebar Estilo Profesional Impormotor */

.sidebar {
    background: #1a233a;
    min-height: 100vh;
    color: #fff;
    box-shadow: 2px 0 8px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    padding-top: 1rem;
    z-index: 1030;
    overflow-y: auto;
}

.sidebar .nav-link {
    color: #bfc9da;
    font-weight: 500;
    padding: 0.4rem 1rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-decoration: none;
}

.sidebar .nav-link:hover, 
.sidebar .nav-link.active {
    background: #24304e;
    color: #fff;
}

.sidebar .nav-link i.nav-icon {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

/* Flecha de Acordeón */
.sidebar .right-arrow {
    font-size: 0.8rem;
    transition: transform 0.3s ease;
    padding: 5px;
}

/* Estado Abierto */
.sidebar .nav-item.dropdown.open > .nav-link .right-arrow {
    transform: rotate(-90deg);
}

.sidebar .submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: #212b43;
    list-style: none;
    padding: 0;
    border-left: 3px solid #4e73df;
}

.sidebar .nav-item.dropdown.open > .submenu {
    max-height: 800px; /* Suficiente para los items */
}

.sidebar .submenu .nav-link {
    padding-left: 1.5rem;
    font-size: 0.85em;
}

/* Scrollbar personalizado */
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: #4e73df; border-radius: 10px; }
/* --- Ajustes Responsive --- */

/* Botón de cierre para móviles (opcional, dentro del sidebar) */
.close-sidebar {
    display: none;
}

@media (max-width: 768px) {
    .sidebar {
        left: -250px; /* Oculto por defecto */
        transition: all 0.3s ease;
        z-index: 9999;
        width: 250px;
    }

    .sidebar.active {
        left: 0; /* Aparece */
    }

    /* Overlay para oscurecer el fondo cuando el menú esté abierto */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9998;
    }

    .sidebar-overlay.active {
        display: block;
    }
}

/* Ajuste del contenido principal para que no se solape */
@media (min-width: 769px) {
    body {
        padding-left: 250px; /* Espacio para el sidebar fijo */
    }
}

</style>

<div class="sidebar d-md-block">
    <div class="d-flex flex-column align-items-center mb-3">
        <img src="{{ asset('img/logomini.png') }}" alt="Logo" class="img-fluid rounded-circle border border-3 border-secondary" style="max-width: 80px; background: white; padding: 5px;">
        <span class="mt-2 text-white"><strong>TuCombustible</strong></span>
        <small class="text-muted">Impordiesel</small>
    </div>

    <ul class="nav flex-column px-2">
        {{-- Dashboard Principal --}}
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ Request::routeIs('dashboard') ? 'active' : '' }}">
                <span><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</span>
            </a>
        </li>

        <hr class="text-muted my-2">

        @foreach($modulos as $modulo)
            @php
                // Obtener sub-módulos
                $secciones = Modulo::where('id_padre', $modulo->id)->where('visible', 1);
                if($user->id_perfil != 1) {
                    $secciones = $secciones->whereIn('id', $modulosPermitidosIds);
                }
                $secciones = $secciones->orderBy('orden')->get();
                $hasSubmenu = !$secciones->isEmpty();

                // Lógica de persistencia: ¿Este menú debe estar abierto?
                // Comprueba si la URL actual empieza por la ruta base del módulo
                $isActive = Request::is($modulo->ruta . '*') || Request::routeIs($modulo->ruta . '*');
            @endphp
            
            <li class="nav-item {{ $hasSubmenu ? 'dropdown' : '' }} {{ $isActive ? 'open' : '' }}">
                {{-- El link principal ahora tiene la RUTA REAL del módulo --}}
                <a class="nav-link {{ $isActive ? 'active' : '' }}" 
                   href="{{ Route::has($modulo->ruta) ? route($modulo->ruta) : '#' }}">
                    <span>
                        <i class="{{ $modulo->icono }} nav-icon"></i> 
                        {{ $modulo->modulo }}
                    </span>
                    @if($hasSubmenu)
                        <i class="fas fa-angle-left right-arrow toggle-icon"></i>
                    @endif
                </a>

                @if($hasSubmenu)
                    <ul class="nav flex-column submenu">
                        {{-- Ya no hay link de 'Resumen', el padre hace esa función --}}
                        @foreach($secciones as $seccion)
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is($seccion->ruta . '*') ? 'active' : '' }}" 
                                   href="{{ $seccion->url_directa == 1 && Route::has($seccion->ruta) ? route($seccion->ruta) : '#' }}">
                                    <i class="{{ $seccion->icono }} nav-icon"></i> {{ $seccion->modulo }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Manejo inteligente del Sidebar
    document.querySelectorAll('.sidebar .nav-item.dropdown > .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            const parent = link.closest('.dropdown');
            const isToggleIcon = e.target.classList.contains('toggle-icon');

            // Si se hace click en la flecha: Solo abrir/cerrar sin navegar
            if (isToggleIcon) {
                e.preventDefault();
                e.stopPropagation();
                parent.classList.toggle('open');
            } else {
                // Si hace click en el texto: Navegar (comportamiento por defecto)
                // Pero dejamos el menú visualmente abierto para la transición
                parent.classList.add('open');
            }

            // Cerrar otros menús abiertos para mantener limpieza (opcional)
            document.querySelectorAll('.sidebar .nav-item.dropdown').forEach(function(item) {
                if(item !== parent) {
                    item.classList.remove('open');
                }
            });
        });
    });
});
</script>