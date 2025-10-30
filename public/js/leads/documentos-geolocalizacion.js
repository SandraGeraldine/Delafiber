/**
 * Manejo de Documentos y Geolocalización para Leads
 * Sistema Delafiber CRM - Integración WhatsApp
 */

// =====================================================
// 1. GEOLOCALIZACIÓN
// =====================================================

// Obtener ubicación actual del navegador
document.getElementById('btnObtenerUbicacion')?.addEventListener('click', function() {
    const btn = this;
    
    if (!navigator.geolocation) {
        Swal.fire({
            icon: 'error',
            title: 'No soportado',
            text: 'Tu navegador no soporta geolocalización'
        });
        return;
    }

    btn.innerHTML = '<i class="icon-refresh rotating"></i> Obteniendo ubicación...';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Guardar coordenadas
            document.getElementById('coordenadas_servicio').value = `${lat},${lng}`;
            
            // Mostrar información
            const infoDiv = document.getElementById('coordenadas-info');
            const textoDiv = document.getElementById('coordenadas-texto');
            textoDiv.innerHTML = `
                ✅ <strong>Ubicación capturada:</strong><br>
                Latitud: ${lat.toFixed(6)}, Longitud: ${lng.toFixed(6)}
            `;
            infoDiv.style.display = 'block';
            
            // Verificar cobertura con las coordenadas
            verificarCoberturaPorCoordenadas(lat, lng);
            
            btn.innerHTML = '<i class="icon-check"></i> Ubicación obtenida';
            btn.classList.remove('btn-info');
            btn.classList.add('btn-success');
            btn.disabled = false;
        },
        (error) => {
            let mensaje = 'No se pudo obtener la ubicación';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    mensaje = 'Permiso denegado. Por favor, permite el acceso a tu ubicación.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    mensaje = 'Información de ubicación no disponible.';
                    break;
                case error.TIMEOUT:
                    mensaje = 'Tiempo de espera agotado.';
                    break;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error de geolocalización',
                text: mensaje
            });
            
            btn.innerHTML = '<i class="icon-location-pin"></i> Obtener mi ubicación';
            btn.disabled = false;
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
});

// Pegar ubicación desde WhatsApp
document.getElementById('btnPegarUbicacionWhatsapp')?.addEventListener('click', async function() {
    try {
        const texto = await navigator.clipboard.readText();
        
        // Intentar extraer coordenadas de diferentes formatos de WhatsApp
        // Formato 1: https://maps.google.com/?q=-13.4099,-76.1317
        // Formato 2: -13.4099,-76.1317
        // Formato 3: https://www.google.com/maps/place/-13.4099,-76.1317
        
        let lat, lng;
        
        // Intentar extraer de URL de Google Maps
        const urlMatch = texto.match(/q=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
        if (urlMatch) {
            lat = parseFloat(urlMatch[1]);
            lng = parseFloat(urlMatch[2]);
        } else {
            // Intentar formato simple de coordenadas
            const coordMatch = texto.match(/(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)/);
            if (coordMatch) {
                lat = parseFloat(coordMatch[1]);
                lng = parseFloat(coordMatch[2]);
            }
        }
        
        if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
            // Validar rango de coordenadas (Perú aproximadamente)
            if (lat >= -18.5 && lat <= -0 && lng >= -81.5 && lng <= -68) {
                document.getElementById('coordenadas_servicio').value = `${lat},${lng}`;
                document.getElementById('ubicacion_compartida').value = texto;
                
                const infoDiv = document.getElementById('coordenadas-info');
                const textoDiv = document.getElementById('coordenadas-texto');
                textoDiv.innerHTML = `
                    ✅ <strong>Ubicación de WhatsApp capturada:</strong><br>
                    Latitud: ${lat.toFixed(6)}, Longitud: ${lng.toFixed(6)}
                `;
                infoDiv.style.display = 'block';
                
                // Verificar cobertura
                verificarCoberturaPorCoordenadas(lat, lng);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Ubicación capturada',
                    text: 'La ubicación de WhatsApp se guardó correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error('Las coordenadas no parecen estar en Perú');
            }
        } else {
            throw new Error('No se encontraron coordenadas válidas');
        }
    } catch (error) {
        Swal.fire({
            icon: 'info',
            title: 'Formato no reconocido',
            html: `
                <p>No se pudo extraer la ubicación del portapapeles.</p>
                <p><strong>Formatos aceptados:</strong></p>
                <ul style="text-align: left;">
                    <li>Link de Google Maps de WhatsApp</li>
                    <li>Coordenadas: -13.4099,-76.1317</li>
                </ul>
                <p class="mt-3"><small>Copia la ubicación compartida en WhatsApp y vuelve a intentar</small></p>
            `
        });
    }
});

