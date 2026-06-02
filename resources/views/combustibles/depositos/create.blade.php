@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="constructorDeTanque('{{ old('forma', 'CH') }}', '{{ old('capacidad_maxima', 0) }}')">
    
    {{-- ENCABEZADO COPIADO DE TU ESTILO --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border-left-orange">
            <div>
                <h3 class="text-orange mb-0 fw-black text-uppercase">
                    <i class="fas fa-database me-2"></i>Infraestructura: Registrar Nuevo Tanque
                </h3>
            </div>
            <div class="text-right">
                <a href="{{ route('depositos.index') }}" class="btn btn-sm btn-outline-secondary fw-bold">VOLVER</a>
            </div>
        </div>
    </div>

    {{-- ALERTAS DE ERROR NATIVAS DE LARAVEL --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm border-0 mb-4 rounded">
            <h5 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Por favor corrige los siguientes errores:</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORMULARIO PRINCIPAL --}}
    <form action="{{ route('depositos.store') }}" method="POST">
        @csrf

        <div class="row g-4">
            
            {{-- BLOQUE 1: DATOS OPERATIVOS DEL TANQUE --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-secondary text-uppercase">
                            <i class="fas fa-sliders-h me-2 text-orange"></i>Datos Operativos
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold text-muted small text-uppercase">Serial o Nombre del Tanque</label>
                                <input type="text" name="serial" class="form-control border-orange @error('serial') is-invalid @enderror" value="{{ old('serial') }}" placeholder="Ej: Tanque 1" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase">Sede Asignada</label>
                                <select name="id_sede" class="form-select" required>
                                    <option value="">Seleccione Sede...</option>
                                    @foreach($sedes as $sede)
                                        <option value="{{ $sede->id }}" {{ old('id_sede') == $sede->id ? 'selected' : '' }}>
                                            {{ $sede->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase">Tipo de Combustible</label>
                                <select name="tipo_combustible_id" class="form-select" required>
                                    <option value="">Seleccione Combustible...</option>
                                    @foreach($tiposCombustible as $tipo)
                                        <option value="{{ $tipo->id }}" {{ old('tipo_combustible_id') == $tipo->id ? 'selected' : '' }}>
                                            {{ $tipo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase">Capacidad Máxima Nominal (Lts)</label>
                                {{-- Agregamos x-model para capturar el número en tiempo real --}}
                                <input type="number" step="0.01" name="capacidad_maxima" x-model.number="capacidadMaxima" class="form-control" placeholder="Ej: 35000" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase">Nivel de Alerta Stock Bajo (Lts) <span class="text-orange fw-bold">(20%)</span></label>
                                {{-- Campo de Solo Lectura calculado dinámicamente por Alpine --}}
                                <input type="text" class="form-control bg-light fw-bold text-secondary border-orange" 
                                    :value="formatoNumero(capacidadMaxima * 0.20)" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BLOQUE 2: GEOMETRÍA Y DIMENSIONES MECÁNICAS --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-secondary text-uppercase">
                            <i class="fas fa-ruler-combined me-2 text-orange"></i>Geometría Física
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold text-muted small text-uppercase">Forma Geométrica del Tanque</label>
                                <select name="forma" class="form-select border-orange fw-bold text-secondary" x-model="forma" required>
                                    <option value="CH">Cilíndrico Horizontal (CH)</option>
                                    <option value="CV">Cilíndrico Vertical (CV)</option>
                                    <option value="OH">Oval Horizontal (OH)</option>
                                    <option value="OV">Oval Vertical (OV)</option>
                                    <option value="R">Rectangular / Prisma (R)</option>
                                    <option value="C">Cúbico (C)</option>
                                    <option value="E">Esférico (E)</option>
                                </select>
                            </div>

                            {{-- INPUTS CONDICIONALES ANIMADOS CON ALPINE --}}
                            
                            {{-- DIÁMETRO: Requerido en CH, CV, OH, Cúbicos (C) y Esféricos (E) según el backend viejo --}}
                            <div class="col-md-6" x-show="['CH', 'CV', 'OH', 'C', 'E'].includes(forma)" x-transition>
                                <label class="form-label fw-bold text-muted small text-uppercase text-orange">Diámetro / Dimensión Base (cm)</label>
                                <input type="number" step="0.01" name="diametro" class="form-control border-orange" value="{{ old('diametro') }}" placeholder="Medida en cm">
                            </div>

                            {{-- LONGITUD: Requerido en tanques con cuerpo horizontal (CH, R, OH, OV) --}}
                            <div class="col-md-6" x-show="['CH', 'R', 'OH', 'OV'].includes(forma)" x-transition>
                                <label class="form-label fw-bold text-muted small text-uppercase text-orange">Longitud / Largo (cm)</label>
                                <input type="number" step="0.01" name="longitud" class="form-control border-orange" value="{{ old('longitud') }}" placeholder="Medida en cm">
                            </div>

                            {{-- ANCHO: Requerido en prismas y óvalos (R, OH, OV) --}}
                            <div class="col-md-6" x-show="['R', 'OH', 'OV'].includes(forma)" x-transition>
                                <label class="form-label fw-bold text-muted small text-uppercase text-orange">Ancho (cm)</label>
                                <input type="number" step="0.01" name="ancho" class="form-control border-orange" value="{{ old('ancho') }}" placeholder="Medida en cm">
                            </div>

                            {{-- ALTO: Requerido en Rectangulares (R), Cilíndricos Verticales (CV) y Ovales Verticales (OV) --}}
                            <div class="col-md-6" x-show="['R', 'CV', 'OV'].includes(forma)" x-transition>
                                <label class="form-label fw-bold text-muted small text-uppercase text-orange">Alto / Altura Total (cm)</label>
                                <input type="number" step="0.01" name="alto" class="form-control border-orange" value="{{ old('alto') }}" placeholder="Medida en cm">
                            </div>

                            {{-- NOTA DE INGENIERÍA INFORMATIVA --}}
                            <div class="col-12 mt-4 bg-light p-3 rounded text-muted small">
                                <i class="fas fa-info-circle text-orange me-1"></i> 
                                <span class="fw-bold">Nota de cubicación:</span> Las dimensiones ingresadas en centímetros serán utilizadas de manera automática por el motor del sistema para calcular el varillaje y auditorías en tiempo real basadas en la física de la forma elegida.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BOTÓN DE GUARDADO AL FINAL --}}
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-orange px-5 fw-bold text-uppercase shadow-sm py-2">
                    <i class="fas fa-save me-2"></i>Guardar e Infraestructurar Tanque
                </button>
            </div>

        </div>
    </form>
</div>

{{-- CARGA DE ALPINE AL ESTILO DE TU VISTA --}}
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function constructorDeTanque(formaInicial, capacidadInicial) {
        return {
            forma: formaInicial || 'CH',
            capacidadMaxima: parseFloat(capacidadInicial) || 0,
            
            // Función auxiliar para formatear con decimales en la vista al estilo de tu otra pantalla
            formatoNumero(n) {
                return new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
            }
        }
    }
</script>

<style>
    .uppercase { text-transform: uppercase; }
    .btn-orange { background-color: #ff6600 !important; color: white !important; }
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900 !important; }
    .border-left-orange { border-left: 5px solid #ff6600 !important; }
    .border-orange { border-color: #ff6600 !important; }
    
    /* Pequeño ajuste visual para los inputs enfocados */
    .form-control:focus, .form-select:focus {
        border-color: #ff6600 !important;
        box-shadow: 0 0 0 0.25 rgh(255, 102, 0, 0.25) !important;
    }
</style>
@endsection