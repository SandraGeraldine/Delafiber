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
    
});

/**
 * Inicializar Mini Mapa
 */
function initMiniMap(coordenadas) {
    if (!coordenadas || coordenadas === '') return;
    
    try {
        const coords = coordenadas.split(',');
        const lat = parseFloat(coords[0]);
        const lng = parseFloat(coords[1]);
        
        if (isNaN(lat) || isNaN(lng)) {
            console.error('❌ Coordenadas inválidas:', coordenadas);
            return;
        }
        
        // Crear mapa
        const map = new google.maps.Map(document.getElementById('miniMapLead'), {
            zoom: 16,
            center: { lat, lng },
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true
        });
        
        // Datos del lead
        const leadNombre = document.querySelector('[data-lead-nombre]')?.dataset.leadNombre || '';
        const leadTelefono = document.querySelector('[data-lead-telefono]')?.dataset.leadTelefono || '';
        const leadDireccion = document.querySelector('[data-lead-direccion]')?.dataset.leadDireccion || 'Sin dirección';
        
        // Marker del lead
        const marker = new google.maps.Marker({
            position: { lat, lng },
            map: map,
            title: leadNombre,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: '#e74c3c',
                fillOpacity: 1,
                strokeColor: '#c0392b',
                strokeWeight: 2
            },
            animation: google.maps.Animation.DROP
        });
        
        // InfoWindow
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px;">
                    <h6 style="margin: 0 0 8px 0;">${leadNombre}</h6>
                    <p style="margin: 0; font-size: 13px;">
                        <i class="icon-phone"></i> ${leadTelefono}<br>
                        <i class="icon-map-pin"></i> ${leadDireccion}
                    </p>
                </div>
            `
        });
        
        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });
        
    // Mapa inicializado correctamente
        
    } catch (error) {
        console.error('❌ Error al inicializar mapa:', error);
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
            console.error('❌ Error:', error);
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
        console.warn('⚠️ Formulario de seguimiento no encontrado');
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
            console.warn('⚠️ Ya se está enviando el formulario');
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
        
        console.log('📤 Enviando seguimiento...', {
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
            console.error('❌ Error al guardar seguimiento:', error);
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
    
    console.log('✅ Formulario de seguimiento inicializado');
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
                console.error('❌ Error:', error);
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
 * Geocodificar lead sin coordenadas
 */
window.geocodificarLeadAhora = function() {
    mostrarNotificacion('info', 'Funcionalidad de geocodificación manual próximamente. Por ahora, edita el lead y agrega una dirección.');
};
    