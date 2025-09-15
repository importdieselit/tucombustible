@php
    use App\Models\Acceso;
    use Illuminate\Support\Facades\Auth;
    use App\Models\Modulo;
    // Obtener el usuario autenticado
    $user = Auth::user();

    if ($user && $user->id_perfil == 1) {
        // Si el usuario es super usuario (id_perfil = 1), obtener todos los módulos visibles
        $modulos = Modulo::where('id_padre', 0)
            ->where('visible', 1)
            ->orderBy('orden')
            ->get();
    } else {// Obtener los IDs de los módulos permitidos para el usuario actual
        $modulosPermitidosIds = Acceso::where('id_usuario', Auth::id())
                                    ->where('read', 1)
                                    ->pluck('id_modulo');

        // Obtener los módulos principales (id_padre = 0) que el usuario tiene permitido ver
        $modulos = App\Models\Modulo::where('id_padre', 0)
            ->where('visible', 1)
            ->whereIn('id', $modulosPermitidosIds)
            ->orderBy('orden')
            ->get();
    }
@endphp
<style>
/* Sidebar profesional */
.sidebar {
    background: #1a233a;
    min-height: 100vh;
    color: #fff;
    box-shadow: 2px 0 8px rgba(0,0,0,0.08);
    position: fixed; /* Asegura que el sidebar se quede fijo */
    top: 0;
    left: 0;
    width: 250px;
    padding-top: 5rem; /* Ajusta el padding superior para no solapar con el navbar */
}
.sidebar .nav-link {
    color: #bfc9da;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    border-radius: 0.375rem;
    transition: background 0.2s, color 0.2s;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active {
    background: #24304e;
    color: #fff;
}
.sidebar .nav-item.dropdown > .nav-link.dropdown-toggle:after {
    content: " ▼";
    font-size: 0.7em;
    float: right;
    margin-top: 0.3em;
}
.sidebar .nav-item.dropdown > .submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1);
    background: #212b43;
    margin-left: 0;
    border-left: 3px solid #4e73df;
}
.sidebar .nav-item.dropdown.open > .submenu {
    max-height: 500px; /* suficiente para mostrar los items */
    transition: max-height 0.5s cubic-bezier(0.4,0,0.2,1);
}
.sidebar .submenu .nav-link {
    padding-left: 2.5rem;
    color: #bfc9da;
    font-size: 0.97em;
}
.sidebar .submenu .nav-link:hover {
    background: #2c3a5a;
    color: #fff;
}
.sidebar .nav-link i {
    margin-right: 0.7em;
    font-size: 1.1em;
    vertical-align: middle;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sidebar accordion animation
    document.querySelectorAll('.sidebar .nav-item.dropdown > .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Evita la navegación si hay un submenú
            let parent = link.closest('.dropdown');
            if (parent) {
                e.preventDefault();
                parent.classList.toggle('open');
                // Cierra otros abiertos
                document.querySelectorAll('.sidebar .nav-item.dropdown').forEach(function(item) {
                    if(item !== parent) {
                        item.classList.remove('open');
                    }
                });
            }
        });
    });
});
</script>

<div class="col-md-3 col-lg-2 d-md-block sidebar ">
    <div class="d-flex flex-column align-items-center mb-4">
          <img src="img/logomini.png" alt="Logo de la empresa" class="img-fluid rounded-circle mb-3 border border-3 border-secondary" style="max-width: 100px;background: white;">
            
            <p class=" text-center mt-1">TuCombustible</p>

        </div>
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            {{-- Enlace del Dashboard, siempre visible --}}
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ Request::routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <hr class="text-white my-2">

            @foreach($modulos as $modulo)
                @php
   // Obtener los submódulos (hijos) permitidos para este usuario y módulo padre
                    $secciones = App\Models\Modulo::where('id_padre', $modulo->id)
                        ->where('visible', 1);
                        if(!($user && $user->id_perfil == 1)) {
                            $secciones = $secciones->whereIn('id', $modulosPermitidosIds);
                        }
                        $secciones = $secciones->orderBy('orden')
                        ->get();


                   $hasSubmenu = !$secciones->isEmpty();
                @endphp
                
                <li class="nav-item {{ $hasSubmenu ? 'dropdown' : '' }}">
                    @if($hasSubmenu)
                        <a class="nav-link dropdown-toggle" href="#" title="{{ $modulo->descripcion }}">
                            <i class="{{ $modulo->icono }}"></i> {{ $modulo->modulo }}
                        </a>
                        <ul class="nav flex-column submenu">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route($modulo->ruta) }}" title="Listado de {{ $modulo->modulo }}">
                                    <i class="bi bi-list"></i> Resumen
                                </a>
                            </li>
                            @foreach($secciones as $seccion)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route($seccion->ruta) }}" title="{{ $seccion->descripcion }}">
                                        <i class="{{ $seccion->icono }}"></i> {{ $seccion->modulo }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a class="nav-link {{ Request::routeIs($modulo->ruta . '*') ? 'active' : '' }}" href="{{ route($modulo->ruta ) }}" title="{{ $modulo->descripcion }}">
                            <i class="{{ $modulo->icono }}"></i> {{ $modulo->modulo }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
