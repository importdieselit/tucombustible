@extends('layouts.app') 

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Inspección de <span class="text-uppercase text-danger">{{ $tipo ?? 'Salida' }}</span> - {{ $vehiculo->placa }}
            </h2>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>
    <div class="col-12 mb-4">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-dark py-2 d-flex justify-content-between align-items-center">
            <h6 class="text-white mb-0 fw-black text-uppercase small">
                <i class="fas fa-truck-monster me-2 text-orange"></i> Identificación del Activo
            </h6>
            <span class="badge bg-orange text-white fw-black small">{{ $vehiculo->tipoVehiculo->tipo ?? 'N/A' }}</span>
        </div>
        <div class="card-body bg-white border-bottom border-4 border-orange">
            <div class="row g-3">
                {{-- Placa y Marca/Modelo (Fila Principal) --}}
                <div class="col-md-3 border-end">
                    <label class="d-block fw-black text-muted text-uppercase mb-0" style="font-size: 10px;">Placa</label>
                    <span class="h5 fw-black text-dark text-uppercase">{{ $vehiculo->placa }}</span>
                </div>
                <div class="col-md-3 border-end">
                    <label class="d-block fw-black text-muted text-uppercase mb-0" style="font-size: 10px;">Marca / Modelo</label>
                    <span class="d-block fw-bold text-dark text-uppercase">
                        {{ $vehiculo->isMarca->marca ?? 'S/M' }} - {{ $vehiculo->isModelo->modelo ?? 'S/M' }}
                    </span>
                </div>
                <div class="col-md-2 border-end">
                    <label class="d-block fw-black text-muted text-uppercase mb-0" style="font-size: 10px;">Color</label>
                    <span class="d-block fw-bold text-dark text-uppercase">{{ $vehiculo->color ?? 'N/P' }}</span>
                </div>
                <div class="col-md-4">
                    <div class="row">
                        <div class="col-6">
                            <label class="d-block fw-black text-muted text-uppercase mb-0" style="font-size: 9px;">No. Motor</label>
                            <code class="text-dark fw-bold" style="font-size: 11px;">{{ $vehiculo->serial_motor ?? '---' }}</code>
                        </div>
                        <div class="col-6">
                            <label class="d-block fw-black text-muted text-uppercase mb-0" style="font-size: 9px;">No. Serial / VIN</label>
                            <code class="text-dark fw-bold" style="font-size: 11px;">{{ $vehiculo->serial_carroceria ?? '---' }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <form id="inspeccionForm" class="row">
        @csrf
        <input type="hidden" name="vehiculo_id" id="vehiculo_id" value="{{ $vehiculo->id }}">
        
        <div class="col-lg-12" id="checklist-container">
            </div>

        <div class="col-12 mt-4 mb-5">
            <div class="progress mb-3 shadow-sm" style="height: 12px; border-radius: 8px; display: none;" id="wizardProgressContainer">
                <div id="wizardProgressBar" class="progress-bar" style="background-color: #ff6600; font-size: 10px; font-weight: bold;" role="progressbar" style="width: 0%;"></div>
            </div>

            <div class="card border-0 shadow-sm p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary shadow-sm" id="btnPrev" style="display: none;">
                        <i class="fas fa-arrow-left me-2"></i>Atrás
                    </button>
                    
                    <button type="button" class="btn text-white fw-bold shadow-sm ms-auto" id="btnNext" style="background-color: #ff6600; display: none;">
                        Siguiente<i class="fas fa-arrow-right ms-2"></i>
                    </button>
                    
                    <button type="submit" class="btn btn-primary btn-lg shadow ms-auto" id="submitBtn" style="display: none;">
                        <i class="fas fa-save me-2"></i>Finalizar y Notificar
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    /* Estilos Corporativos TuCombustible */
    .inspection-item {
        transition: all 0.3s ease;
        border-left: 5px solid #dee2e6;
        border-radius: 8px;
    }
    .status-ok {
        border-left-color: #198754 !important;
        background-color: #f8fff9;
    }
    .status-fail {
        border-left-color: #dc3545 !important;
        background-color: #fff8f8;
    }
    .card-header {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.9rem;
    }
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        cursor: pointer;
    }
