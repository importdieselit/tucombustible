@php
    $docsRequeridos = [
        'planilla_solicitud'    => 'Planilla de Solicitud',
        'declaracion_jurada'    => 'Declaración Jurada',
        'carta_ministerio'      => 'Carta Ministerio',
        'registro_mercantil'    => 'Registro Mercantil',
        'acta_constitutiva'     => 'Acta Constitutiva',
        'rif_legalizado'        => 'RIF Legalizado',
        'dni_contacto'          => 'C. I. de Persona de Contacto',
        'rif_contacto'          => 'RIF de Persona de Contacto',
        'islr'                  => 'ISLR (Impuesto sobre la renta)',
        'permiso_bomberos'      => 'Permiso de Bombero',
        'maquinaria_tanques'    => 'Maquinaria (Equipos y Tanques)',
        'croquis_ubicacion'     => 'Croquis de Ubicación'
    ];
    $docsSubidos = $cliente->documentos->pluck('nombre_documento')->toArray();
@endphp

<div class="bg-white rounded-lg shadow-sm border-t-4 border-orange-impordiesel overflow-hidden">
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center">
        <i class="fas fa-file-upload mr-2 text-orange-impordiesel"></i>
        <span class="text-sm font-bold text-gray-700 uppercase tracking-wider">Carga de Expediente Digital (12 Requisitos)</span>
    </div>
    
    <div class="p-6">
        <p class="text-xs text-gray-500 mb-6 uppercase tracking-tighter">Formatos admitidos: <strong>PDF, DOC, DOCX u ODT</strong> (Máx 10MB por archivo).</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            @foreach($docsRequeridos as $key => $label)
                @php $yaSubido = in_array($key, $docsSubidos); @endphp
                <div class="flex flex-col p-3 rounded-lg border {{ $yaSubido ? 'border-green-100 bg-green-50' : 'border-gray-100 bg-white shadow-sm' }}">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase {{ $yaSubido ? 'text-green-700' : 'text-gray-600' }}">
                            {{ $label }}
                        </label>
                        @if($yaSubido)
                            <span class="text-green-600 text-xs font-bold"><i class="fas fa-check-circle"></i> CARGADO</span>
                        @endif
                    </div>

                    <form action="{{ route('portal.clientes.upload.doc') }}" method="POST" enctype="multipart/form-data" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="tipo_documento" value="{{ $key }}">
                        <input type="file" name="archivo" required 
                               class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300 focus:outline-none">
                        <button type="submit" class="bg-gray-industrial hover:bg-black text-white px-3 py-1 rounded text-xs transition duration-200 uppercase font-bold">
                            Subir
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        {{-- PIE DE FORMULARIO: ENVIAR A REVISIÓN --}}
        <div class="mt-8 p-6 rounded-lg text-center transition-all duration-300 {{ count($docsSubidos) >= 12 ? 'bg-orange-50 border-2 border-orange-impordiesel' : 'bg-gray-100 border-2 border-dashed border-gray-300' }}">
            @if(count($docsSubidos) >= 12)
                <h5 class="text-orange-impordiesel font-bold uppercase mb-2"><i class="fas fa-check-double mr-2"></i>¡Expediente Completo!</h5>
                <p class="text-sm text-gray-600 mb-4">Ya puede enviar su solicitud para la validación de nuestros analistas.</p>
                <form action="{{ route('portal.clientes.finalizar.paso2') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full md:w-auto bg-orange-impordiesel hover:bg-orange-600 text-white font-bold py-4 px-12 rounded shadow-lg transition duration-300 uppercase tracking-widest text-sm">
                        <i class="fas fa-paper-plane mr-2"></i> Enviar Expediente a Revisión
                    </button>
                </form>
            @else
                <div class="max-w-md mx-auto">
                    <h5 class="text-gray-500 font-bold uppercase text-sm mb-3">Progreso de Carga: {{ count($docsSubidos) }} de 12</h5>
                    <div class="w-full bg-gray-300 rounded-full h-2 mb-4">
                        @php $porcentajeDocs = (count($docsSubidos) / 12) * 100; @endphp
                        <div class="bg-gray-industrial h-2 rounded-full transition-all duration-500" style="width: {{ $porcentajeDocs }}%"></div>
                    </div>
                    <button class="w-full bg-gray-300 text-gray-500 font-bold py-3 px-8 rounded cursor-not-allowed uppercase text-xs tracking-widest" disabled>
                        Faltan Documentos por cargar
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>