@extends('layouts.app')

@php
    $isEdit = $item->exists;
    $formTitle = $isEdit ? 'Editar Ítem de Inventario' : 'Crear Nuevo Ítem de Inventario';
    $formAction = $isEdit ? route('inventario.update', $item->id) : route('inventario.store');
    $buttonText = $isEdit ? 'Actualizar' : 'Guardar';
@endphp

@section('title', $formTitle)

@section('page-title', $formTitle)

@section('content')
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">{{ $formTitle }}</h3>
            <a href="{{ route('inventario.list') }}" class="btn btn-info d-flex align-items-center">
                <i class="bi bi-list me-1"></i> Ver Listado
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <p class="font-bold">¡Hubo un error!</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>- {{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Detalles del Ítem</h5>
        </div>
        <div class="card-body">
            <form action="{{ $formAction }}" method="POST">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="row">
                    <!-- Columna 1 -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código de Parte</label>
                            <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $item->codigo ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="codigo_interno" class="form-label">Código Interno</label>
                            <input type="text" name="codigo_interno" id="codigo_interno" value="{{ old('codigo_interno', $item->codigo_interno ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="codigo_fabricante" class="form-label">Código de Fabricante</label>
                            <input type="text" name="codigo_fabricante" id="codigo_fabricante" value="{{ old('codigo_fabricante', $item->codigo_fabricante ?? '') }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="referencia" class="form-label">Referencia / Ubicación</label>
                            <input type="text" name="referencia" id="referencia" value="{{ old('referencia', $item->referencia ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea name="descripcion" id="descripcion" rows="3" class="form-control" required>{{ old('descripcion', $item->descripcion ?? '') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación</label>
                            <textarea name="observacion" id="observacion" rows="3" class="form-control">{{ old('observacion', $item->observacion ?? '') }}</textarea>
                        </div>
                    </div>
                    <!-- Columna 2 -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="id_almacen" class="form-label">Almacén</label>
                            <select name="id_almacen" id="id_almacen" class="form-control" required>
                                <option value="">Seleccione un almacén</option>
                                @foreach ($almacenes as $almacen)
                                    <option value="{{ $almacen->id }}" @selected(old('id_almacen', $item->id_almacen ?? '') == $almacen->id)>
                                        {{ $almacen->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="prioridad" class="form-label">Prioridad</label>
                            <select name="prioridad" id="prioridad" class="form-control" required>
                                <option value="1" @selected(old('prioridad', $item->prioridad ?? '') == 1)>1 > Normal</option>
                                <option value="2" @selected(old('prioridad', $item->prioridad ?? '') == 2)>2 > Alta</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="existencia" class="form-label">Existencia</label>
                            <input type="number" step="any" name="existencia" id="existencia" value="{{ old('existencia', $item->existencia ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="existencia_minima" class="form-label">Existencia Mínima</label>
                            <input type="number" name="existencia_minima" id="existencia_minima" value="{{ old('existencia_minima', $item->existencia_minima ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="existencia_maxima" class="form-label">Existencia Máxima</label>
                            <input type="number" name="existencia_maxima" id="existencia_maxima" value="{{ old('existencia_maxima', $item->existencia_maxima ?? '') }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="costo" class="form-label">Costo (Bs)</label>
                            <input type="number" step="0.01" name="costo" id="costo" value="{{ old('costo', $item->costo ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="costo_div" class="form-label">Costo ($)</label>
                            <input type="number" step="0.01" name="costo_div" id="costo_div" value="{{ old('costo_div', $item->costo_div ?? '') }}" class="form-control" required>
                        </div>
                    </div>
                    <!-- Columna 3 -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fabricante" class="form-label">Fabricante</label>
                            <input type="text" name="fabricante" id="fabricante" value="{{ old('fabricante', $item->fabricante ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="grupo" class="form-label">Grupo</label>
                            <input type="text" name="grupo" id="grupo" value="{{ old('grupo', $item->grupo ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_in" class="form-label">Fecha de Entrada</label>
                            <input type="date" name="fecha_in" id="fecha_in" value="{{ old('fecha_in', $item->fecha_in ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="estatus" class="form-label">Estatus</label>
                            <input type="number" name="estatus" id="estatus" value="{{ old('estatus', $item->estatus ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="number" name="marca" id="marca" value="{{ old('marca', $item->marca ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="number" name="modelo" id="modelo" value="{{ old('modelo', $item->modelo ?? '') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="clasificacion" class="form-label">Clasificación</label>
                            <input type="number" name="clasificacion" id="clasificacion" value="{{ old('clasificacion', $item->clasificacion ?? '') }}" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('inventario.list') }}" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> {{ $buttonText }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
