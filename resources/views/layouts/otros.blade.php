@extends('layouts.app')

@section('title', 'Gestión de Depósitos de Combustible')
@push('styles')
<style>
        .welcome-container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            border-radius: 16px;
            background: rgba(30, 41, 59, 0.7); /* Contenedor sutil */
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out-behind;
        }

        .logo-placeholder {
            margin-bottom: 25px;
        }

        .logo-placeholder img {
            max-width: 180px;
            height: auto;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        p {
            font-size: 1.1rem;
            color: #94a3b8; /* Gris suave para lectura */
            font-weight: 300;
            line-height: 1.6;
        }

        .divider {
            height: 3px;
            width: 60px;
            background: linear-gradient(90deg, #38bdf8, #0369a1); /* Detalle azul tecnológico */
            margin: 20px auto;
            border-radius: 2px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush
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