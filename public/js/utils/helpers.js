/**
 * Helpers JavaScript Reutilizables
 * Funciones comunes para todo el proyecto
 */

// ============================================
// VALIDACIONES
// ============================================

/**
 * Valida formato de teléfono peruano
 * @param {string} telefono 
 * @returns {boolean}
 */
function validarTelefono(telefono) {
    const regex = /^9[0-9]{8}$/;
    return regex.test(telefono);
}

/**
 * Valida formato de DNI peruano
 * @param {string} dni 
 * @returns {boolean}
 */
function validarDNI(dni) {
    const regex = /^[0-9]{8}$/;
    return regex.test(dni);
}

/**
 * Valida formato de email
 * @param {string} email 
 * @returns {boolean}
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Valida que un campo no esté vacío
 * @param {string} valor 
 * @returns {boolean}
 */
function validarRequerido(valor) {
    return valor !== null && valor !== undefined && valor.toString().trim() !== '';
}

/**
 * Valida que un número sea positivo
 * @param {number} numero 
 * @returns {boolean}
 */
function validarPositivo(numero) {
    return !isNaN(numero) && parseFloat(numero) > 0;
}

/**
 * Valida rango de fechas
 * @param {string} fechaInicio 
 * @param {string} fechaFin 
 * @returns {boolean}
 */
function validarRangoFechas(fechaInicio, fechaFin) {
    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);
    return fin > inicio;
}

// ============================================
// FORMATEO
// ============================================

/**
 * Formatea número como moneda peruana
 * @param {number} monto 
 * @returns {string}
 */
function formatearMoneda(monto) {
    return 'S/ ' + parseFloat(monto).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Formatea teléfono con espacios
 * @param {string} telefono 
 * @returns {string}
 */
function formatearTelefono(telefono) {
    if (!telefono || telefono.length !== 9) return telefono;
    return telefono.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
}

/**
 * Formatea fecha a formato legible
 * @param {string} fecha 
 * @param {boolean} incluirHora 
 * @returns {string}
 */
function formatearFecha(fecha, incluirHora = false) {
    const date = new Date(fecha);
    const opciones = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    
    if (incluirHora) {
        opciones.hour = '2-digit';
        opciones.minute = '2-digit';
    }
    
    return date.toLocaleDateString('es-PE', opciones);
}

/**
 * Formatea fecha relativa (hace X tiempo)
 * @param {string} fecha 
 * @returns {string}
 */
function formatearFechaRelativa(fecha) {
    const ahora = new Date();
    const entonces = new Date(fecha);
    const diff = ahora - entonces;
    
    const segundos = Math.floor(diff / 1000);
    const minutos = Math.floor(segundos / 60);
    const horas = Math.floor(minutos / 60);
    const dias = Math.floor(horas / 24);
    
    if (segundos < 60) return 'Hace un momento';
    if (minutos < 60) return `Hace ${minutos} minuto${minutos > 1 ? 's' : ''}`;
    if (horas < 24) return `Hace ${horas} hora${horas > 1 ? 's' : ''}`;
    if (dias < 7) return `Hace ${dias} día${dias > 1 ? 's' : ''}`;
    
    return formatearFecha(fecha);
}

/**
 * Capitaliza primera letra de cada palabra
 * @param {string} texto 
 * @returns {string}
 */
function capitalizarTexto(texto) {
    return texto.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
}

// ============================================
// AJAX Y PETICIONES
// ============================================

/**
 * Obtiene el token CSRF del meta tag
 * @returns {string}
 */
function obtenerCSRFToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Obtiene la base URL del proyecto
 * @returns {string}
 */
function obtenerBaseURL() {
    const meta = document.querySelector('meta[name="base-url"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Realiza petición AJAX con manejo de errores
 * @param {string} url 
 * @param {object} opciones 
 * @returns {Promise}
 */
async function peticionAjax(url, opciones = {}) {
    const config = {
        method: opciones.method || 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': obtenerCSRFToken(),
            ...opciones.headers
        }
    };
    
    if (opciones.data) {
        if (config.method === 'GET') {
            const params = new URLSearchParams(opciones.data);
            url += '?' + params.toString();
        } else {
            config.body = JSON.stringify(opciones.data);
        }
    }
    
    try {
        const response = await fetch(obtenerBaseURL() + url, config);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error en petición AJAX:', error);
        throw error;
    }
}

/**
 * Realiza petición POST
 * @param {string} url 
 * @param {object} data 
 * @returns {Promise}
 */
function post(url, data) {
    return peticionAjax(url, { method: 'POST', data });
}

/**
 * Realiza petición GET
 * @param {string} url 
 * @param {object} params 
 * @returns {Promise}
 */
function get(url, params = {}) {
    return peticionAjax(url, { method: 'GET', data: params });
}

// ============================================
// NOTIFICACIONES
// ============================================

/**
 * Muestra notificación de éxito con SweetAlert2
 * @param {string} mensaje 
 * @param {string} titulo 
 */
function mostrarExito(mensaje, titulo = '¡Éxito!') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: titulo,
            text: mensaje,
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        alert(mensaje);
    }
}

