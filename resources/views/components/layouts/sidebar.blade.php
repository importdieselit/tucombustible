<aside class="fixed inset-y-0 left-0 w-64 bg-[#1e293b] text-slate-300 transition-transform duration-300 transform md:translate-x-0 z-50 shadow-2xl">
    <div class="flex items-center justify-center h-16 bg-[#0f172a] text-white font-bold text-lg border-b border-slate-700">
        TuCombustible
    </div>
    
    <nav class="mt-4 px-4 space-y-1">
        <p class="px-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Mi Cuenta</p>
        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-slate-800 hover:text-white transition group">
            <i class="fas fa-tasks w-6 text-slate-500 group-hover:text-blue-400"></i> Mi Progreso
        </a>

        {{-- SOLO SI ES ADMIN (Perfil 1 o 2) SE MUESTRAN LOS MÓDULOS --}}
        @if(Auth::user()->id_perfil != 3)
            <p class="mt-6 px-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Módulos Administrativos</p>
            
            {{-- Cambiamos 'captacion.index' por 'clientes.index' que es la ruta real en web.php --}}
            <a href="{{ route('clientes.index') }}" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg hover:bg-slate-800 transition">
                <i class="fas fa-users w-6"></i> Gestión de Clientes
            </a>
        @endif

        <div class="absolute bottom-0 left-0 w-full p-4 border-t border-slate-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full px-4 py-2 text-sm font-medium text-red-400 hover:bg-red-900/20 rounded-lg transition">
                    <i class="fas fa-sign-out-alt w-6"></i> Salir del Sistema
                </button>
            </form>
        </div>
    </nav>
</aside>