@extends('layouts.app')
@section('title', 'Editar Cliente')

@section('content')
<div class="container-fluid">

    {{-- ENCABEZADO DE PÁGINA --}}
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="text-orange mb-0 fw-bold"><i class="fas fa-user-edit me-2"></i>Editar Cliente</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-muted">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}" class="text-muted">Clientes</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('clientes.show', $cliente->id) }}" class="text-muted">Expediente</a></li>
                        <li class="breadcrumb-item active text-orange" aria-current="page">Editar</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-orange">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="fas fa-id-card me-2 text-orange"></i>Actualizar Información: <span class="text-muted">{{ $cliente->nombre }}</span>
                    </h5>
                    <span class="badge bg-light text-dark border fw-normal">RIF: {{ $cliente->rif }}</span>
                </div>
                <div class="card-body p-4">

                    <form action="{{ route('clientes.update', $cliente->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm mb-4">
                                <div class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Por favor corrige los siguientes errores:</div>
                                <ul class="mb-0 mt-2 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- SECCIÓN: DATOS DE LA EMPRESA --}}
                        <div class="d-flex align-items-center mb-3 mt-2">
                            <span class="bg-orange text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:24px; height:24px; font-size: 12px;">1</span>
                            <h5 class="text-orange mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Datos de la Empresa</h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                                       class="form-control text-uppercase" placeholder="RAZÓN SOCIAL">
                                @error('nombre') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            @php
                                $rifPartes  = explode('-', $cliente->rif);
                                $letraBase  = $rifPartes[0] ?? 'J';
                                $numeroBase = $rifPartes[1] ?? '';
                            @endphp
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">RIF <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="rif_tipo" required class="form-select bg-light" style="max-width:80px;">
                                        @foreach(['V','E','J','P','G','C'] as $letra)
                                            <option value="{{ $letra }}" {{ old('rif_tipo', $letraBase) == $letra ? 'selected' : '' }}>{{ $letra }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="rif_numero" value="{{ old('rif_numero', $numeroBase) }}" required
                                           class="form-control" maxlength="10"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                @error('rif') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Correo Electrónico <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" name="email" value="{{ old('email', $cliente->email) }}" required
                                           class="form-control border-start-0" placeholder="cliente@ejemplo.com">
                                </div>
                                @error('email') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- SECCIÓN: PERSONAS DE CONTACTO --}}
                        <div class="d-flex align-items-center mb-3 mt-4">
                            <span class="bg-orange text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:24px; height:24px; font-size: 12px;">2</span>
                            <h5 class="text-orange mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Personas de Contacto</h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Persona de Contacto Principal <span class="text-danger">*</span></label>
                                <input type="text" name="contacto" value="{{ old('contacto', $cliente->contacto) }}" required
                                       class="form-control text-uppercase"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')">
                                @error('contacto') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Teléfono Principal</label>
                                <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                                       class="form-control"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       maxlength="11" placeholder="04XXXXXXXXX">
                                @error('telefono') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Persona de Contacto Alternativa</label>
                                <input type="text" name="contacto_alt" value="{{ old('contacto_alt', $cliente->contacto_alt) }}"
                                       class="form-control text-uppercase"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')">
                                @error('contacto_alt') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Teléfono Alternativo</label>
                                <input type="text" name="telefono_alt" value="{{ old('telefono_alt', $cliente->telefono_alt) }}"
                                       class="form-control"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       maxlength="11" placeholder="04XXXXXXXXX">
                                @error('telefono_alt') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- SECCIÓN: UBICACIÓN --}}
                        <div class="d-flex align-items-center mb-3 mt-4">
                            <span class="bg-orange text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:24px; height:24px; font-size: 12px;">3</span>
                            <h5 class="text-orange mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Ubicación</h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Estado</label>
                                <select name="estado_id" id="estado_id" class="form-select select2-basic"
                                        onchange="cargarCiudades(this.value)">
                                    <option value="">Seleccione...</option>
                                    @foreach($estados as $estado)
                                        <option value="{{ $estado->id }}"
                                            {{ old('estado_id', $cliente->estado_id) == $estado->id ? 'selected' : '' }}>
                                            {{ $estado->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Ciudad</label>
                                <select name="ciudad_id" id="ciudad_id" class="form-select select2-basic">
                                    @if($cliente->ciudad)
                                        <option value="{{ $cliente->ciudad_id }}" selected>{{ $cliente->ciudad->nombre }}</option>
                                    @else
                                        <option value="">Seleccione primero un estado...</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">Dirección Fiscal</label>
                                <textarea name="direccion" class="form-control text-uppercase" rows="2">{{ old('direccion', $cliente->direccion) }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">Dirección Operativa <span class="text-danger">*</span></label>
                                <textarea name="direccion_operativa" class="form-control text-uppercase" rows="2" required>{{ old('direccion_operativa', $cliente->direccion_operativa) }}</textarea>
                                @error('direccion_operativa') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- BOTONERA --}}
                        <div class="d-flex justify-content-end mt-5 pt-3 border-top">
                            <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-light border me-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class=" btn-orange px-4 text-white fw-bold">
                                <i class="fas fa-sync-alt me-2"></i> Actualizar Información
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function cargarCiudades(estadoId) {
        const select = document.getElementById('ciudad_id');
        select.innerHTML = '<option value="">Cargando...</option>';
        if (!estadoId) {
            select.innerHTML = '<option value="">Seleccione primero un estado...</option>';
            return;
        }

        let urlBase = "{{ route('ciudades.get', ':id') }}";
        let urlFinal = urlBase.replace(':id', estadoId);

        fetch(urlFinal)
            .then(r => r.json())
            .then(ciudades => {
                select.innerHTML = '<option value="">Seleccione una ciudad...</option>';
                ciudades.forEach(c => {
                    select.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
                });
            })
            .catch(() => {
                select.innerHTML = '<option value="">Error al cargar ciudades</option>';
            });
    }
</script>
@endsection