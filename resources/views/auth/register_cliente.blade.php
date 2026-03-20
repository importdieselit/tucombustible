@extends('layouts.app')

@section('content')
<div class="container mt-5 mb-5">
    <div class="card shadow border-0">
        <div class="card-header bg-primary text-white py-3">
            <h4 class="mb-0"><i class="fas fa-user-plus mr-2"></i> Registro de Nuevo Cliente</h4>
        </div>
        <div class="card-body">
            
            {{-- BLOQUE DE ERRORES --}}
            @if ($errors->any())
                <div class="alert alert-danger shadow-sm">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li><i class="fas fa-exclamation-circle mr-2"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('cliente.register.store') }}" method="POST" id="formRegistro">
                @csrf
                <div class="row">
                    {{-- Razón Social --}}
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Razón Social</label>
                        <input type="text" name="razon_social" class="form-control text-uppercase" placeholder="Nombre de la empresa" value="{{ old('razon_social') }}" required>
                    </div>

                    {{-- RIF Dividido --}}
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">RIF</label>
                        <div class="d-flex">
                            <select name="rif_tipo" class="form-control mr-2" style="width: 80px;" required>
                                <option value="J" {{ old('rif_tipo') == 'J' ? 'selected' : '' }}>J</option>
                                <option value="G" {{ old('rif_tipo') == 'G' ? 'selected' : '' }}>G</option>
                                <option value="V" {{ old('rif_tipo') == 'V' ? 'selected' : '' }}>V</option>
                                <option value="E" {{ old('rif_tipo') == 'E' ? 'selected' : '' }}>E</option>
                                <option value="P" {{ old('rif_tipo') == 'P' ? 'selected' : '' }}>P</option>
                            </select>
                            <input type="text" name="rif_numero" class="form-control" 
                                   placeholder="123456789" 
                                   value="{{ old('rif_numero') }}"
                                   required 
                                   maxlength="10"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>

                    {{-- Persona de Contacto --}}
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Persona de Contacto</label>
                        <input type="text" name="contacto" class="form-control" 
                               value="{{ old('contacto') }}"
                               required 
                               oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                    </div>

                    {{-- Teléfono de Persona de Contacto --}}
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Teléfono de Contacto</label>
                        <input type="text" name="telefono" class="form-control" 
                               value="{{ old('telefono') }}"
                               placeholder="04141234567" 
                               required 
                               maxlength="11"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>

                    {{-- Correo Electrónico --}}
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" placeholder="ejemplo@correo.com" value="{{ old('email') }}" required>
                    </div>

                    {{-- Estado --}}
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Estado</label>
                        <select name="estado_id" id="estado_id" class="form-control" required>
                            <option value="">Seleccione Estado</option>
                            @foreach($estados as $estado)
                                <option value="{{ $estado->id }}" {{ old('estado_id') == $estado->id ? 'selected' : '' }}>{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Ciudad --}}
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Ciudad</label>
                        <select name="ciudad_id" id="ciudad_id" class="form-control" required>
                            <option value="">Seleccione Ciudad</option>
                        </select>
                    </div>

                    {{-- Dirección Operativa --}}
                    <div class="col-md-12 mb-4">
                        <label class="font-weight-bold">Dirección Operativa</label>
                        <input type="text" name="direccion_operativa" class="form-control" value="{{ old('direccion_operativa') }}" required>
                    </div>

                    {{-- SECCIÓN DE COMBUSTIBLES --}}
                    <div class="col-12">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-info-circle mr-1"></i> Indique la cantidad de litros mensuales para el producto que desea solicitar. Puede solicitar ambos.
                        </div>
                    </div>

                    {{-- Litros Diesel --}}
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold text-primary">Litros Diesel Mensuales</label>
                        <div class="input-group">
                            <input type="number" name="litros_diesel" class="form-control" 
                                   value="{{ old('litros_diesel') }}"
                                   placeholder="Cantidad para Diesel"
                                   min="0"
                                   onkeydown="return event.keyCode !== 69">
                            <div class="input-group-append">
                                <span class="input-group-text">Lts</span>
                            </div>
                        </div>
                    </div>

                    {{-- Litros MGO --}}
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold text-info">Litros MGO Mensuales</label>
                        <div class="input-group">
                            <input type="number" name="litros_mgo" class="form-control" 
                                   value="{{ old('litros_mgo') }}"
                                   placeholder="Cantidad para MGO"
                                   min="0"
                                   onkeydown="return event.keyCode !== 69">
                            <div class="input-group-append">
                                <span class="input-group-text">Lts</span>
                            </div>
                        </div>
                    </div>

                    {{-- Configuración de Solicitud --}}
                    <div class="col-md-6 mb-3 mt-2">
                        <label class="font-weight-bold">Tipo de Solicitud</label>
                        <select name="tipo_solicitud" class="form-control" required>
                            <option value="nuevo" {{ old('tipo_solicitud') == 'nuevo' ? 'selected' : '' }}>Nueva Solicitud</option>
                            <option value="migracion" {{ old('tipo_solicitud') == 'migracion' ? 'selected' : '' }}>Migración</option>
                        </select>
                    </div>

                    {{-- Sucursal --}}
                    <div class="col-md-12 mb-3 mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="es_sucursal" name="tipo_cliente" value="sucursal" {{ old('tipo_cliente') == 'sucursal' ? 'checked' : '' }}>
                            <label class="form-check-label font-weight-bold" for="es_sucursal">¿Es una sucursal de una empresa ya registrada?</label>
                        </div>
                        <input type="text" name="token_padre" id="token_padre" 
                               class="form-control mt-2 {{ old('tipo_cliente') == 'sucursal' ? '' : 'd-none' }}" 
                               placeholder="Ingrese el Token de la Empresa Principal"
                               value="{{ old('token_padre') }}">
                    </div>
                </div>

                <div class="text-right mt-4">
                    <a href="{{ route('login') }}" class="btn btn-secondary">Volver al Login</a>
                    <button type="submit" class="btn btn-success px-5 font-weight-bold">Enviar Datos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('es_sucursal').addEventListener('change', function() {
        const tokenInput = document.getElementById('token_padre');
        if(this.checked) {
            tokenInput.classList.remove('d-none');
            tokenInput.required = true;
        } else {
            tokenInput.classList.add('d-none');
            tokenInput.required = false;
            tokenInput.value = '';
        }
    });

    document.getElementById('estado_id').addEventListener('change', function() {
        const estadoId = this.value;
        const ciudadSelect = document.getElementById('ciudad_id');
        const oldCiudadId = "{{ old('ciudad_id') }}";
        
        ciudadSelect.innerHTML = '<option value="">Cargando ciudades...</option>';

        if (estadoId) {
            fetch(`{{ route('ciudades.get',${estadoId}) }}`)
                .then(response => response.json())
                .then(data => {
                    ciudadSelect.innerHTML = '<option value="">Seleccione Ciudad</option>';
                    data.forEach(ciudad => {
                        const selected = (oldCiudadId == ciudad.id) ? 'selected' : '';
                        ciudadSelect.innerHTML += `<option value="${ciudad.id}" ${selected}>${ciudad.nombre}</option>`;
                    });
                })
                .catch(error => {
                    ciudadSelect.innerHTML = '<option value="">Error al cargar</option>';
                });
        } else {
            ciudadSelect.innerHTML = '<option value="">Seleccione Ciudad</option>';
        }
    });

    window.onload = function() {
        if(document.getElementById('estado_id').value) {
            document.getElementById('estado_id').dispatchEvent(new Event('change'));
        }
    };
</script>
@endsection