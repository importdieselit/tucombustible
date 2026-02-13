<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm sticky-top">
    <div class="container-fluid">
        
        <button type="button" id="sidebarCollapse" class="btn btn-outline-primary me-2 d-md-none border-0">
            <i class="fa fa-list fs-3"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center d-md-none" href="{{ route('dashboard') }}">
            <img src="{{ asset('img/logomini.png') }}" alt="Logo" class="img-fluid rounded-circle border border-2 border-secondary me-2" style="max-width: 35px; background: white; padding: 2px;">
            <span class="d-none d-sm-inline fw-bold text-primary">TuCombustible</span>
        </a>

        <div class="ms-md-auto me-2 me-md-5" id="globalSearchContainer">
            <button class="btn btn-link text-secondary d-md-none p-2" id="toggleMobileSearch">
                <i class="fa fa-search fs-5"></i>
            </button>

            <form id="globalSearchForm" class="search-form-header d-none d-md-flex" action="{{ route('search.global') }}" method="GET">
                <div class="input-group">
                    <input 
                        class="form-control form-control-sm border-primary-subtle" 
                        type="search" 
                        placeholder="Placa, Chofer, Cliente..."  
                        name="query" 
                        value="{{ request('query') }}" 
                        required
                    >
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="d-flex align-items-center">
            <div class="dropdown me-2">
                <a href="#" class="btn btn-link text-secondary position-relative p-1" id="alertsDropdown" data-bs-toggle="dropdown">
                    <i class="fa fa-bell fs-5"></i>
                    @if($unreadAlertsCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            {{ $unreadAlertsCount }}
                        </span>
                    @endif
                </a>
                </div>

            <div class="dropdown">
                <a class="btn btn-sm btn-outline-primary rounded-pill px-3 dropdown-toggle d-flex align-items-center" href="#" id="userMenu" data-bs-toggle="dropdown">
                    <i class="fa fa-person-circle me-1"></i>
                    <span class="d-none d-lg-inline">{{ Auth::user()->name ?? 'User' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item py-2" href="{{ route('usuarios.index') }}"><i class="fa fa-person me-2"></i> Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger py-2" type="submit"><i class="fa fa-box-arrow-right me-2"></i> Salir</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>