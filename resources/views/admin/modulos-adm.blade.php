@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-sitemap"></i> Administración del Menú y Módulos</h1>
        <button class="btn btn-primary shadow-sm" onclick="openModuloModal()">
            <i class="fas fa-plus"></i> Nuevo Ítem
        </button>
    </div>

    <!-- Tabla Principal con Jerarquía -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" width="100%">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%" class="text-center">Orden</th>
                            <th>Módulo / Etiqueta</th>
                            <th>Ruta / URL</th>
                            <th class="text-center" width="8%">Icono</th>
                            <th class="text-center" width="10%">URL Directa</th>
                            <th class="text-center" width="10%">Visible Menú</th>
                            <th class="text-center" width="12%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modulos as $padre)
                            <!-- Fila Módulo Padre -->
                            <tr class="table-secondary fw-bold">
                                <td class="text-center"><span class="badge bg-secondary">{{ $padre->orden }}</span></td>
                                <td>
                                    <i class="{{ $padre->icono ?? 'fas fa-folder' }} me-2 text-primary"></i>
                                    {{ $padre->modulo }}
                                </td>
                                <td><code>{{ $padre->ruta ?? 'N/A' }}</code></td>
                                <td class="text-center"><i class="{{ $padre->icono }} fa-lg"></i></td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input toggle-directa" type="checkbox" 
                                               data-id="{{ $padre->id }}" {{ $padre->url_directa ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input toggle-visible" type="checkbox" 
                                               data-id="{{ $padre->id }}" {{ $padre->visible ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" onclick='openModuloModal(@json($padre))'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteModulo({{ $padre->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Submódulos Hijos -->
                            @foreach($padre->hijos as $hijo)
                            <tr>
                                <td class="text-center"><span class="badge bg-light text-dark border">{{ $hijo->orden }}</span></td>
                                <td class="ps-4">
                                    <i class="fas fa-level-up-alt fa-rotate-90 text-muted me-2"></i>
                                    <i class="{{ $hijo->icono ?? 'fas fa-circle' }} me-2 text-secondary"></i>
                                    {{ $hijo->modulo }}
                                </td>
                                <td><code>{{ $hijo->ruta ?? 'N/A' }}</code></td>
                                <td class="text-center"><i class="{{ $hijo->icono }}"></i></td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input toggle-directa" type="checkbox" 
                                               data-id="{{ $hijo->id }}" {{ $hijo->url_directa ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input toggle-visible" type="checkbox" 
                                               data-id="{{ $hijo->id }}" {{ $hijo->visible ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" onclick='openModuloModal(@json($hijo))'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteModulo({{ $hijo->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Form oculto para eliminar -->
<form id="deleteForm" method="POST" action="" class="d-none">
    @csrf
    @method('DELETE')
</form>

<!-- ================= MODAL: CREAR / EDITAR MÓDULO ================= -->
<div class="modal fade" id="moduloModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="moduloForm" method="POST" action="">
                @csrf
                <input type="hidden" name="_method" id="moduloMethod" value="POST">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="moduloModalTitle">Nuevo Ítem de Menú</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre del Menú / Módulo <span class="text-danger">*</span></label>
                            <input type="text" name="modulo" id="modNombre" class="form-control" required placeholder="Ej: Usuarios">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Módulo Padre</label>
                            <select name="id_padre" id="modPadre" class="form-select">
                                <option value="0">--- Módulo Raíz (Sin Padre) ---</option>
                                @foreach($padresSelect as $padre)
                                    <option value="{{ $padre->id }}">{{ $padre->modulo }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ruta / URL</label>
                            <input type="text" name="ruta" id="modRuta" class="form-control" placeholder="Ej: usuarios.index o /admin/users">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Clase de Icono (FontAwesome)</label>
                            <input type="text" name="icono" id="modIcono" class="form-control" placeholder="Ej: fas fa-users">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Orden <span class="text-danger">*</span></label>
                            <input type="number" name="orden" id="modOrden" class="form-control" value="1" min="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="descripcion" id="modDesc" class="form-control" placeholder="Breve nota funcional...">
                    </div>

                    <div class="row pt-2">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="visible" id="modVisible" value="1" checked>
                                <label class="form-check-label" for="modVisible"><strong>Visible en Menú</strong></label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="url_directa" id="modUrlDirecta" value="1">
                                <label class="form-check-label" for="modUrlDirecta"><strong>Es URL Directa</strong> (HTTP/path custom)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const urlBase = '{{ url("modulos") }}';
    const moduloModal = new bootstrap.Modal(document.getElementById('moduloModal'));

    function openModuloModal(data = null) {
        const form = document.getElementById('moduloForm');
        const methodInput = document.getElementById('moduloMethod');
        const title = document.getElementById('moduloModalTitle');

        if (data) {
            title.textContent = 'Editar Ítem: ' + data.modulo;
            form.action = `${urlBase}/${data.id}`;
            methodInput.value = 'PUT';

            document.getElementById('modNombre').value = data.modulo;
            document.getElementById('modPadre').value = data.id_padre;
            document.getElementById('modRuta').value = data.ruta || '';
            document.getElementById('modIcono').value = data.icono || '';
            document.getElementById('modOrden').value = data.orden;
            document.getElementById('modDesc').value = data.descripcion || '';
            document.getElementById('modVisible').checked = (data.visible == 1 || data.visible === true);
            document.getElementById('modUrlDirecta').checked = (data.url_directa == 1 || data.url_directa === true);
        } else {
            title.textContent = 'Nuevo Ítem de Menú';
            form.action = urlBase;
            methodInput.value = 'POST';
            form.reset();
            document.getElementById('modPadre').value = 0;
            document.getElementById('modOrden').value = 1;
            document.getElementById('modVisible').checked = true;
            document.getElementById('modUrlDirecta').checked = false;
        }
        moduloModal.show();
    }

    function deleteModulo(id) {
        if(confirm('¿Está seguro de eliminar este ítem de menú?')) {
            const form = document.getElementById('deleteForm');
            form.action = `${urlBase}/${id}`;
            form.submit();
        }
    }

    // Swtich Toggle para Visible
    document.querySelectorAll('.toggle-visible').forEach(btn => {
        btn.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`${urlBase}/${id}/toggle-visible`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }
            })
            .then(res => res.json())
            .then(data => { if(!data.success) this.checked = !this.checked; });
        });
    });

    // Swtich Toggle para URL Directa
    document.querySelectorAll('.toggle-directa').forEach(btn => {
        btn.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`${urlBase}/${id}/toggle-url-directa`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }
            })
            .then(res => res.json())
            .then(data => { if(!data.success) this.checked = !this.checked; });
        });
    });
</script>
@endsection