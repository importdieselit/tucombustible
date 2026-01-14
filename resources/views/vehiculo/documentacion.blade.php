@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-4">
            <h3>📋 Control de Documentación</h3>
        </div>
        <div class="col-md-8">
            <form action="{{ route('vehiculos.documentacion') }}" method="GET" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Buscar Placa o Marca..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-dark">
                        <tr>
                            <th>Vehículo / Placa</th>
                            <th>Seguro</th>
                            <th>RCV</th>
                            <th>RACDA</th>
                            <th>SENCAMER</th>
                            <th>ROTC</th>
                            <th>INTT (Homol.)</th>
                            <th>INTT (Perm.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehiculos as $v)
                        
                        <tr>
                            <td style="font-size: 14pt">
                                <b> {{$v->flota}} - {{ $v->marca()->marca ?? '-' }} {{ $v->modelo()->modelo ?? '-' }}</b>
                                <span class="badge bg-light text-dark border" style="font-size: 15pt">{{ $v->placa }}</span>
                            </td>
                            <td>{!! formatVencimiento($v->poliza_fecha_out) !!}</td>
                            <td>{!! formatVencimiento($v->rcv) !!}</td>
                            <td>{!! formatVencimiento($v->racda) !!}</td>
                            <td>{{ $v->semcamer ?? '-' }}</td>
                            <td>{!! formatVencimiento($v->rotc_venc) !!}</td>
                            <td>{!! formatVencimiento($v->venc_homologacion_intt) !!}</td>
                            <td>{!! formatVencimiento($v->permiso_intt) !!}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection