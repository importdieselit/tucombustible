@extends('layouts.app')

@section('content')

<div class="container">

    <div class="card shadow">

        <div class="card-header">
            Evaluación de Personal
        </div>

        <div class="card-body">

            @if($formulario)

                <p>
                    Debe completar la evaluación correspondiente a su cargo.
                </p>

                <iframe
                    src="{{ $formulario->google_form_url }}"
                    width="100%"
                    height="900"
                    frameborder="0">
                </iframe>

            @else

                <div class="alert alert-warning">
                    No existe evaluación asignada para su cargo.
                </div>

            @endif

        </div>

    </div>

</div>

@endsection