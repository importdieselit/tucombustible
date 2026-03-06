@extends('layouts.app') {{-- O el layout que uses para el login --}}

@section('content')
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Registro de Nuevo Cliente - ImporDiesel</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('cliente.register.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Razón Social</label>
                        <input type="text" name="razon_social" class="form-control" placeholder="Nombre de la empresa" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>RIF</label>
                        <input type="text" name="rif" class="form-control" placeholder="J-12345678-9" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Persona de Contacto</label>
                        <input type="text" name="contacto" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Estado</label>
                        <select name="estado_id" class="form-control" required>
                            <option value="1">Miranda</option> {{-- Hardcoded para prueba --}}
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label>Dirección Operativa</label>
                        <input type="text" name="direccion_operativa" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Tipo de Solicitud</label>
                        <select name="tipo_solicitud" class="form-control">
                            <option value="nuevo">Nueva Solicitud</option>
                            <option value="migracion">Migración</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Producto Solicitado</label>
                        <select name="tipo_servicio" class="form-control">
                            <option value="diesel">DIESEL</option>
                            <option value="mgo">MGO</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Litros Mensuales Solicitados</label>
                        <input type="number" name="litros_solicitados" class="form-control" placeholder="Ej: 30000">
                    </div>

                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="es_sucursal" name="tipo_cliente" value="sucursal">
                            <label class="form-check-label" for="es_sucursal">¿Es una sucursal de una empresa ya registrada?</label>
                        </div>
                        <input type="text" name="token_padre" id="token_padre" class="form-control mt-2 d-none" placeholder="Ingrese el Token de la Empresa Principal">
                    </div>
                </div>

                <div class="text-right">
                    <a href="{{ route('login') }}" class="btn btn-secondary">Volver al Login</a>
                    <button type="submit" class="btn btn-success px-5">Completar Paso 1</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Mostrar/Ocultar campo de Token
    document.getElementById('es_sucursal').addEventListener('change', function() {
        const tokenInput = document.getElementById('token_padre');
        tokenInput.classList.toggle('d-none', !this.checked);
        tokenInput.required = this.checked;
    });
</script>
@endsection