// Verificar cobertura por coordenadas
function verificarCoberturaPorCoordenadas(lat, lng) {
    const alertaDiv = document.getElementById('alerta-cobertura-ubicacion');
    
    alertaDiv.innerHTML = '<div class="alert alert-info"><i class="icon-refresh rotating"></i> Verificando cobertura...</div>';
    alertaDiv.style.display = 'block';
    
    fetch(`${BASE_URL}/leads/verificarCoberturaCoordenadas?lat=${lat}&lng=${lng}`)
        .then(response => response.json())
        .then(data => {
            if (data.tiene_cobertura) {
                alertaDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="icon-check"></i> <strong>¡Excelente!</strong> 
                        Esta ubicación tiene cobertura de servicio.
                        ${data.zona ? `<br><small>Zona: ${data.zona}</small>` : ''}
                    </div>
                `;
            } else {
                alertaDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="icon-close"></i> <strong>Sin cobertura</strong> 
                        Esta ubicación no tiene cobertura actualmente.
                        ${data.mensaje ? `<br><small>${data.mensaje}</small>` : ''}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error al verificar cobertura:', error);
            alertaDiv.style.display = 'none';
        });
}

// =====================================================
// 2. PREVIEW DE ARCHIVOS
// =====================================================

// Función genérica para preview de imágenes
function setupFilePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (!input || !preview) return;
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (!file) {
            preview.innerHTML = '';
            return;
        }
        
        // Validar tamaño (3MB - será comprimido en el servidor)
        const maxSize = 3 * 1024 * 1024; // 3MB en bytes
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: 'El archivo no debe superar los 3MB. Será comprimido automáticamente al guardar.'
            });
            input.value = '';
            preview.innerHTML = '';
            return;
        }
        
        // Validar tipo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Formato no válido',
                text: 'Solo se aceptan imágenes JPG, PNG o archivos PDF'
            });
            input.value = '';
            preview.innerHTML = '';
            return;
        }
        
        // Mostrar preview
        if (file.type === 'application/pdf') {
            preview.innerHTML = `
                <div class="alert alert-success">
                    <i class="icon-doc"></i> PDF seleccionado: <strong>${file.name}</strong>
                    <br><small>${(file.size / 1024).toFixed(2)} KB</small>
                </div>
            `;
        } else {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <div class="card" style="max-width: 300px;">
                        <img src="${e.target.result}" class="card-img-top" alt="Preview">
                        <div class="card-body p-2">
                            <small class="text-muted">${file.name} (${(file.size / 1024).toFixed(2)} KB)</small>
                        </div>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        }
    });
}

// Configurar previews para todos los campos de archivo
setupFilePreview('foto_dni_frontal', 'preview_dni_frontal');
setupFilePreview('foto_dni_reverso', 'preview_dni_reverso');
setupFilePreview('recibo_luz_agua', 'preview_recibo');
setupFilePreview('foto_domicilio', 'preview_domicilio');

// =====================================================
// 3. VALIDACIÓN DE CLIENTE EXISTENTE MEJORADA
// =====================================================

// Mejorar búsqueda por teléfono para incluir validación de documentos
const btnBuscarTelefono = document.getElementById('btnBuscarTelefono');
if (btnBuscarTelefono) {
    btnBuscarTelefono.addEventListener('click', function() {
        const telefono = document.getElementById('buscar_telefono').value.trim();
        
        if (telefono.length !== 9) {
            Swal.fire({
                icon: 'warning',
                title: 'Teléfono inválido',
                text: 'Ingresa un número de 9 dígitos'
            });
            return;
        }
        
        buscarClienteExistente(telefono, null);
    });
}

// Mejorar búsqueda por DNI
const btnBuscarDni = document.getElementById('btnBuscarDni');
if (btnBuscarDni) {
    const originalClickHandler = btnBuscarDni.onclick;
    btnBuscarDni.addEventListener('click', function(e) {
        const dni = document.getElementById('dni').value.trim();
        
        if (dni.length === 8) {
            // Primero buscar si ya existe en la BD
            buscarClienteExistente(null, dni);
        }
        
        // Llamar al handler original si existe
        if (originalClickHandler) {
            originalClickHandler.call(this, e);
        }
    });
}

function buscarClienteExistente(telefono, dni) {
    const resultadoDiv = document.getElementById('resultado-busqueda');
    
    resultadoDiv.innerHTML = '<div class="alert alert-info"><i class="icon-refresh rotating"></i> Buscando cliente...</div>';
    resultadoDiv.style.display = 'block';
    
    const params = new URLSearchParams();
    if (telefono) params.append('telefono', telefono);
    if (dni) params.append('dni', dni);
    
    fetch(`${BASE_URL}/leads/verificarClienteExistente?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.existe) {
                let html = `
                    <div class="alert alert-warning">
                        <h6><i class="icon-info"></i> Cliente encontrado en el sistema</h6>
                        <p><strong>Nombre:</strong> ${data.cliente.nombres} ${data.cliente.apellidos}</p>
                        <p><strong>Teléfono:</strong> ${data.cliente.telefono}</p>
                        ${data.cliente.dni ? `<p><strong>DNI:</strong> ${data.cliente.dni}</p>` : ''}
                `;
                
                if (data.es_lead) {
                    html += `
                        <hr>
                        <p class="mb-0">
                            <strong>Estado:</strong> Ya es un lead ${data.estado_lead}
                            <br><strong>Etapa:</strong> ${data.etapa_actual}
                            <br><a href="${BASE_URL}/leads/view/${data.idlead}" class="btn btn-sm btn-primary mt-2" target="_blank">
                                <i class="icon-eye"></i> Ver Lead Existente
                            </a>
                        </p>
                    `;
                } else if (data.es_cliente) {
                    html += `
                        <hr>
                        <p class="mb-0">
                            <strong>⚠️ Ya es cliente activo</strong>
                            <br><small>Verifica si realmente necesita un nuevo servicio</small>
                        </p>
                    `;
                } else {
                    html += `
                        <hr>
                        <p class="mb-0">
                            <button type="button" class="btn btn-success btn-sm" onclick="usarDatosExistentes(${JSON.stringify(data.cliente).replace(/"/g, '&quot;')})">
                                <i class="icon-check"></i> Usar estos datos
                            </button>
                        </p>
                    `;
                }
                
                html += '</div>';
                resultadoDiv.innerHTML = html;
            } else {
                resultadoDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="icon-check"></i> Cliente nuevo - No existe en el sistema
                    </div>
                `;
                setTimeout(() => {
                    resultadoDiv.style.display = 'none';
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultadoDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="icon-close"></i> Error al buscar cliente
                </div>
            `;
        });
}

function usarDatosExistentes(cliente) {
    document.getElementById('idpersona').value = cliente.idpersona || '';
    document.getElementById('nombres').value = cliente.nombres || '';
    document.getElementById('apellidos').value = cliente.apellidos || '';
    document.getElementById('telefono').value = cliente.telefono || '';
    document.getElementById('correo').value = cliente.correo || '';
    document.getElementById('dni').value = cliente.dni || '';
    
    Swal.fire({
        icon: 'success',
        title: 'Datos cargados',
        text: 'Los datos del cliente se han cargado. Completa la información del servicio.',
        timer: 2000,
        showConfirmButton: false
    });
    
    // Avanzar al paso 2
    setTimeout(() => {
        document.getElementById('btnSiguiente')?.click();
    }, 2000);
}

// =====================================================
// 4. VERIFICACIÓN DE COBERTURA AL CAMBIAR DISTRITO
// =====================================================

const selectDistrito = document.getElementById('iddistrito');
if (selectDistrito) {
    selectDistrito.addEventListener('change', function() {
        const distritoId = this.value;
        
        if (!distritoId) return;
        
        const alertaDiv = document.getElementById('alerta-cobertura-zona');
        alertaDiv.innerHTML = '<div class="alert alert-info"><i class="icon-refresh rotating"></i> Verificando cobertura en el distrito...</div>';
        alertaDiv.style.display = 'block';
        
        fetch(`${BASE_URL}/leads/verificarCobertura?distrito=${distritoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.tiene_cobertura) {
                    alertaDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="icon-check"></i> <strong>Distrito con cobertura</strong>
                            ${data.zonas && data.zonas.length > 0 ? `
                                <br><small>Zonas disponibles: ${data.zonas.map(z => z.nombre).join(', ')}</small>
                            ` : ''}
                        </div>
                    `;
                } else {
                    alertaDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="icon-info"></i> <strong>Distrito sin cobertura actualmente</strong>
                            <br><small>Puedes registrar el lead para futuras expansiones</small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertaDiv.style.display = 'none';
            });
    });
}

console.log('✅ Sistema de documentos y geolocalización cargado');
