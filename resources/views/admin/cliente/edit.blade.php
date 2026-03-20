@extends('layouts.app')
@section('title', 'Editar Cliente')

@section('content')
<div class="container-fluid">

    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">Editar: {{ $cliente->nombre }}</h3>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
                <li class="breadcrumb-item"><a href="{{ route('clientes.show', $cliente->id) }}">Expediente</a></li>
                <li class="breadcrumb-item active">Editar</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-muted small text-uppercase">Actualizar Datos Generales</span>
                    <span class="badge bg-secondary">RIF actual: {{ $cliente->rif }}</span>
                </div>
                <div class="card-body">

                    <form action="{{ route('clientes.update', $cliente->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- SECCIÓN: DATOS DE LA EMPRESA --}}
                        <h5 class="text-primary mb-3 mt-2">
                            <i class="fas fa-file-alt me-2"></i> Datos de la Empresa
                        </h5>
                        <div class="row g-3 mb-4">

                            <div class="col-md-8">
                                <label class="form-label text-primary">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                                       class="form-control text-uppercase">
                                @error('nombre') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            @php
                                $rifPartes  = explode('-', $cliente->rif);
                                $letraBase  = $rifPartes[0] ?? 'J';
                                $numeroBase = $rifPartes[1] ?? '';
                            @endphp
                            <div class="col-md-4">
                                <label class="form-label text-primary">RIF <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="rif_tipo" required class="form-select" style="max-width:80px;">
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
                                <label class="form-label text-primary">Correo Electrónico <span class="text-danger">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $cliente->email) }}" required
                                       class="form-control">
                                @error('email') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                        </div>

                        <hr class="my-4">

                        {{-- SECCIÓN: PERSONAS DE CONTACTO --}}
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-users me-2"></i> Personas de Contacto
                        </h5>
                        <div class="row g-3 mb-4">

                            <div class="col-md-6">
                                <label class="form-label text-primary">Persona de Contacto Principal <span class="text-danger">*</span></label>
                                <input type="text" name="contacto" value="{{ old('contacto', $cliente->contacto) }}" required
                                       class="form-control text-uppercase"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')">
                                @error('contacto') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Teléfono de Contacto Principal</label>
                                <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                                       class="form-control"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       maxlength="11" placeholder="04XXXXXXXXX">
                                @error('telefono') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Persona de Contacto Alternativa</label>
                                <input type="text" name="contacto_alt" value="{{ old('contacto_alt', $cliente->contacto_alt) }}"
                                       class="form-control text-uppercase"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"
                                       placeholder="Nombre del contacto alternativo">
                                @error('contacto_alt') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Teléfono de Contacto Alternativo</label>
                                <input type="text" name="telefono_alt" value="{{ old('telefono_alt', $cliente->telefono_alt) }}"
                                       class="form-control"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       maxlength="11" placeholder="04XXXXXXXXX">
                                @error('telefono_alt') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                        </div>

                        <hr class="my-4">

                        {{-- SECCIÓN: UBICACIÓN --}}
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i> Ubicación
                        </h5>
                        <div class="row g-3 mb-4">

                            <div class="col-md-6">
                                <label class="form-label text-primary">Estado</label>
                                <select name="estado_id" id="estado_id" class="form-select"
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
                                <label class="form-label text-primary">Ciudad</label>
                                <select name="ciudad_id" id="ciudad_id" class="form-select">
                                    @if($cliente->ciudad)
                                        <option value="{{ $cliente->ciudad_id }}" selected>{{ $cliente->ciudad->nombre }}</option>
                                    @else
                                        <option value="">Seleccione primero un estado...</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-primary">Dirección Fiscal</label>
                                <textarea name="direccion" class="form-control text-uppercase" rows="2">{{ old('direccion', $cliente->direccion) }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-primary">Dirección Operativa <span class="text-danger">*</span></label>
                                <textarea name="direccion_operativa" class="form-control text-uppercase" rows="2" required>{{ old('direccion_operativa', $cliente->direccion_operativa) }}</textarea>
                                @error('direccion_operativa') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('clientes.show', $cliente->id) }}" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
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
        fetch(`/obtener-ciudades/${estadoId}`)
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