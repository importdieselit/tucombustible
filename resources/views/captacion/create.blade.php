@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Formulario de Captación de Prospectos</h3>
                </div>
                <div class="card-body">

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                    </ul>
                </div>
                @endif

                    <form action="{{ route('captacion.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Razón Social</label>
                                <input type="text" name="razon_social" class="form-control" value="{{ old('razon_social') }}" required>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>RIF</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <select name="rif_tipo" class="form-control">
                                            <option value="J">J</option>
                                            <option value="G">G</option>
                                            <option value="V">V</option>
                                            <option value="E">E</option>
                                        </select>
                                    </div>
                                    <input type="text" name="rif_numero" class="form-control" 
                                           maxlength="9" required
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Tipo de Solicitud</label>
                                <select name="tipo_solicitud" class="form-control" required>
                                    <option value="nuevo">Nuevo Cupo</option>
                                    <option value="migracion">Migración</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Correo Electrónico</label>
                                <input type="email" name="correo" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Estado</label>
                                <select name="estado" class="form-control" required>
                                    <option value="">Seleccione...</option>
                                    @foreach(['Amazonas','Anzoátegui','Apure','Aragua','Barinas','Bolívar','Carabobo','Cojedes','Delta Amacuro','Distrito Capital','Falcón','Guárico','Lara','Mérida','Miranda','Monagas','Nueva Esparta','Portuguesa','Sucre','Táchira','Trujillo','Vargas','Yaracuy','Zulia'] as $edo)
                                        <option value="{{ $edo }}">{{ $edo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Ciudad</label>
                                <input type="text" name="ciudad" class="form-control" required>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Teléfono</label>
                                <input type="text" name="telefono" class="form-control" 
                                       placeholder="Solo números" required
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Cantidad de Litros Solicitados</label>
                                <input type="text" name="cantidad_litros" class="form-control" 
                                       placeholder="Ej: 5000" required
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Tipo de Servicio</label>
                                <select name="tipo_servicio" class="form-control" required>
                                    <option value="Maritimo">Marítimo</option>
                                    <option value="Industrial">Industrial</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>Estructura del Cliente</label><br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_cliente" id="tipo_padre" value="padre" checked>
                                    <label class="form-check-label" for="tipo_padre">Padre</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_cliente" id="tipo_sucursal" value="sucursal">
                                    <label class="form-check-label" for="tipo_sucursal">Sucursal</label>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Enviar Registro</button>
                            <a href="{{ url('/') }}" class="btn btn-default">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection