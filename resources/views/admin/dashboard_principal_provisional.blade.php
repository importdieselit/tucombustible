@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mt-5">
        <div class="col-md-12 text-center">
            <h1>Panel Administrativo (Provisional)</h1>
            <p class="lead">Entorno de Desarrollo - Gestión de Clientes</p>
            <hr>
            <a href="{{ route('clientes.index') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-users"></i> Ir al Módulo de Gestión de Clientes
            </a>
        </div>
    </div>
</div>
@endsection