</style>
@endsection

@push('scripts')
<script>
    const CHECKLIST_BLUEPRINT = @json($checklist->checklist??$checklist);
    const VEHICULO_DATA = @json($vehiculo);
    console.log(VEHICULO_DATA);
    const container = document.getElementById('checklist-container');
    const form = document.getElementById('inspeccionForm');
    const submitBtn = document.getElementById('submitBtn');
    let currentStep = 0;
    let totalWizardSteps = 0;
 


    document.addEventListener('input', function (e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            e.target.value = e.target.value.toUpperCase();
        }
    });

    function generateInput(item, sectionIndex, itemIndex, subIndex = -1) {
        // El nombre debe coincidir con lo que espera serializeFormToJson
        const name = `sec_${sectionIndex}_item_${itemIndex}_sub_${subIndex}_field_-1_${item.label.replace(/\s/g, '_')}`;
        
        if(item.label == 'Fecha de Inspección'){
            const hoy = new Date().toISOString().split('T')[0];
            item.value = hoy;
        }

        let valorFinal = item.value;
        if (item.data_source) {
            valorFinal = obtenerValorDataSource(item.data_source);
        }

        const options = Array.isArray(item.options) ? item.options : [];

        switch (item.response_type) {
            case 'text':
            case 'date':
            case 'number':
                return `<input type="${item.response_type}" class="form-control shadow-sm" name="${name}" value="${valorFinal || ''}">`;
            
            case 'textarea':
                return `<textarea class="form-control shadow-sm" name="${name}" rows="2">${valorFinal || ''}</textarea>`;
            
            case 'select':
                let selectHtml = `<select class="form-select shadow-sm" name="${name}">`;
                selectHtml += `<option value="" disabled ${!valorFinal ? 'selected' : ''}>Seleccione...</option>`;
                options.forEach(opt => {
                    selectHtml += `<option value="${opt}" ${valorFinal == opt ? 'selected' : ''}>${opt}</option>`;
                });
                selectHtml += `</select>`;
                return selectHtml;

            case 'radio':
                let radioHtml = `<div class="bg-white p-2 rounded border shadow-sm">`;
                options.forEach((opt, i) => {
                    const isChecked = (valorFinal == opt) ? 'checked' : '';
                    radioHtml += `
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="${name}" id="${name}_${i}" value="${opt}" ${isChecked}>
                            <label class="form-check-label" for="${name}_${i}">${opt}</label>
                        </div>`;
                });
                radioHtml += `</div>`;
                return radioHtml;

            case 'boolean':
                return `
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input check-item-input" type="checkbox" role="switch" name="${name}" ${valorFinal ? 'checked' : ''} id="${name}">
                        <label class="form-check-label fw-bold text-uppercase ms-2" for="${name}">
                            ${valorFinal ? '<span class="text-success text-sm">Operativo (OK)</span>' : '<span class="text-danger text-sm">Falla Detectada</span>'}
                        </label>
                    </div>`;

            case 'composite':
                return `
                    <div class="row g-2 align-items-center bg-white p-2 rounded border shadow-sm">
                        <div class="col-6 border-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input check-item-input composite-status" type="checkbox" name="${name}_status" ${item.value?.status ? 'checked' : ''} id="${name}_status">
                                <label class="form-check-label small d-block" for="${name}_status">Estado: ${item.value?.status ? 'OK' : 'Fallo'}</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted d-block mb-1">Vigencia</label>
                            <input type="date" class="form-control form-control-sm" name="${name}_vigencia" value="${item.value?.vigencia || ''}">
                        </div>
                    </div>`;
            default: return '';
        }
    }

    function obtenerValorDataSource(dataSource) {
        if (!dataSource) return '';

        // Si es un string simple tipo "Vehiculo.campo"
        if (typeof dataSource === 'string') {
            const [modelo, campo] = dataSource.split('.');            
            if (modelo === 'Vehiculo' && VEHICULO_DATA) {
                // Manejo de relaciones anidadas (Marca/Modelo/Tipo)
                if (campo === 'marca') return VEHICULO_DATA.is_marca?.marca || 'N/A';
                if (campo === 'modelo') return VEHICULO_DATA.is_modelo?.modelo || 'N/A';
                if (campo === 'tipo_vehiculo') return VEHICULO_DATA.tipo_vehiculo?.tipo || 'N/A';
                
                return VEHICULO_DATA[campo] || '';
            }           
        }

        // Si es un objeto (como el caso de Verificación Técnica o Seguros)
        if (typeof dataSource === 'object') {
            const obj = (dataSource.model === 'Vehiculo') ? VEHICULO_DATA :null;
            if (!obj) return { status: null, vigencia: null };

            return {
                status: obj[dataSource.status_field],
                vigencia: obj[dataSource.date_field]
            };
        }

        return '';
    }

    function renderChecklist(blueprint) {
        blueprint.sections.forEach((section, secIndex) => {
            // NUEVO: Contenedor del paso (Wizard Step)
            const stepDiv = document.createElement('div');
            stepDiv.className = 'wizard-step';
            stepDiv.id = `step-${secIndex}`;
            stepDiv.style.display = secIndex === 0 ? 'block' : 'none'; // Mostrar solo el paso 1 al inicio

            // TU CÓDIGO ORIGINAL de la tarjeta
            const sectionDiv = document.createElement('div');
            sectionDiv.className = 'card border-0 shadow-sm mb-4';
            sectionDiv.innerHTML = `
                <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between">
                    <div><i class="fas fa-layer-group me-2"></i> ${section.section_title}</div>
                    <span class="badge bg-secondary opacity-75">Paso ${secIndex + 1} de ${blueprint.sections.length}</span>
                </div>
                <div class="card-body row g-3"></div>`;
            
            const cardBody = sectionDiv.querySelector('.card-body');

            if (section.items) {
                section.items.forEach((item, itemIndex) => {
                    const colClass = item.col_width ? `col-md-${item.col_width}` : 'col-md-6';
                    const itemDiv = document.createElement('div');
                    itemDiv.className = `${colClass} mb-2`;
                    itemDiv.innerHTML = `
                        <div class="inspection-item p-3 border shadow-sm h-100 bg-white">
                            <label class="form-label d-block text-muted small fw-bold mb-2">${item.label}</label>
                            ${generateInput(item, secIndex, itemIndex)}
                        </div>`;
                    cardBody.appendChild(itemDiv);
                });
            }
            
            // Adjuntamos la tarjeta al Paso, y el Paso al contenedor
            stepDiv.appendChild(sectionDiv);
            container.appendChild(stepDiv);
        });

        // TU CÓDIGO ORIGINAL: Inicializar lógica de colores y etiquetas
        document.querySelectorAll('.check-item-input').forEach(input => {
            const updateUI = (el) => {
                const itemContainer = el.closest('.inspection-item') || el.closest('.composite-group');
                const label = el.nextElementSibling;
                
                if (el.checked) {
                    itemContainer?.classList.add('status-ok');
                    itemContainer?.classList.remove('status-fail');
                    if(label) label.innerHTML = '<span class="text-success text-sm">Operativo (OK)</span>';
                } else {
                    itemContainer?.classList.add('status-fail');
                    itemContainer?.classList.remove('status-ok');
                    if(label) label.innerHTML = '<span class="text-danger text-sm">Falla Detectada</span>';
                }
            };

            input.addEventListener('change', (e) => updateUI(e.target));
            updateUI(input); // Carga inicial
        });

        // TU CÓDIGO ORIGINAL: Auto-llenado de datos básicos
        if (VEHICULO_DATA) {
            const inputPlaca = document.querySelector('[name*="Placa"]');
            if(inputPlaca) inputPlaca.value = VEHICULO_DATA.placa || '';
        }

        // NUEVO: Inicializar la interfaz del Wizard
        initWizard(blueprint.sections.length);
    }

    renderChecklist(CHECKLIST_BLUEPRINT);

    
    // 4. Función para serializar el formulario de vuelta al JSON Blueprint
    function serializeFormToJson() {
        const result = JSON.parse(JSON.stringify(CHECKLIST_BLUEPRINT));
        const container = document.getElementById('checklist-container');
        
        // Capturamos todos los tipos de entrada, incluidos SELECT y RADIOS chekeados
        const inputs = container.querySelectorAll('input, textarea, select');
        const data = {};

        inputs.forEach(input => {
            if (input.type === 'checkbox') {
                data[input.name] = input.checked;
            } else if (input.type === 'radio') {
                if (input.checked) data[input.name] = input.value;
            } else {
                data[input.name] = input.value;
            }
        });

        result.sections.forEach((section, secIndex) => {
            if (section.items) {
                section.items.forEach((item, itemIndex) => {
                    const nameBase = `sec_${secIndex}_item_${itemIndex}_sub_-1_field_-1_${item.label.replace(/\s/g, '_')}`;

                    if (['text', 'date', 'textarea', 'select', 'radio', 'number'].includes(item.response_type)) {
                        item.value = data[nameBase] || null;
                    } else if (item.response_type === 'boolean') {
                        item.value = data[nameBase] || false;
                    } else if (item.response_type === 'composite') {
                        item.value.status = data[`${nameBase}_status`] || false;
                        item.value.vigencia = data[`${nameBase}_vigencia`] || null;
                    }
                });
            }
            
            // Lo mismo para subsections si las usas...
            if (section.subsections) {
                section.subsections.forEach((sub, subIndex) => {
                    sub.items.forEach((item, itemIndex) => {
                        const nameBase = `sec_${secIndex}_item_${itemIndex}_sub_${subIndex}_field_-1_${item.label.replace(/\s/g, '_')}`;
                        if (['text', 'date', 'textarea', 'select', 'radio'].includes(item.response_type)) {
                            item.value = data[nameBase] || null;
                        } else if (item.response_type === 'boolean') {
                            item.value = data[nameBase] || false;
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
            const response = await fetch('{{ route('inspecciones.store') }}', {
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
                    window.location.href = '/inspecciones'; // Redirigir al dashboard
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



    function initWizard(total) {
        totalWizardSteps = total;
        document.getElementById('wizardProgressContainer').style.display = 'flex';
        
        // Listeners de los botones
        document.getElementById('btnNext').addEventListener('click', () => {
            if (currentStep < totalWizardSteps - 1) {
                currentStep++;
                updateWizardUI();
            }
        });

        document.getElementById('btnPrev').addEventListener('click', () => {
            if (currentStep > 0) {
                currentStep--;
                updateWizardUI();
            }
        });

        // Dibuja el estado inicial
        updateWizardUI();
    }

    function updateWizardUI() {
        // Ocultar todos los pasos y mostrar el actual
        document.querySelectorAll('.wizard-step').forEach((step, index) => {
            step.style.display = index === currentStep ? 'block' : 'none';
        });

        // Actualizar barra de progreso
        const progress = ((currentStep + 1) / totalWizardSteps) * 100;
        const progressBar = document.getElementById('wizardProgressBar');
        progressBar.style.width = `${progress}%`;
        progressBar.innerText = `${Math.round(progress)}%`;

        // Mostrar/Ocultar Botones dependiendo del paso
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const submitBtn = document.getElementById('submitBtn');

        btnPrev.style.display = currentStep > 0 ? 'block' : 'none'; // Mostrar "Atrás" si no es el primer paso
        
        if (currentStep === totalWizardSteps - 1) {
            // Si es el último paso
            btnNext.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            // Si NO es el último paso
            btnNext.style.display = 'block';
            submitBtn.style.display = 'none';
        }

        // Subir al inicio de la página para que el usuario no quede abajo al cambiar de paso
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

</script>
@endpush