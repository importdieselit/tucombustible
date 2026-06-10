@extends('layouts.app')
@section('title', 'Gestión de Pagos')

@section('content')
<div class="container mx-auto py-6 px-4">

    {{-- CARDS DE RESUMEN --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-kpi border-b-success shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: 1px;">Total Pagos Registrados</h6>
                    <h2 class="fw-black text-dark mb-0">{{ $pagos->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-kpi border-b-orange shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: 1px;">Volumen Pagado</h6>
                    <h2 class="fw-black text-dark mb-0">{{ number_format($pagos->sum('litros'), 0, ',', '.') }} <span class="text-sm text-gray-400">LTS</span></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-kpi border-b-corporate shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <button type="button" onclick="openModalPago()" class="w-full bg-orange-impordiesel text-white px-4 py-3 rounded text-sm font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                        <i class="fas fa-plus-circle mr-2"></i> Registrar Nuevo Pago
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN DE HISTÓRICO DE PAGOS --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col gap-4">
                <h5 class="text-lg font-black uppercase tracking-tight text-gray-800 italic">
                    <span class="text-orange-impordiesel">|</span> Histórico de Pagos
                </h5>
                
                {{-- TOOLBAR DE FILTROS --}}
                <form action="{{ route('pagos.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    
                    <div class="flex flex-col">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Cliente</label>
                        <select name="id_cliente" onchange="this.form.submit()" class="text-xs font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase">
                            <option value="">Todos los Clientes</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}" {{ request('id_cliente') == $c->id ? 'selected' : '' }}>
                                    {{ $c->nombre }} ({{ $c->rif }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Estatus Pedido</label>
                        <select name="status_pedido" onchange="this.form.submit()" class="text-xs font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase">
                            <option value="">Todos</option>
                            <option value="pendiente" {{ request('status_pedido') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="aprobado" {{ request('status_pedido') == 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Fecha Pago</label>
                        <input type="date" name="fecha_pago" value="{{ request('fecha_pago') }}" class="text-xs font-black border-2 border-gray-300 rounded p-1.5 outline-none focus:border-orange-impordiesel bg-white uppercase">
                    </div>

                    <div class="flex items-end gap-2 lg:col-span-2">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-xs font-black uppercase hover:bg-black transition shadow-md flex-1">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>
                        @if(request()->anyFilled(['id_cliente', 'status_pedido', 'fecha_pago']))
                            <a href="{{ route('pagos.index') }}" class="bg-red-600 text-white px-3 py-2 rounded text-xs font-black uppercase hover:bg-red-700 transition shadow-md">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-auto max-h-[450px]">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 z-20">
                    <tr class="bg-gray-industrial text-white text-xs font-black uppercase tracking-widest">
                        <th class="px-6 py-4">Ref / Fecha</th>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4 text-center">Volumen</th>
                        <th class="px-6 py-4 text-center">Pedido Asociado</th>
                        <th class="px-6 py-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($pagos as $pago)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="font-black text-gray-800 uppercase text-sm leading-tight">
                                {{ $pago->referencia }}
                            </div>
                            <div class="text-xs font-bold text-gray-500 mt-1">
                                Pago: {{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-black text-gray-800 uppercase text-sm leading-tight">
                                {{ $pago->cliente->nombre ?? 'N/A' }}
                            </div>
                            <div class="text-xs font-bold text-gray-500 mt-1">
                                RIF: {{ $pago->cliente->rif ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-sm font-black text-gray-700">
                                {{ number_format($pago->litros, 0, ',', '.') }}
                                <span class="text-[10px] text-gray-400 uppercase">Lts</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($pago->pedido)
                                <span class="px-3 py-1 rounded text-[10px] font-black uppercase border shadow-sm bg-gray-100 text-gray-700 border-gray-300">
                                    #{{ str_pad($pago->pedido->id, 5, '0', STR_PAD_LEFT) }} - {{ $pago->pedido->estado }}
                                </span>
                            @else
                                <span class="text-xs font-bold text-gray-400">Generando...</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="viewPago({{ $pago->id }})" class="text-gray-500 hover:text-orange-impordiesel mx-1 transition">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 font-black uppercase text-xs tracking-widest">
                            No se encontraron registros de pagos.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL MULTIFUNCIÓN (REGISTRO / EDICIÓN / CONSULTA) --}}
<div id="modalPago" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-industrial text-white flex justify-between items-center">
            <h5 class="text-md font-black uppercase tracking-tight" id="modalTitle">Registrar Nuevo Pago</h5>
            <button onclick="closeModalPago()" class="text-white hover:text-orange-impordiesel transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <form id="formPago" onsubmit="submitPago(event)" class="p-6">
            @csrf
            <input type="hidden" name="id_usuario" value="{{ auth()->id() ?? 1 }}">
            <input type="hidden" name="id_pedido" id="id_pedido" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                {{-- Bloque de Cliente y Contacto --}}
                <div class="col-span-1 md:col-span-2">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Cliente *</label>
                    <select name="id_cliente" id="id_cliente" required onchange="cargarDatosContacto()" class="w-full text-sm font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase">
                        <option value="">Seleccione un cliente...</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" 
                                data-telefono="{{ $c->telefono }}" 
                                data-contacto="{{ $c->contacto }}">
                                {{ $c->rif }} - {{ $c->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-1">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Persona Contacto *</label>
                    <input type="text" name="persona_contacto" id="persona_contacto" required class="w-full text-sm font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase" placeholder="Ej: Juan Perez">
                </div>

                <div class="col-span-1">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Teléfono (WhatsApp) *</label>
                    <input type="text" name="telefono_contacto" id="telefono_contacto" required class="w-full text-sm font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase" placeholder="Ej: 04121234567">
                </div>

                {{-- Bloque de Detalles del Pago --}}
                <div class="col-span-1 md:col-span-2 my-2 border-t border-gray-200"></div>

                <div class="col-span-1">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">N° de Referencia *</label>
                    <input type="text" name="referencia" id="referencia" required class="w-full text-sm font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase">
                </div>

                <div class="col-span-1">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Cantidad (Litros) *</label>
                    <input type="number" name="litros" id="litros" step="1" required class="w-full text-sm font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase">
                </div>

                <div class="col-span-1">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Fecha de Solicitud *</label>
                    <input type="date" name="fecha_solicitud" id="fecha_solicitud" required class="w-full text-sm font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase">
                </div>

                <div class="col-span-1">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Fecha de Pago *</label>
                    <input type="date" name="fecha_pago" id="fecha_pago" required class="w-full text-sm font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeModalPago()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-xs font-black uppercase hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" id="btnSubmitPago" class="bg-orange-impordiesel text-white px-6 py-2 rounded text-xs font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                    Guardar Pago
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModalPago() {
        document.getElementById('formPago').reset();
        document.getElementById('modalTitle').innerText = 'Registrar Nuevo Pago';
        document.getElementById('btnSubmitPago').style.display = 'block';
        document.getElementById('modalPago').classList.remove('hidden');
        
        // Seteo de fechas por defecto al día de hoy
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('fecha_solicitud').value = hoy;
        document.getElementById('fecha_pago').value = hoy;
    }

    function closeModalPago() {
        document.getElementById('modalPago').classList.add('hidden');
    }

    function cargarDatosContacto() {
        const select = document.getElementById('id_cliente');
        const opcionSeleccionada = select.options[select.selectedIndex];
        
        const inputContacto = document.getElementById('persona_contacto');
        const inputTelefono = document.getElementById('telefono_contacto');

        if(opcionSeleccionada.value !== "") {
            // Lee los datos almacenados en los atributos de la etiqueta option
            const contactoDB = opcionSeleccionada.getAttribute('data-contacto');
            const telefonoDB = opcionSeleccionada.getAttribute('data-telefono');

            inputContacto.value = contactoDB ? contactoDB : '';
            inputTelefono.value = telefonoDB ? telefonoDB : '';
        } else {
            inputContacto.value = '';
            inputTelefono.value = '';
        }
    }

    async function submitPago(e) {
        e.preventDefault();
        const form = document.getElementById('formPago');
        const formData = new FormData(form);
        const btnSubmit = document.getElementById('btnSubmitPago');
        
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...';
        btnSubmit.disabled = true;

        try {
            const response = await fetch("{{ route('pagos.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (response.ok) {
                alert('Pago registrado con éxito y pedido generado (si aplicaba).');
                location.reload(); // Recarga para actualizar la tabla
            } else {
                const errorData = await response.json();
                console.error(errorData);
                alert('Error al validar/guardar los datos. Verifique los campos obligatorios.');
            }
        } catch (error) {
            console.error('Error de red:', error);
            alert('Error al intentar conectar con el servidor.');
        } finally {
            btnSubmit.innerHTML = 'Guardar Pago';
            btnSubmit.disabled = false;
        }
    }

    function viewPago(id) {
        // Aquí podrías crear un Fetch hacia un endpoint de show o getDetailsForView 
        // para rellenar el formulario en modo "Solo lectura". 
        alert("Integración de visualización pendiente para el ID: " + id);
    }
</script>
@endpush