/**
 * Muestra notificación de error
 * @param {string} mensaje 
 * @param {string} titulo 
 */
function mostrarError(mensaje, titulo = 'Error') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje
        });
    } else {
        alert(mensaje);
    }
}

/**
 * Muestra notificación de advertencia
 * @param {string} mensaje 
 * @param {string} titulo 
 */
function mostrarAdvertencia(mensaje, titulo = 'Atención') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: titulo,
            text: mensaje
        });
    } else {
        alert(mensaje);
    }
}

/**
 * Muestra diálogo de confirmación
 * @param {string} mensaje 
 * @param {string} titulo 
 * @returns {Promise<boolean>}
 */
async function confirmar(mensaje, titulo = '¿Estás seguro?') {
    if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
            icon: 'question',
            title: titulo,
            text: mensaje,
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        });
        return result.isConfirmed;
    } else {
        return confirm(mensaje);
    }
}

/**
 * Muestra loading/spinner
 * @param {string} mensaje 
 */
function mostrarLoading(mensaje = 'Cargando...') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: mensaje,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
}

/**
 * Cierra el loading
 */
function cerrarLoading() {
    if (typeof Swal !== 'undefined') {
        Swal.close();
    }
}

// ============================================
// UTILIDADES DOM
// ============================================

/**
 * Sanitiza HTML para prevenir XSS
 * @param {string} texto 
 * @returns {string}
 */
