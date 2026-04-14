@php
    use App\Models\PermisoPerfil;
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
        $modulosPermitidosIds = PermisoPerfil::where('id_perfil', $user->id_perfil)
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
    z-index: 10;
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
            <a href="{{ route('dashboard.admin') }}" class="nav-link {{ Request::routeIs('dashboard.admin') ? 'active' : '' }}">
                <span><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</span>
            </a>
        </li>

        <hr class="text-muted my-2">

        {{-- INICIO DE MÓDULOS EN DESARROLLO (SOLO ADMINS) --}}
        @if(in_array($user->id_perfil, [1, 2])) 
            <li class="nav-item px-3 mb-1">
                <small class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: bold;">Operaciones Principales</small>
            </li>

            <li class="nav-item">
                <a href="{{ route('clientes.index') }}" class="nav-link {{ Request::routeIs('clientes.*') ? 'active' : '' }}">
                    <span><i class="fas fa-users nav-icon"></i> Módulo Clientes</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('combustible.estadisticas') }}" class="nav-link {{ Request::routeIs('combustible.*') ? 'active' : '' }}">
                    <span><i class="fas fa-gas-pump nav-icon"></i> Módulo Combustible</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('logistica.index') }}" class="nav-link {{ Request::routeIs('logistica.*') ? 'active' : '' }}">
                    <span><i class="fas fa-truck-loading nav-icon"></i> Módulo Logística</span>
                </a>
            </li>

            <hr class="text-muted my-2">
        @endif
        {{-- FIN DE MÓDULOS EN DESARROLLO --}}

        {{-- RENDERIZADO DINÁMICO (MÓDULOS ANTIGUOS) --}}
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
    
    <button id="install-button" class="btn btn-orange shadow-sm px-4 text-white fw-bold" style="display:none;">
            <i class="fa fa-download me-2"></i> INSTALAR APP
    </button>
    
    <div class="card mt-3">
        <div class="card-body text-center p-2">
            <div id="push-container" class="d-flex flex-column align-items-center justify-content-center" style="min-height: 20px;">
                
                <h5 id="push-title" class="card-title h6 mb-2">Notificaciones en tiempo real</h5>
                
                <button id="btn-push" class="btn btn-primary shadow-sm px-4">
                    <i class="bi bi-bell"></i> <span id="btn-text">Activar Notificaciones</span>
                </button>
                
                <div id="push-status" class="mt-2"></div>
            </div>
        </div>
    </div>
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

            // Cerrar otros menús abiertos para mantener limieza (opcional)
            document.querySelectorAll('.sidebar .nav-item.dropdown').forEach(function(item) {
                if(item !== parent) {
                    item.classList.remove('open');
                }
            });
        });
    });


    const btnPush = document.getElementById('btn-push');
    const btnText = document.getElementById('btn-text');
    const pushStatus = document.getElementById('push-status');

    // 1. Función para convertir la llave VAPID (Tu estándar de código reutilizable)
    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
    };

    // 2. Función principal de suscripción
    async function suscribirDispositivo() {
        try {
            btnPush.disabled = true;
            btnText.innerText = 'Procesando...';

            const registration = await navigator.serviceWorker.ready;
            const publicKey = @json(config('webpush.vapid.public_key')); 

            if (!publicKey) {
                alert("Error: La llave pública no llegó al navegador. Revisa tu config/webpush.php");
                return;
            }

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey)
            });

            // Enviar a Laravel (Usando Fetch para evitar líos con Axios)
            const response = await fetch('/notifications/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(subscription)
            });

            const result = await response.json();

            if (result.success) {
                actualizarInterfaz('suscrito');
            }
        } catch (error) {
            console.error("Error en suscripción:", error);
            // AGREGA ESTA LÍNEA PARA VER EL ERROR EN EL CELULAR:
            alert("Error real: " + error.name + " - " + error.message); 
            actualizarInterfaz('error');
        }
    }

    // 3. Manejo de la interfaz
    function actualizarInterfaz(estado) {
        if (estado === 'suscrito') {
            const pushTitle = document.getElementById('push-title');
    const pushContainer = document.getElementById('push-container');
            // 1. Ocultar el título y el botón original
            pushTitle.classList.add('d-none');
            btnPush.classList.add('d-none');
            
            // 2. Limpiar cualquier mensaje de error previo
            pushStatus.innerHTML = '';

            // 3. Crear el indicador visual: Campanita Verde + Check
            // Usamos Flexbox para centrarlo perfectamente en la pantalla del Redmi
            pushStatus.innerHTML = `
                <div class="d-inline align-items-center justify-content-center text-success position-relative" 
                    style="font-size: 1.2rem; width: 20px; height: 20px;">
                    
                    <i class="fa fa-bell"></i>
                    
                    <i class="fa fa-check-circle position-absolute bg-white rounded-circle shadow-sm" 
                    style="font-size: 0.5rem; bottom: 3px; right: 3px; padding: 1px;"></i>
                </div>
                <div class="small text-muted mt-1 text-center d-inline"> Notificaciones activas</div>
            `;
            
            // Ajustamos el contenedor para que el icono quede centrado
            pushContainer.classList.remove('flex-column');
            pushContainer.classList.add('justify-content-center');

        } else if (estado === 'error') {
            // En caso de error, mostramos el botón y un mensaje de advertencia
            pushTitle.classList.remove('d-none');
            btnPush.classList.remove('d-none');
            btnPush.disabled = false;
            btnPush.innerText = 'Reintentar';
            
            pushStatus.innerHTML = `
                <div class="text-danger small fw-bold mt-2">
                    <i class="bi bi-exclamation-triangle"></i> Error al vincular. Intenta de nuevo.
                </div>
            `;
        }
    }

    // Evento del botón
    btnPush.addEventListener('click', suscribirDispositivo);

    // 4. Check inicial: ¿Ya está suscrito en este navegador?
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(reg => {
            reg.pushManager.getSubscription().then(sub => {
                if (sub) actualizarInterfaz('suscrito');
            });
        });
    }

    
});

</script>