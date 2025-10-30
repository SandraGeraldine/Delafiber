/**
 * Campos Dinámicos según Origen del Lead
 * Muestra campos adicionales contextuales según el origen seleccionado
 */

//

document.addEventListener('DOMContentLoaded', function() {
    // Inicializando campos dinámicos de origen
    initCamposDinamicosOrigen();
});

/**
 * Obtener opciones de campañas desde el servidor o datos globales
 */
function obtenerOpcionesCampanias() {
    // Intentar obtener campañas desde variable global si existe
    if (typeof campanias !== 'undefined' && Array.isArray(campanias)) {
        let opciones = '';
        campanias.forEach(campania => {
            opciones += `<option value="${campania.idcampania}">${campania.nombre}</option>`;
        });
        return opciones || '<option value="">No hay campañas activas</option>';
    }
    
    // Si no hay variable global, retornar mensaje
    return '<option value="">No hay campañas disponibles</option>';
}

function initCamposDinamicosOrigen() {
    const origenSelect = document.getElementById('idorigen');
    const camposDinamicos = document.getElementById('campos-dinamicos-origen');
    
    /* Elementos encontrados: {
        origenSelect: origenSelect,
        camposDinamicos: camposDinamicos
    }); */
    
    if (!origenSelect || !camposDinamicos) {
        console.error('❌ No se encontraron los elementos necesarios');
        return;
    }
    
    origenSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const nombreOrigen = selectedOption.getAttribute('data-nombre');
        
    // Origen seleccionado: nombreOrigen
        
        // Limpiar campos anteriores
        camposDinamicos.innerHTML = '';
        
        if (!nombreOrigen) {
            // No hay origen seleccionado
            return;
        }
        
        // Configuración de campos según origen
        const camposConfig = {
            'Campaña': {
                html: `
                    <div class="campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="idcampania_dinamica">¿Qué campaña? *</label>
                        <select class="form-control" id="idcampania_dinamica" name="idcampania" required 
                                onchange="sincronizarCampania(this.value)">
                            <option value="">Seleccione la campaña</option>
                            ${obtenerOpcionesCampanias()}
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="icon-info"></i> Campaña por la que nos conoció
                        </small>
                    </div>
                `
            },
            'Campana': {
                html: `
                    <div class="campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="idcampania_dinamica">¿Qué campaña? *</label>
                        <select class="form-control" id="idcampania_dinamica" name="idcampania" required 
                                onchange="sincronizarCampania(this.value)">
                            <option value="">Seleccione la campaña</option>
                            ${obtenerOpcionesCampanias()}
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="icon-info"></i> Campaña por la que nos conoció
                        </small>
                    </div>
                `
            },
            'Referido': {
                html: `
                    <div class="campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="referido_por">¿Quién lo refirió? *</label>
                        <input type="text" class="form-control" id="referido_por" name="referido_por" 
                               placeholder="Nombre del cliente que lo recomendó" required>
                        <small class="text-muted d-block mt-1">
                            <i class="icon-user"></i> Persona que recomendó nuestro servicio
                        </small>
                    </div>
                `
            },
            'Facebook': {
                html: `
                    <div class="form-group campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="detalle_facebook">Detalle de Facebook</label>
                        <select class="form-control" id="detalle_facebook" name="detalle_facebook">
                            <option value="">Seleccione</option>
                            <option value="Publicación orgánica">Publicación orgánica</option>
                            <option value="Anuncio pagado">Anuncio pagado</option>
                            <option value="Messenger">Messenger</option>
                            <option value="Comentario">Comentario en publicación</option>
                            <option value="Grupo">Grupo de Facebook</option>
                        </select>
                        <small class="text-muted">¿Cómo nos contactó por Facebook?</small>
                    </div>
                `
            },
            'WhatsApp': {
                html: `
                    <div class="form-group campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="origen_whatsapp">¿Cómo obtuvo nuestro WhatsApp?</label>
                        <select class="form-control" id="origen_whatsapp" name="origen_whatsapp">
                            <option value="">Seleccione</option>
                            <option value="Publicidad">Vio en publicidad</option>
                            <option value="Referido">Se lo pasó un conocido</option>
                            <option value="Redes sociales">Redes sociales</option>
                            <option value="Búsqueda web">Búsqueda en internet</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                `
            },
            'Publicidad': {
                html: `
                    <div class="form-group campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="tipo_publicidad">Tipo de Publicidad</label>
                        <select class="form-control" id="tipo_publicidad" name="tipo_publicidad">
                            <option value="">Seleccione</option>
                            <option value="Volante">Volante</option>
                            <option value="Banner">Banner/Letrero</option>
                            <option value="Perifoneo">Perifoneo</option>
                            <option value="Radio">Radio</option>
                            <option value="Periódico">Periódico</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group mt-2 campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="ubicacion_publicidad">¿Dónde vio la publicidad?</label>
                        <input type="text" class="form-control" id="ubicacion_publicidad" name="ubicacion_publicidad" 
                               placeholder="Ej: Av. Benavides, Mercado Central">
                    </div>
                `
            },
            'Página Web': {
                html: `
                    <div class="form-group campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="accion_web">¿Qué hizo en la web?</label>
                        <select class="form-control" id="accion_web" name="accion_web">
                            <option value="">Seleccione</option>
                            <option value="Formulario contacto">Llenó formulario de contacto</option>
                            <option value="Chat">Usó el chat en vivo</option>
                            <option value="Llamó">Llamó al teléfono publicado</option>
                            <option value="WhatsApp web">Click en botón WhatsApp</option>
                        </select>
                    </div>
                `
            },
            'Llamada Directa': {
                html: `
                    <div class="form-group campo-dinamico" style="animation: fadeIn 0.3s;">
                        <label for="origen_numero">¿Cómo obtuvo nuestro número?</label>
                        <select class="form-control" id="origen_numero" name="origen_numero">
                            <option value="">Seleccione</option>
                            <option value="Publicidad">Publicidad</option>
                            <option value="Referido">Referido</option>
                            <option value="Internet">Búsqueda en internet</option>
                            <option value="Cliente anterior">Es cliente anterior</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                `
            }
        };
        
        // Mostrar campos correspondientes
    // Buscando configuración para origen
        
        // Intentar búsqueda directa
        let config = camposConfig[nombreOrigen];
        
        // Si no encuentra, intentar normalizar (quitar tildes y comparar)
        if (!config) {
            const nombreNormalizado = nombreOrigen.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            // Intentando con nombre normalizado
            
            for (let clave in camposConfig) {
                const claveNormalizada = clave.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                if (claveNormalizada === nombreNormalizado) {
                    config = camposConfig[clave];
                    // Encontrado con normalización
                    break;
                }
            }
        }
        
        if (config) {
            // Mostrando campos para el origen
            camposDinamicos.innerHTML = config.html;
        } else {
            // No hay configuración para el origen
        }
    });
    
    // Event listener agregado correctamente

    // Si ya hay un origen seleccionado al cargar la página, disparar el handler
    try {
        if (origenSelect.value && origenSelect.value !== '') {
            // Disparar change para mostrar campos iniciales
            origenSelect.dispatchEvent(new Event('change'));
        }
    } catch (err) {
        // No se pudo disparar evento inicial de origen
    }
}

/**
 * Sincronizar valor de campaña dinámica con campo oculto
 */
function sincronizarCampania(valor) {
    const campaniaOculta = document.getElementById('idcampania');
    if (campaniaOculta) {
    campaniaOculta.value = valor;
    }
}

// Hacer la función global para que pueda ser llamada desde el HTML
window.sincronizarCampania = sincronizarCampania;

// Agregar estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .campo-dinamico {
        animation: fadeIn 0.3s ease-out;
    }
`;
document.head.appendChild(style);
