<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container-fluid">
        
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <img src="{{ asset('img/logomini.png') }}" alt="Logo" class="img-fluid rounded-circle me-2 border border-2 border-secondary" style="max-width: 45px; background: white; padding: 2px;">
            <span class="font-black uppercase tracking-tighter italic text-dark">Tu<span class="text-orange-500">Combustible</span></span>
        </a>

        <div class="d-flex ms-auto align-items-center">
            
            <div class="me-3 dropdown">
                <a href="#" 
                   class="btn btn-outline-secondary position-relative p-2" 
                   id="alertsDropdown" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false"
                   style="border-radius: 8px;">
                    <i class="bi bi-bell-fill fs-5"></i>
                    
                    @if(isset($unreadAlertsCount) && $unreadAlertsCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.65rem;">
                            {{ $unreadAlertsCount }}
                        </span>
                    @endif
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" aria-labelledby="alertsDropdown" style="min-width: 320px;">
                    <li class="dropdown-header bg-gray-100 text-dark font-black uppercase text-center py-3 border-bottom">
                        @if(isset($unreadAlertsCount) && $unreadAlertsCount > 0)
                            <i class="bi bi-exclamation-triangle-fill text-orange-500 me-1"></i> Tienes {{ $unreadAlertsCount }} alertas sin leer
                        @else
                            No tienes alertas pendientes
                        @endif
                    </li>
                    <div class="max-h-64 overflow-y-auto">
                        @forelse($unreadAlerts ?? [] as $alerta)
                            <li>
                                <a class="dropdown-item py-3 border-bottom" href="{{ url('/alertas/ver', $alerta->id_alerta) }}">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 bg-light p-2 rounded-circle me-3 border">
                                            <i class="bi bi-lightning-fill text-orange-500"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-0 text-xs font-bold text-gray-800">{{ Str::limit($alerta->observacion, 60) }}</p>
                                            <small class="text-muted italic" style="font-size: 9px;">{{ \Carbon\Carbon::parse($alerta->fecha)->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="p-4 text-center">
                                <i class="bi bi-check2-all fs-2 text-success d-block mb-1"></i>
                                <span class="text-muted text-[10px] font-black uppercase">Sistema al día</span>
                            </li>
                        @endforelse
                    </div>
                    
                    @if(isset($unreadAlertsCount) && $unreadAlertsCount > 0)
                        <li><a class="dropdown-item text-center text-primary font-black py-2 uppercase text-[10px]" href="{{ url('/alertas') }}">Ver todas las notificaciones</a></li>
                    @endif
                </ul>
            </div>

            <div class="dropdown border-start ps-3">
                <a class="btn btn-outline-primary dropdown-toggle font-black uppercase text-[11px] tracking-widest px-4" href="#" role="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-fill me-1"></i> {{ Auth::user()->name ?? 'Admin' }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userMenu">
                    <li><a class="dropdown-item text-xs font-bold uppercase" href="{{ route('usuarios.index') }}">Mi Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger text-xs font-black uppercase" type="submit">Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>