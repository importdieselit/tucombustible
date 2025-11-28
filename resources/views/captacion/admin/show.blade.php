@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Expediente: {{ $captacion->razon_social }} (ID: {{ $captacion->id }})</h2>
    <p>Estatus: <strong>{{ $captacion->estatus_captacion }}</strong></p>

    <h4>Documentos</h4>
    <table class="table">
        <thead><tr><th>Tipo</th><th>Nombre</th><th>Archivo</th><th>Validado</th><th>Acción</th></tr></thead>
        <tbody>
            @foreach($captacion->documentos as $doc)
            <tr id="doc-{{ $doc->id }}">
                <td>{{ $doc->tipo_anexo }}</td>
                <td>{{ $doc->nombre_documento }}</td>
                <td>
                    <a href="{{ route('captacion.documento.download', $doc->id) }}" target="_blank">Ver / Descargar</a>
                </td>
                <td id="valid-{{ $doc->id }}">{{ $doc->validado ? 'Sí' : 'No' }}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="validarDoc({{ $doc->id }},1)">Validar</button>
                    <button class="btn btn-sm btn-danger" onclick="validarDoc({{ $doc->id }},0)">Marcar Incompleto</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <form method="POST" action="{{ route('captacion.enviar.planillas', $captacion->id) }}">
        @csrf
        <button class="btn btn-primary">Enviar Planillas / Habilitar Descarga</button>
    </form>

    <form class="mt-3" method="POST" action="{{ route('captacion.programar.inspeccion', $captacion->id) }}">
        @csrf
        <button class="btn btn-warning">Marcar Pendiente de Inspección</button>
    </form>

    <hr>

    <h4>Registrar Inspección</h4>
    <form method="POST" action="{{ route('captacion.registrar.inspeccion', $captacion->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label>Resultado</label>
            <select name="aprobado" class="form-control">
                <option value="1">Aprobado</option>
                <option value="0">Rechazado</option>
            </select>
        </div>
        <div class="form-group">
            <label>Observaciones</label>
            <textarea name="observaciones" class="form-control"></textarea>
        </div>
        <div class="form-group">
            <label>Fotos de inspección</label>
            <input type="file" name="fotos[]" multiple class="form-control">
        </div>
        <button class="btn btn-success mt-2">Registrar Inspección</button>
    </form>

</div>

<script>
function validarDoc(id, valid) {
    fetch('{{ url("admin/captacion/documento") }}/' + id + '/validar', {
        method: 'POST',
        headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },
        body: JSON.stringify({ validado: valid })
    })
    .then(r=>r.json())
    .then(data=>{
        if (data.ok) {
            document.getElementById('valid-'+id).innerText = valid ? 'Sí' : 'No';
        } else {
            alert('Error');
        }
    });
}
</script>
@endsection
