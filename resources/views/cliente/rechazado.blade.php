@extends('layouts.app')
@section('title', 'Solicitud Rechazada - ImporDiesel')

@section('content')
<div class="container mx-auto py-8 px-4">
    <div class="max-w-2xl mx-auto">

        <div class="bg-white rounded-xl shadow-sm border-t-8 border-red-600 border border-gray-200 p-12 text-center">
            <div class="w-24 h-24 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-red-200">
                <i class="fas fa-times-circle fa-3x"></i>
            </div>

            <h2 class="text-2xl font-black text-gray-800 uppercase mb-2">Solicitud No Aprobada</h2>
            <p class="text-gray-500 font-bold uppercase text-sm max-w-md mx-auto mb-2">
                Lamentablemente, su solicitud de registro no fue aprobada por nuestro equipo administrativo
                o por el Ministerio de Hidrocarburos.
            </p>
            <p class="text-gray-400 text-xs font-bold uppercase max-w-md mx-auto mb-8">
                Si considera que esto es un error, comuníquese con nosotros a través de nuestros canales de atención.
            </p>

            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 mb-8 text-left max-w-sm mx-auto">
                <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest mb-2">Datos de su registro</p>
                <p class="text-xs font-black text-gray-700 uppercase">{{ $cliente->nombre }}</p>
                <p class="text-xs font-bold text-gray-500">RIF: {{ $cliente->rif }}</p>
                <p class="text-xs font-bold text-gray-500">Registrado el: {{ $cliente->created_at?->format('d/m/Y') ?? 'N/A' }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-8 py-3 bg-gray-industrial text-white font-black rounded shadow-lg hover:bg-black transition duration-300 text-sm uppercase tracking-widest border-b-4 border-black">
                    <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                </button>
            </form>
        </div>

        <div class="text-center mt-8">
            <small class="text-gray-400 uppercase tracking-widest text-xs font-black">
                Portal de Clientes - ImporDiesel &copy; {{ date('Y') }}
            </small>
        </div>
    </div>
</div>
@endsection