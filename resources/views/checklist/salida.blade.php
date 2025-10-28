@extends('layouts.app') 

@section('content')
{{dd(json_decode($checklist))}}
<div class="container my-5">
    <h2>Inspección de Salida: {{ $vehiculo->placa }}</h2>
    <p class="text-muted">Checklist: {{ $checklist->nombre ?? $checklist->checklist_name }} (v{{ $checklist->version??null }})</p>
    
    <form id="inspeccionForm">
        @csrf
        <input type="hidden" name="vehiculo_id" id="vehiculo_id" value="{{ $vehiculo->id }}">
        
        <div id="checklist-container">
            </div>

        <hr class="my-4">
        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
            Guardar Inspección y Notificar
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // 1. Obtener el JSON del blueprint desde Blade (Laravel lo castea a objeto JS)
    const CHECKLIST_BLUEPRINT = @json($checklist->checklist??$cheklist);
    const VEHICULO_DATA = @json($vehiculo);
    const container = document.getElementById('checklist-container');
    const form = document.getElementById('inspeccionForm');

    // Mapeo simple de nombres para inputs
    let inputNameCounter = 0;

    // 2. Función para generar el campo de respuesta
    function generateInput(item, sectionIndex, itemIndex, subIndex = -1, fieldIndex = -1) {
        const name = `sec_${sectionIndex}_item_${itemIndex}_sub_${subIndex}_field_${fieldIndex}_${item.label.replace(/\s/g, '_')}`;
        let html = '';
        
        switch (item.response_type) {
            case 'text':
            case 'date':
                html = `<input type="${item.response_type}" class="form-control" data-type="${item.response_type}" name="${name}" value="${item.value || ''}">`;
                break;
            case 'textarea':
                html = `<textarea class="form-control" data-type="textarea" name="${name}" rows="3">${item.value || ''}</textarea>`;
                break;
            case 'boolean':
                // Botones Switch (más amigable para móvil)
                html = `
                    <div class="form-check form-switch form-check-inline">
                        <input class="form-check-input check-item-input" type="checkbox" role="switch" data-type="boolean" name="${name}" ${item.value ? 'checked' : ''} id="${name}">
                        <label class="form-check-label" for="${name}">${item.value ? 'OK' : 'Fallo/Revisar'}</label>
                    </div>`;
                break;
            case 'composite':
                // Genera un grupo para los campos compuestos (Estado y Vigencia)
                html = '<div class="row g-2 align-items-center composite-group">';
                
                // Campo de Estado (boolean)
                const statusName = `${name}_status`;
                html += `
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input check-item-input composite-status" type="checkbox" data-type="boolean" name="${statusName}" ${item.value.status ? 'checked' : ''} id="${statusName}">
                            <label class="form-check-label" for="${statusName}">Estado: ${item.value.status ? 'OK' : 'Fallo'}</label>
                        </div>
                    </div>`;

                // Campo de Vigencia (date)
                const vigenciaName = `${name}_vigencia`;
                html += `
                    <div class="col-6">
                        <input type="date" class="form-control form-control-sm" data-type="date" name="${vigenciaName}" value="${item.value.vigencia || ''}">
                        <small class="text-muted">Vigente Hasta</small>
                    </div>`;
                
                html += '</div>';
                break;
        }
        return html;
    }

    // 3. Función principal para renderizar el checklist
    function renderChecklist(blueprint) {
        blueprint.sections.forEach((section, secIndex) => {
            const sectionDiv = document.createElement('div');
            sectionDiv.className = 'card shadow-sm mb-4';
            sectionDiv.innerHTML = `<div class="card-header bg-light"><h4>${section.section_title}</h4></div><div class="card-body row"></div>`;
            const cardBody = sectionDiv.querySelector('.card-body');

            if (section.items) {
                section.items.forEach((item, itemIndex) => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'mb-3 pb-2 border-bottom col-md-' + (item.col_width || 12);
                    itemDiv.innerHTML = `<label class="form-label fw-bold">${item.label}</label>${generateInput(item, secIndex, itemIndex)}`;
                    cardBody.appendChild(itemDiv);
                });
            }

            if (section.subsections) {
                 section.subsections.forEach((subsection, subIndex) => {
                    const subDiv = document.createElement('div');
                    subDiv.className = 'card bg-light shadow-sm mb-3';
                    subDiv.innerHTML = `<div class="card-header border-bottom-0"><h5>${subsection.subsection_title}</h5></div><div class="card-body row"></div>`;
                    const subBody = subDiv.querySelector('.card-body');
                    
                    subsection.items.forEach((item, itemIndex) => {
                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'mb-3 pb-2 border-bottom col-md-' + (item.col_width || 12);
                        itemDiv.innerHTML = `<label class="form-label">${item.label}</label>${generateInput(item, secIndex, itemIndex, subIndex)}`;
                        subBody.appendChild(itemDiv);
                    });
                    
                    cardBody.appendChild(subDiv);
                });
            }
            
            container.appendChild(sectionDiv);
        });
        
        // Pre-llenar campos de 'Datos del Vehículo' si existen
        // Lógica simple: asume que 'Placa', 'Marca', etc. son los primeros campos de texto
        if (VEHICULO_DATA) {
            document.querySelector('[name*="Placa"]').value = VEHICULO_DATA.placa || '';
            document.querySelector('[name*="Marca"]').value = VEHICULO_DATA.marca || '';
            // Añadir más campos según sea necesario
        }
        
        // Añadir listeners para actualizar la etiqueta de los switch (boolean)
        document.querySelectorAll('.check-item-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const label = e.target.nextElementSibling;
                if (label) {
                    label.textContent = e.target.checked ? 'OK' : 'Fallo/Revisar';
                    e.target.closest('.mb-3').style.backgroundColor = e.target.checked ? 'transparent' : '#f8d7da'; // Destacar fallos
                }
            });
             // Ejecutar una vez al cargar para el color inicial
            const label = input.nextElementSibling;
            if (label) {
                 input.closest('.mb-3').style.backgroundColor = input.checked ? 'transparent' : '#f8d7da';
            }
        });
    }

    // Ejecutar renderizado
    renderChecklist(CHECKLIST_BLUEPRINT);
    
    // 4. Función para serializar el formulario de vuelta al JSON Blueprint
    function serializeFormToJson() {
        const result = JSON.parse(JSON.stringify(CHECKLIST_BLUEPRINT)); // Clonar el blueprint
        const inputs = container.querySelectorAll('input, textarea');
        const data = {};

        // Recolectar todos los valores del formulario por nombre
        inputs.forEach(input => {
            if (input.type === 'checkbox') {
                data[input.name] = input.checked;
            } else {
                data[input.name] = input.value;
            }
        });

        // Iterar el blueprint y rellenar los valores
        result.sections.forEach((section, secIndex) => {
            
            // Recorrer items de la sección principal
            if (section.items) {
                section.items.forEach((item, itemIndex) => {
                    const nameBase = `sec_${secIndex}_item_${itemIndex}_sub_-1_field_-1_${item.label.replace(/\s/g, '_')}`;

                    if (item.response_type === 'boolean') {
                        item.value = data[nameBase];
                    } else if (item.response_type === 'text' || item.response_type === 'date' || item.response_type === 'textarea') {
                        item.value = data[nameBase];
                    } else if (item.response_type === 'composite') {
                        const statusName = `${nameBase}_status`;
                        const vigenciaName = `${nameBase}_vigencia`;
                        item.value.status = data[statusName] || false;
                        item.value.vigencia = data[vigenciaName] || null;
                    }
                });
            }
            
            // Recorrer items de subsecciones
            if (section.subsections) {
                section.subsections.forEach((subsection, subIndex) => {
                    subsection.items.forEach((item, itemIndex) => {
                        const nameBase = `sec_${secIndex}_item_${itemIndex}_sub_${subIndex}_field_-1_${item.label.replace(/\s/g, '_')}`;
                        
                        // Lógica similar a la de arriba para composite y boolean
                        if (item.response_type === 'composite') {
                            const statusName = `${nameBase}_status`;
                            const vigenciaName = `${nameBase}_vigencia`;
                            item.value.status = data[statusName] || false;
                            item.value.vigencia = data[vigenciaName] || null;
                        } else if (item.response_type === 'boolean') {
                            item.value = data[nameBase];
                        }
                    });
                });
            }
        });

        return result;
    }


    // 5. Envío del Formulario (AJAX/Fetch)
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const respuestaJson = serializeFormToJson();
        const submitBtn = document.getElementById('submitBtn');
        const vehiculoId = document.getElementById('vehiculo_id').value;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Guardando...';

        try {
            const response = await fetch('{{ route('inspeccion.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    vehiculo_id: vehiculoId,
                    respuesta_json: respuestaJson
                })
            });

            const result = await response.json();

            if (response.ok) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: result.message,
                    icon: result.estatus === 'OK' ? 'success' : 'warning',
                    confirmButtonText: 'Ver Dashboard'
                }).then(() => {
                    window.location.href = '/dashboard'; // Redirigir al dashboard
                });
            } else {
                Swal.fire('Error', result.message || 'Error al procesar la inspección.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Guardar Inspección y Notificar';
        }
    });

</script>
@endpush