@extends('layouts.app')

@section('title', 'Gestión de Depósitos de Combustible')

@section('content')
<div class="welcome-container">
    <div class="logo-placeholder">
        <img src="{{ asset('images/logo.png') }}" alt="TuCombustible Logo" onerror="this.style.display='none'">
    </div>
    <h1>¡Hola, {{ Auth::user()->apellido_nombre ?? 'Bienvenido' }}!</h1>
        
    <div class="divider"></div>
        
    <p>Has ingresado correctamente al sistema.</p>
    <p style="margin-top: 5px; font-size: 0.95rem; color: #64748b;">Tu sesión se encuentra activa y validada de forma segura.</p>
</div>
@endsection