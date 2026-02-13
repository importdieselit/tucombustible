@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center" style="margin-top: 50px;">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="text-bold">¡Expediente Recibido!</h2>
                    <p class="lead text-muted">
                        Hemos recibido tus documentos correctamente. Nuestro equipo administrativo revisará la información y se pondrá en contacto contigo a la brevedad posible.
                    </p>
                    <hr>
                    <p>Recibirás una notificación en tu correo electrónico una vez validado el expediente.</p>
                    
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg mt-3" 
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i> Finalizar y Salir
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection