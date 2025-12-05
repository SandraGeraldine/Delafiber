/**
 * JavaScript para Vista de Lead - VERSIÓN CORREGIDA
 */
// Namespace para evitar conflictos con otros archivos JS
const LeadView = {
    baseUrl: '',
    leadId: 0
};

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    
    // Obtener variables del DOM
    let baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || window.location.origin;
    // Eliminar barra final si existe
    LeadView.baseUrl = baseUrl.replace(/\/$/, '');
    LeadView.leadId = parseInt(document.querySelector('[data-lead-id]')?.dataset.leadId) || 0;
    
    
    if (!LeadView.leadId) {
        console.error(' No se pudo obtener el ID del lead');
        return;
    }
    
    // Inicializar mapa si hay coordenadas
    const coordenadas = document.querySelector('[data-coordenadas]')?.dataset.coordenadas;
    if (coordenadas && coordenadas !== '') {
        initMiniMap(coordenadas);
    }
    
    // Inicializar formularios
    initFormCambiarEtapa();
    initFormSeguimiento();
    initFormTarea();
    initFormDescartar();
    initVisorFotosDocumentos();

    // Asegurar cierre correcto de todos los modals de la vista
    initModalCloseBehavior('modalSeguimiento');
    initModalCloseBehavior('modalTarea');
    initModalCloseBehavior('modalConvertir');
    initModalCloseBehavior('modalDescartar');
    initModalCloseBehavior('modalFotoDocumento');
});

/**
 * Inicializar Mini Mapa
 */
async function initMiniMap(coordenadas) {
    if (!coordenadas || coordenadas === '') return;

    const coords = coordenadas.split(',');
    const lat = parseFloat(coords[0]);
    const lng = parseFloat(coords[1]);

    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        console.error(' Coordenadas inválidas en vista de lead:', coordenadas);
        return;
    }

    try {
        // Reutilizar el mismo módulo de mapa que en Validar Cobertura
        const mapa = await import(`${LeadView.baseUrl}/js/api/Mapa.js`);

        // Usamos "Cajas" por defecto, mismo estilo que Validar Cobertura paso 2
        await mapa.iniciarMapa('Cajas', 'miniMapLead', 'inline');
        await mapa.eventoMapa(true);

        // Centrar y mostrar la coordenada del lead
        await mapa.buscarCoordenadassinMapa(lat, lng);

    } catch (error) {
        console.error(' Error al inicializar mapa de cobertura en vista de lead:', error);
    }
}

/**
 * Inicializar Formulario de Cambio de Etapa
 */
function initFormCambiarEtapa() {
    const formCambiarEtapa = document.getElementById('formCambiarEtapa');
    
    if (!formCambiarEtapa) {
    // Formulario de cambio de etapa no encontrado
        return;
    }
    
    formCambiarEtapa.addEventListener('submit', function(e) {
        e.preventDefault();
    // Enviando cambio de etapa
        
        const formData = new FormData(this);
        formData.append('idlead', LeadView.leadId);
        
        // Mostrar datos que se envían
        for (let [key, value] of formData.entries()) {
            // Campo de formulario
        }
        
        fetch(`${LeadView.baseUrl}/leads/moverEtapa`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => {
            // Status de respuesta
            return response.json();
        })
        .then(data => {
            // Respuesta recibida
            
            if (data.success) {
                mostrarNotificacion('success', data.message || 'Etapa cambiada correctamente');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarNotificacion('error', data.message || 'Error al cambiar etapa');
            }
        })
        .catch(error => {
            console.error(' Error:', error);
            mostrarNotificacion('error', 'Error de conexión al cambiar etapa');
        });
    });
    
    // Formulario de cambio de etapa inicializado
}

/**
 * Inicializar Formulario de Seguimiento - CORREGIDO
 */