function sanitizarHTML(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

/**
 * Muestra/oculta elemento
 * @param {string|HTMLElement} elemento 
 * @param {boolean} mostrar 
 */
function toggleElemento(elemento, mostrar) {
    const el = typeof elemento === 'string' ? document.querySelector(elemento) : elemento;
    if (el) {
        el.style.display = mostrar ? 'block' : 'none';
    }
}

/**
 * Habilita/deshabilita elemento
 * @param {string|HTMLElement} elemento 
 * @param {boolean} habilitar 
 */
function toggleHabilitar(elemento, habilitar) {
    const el = typeof elemento === 'string' ? document.querySelector(elemento) : elemento;
    if (el) {
        el.disabled = !habilitar;
    }
}

/**
 * Agrega clase a elemento
 * @param {string|HTMLElement} elemento 
 * @param {string} clase 
 */
function agregarClase(elemento, clase) {
    const el = typeof elemento === 'string' ? document.querySelector(elemento) : elemento;
    if (el) {
        el.classList.add(clase);
    }
}

/**
 * Remueve clase de elemento
 * @param {string|HTMLElement} elemento 
 * @param {string} clase 
 */
function removerClase(elemento, clase) {
    const el = typeof elemento === 'string' ? document.querySelector(elemento) : elemento;
    if (el) {
        el.classList.remove(clase);
    }
}

// ============================================
// FORMULARIOS
// ============================================

/**
 * Obtiene datos de formulario como objeto
 * @param {string|HTMLFormElement} formulario 
 * @returns {object}
 */
function obtenerDatosFormulario(formulario) {
    const form = typeof formulario === 'string' ? document.querySelector(formulario) : formulario;
    if (!form) return {};
    
    const formData = new FormData(form);
    const datos = {};
    
    for (let [key, value] of formData.entries()) {
        datos[key] = value;
    }
    
    return datos;
}

/**
 * Limpia formulario
 * @param {string|HTMLFormElement} formulario 
 */
function limpiarFormulario(formulario) {
    const form = typeof formulario === 'string' ? document.querySelector(formulario) : formulario;
    if (form) {
        form.reset();
        // Limpiar errores de validación
        const errores = form.querySelectorAll('.is-invalid');
        errores.forEach(el => el.classList.remove('is-invalid'));
        const mensajesError = form.querySelectorAll('.invalid-feedback');
        mensajesError.forEach(el => el.remove());
    }
}

/**
 * Muestra errores de validación en formulario
 * @param {string|HTMLFormElement} formulario 
 * @param {object} errores 
 */
function mostrarErroresFormulario(formulario, errores) {
    const form = typeof formulario === 'string' ? document.querySelector(formulario) : formulario;
    if (!form) return;
    
    // Limpiar errores previos
    const erroresPrevios = form.querySelectorAll('.is-invalid');
    erroresPrevios.forEach(el => el.classList.remove('is-invalid'));
    const mensajesPrevios = form.querySelectorAll('.invalid-feedback');
    mensajesPrevios.forEach(el => el.remove());
    
    // Mostrar nuevos errores
    for (let [campo, mensaje] of Object.entries(errores)) {
        const input = form.querySelector(`[name="${campo}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = mensaje;
            input.parentNode.appendChild(feedback);
        }
    }
}

// ============================================
// ALMACENAMIENTO LOCAL
// ============================================

/**
 * Guarda dato en localStorage
 * @param {string} clave 
 * @param {any} valor 
 */
function guardarLocal(clave, valor) {
    try {
        localStorage.setItem(clave, JSON.stringify(valor));
    } catch (error) {
        console.error('Error al guardar en localStorage:', error);
    }
}

/**
 * Obtiene dato de localStorage
 * @param {string} clave 
 * @param {any} valorPorDefecto 
 * @returns {any}
 */
function obtenerLocal(clave, valorPorDefecto = null) {
    try {
        const item = localStorage.getItem(clave);
        return item ? JSON.parse(item) : valorPorDefecto;
    } catch (error) {
        console.error('Error al leer de localStorage:', error);
        return valorPorDefecto;
    }
}

/**
 * Elimina dato de localStorage
 * @param {string} clave 
 */
function eliminarLocal(clave) {
    try {
        localStorage.removeItem(clave);
    } catch (error) {
        console.error('Error al eliminar de localStorage:', error);
    }
}

// ============================================
// UTILIDADES VARIAS
// ============================================

/**
 * Debounce para limitar ejecución de funciones
 * @param {Function} func 
 * @param {number} wait 
 * @returns {Function}
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copia texto al portapapeles
 * @param {string} texto 
 * @returns {Promise<boolean>}
 */
async function copiarAlPortapapeles(texto) {
    try {
        await navigator.clipboard.writeText(texto);
        mostrarExito('Copiado al portapapeles');
        return true;
    } catch (error) {
        console.error('Error al copiar:', error);
        return false;
    }
}

/**
 * Genera número aleatorio en rango
 * @param {number} min 
 * @param {number} max 
 * @returns {number}
 */
function numeroAleatorio(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

/**
 * Abre WhatsApp con mensaje predefinido
 * @param {string} telefono 
 * @param {string} mensaje 
 */
function abrirWhatsApp(telefono, mensaje = '') {
    const url = `https://wa.me/51${telefono}?text=${encodeURIComponent(mensaje)}`;
    window.open(url, '_blank');
}

/**
 * Inicia llamada telefónica
 * @param {string} telefono 
 */
function iniciarLlamada(telefono) {
    window.location.href = `tel:+51${telefono}`;
}

/**
 * Abre email con destinatario
 * @param {string} email 
 * @param {string} asunto 
 * @param {string} cuerpo 
 */
function abrirEmail(email, asunto = '', cuerpo = '') {
    const url = `mailto:${email}?subject=${encodeURIComponent(asunto)}&body=${encodeURIComponent(cuerpo)}`;
    window.location.href = url;
}

// Exportar funciones si se usa como módulo
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validarTelefono,
        validarDNI,
        validarEmail,
        formatearMoneda,
        formatearFecha,
        mostrarExito,
        mostrarError,
        confirmar,
        post,
        get,
        // ... agregar más según necesidad
    };
}
