@extends('layouts.app')
@section('title', 'Nuevo Cliente')

@section('content')
<div class="container-fluid">

    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">Crear Nuevo Cliente — Combustible</h3>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
                <li class="breadcrumb-item active">Nuevo Cliente</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <form action="{{ route('clientes.store') }}" method="POST">
                        @csrf

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- SECCIÓN: TIPO DE CLIENTE --}}
                        <h5 class="text-primary mb-3 mt-2">
                            <i class="fas fa-building me-2"></i> Tipo de Cliente
                        </h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_cliente"
                                               id="tipoPadre" value="padre"
                                               {{ old('tipo_cliente', 'padre') == 'padre' ? 'checked' : '' }}
                                               onchange="toggleTokenPadre(this.value)">
                                        <label class="form-check-label" for="tipoPadre">
                                            Cliente Padre (Sede Principal)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_cliente"
                                               id="tipoSucursal" value="sucursal"
                                               {{ old('tipo_cliente') == 'sucursal' ? 'checked' : '' }}
                                               onchange="toggleTokenPadre(this.value)">
                                        <label class="form-check-label" for="tipoSucursal">
                                            Sucursal
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6" id="campoTokenPadre"
                                 style="display: {{ old('tipo_cliente') == 'sucursal' ? 'block' : 'none' }}">
                                <label class="form-label text-primary">Token de Empresa Principal <span class="text-danger">*</span></label>
                                <input type="text" name="token_padre" value="{{ old('token_padre') }}"
                                       class="form-control text-uppercase" placeholder="TOKEN DEL CLIENTE PADRE">
                                @error('token_padre') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- SECCIÓN: DATOS DE LA EMPRESA --}}
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-file-alt me-2"></i> Datos de la Empresa
                        </h5>
                        <div class="row g-3 mb-4">

                            <div class="col-md-8">
                                <label class="form-label text-primary">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" value="{{ old('nombre') }}" required
                                       class="form-control text-uppercase" placeholder="EJ: DISTRIBUIDORA GASOLÍN C.A.">
                                @error('nombre') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-primary">RIF <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="rif_tipo" required class="form-select" style="max-width:80px;">
                                        @foreach(['V','E','J','P','G','C'] as $letra)
                                            <option value="{{ $letra }}" {{ old('rif_tipo') == $letra ? 'selected' : '' }}>{{ $letra }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="rif_numero" value="{{ old('rif_numero') }}" required
                                           class="form-control" placeholder="123456789" maxlength="10"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                @error('rif') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Correo Electrónico <span class="text-danger">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                       class="form-control" placeholder="cliente@ejemplo.com">
                                @error('email') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Tipo de Combustible <span class="text-danger">*</span></label>
                                <select name="tipo_combustible_id" required class="form-select">
                                    <option value="" disabled selected>Seleccione...</option>
                                    @foreach($tiposCombustible as $tipo)
                                        <option value="{{ $tipo->id }}" {{ old('tipo_combustible_id') == $tipo->id ? 'selected' : '' }}>
                                            {{ $tipo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tipo_combustible_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Litros Solicitados / Mes <span class="text-danger">*</span></label>
                                <input type="number" name="litros_solicitados" value="{{ old('litros_solicitados') }}"
                                       min="1" required class="form-control" placeholder="Ej: 10000">
                                @error('litros_solicitados') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
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
                                <input type="text" name="contacto" value="{{ old('contacto') }}" required
                                       class="form-control text-uppercase"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"
                                       placeholder="Nombre del responsable principal">
                                @error('contacto') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Teléfono de Contacto Principal</label>
                                <input type="text" name="telefono" value="{{ old('telefono') }}"
                                       class="form-control"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       maxlength="11" placeholder="04XXXXXXXXX">
                                @error('telefono') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Persona de Contacto Alternativa</label>
                                <input type="text" name="contacto_alt" value="{{ old('contacto_alt') }}"
                                       class="form-control text-uppercase"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"
                                       placeholder="Nombre del contacto alternativo">
                                @error('contacto_alt') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Teléfono de Contacto Alternativo</label>
                                <input type="text" name="telefono_alt" value="{{ old('telefono_alt') }}"
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
                                <label class="form-label text-primary">Estado <span class="text-danger">*</span></label>
                                <select name="estado_id" id="estado_id" required class="form-select"
                                        onchange="cargarCiudades(this.value)">
                                    <option value="">Seleccione un estado...</option>
                                    @foreach($estados as $estado)
                                        <option value="{{ $estado->id }}" {{ old('estado_id') == $estado->id ? 'selected' : '' }}>
                                            {{ $estado->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('estado_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-primary">Ciudad <span class="text-danger">*</span></label>
                                <select name="ciudad_id" id="ciudad_id" required class="form-select">
                                    <option value="">Seleccione primero un estado...</option>
                                </select>
                                @error('ciudad_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-primary">Dirección Fiscal</label>
                                <textarea name="direccion" class="form-control text-uppercase" rows="2"
                                          placeholder="Dirección según RIF...">{{ old('direccion') }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-primary">Dirección Operativa (Lugar de Despacho) <span class="text-danger">*</span></label>
                                <textarea name="direccion_operativa" class="form-control text-uppercase" rows="2"
                                          required placeholder="Lugar donde se realiza la actividad...">{{ old('direccion_operativa') }}</textarea>
                                @error('direccion_operativa') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('clientes.index') }}" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Guardar Cliente
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleTokenPadre(valor) {
        document.getElementById('campoTokenPadre').style.display = valor === 'sucursal' ? 'block' : 'none';
    }

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