function initFormSeguimiento() {
    const formSeguimiento = document.getElementById('formSeguimiento');
    
    if (!formSeguimiento) {
        console.warn(' Formulario de seguimiento no encontrado');
        return;
    }
    
    // Flag para evitar envíos duplicados
    let isSubmitting = false;
    
    // Contador de caracteres para el textarea
    const textareaNota = document.getElementById('textareaNota');
    const contadorCaracteres = document.getElementById('contadorCaracteres');
    
    if (textareaNota && contadorCaracteres) {
        textareaNota.addEventListener('input', function() {
            contadorCaracteres.textContent = this.value.length;
        });
    }
    
    // Limpiar formulario al abrir modal
    $('#modalSeguimiento').on('show.bs.modal', function() {
        formSeguimiento.reset();
        isSubmitting = false; // Reset flag
        if (contadorCaracteres) {
            contadorCaracteres.textContent = '0';
        }
    });
    
    // Remover listeners anteriores para evitar duplicados
    const newForm = formSeguimiento.cloneNode(true);
    formSeguimiento.parentNode.replaceChild(newForm, formSeguimiento);
    
    // Agregar listener al nuevo formulario
    document.getElementById('formSeguimiento').addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Evitar envíos duplicados
        if (isSubmitting) {
            console.warn(' Ya se está enviando el formulario');
            return;
        }
        
        const formData = new FormData(this);
        
        // Verificar que todos los campos estén presentes
        const idlead = formData.get('idlead');
        const idmodalidad = formData.get('idmodalidad');
        const nota = formData.get('nota');
        
    /* Datos del formulario: {
            idlead,
            idmodalidad,
            nota: nota ? nota.substring(0, 50) + '...' : '(vacío)'
    }); */
        
        // Validación básica en frontend
        if (!idlead || !idmodalidad || !nota || nota.trim() === '') {
            mostrarNotificacion('error', 'Todos los campos son obligatorios');
            return;
        }
        
        // Marcar como enviando
        isSubmitting = true;
        
        // Deshabilitar botón para evitar doble envío
        const btnSubmit = this.querySelector('button[type="submit"]');
        const textoOriginal = btnSubmit ? btnSubmit.innerHTML : '';
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
        }
        
        console.log(' Enviando seguimiento...', {
            idlead,
            idmodalidad,
            nota: nota.substring(0, 50) + '...'
        });
        
        fetch(`${LeadView.baseUrl}/leads/agregarSeguimiento`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => {
            // Status de respuesta
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Respuesta recibida
            
            if (data.success) {
                mostrarNotificacion('success', data.message || 'Seguimiento agregado correctamente');
                
                // Cerrar modal
                $('#modalSeguimiento').modal('hide');
                
                // Limpiar formulario
                formSeguimiento.reset();
                
                // Recargar página para mostrar el nuevo seguimiento
                setTimeout(() => location.reload(), 1000);
            } else {
                mostrarNotificacion('error', data.message || 'Error al agregar seguimiento');
                
                // Mostrar errores de validación si existen
                if (data.debug) {
                    console.error('Errores de validación:', data.debug);
                }
            }
        })
        .catch(error => {
            console.error(' Error al guardar seguimiento:', error);
            mostrarNotificacion('error', 'Error de conexión: ' + error.message);
        })
        .finally(() => {
            // Rehabilitar botón y resetear flag
            isSubmitting = false;
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = textoOriginal;
            }
        });
    });
    
    console.log(' Formulario de seguimiento inicializado');
}

/**
 * Inicializar Formulario de Tarea - CORREGIDO
 */
function initFormTarea() {
    const formTarea = document.getElementById('formTarea');
    
    if (!formTarea) {
    // Formulario de tarea no encontrado
        return;
    }
    
    formTarea.addEventListener('submit', function(e) {
        e.preventDefault();
    // Enviando tarea
        
        const formData = new FormData(this);
        
        // Verificar que todos los campos obligatorios estén presentes
        const idlead = formData.get('idlead');
        const titulo = formData.get('titulo');
        const fechaVencimiento = formData.get('fecha_vencimiento');
        
    /* Datos del formulario: {
            idlead,
            titulo,
            fechaVencimiento,
            prioridad: formData.get('prioridad'),
            descripcion: formData.get('descripcion')
    }); */
        
        // Validación básica en frontend
        if (!idlead || !titulo || !fechaVencimiento) {
            mostrarNotificacion('error', 'Título y fecha de vencimiento son obligatorios');
            return;
        }
        
        // Deshabilitar botón para evitar doble envío
        const btnSubmit = this.querySelector('button[type="submit"]');
        const textoOriginal = btnSubmit ? btnSubmit.innerHTML : '';
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';
        }
        
        fetch(`${LeadView.baseUrl}/leads/crearTarea`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => {
            // Status de respuesta
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Respuesta recibida
            
            if (data.success) {
                mostrarNotificacion('success', data.message || 'Tarea creada correctamente');
                
                // Cerrar modal
                $('#modalTarea').modal('hide');
                
                // Limpiar formulario
                formTarea.reset();
                
                // Recargar página para mostrar la nueva tarea
                setTimeout(() => location.reload(), 1000);
            } else {
                mostrarNotificacion('error', data.message || 'Error al crear tarea');
                
                // Mostrar errores de validación si existen
                if (data.debug) {
                    console.error('Errores de validación:', data.debug);
                }
            }
        })
        .catch(error => {
            console.error(' Error:', error);
            mostrarNotificacion('error', 'Error de conexión al crear tarea');
        })
        .finally(() => {
            // Rehabilitar botón
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = textoOriginal;
            }
        });
    });
    
    // Formulario de tarea inicializado
}

