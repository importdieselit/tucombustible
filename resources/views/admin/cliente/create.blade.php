@extends('layouts.app')
@section('title', 'Nuevo Cliente')
@section('content')
<div class="container-fluid">

    {{-- ENCABEZADO DE PÁGINA --}}
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="text-orange mb-0 fw-bold"><i class="fas fa-user-plus me-2"></i>Crear Nuevo Cliente</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-muted">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}" class="text-muted">Clientes</a></li>
                        <li class="breadcrumb-item active text-orange" aria-current="page">Nuevo Cliente</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-orange">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="fas fa-file-signature me-2 text-orange"></i>Formulario de Registro
                    </h5>
                </div>
                <div class="card-body p-4">

                    <form action="{{ route('clientes.store') }}" method="POST">
                        @csrf

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

                        {{-- SECCIÓN: TIPO DE CLIENTE --}}
                        <div class="d-flex align-items-center mb-3">
                            <span class="bg-orange text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:24px; height:24px; font-size: 12px;">1</span>
                            <h5 class="text-orange mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Tipo de Cliente</h5>
                        </div>

                        <div class="row g-3 mb-4 bg-light p-3 rounded-3 border">
                            <div class="col-md-12">
                                <div class="d-flex gap-4">
                                    <div class="form-check custom-radio">
                                        <input class="form-check-input" type="radio" name="tipo_cliente"
                                               id="tipoPadre" value="padre"
                                               {{ old('tipo_cliente', 'padre') == 'padre' ? 'checked' : '' }}
                                               onchange="toggleTokenPadre(this.value)">
                                        <label class="form-check-label fw-bold" for="tipoPadre">
                                            Cliente Padre (Sede Principal)
                                        </label>
                                    </div>
                                    <div class="form-check custom-radio">
                                        <input class="form-check-input" type="radio" name="tipo_cliente"
                                               id="tipoSucursal" value="sucursal"
                                               {{ old('tipo_cliente') == 'sucursal' ? 'checked' : '' }}
                                               onchange="toggleTokenPadre(this.value)">
                                        <label class="form-check-label fw-bold" for="tipoSucursal">
                                            Sucursal
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6" id="campoTokenPadre"
                                 style="display: {{ old('tipo_cliente') == 'sucursal' ? 'block' : 'none' }}">
                                <label class="form-label fw-bold small text-muted">Token de Empresa Principal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-key text-orange"></i></span>
                                    <input type="text" name="token_padre" value="{{ old('token_padre') }}"
                                           class="form-control text-uppercase border-start-0" placeholder="TOKEN DEL CLIENTE PADRE">
                                </div>
                                @error('token_padre') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- SECCIÓN: DATOS DE LA EMPRESA --}}
                        <div class="d-flex align-items-center mb-3 mt-4">
                            <span class="bg-orange text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:24px; height:24px; font-size: 12px;">2</span>
                            <h5 class="text-orange mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Datos de la Empresa</h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" value="{{ old('nombre') }}" required
                                       class="form-control text-uppercase" placeholder="EJ: DISTRIBUIDORA GASOLÍN C.A.">
                                @error('nombre') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">RIF <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="rif_tipo" required class="form-select bg-light" style="max-width:80px;">
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
                                <label class="form-label fw-bold small text-muted">Correo Electrónico <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" name="email" value="{{ old('email') }}" required
                                           class="form-control border-start-0" placeholder="cliente@ejemplo.com">
                                </div>
                                @error('email') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Tipo de Combustible <span class="text-danger">*</span></label>
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
                                <label class="form-label fw-bold small text-muted">Litros Solicitados / Mes <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="litros_solicitados" value="{{ old('litros_solicitados') }}"
                                           min="1" required class="form-control border-end-0" placeholder="Ej: 10000">
                                    <span class="input-group-text bg-light border-start-0 fw-bold">LTS</span>
                                </div>
                                @error('litros_solicitados') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- SECCIÓN: PERSONAS DE CONTACTO --}}
                        <div class="d-flex align-items-center mb-3 mt-4">
                            <span class="bg-orange text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:24px; height:24px; font-size: 12px;">3</span>
                            <h5 class="text-orange mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Personas de Contacto</h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Contacto Principal <span class="text-danger">*</span></label>
                                <input type="text" name="contacto" value="{{ old('contacto') }}" required
                                       class="form-control text-uppercase"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"
                                       placeholder="Nombre del responsable">
                                @error('contacto') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Teléfono Principal</label>
                                <input type="text" name="telefono" value="{{ old('telefono') }}"
                                       class="form-control"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       maxlength="11" placeholder="04XXXXXXXXX">
                                @error('telefono') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Contacto Alternativo</label>
                                <input type="text" name="contacto_alt" value="{{ old('contacto_alt') }}"
                                       class="form-control text-uppercase"
                                       oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"
                                       placeholder="Nombre opcional">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Teléfono Alternativo</label>
                                <input type="text" name="telefono_alt" value="{{ old('telefono_alt') }}"
                                       class="form-control"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       maxlength="11" placeholder="04XXXXXXXXX">
                            </div>
                        </div>

                        {{-- SECCIÓN: UBICACIÓN --}}
                        <div class="d-flex align-items-center mb-3 mt-4">
                            <span class="bg-orange text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:24px; height:24px; font-size: 12px;">4</span>
                            <h5 class="text-orange mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Ubicación y Despacho</h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Estado <span class="text-danger">*</span></label>
                                <select name="estado_id" id="estado_id" required class="form-select select2-basic"
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
                                <label class="form-label fw-bold small text-muted">Ciudad <span class="text-danger">*</span></label>
                                <select name="ciudad_id" id="ciudad_id" required class="form-select select2-basic">
                                    <option value="">Seleccione primero un estado...</option>
                                </select>
                                @error('ciudad_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">Dirección Fiscal</label>
                                <textarea name="direccion" class="form-control text-uppercase" rows="2"
                                          placeholder="Dirección según RIF...">{{ old('direccion') }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">Dirección Operativa (Lugar de Despacho) <span class="text-danger">*</span></label>
                                <textarea name="direccion_operativa" class="form-control text-uppercase" rows="2"
                                          required placeholder="Lugar donde se realiza la actividad...">{{ old('direccion_operativa') }}</textarea>
                                @error('direccion_operativa') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-5 pt-3 border-top">
                            <a href="{{ route('clientes.index') }}" class="btn btn-light border me-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn-orange px-4 text-white">
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