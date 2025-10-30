/**
 * Sistema de Comentarios en Leads
 * Permite agregar y visualizar comentarios internos en tiempo real
 */

// Obtener URL base
const baseURL = document.querySelector('meta[name="base-url"]')?.content || window.location.origin + '/';

let idLeadActual = null;
let intervaloActualizacion = null;

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    // Obtener ID del lead desde el data attribute
    idLeadActual = $('.row[data-lead-id]').data('lead-id');
    
    if (idLeadActual) {
        // Cargar comentarios iniciales
        cargarComentarios();
        
        // Actualizar cada 15 segundos (AJAX Polling)
        intervaloActualizacion = setInterval(cargarComentarios, 15000);
    }
    
    // Manejar envío de nuevo comentario
    $('#formComentario').on('submit', function(e) {
        e.preventDefault();
        enviarComentario();
    });
});

/**
 * Cargar comentarios del lead
 */
function cargarComentarios() {
    $.ajax({
        url: baseURL + 'leads/getComentarios/' + idLeadActual,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarComentarios(response.comentarios);
                $('#contadorComentarios').text(response.comentarios.length);
            }
        },
        error: function() {
            console.error('Error al cargar comentarios');
        }
    });
}

/**
 * Mostrar comentarios en el DOM
 */
function mostrarComentarios(comentarios) {
    const contenedor = $('#listaComentarios');
    
    if (comentarios.length === 0) {
        contenedor.html(`
            <div class="text-center text-muted py-3">
                <i class="icon-speech" style="font-size: 2rem;"></i>
                <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    comentarios.forEach(function(comentario) {
        const tipoClass = comentario.tipo === 'solicitud_apoyo' ? 'border-warning' : 'border-light';
        const tipoBadge = comentario.tipo === 'solicitud_apoyo' 
            ? '<span class="badge badge-warning ml-2"><i class="icon-bell"></i> Solicitud de Apoyo</span>'
            : '';
        
        const fecha = new Date(comentario.created_at);
        const fechaFormateada = formatearFecha(fecha);
        
        html += `
            <div class="card mb-3 ${tipoClass}" style="border-left: 3px solid;">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong>${comentario.usuario_nombre}</strong>
                            ${tipoBadge}
                        </div>
                        <small class="text-muted">${fechaFormateada}</small>
                    </div>
                    <p class="mb-0" style="white-space: pre-wrap;">${escapeHtml(comentario.comentario)}</p>
                </div>
            </div>
        `;
    });
    
    contenedor.html(html);
}

/**
 * Enviar nuevo comentario
 */
function enviarComentario() {
    const form = $('#formComentario');
    const comentario = $('#nuevoComentario').val().trim();
    const tipo = $('input[name="tipo"]:checked').val();
    const btnEnviar = form.find('button[type="submit"]');
    
    if (!comentario) {
        alert('Por favor escribe un comentario');
        return;
    }
    
    // Deshabilitar botón mientras se envía
    btnEnviar.prop('disabled', true).html('<i class="icon-hourglass"></i> Enviando...');
    
    $.ajax({
        url: baseURL + 'leads/crearComentario',
        method: 'POST',
        data: {
            idlead: idLeadActual,
            comentario: comentario,
            tipo: tipo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Limpiar formulario
                $('#nuevoComentario').val('');
                $('input[name="tipo"][value="nota_interna"]').prop('checked', true);
                
                // Recargar comentarios inmediatamente
                cargarComentarios();
                
                // Mostrar mensaje de éxito
                if (tipo === 'solicitud_apoyo') {
                    alert('✓ Solicitud de apoyo enviada. Los supervisores han sido notificados.');
                }
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error al enviar el comentario. Intenta nuevamente.');
        },
        complete: function() {
            // Rehabilitar botón
            btnEnviar.prop('disabled', false).html('<i class="icon-paper-plane"></i> Enviar');
        }
    });
}

/**
 * Formatear fecha de manera amigable
 */
function formatearFecha(fecha) {
    const ahora = new Date();
    const diferencia = ahora - fecha;
    const minutos = Math.floor(diferencia / 60000);
    const horas = Math.floor(diferencia / 3600000);
    const dias = Math.floor(diferencia / 86400000);
    
    if (minutos < 1) return 'Ahora mismo';
    if (minutos < 60) return `Hace ${minutos} min`;
    if (horas < 24) return `Hace ${horas}h`;
    if (dias < 7) return `Hace ${dias}d`;
    
    // Formato completo
    const opciones = { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return fecha.toLocaleDateString('es-PE', opciones);
}

/**
 * Escapar HTML para prevenir XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Limpiar intervalo cuando se salga de la página
$(window).on('beforeunload', function() {
    if (intervaloActualizacion) {
        clearInterval(intervaloActualizacion);
    }
});