function initFormDescartar() {
    console.log('Inicializando formulario Descartar');
    const formDescartar = document.getElementById('formDescartar');
    if (!formDescartar) {
        console.warn('formDescartar no encontrado');
        return;
    }

    formDescartar.addEventListener('submit', function(event) {
        event.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        const formData = new FormData(this);
        fetch(this.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            mostrarNotificacion(data.success ? 'success' : 'error', data.message || (data.success ? 'Lead descartado correctamente' : 'Error al descartar lead'));
            if (data.success) {
                $('#modalDescartar').modal('hide');
                setTimeout(() => location.reload(), 900);
            }
        })
        .catch(error => {
            console.error('Error al descartar lead:', error);
            mostrarNotificacion('error', 'No se pudo descartar el lead. Intenta nuevamente.');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        });
    });
}

/**
 * Inicializar visor de fotos de documentos (modal)
 */
function initVisorFotosDocumentos() {
    const modalEl = document.getElementById('modalFotoDocumento');
    const imgEl = document.getElementById('previewFotoDocumento');
    const downloadEl = document.getElementById('downloadFotoDocumento');

    if (!modalEl || !imgEl || !downloadEl) {
        return;
    }

    // Instancia reutilizable para Bootstrap 5
    let modalInstance = null;

    function showModal() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalEl);
            }
            modalInstance.show();
        } else if (typeof $ !== 'undefined') {
            // Fallback Bootstrap 4 / jQuery
            $('#modalFotoDocumento').modal('show');
        }
    }

    function hideModal() {
        if (modalInstance && typeof modalInstance.hide === 'function') {
            modalInstance.hide();
        } else if (typeof $ !== 'undefined') {
            $('#modalFotoDocumento').modal('hide');
        }
    }

    // Delegación para abrir imágenes
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-ver-doc-imagen');
        if (!btn) return;

        const url = btn.getAttribute('data-url');
        const nombre = btn.getAttribute('data-nombre') || 'foto-lead.jpg';

        if (!url) return;

        imgEl.src = url;
        imgEl.alt = nombre;
        downloadEl.href = url;
        downloadEl.setAttribute('download', nombre);

        showModal();
    });

    // Forzar cierre explícito al hacer clic en la X o en el botón "Cerrar"
    const closeButtons = modalEl.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            hideModal();
        });
    });
}

/**
 * Completar tarea
 */
window.completarTarea = function(idtarea) {
    if (!idtarea) {
        console.error('ID de tarea no especificado');
        return;
    }
    
    // Completando tarea
    
    const confirmar = typeof Swal !== 'undefined' 
        ? Swal.fire({
            title: '¿Marcar como completada?',
            text: 'Esta acción marcará la tarea como completada',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, completar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        })
        : Promise.resolve({ isConfirmed: confirm('¿Marcar como completada?') });
    
    confirmar.then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('idtarea', idtarea);
            
            fetch(`${LeadView.baseUrl}/leads/completarTarea`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => {
                // Status de respuesta
                return response.json();
            })
            .then(data => {
                // Respuesta recibida
                
                if (data.success) {
                    mostrarNotificacion('success', data.message || 'Tarea completada');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarNotificacion('error', data.message || 'Error al completar tarea');
                }
            })
            .catch(error => {
                console.error(' Error:', error);
                mostrarNotificacion('error', 'Error de conexión');
            });
        }
    });
};

/**
 * Función para mostrar notificaciones
 * Usa SweetAlert2 si está disponible, sino alert nativo
 */
function mostrarNotificacion(tipo, mensaje) {
    // Notificación: mostrar en UI sin log de consola
    
    if (typeof Swal !== 'undefined') {
        const iconos = {
            'success': 'success',
            'error': 'error',
            'warning': 'warning',
            'info': 'info'
        };
        
        Swal.fire({
            icon: iconos[tipo] || 'info',
            title: tipo === 'success' ? '¡Éxito!' : tipo === 'error' ? 'Error' : 'Atención',
            text: mensaje,
            timer: tipo === 'success' ? 2000 : 3000,
            showConfirmButton: tipo !== 'success',
            toast: false,
            position: 'center'
        });
    } else {
        alert(mensaje);
    }
}

/**
 * Inicializar comportamiento de cierre para un modal Bootstrap
 * - Cierra correctamente al hacer clic en la X o en botones con data-bs-dismiss="modal"
 * - Soporta tanto bootstrap.Modal (v5) como jQuery modal (v4)
 */
function initModalCloseBehavior(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) return;

    let instance = null;

    function getInstance() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            if (!instance) {
                instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            }
            return instance;
        }
        return null;
    }

    function hide() {
        const inst = getInstance();
        if (inst && typeof inst.hide === 'function') {
            inst.hide();
        } else if (typeof $ !== 'undefined') {
            // Fallback Bootstrap 4
            $('#' + modalId).modal('hide');
        }
    }

    const closeButtons = modalEl.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            hide();
        });
    });
}

/**
 * Geocodificar lead sin coordenadas
 */
window.geocodificarLeadAhora = function() {
    mostrarNotificacion('info', 'Funcionalidad de geocodificación manual próximamente. Por ahora, edita el lead y agrega una dirección.');
};
    