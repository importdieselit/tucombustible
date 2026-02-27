@extends('layouts.app')

@section('title', 'Gestionar Expediente')

@section('content')
<div class="max-w-5xl mx-auto py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $cliente->name }}</h1>
            <p class="text-sm text-gray-500 uppercase tracking-widest">RIF: {{ $cliente->cliente->rif }}</p>
        </div>
        <div class="text-right">
            <span class="px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-xs font-bold uppercase">
                {{ $dashboardService->getNombrePaso($cliente->cliente->registro_paso) }}
            </span>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
        <h3 class="text-xs font-bold text-gray-400 uppercase mb-4">Control de Flujo</h3>
        <div class="flex items-center justify-between gap-1">
            @for ($i = 1; $i <= 10; $i++)
                <form action="{{ route('captacion.updateStep', $cliente->id) }}" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="paso" value="{{ $i }}">
                    <button type="submit" 
                        class="w-full py-2 rounded-md text-[10px] font-bold transition-all
                        {{ $cliente->cliente->registro_paso == $i ? 'bg-blue-600 text-white' : ($cliente->cliente->registro_paso > $i ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400 hover:bg-gray-200') }}">
                        P{{ $i }}
                    </button>
                </form>
            @endfor
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-700 uppercase">Documentación Recibida</h3>
        </div>
        <table class="w-full text-left">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase">Documento</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase">Estado</th>
                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($cliente->cliente->documentos as $doc)
                <tr>
                    <td class="px-6 py-4">
                        <p class="text-sm font-medium text-gray-800">{{ $doc->requisito->nombre }}</p>
                        <a href="{{ Storage::url($doc->ruta) }}" target="_blank" class="text-blue-500 text-[10px] font-bold hover:underline">
                            <i class="fas fa-external-link-alt mr-1"></i> VER ARCHIVO
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        @if($doc->estatus_archivo == 'validado')
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-[10px] font-bold uppercase">Validado</span>
                        @elseif($doc->estatus_archivo == 'rechazado')
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-[10px] font-bold uppercase">Rechazado</span>
                        @else
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-[10px] font-bold uppercase">Pendiente</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        @if($doc->estatus_archivo != 'validado')
                            <form action="{{ route('captacion.validarDoc', $doc->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="validado">
                                <button class="p-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition shadow-sm">
                                    <i class="fas fa-check text-xs"></i>
                                </button>
                            </form>

                            <button onclick="rechazarDocumento({{ $doc->id }})" class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition shadow-sm">
                                <i class="fas fa-times text-xs"></i>
                            </button>

                            {{-- Formulario oculto para el SweetAlert2 de rechazo --}}
                            <form id="form-rechazo-{{ $doc->id }}" action="{{ route('captacion.validarDoc', $doc->id) }}" method="POST" class="hidden">
                                @csrf
                                <input type="hidden" name="status" value="rechazado">
                                <input type="hidden" name="observaciones" id="obs-{{ $doc->id }}">
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function rechazarDocumento(id) {
        Swal.fire({
            title: '¿Rechazar Documento?',
            text: "Indica el motivo para que el cliente pueda corregirlo:",
            input: 'textarea',
            inputPlaceholder: 'Ej: El documento está vencido...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, rechazar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                document.getElementById('obs-' + id).value = result.value;
                document.getElementById('form-rechazo-' + id).submit();
            } else if (result.isConfirmed && !result.value) {
                Swal.fire('Atención', 'Debes escribir un motivo de rechazo', 'error');
            }
        });
    }
</script>
@endpush