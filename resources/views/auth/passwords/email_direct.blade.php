@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Recuperar Acceso</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">
                        Ingrese el correo electrónico asociado a su cuenta. Si el correo es correcto, 
                        se le permitirá asignar una nueva contraseña de inmediato.
                    </p>

                    <form method="POST" action="{{ route('password.email.check') }}">
                        @csrf
                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Correo Electrónico</label>
                            <input type="email" name="email" 
                                   class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                   placeholder="ejemplo@correo.com" required autofocus>
                            @error('email')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block shadow">
                            Validar y Cambiar Contraseña
                        </button>
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}" class="text-muted small">Volver al inicio de sesión</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection