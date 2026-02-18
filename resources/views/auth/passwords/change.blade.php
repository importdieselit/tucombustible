@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <strong><i class="fas fa-lock"></i> Cambio de Contraseña Obligatorio</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted">Por seguridad, debe cambiar la contraseña asignada automáticamente (su RIF) por una nueva antes de continuar.</p>
                    
                    <form method="POST" action="{{ route('password.update_change') }}">
                        @csrf

                        <div class="form-group">
                            <label>Nueva Contraseña</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                            @error('password')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Confirmar Nueva Contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-block">
                                Actualizar y Continuar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection