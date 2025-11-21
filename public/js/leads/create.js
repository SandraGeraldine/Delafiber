/**
 * JavaScript para el formulario de creación de Leads
 * Maneja búsqueda por DNI, validaciones y verificación de cobertura
 */

// Función auxiliar para escapar HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text || '').replace(/[&<>"']/g, m => map[m]);
}

class PersonaManager {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.coberturaInicializada = false; // Flag para evitar doble inicialización
        this.initEvents();
    }

    initEvents() {
        const btnBuscarDni = document.getElementById('btnBuscarDni');
        const dniInput = document.getElementById('dni');
        const dniLoading = document.getElementById('dni-loading');
        
        if (!btnBuscarDni || !dniInput) {
            // Botones de búsqueda no encontrados
            return;
        }
        
        // NO inicializar verificación de cobertura aquí
        // Se inicializará cuando el usuario llegue al Paso 2

        // =========================================
        // BÚSQUEDA POR DNI
        // =========================================
        btnBuscarDni.addEventListener('click', () => {
            const dni = dniInput.value.trim();
            
            if (dni.length !== 8) {
                Swal.fire({
                    icon: 'error',
                    title: 'DNI Inválido',
                    text: 'El DNI debe tener exactamente 8 dígitos',
                    confirmButtonColor: '#3085d6'
                });
                dniInput.focus();
                return;
            }

            dniLoading.style.display = 'block';
            btnBuscarDni.disabled = true;

            // Primero verificar si ya existe en la BD
            fetch(`${this.baseUrl}/personas/verificarDni?dni=${dni}`)
                .then(response => response.json())
                .then(data => {
                    if (data.existe) {
                        dniLoading.style.display = 'none';
                        btnBuscarDni.disabled = false;
                        
                        const personaNombreSafe = escapeHtml(data.persona.nombres || '');
                        const personaApellidosSafe = escapeHtml(data.persona.apellidos || '');
                        const personaTelefonoSafe = escapeHtml(data.persona.telefono || 'No registrado');
                        const personaCorreoSafe = escapeHtml(data.persona.correo || 'No registrado');

                        Swal.fire({
                            icon: 'warning',
                            title: '⚠️ Persona Ya Registrada',
                            html: `
                                <div class="text-start">
                                    <p><strong>Esta persona ya está en el sistema:</strong></p>
                                    <ul class="list-unstyled">
                                        <li>👤 <strong>Nombre:</strong> ${personaNombreSafe} ${personaApellidosSafe}</li>
                                        <li>📞 <strong>Teléfono:</strong> ${personaTelefonoSafe}</li>
                                        <li>📧 <strong>Correo:</strong> ${personaCorreoSafe}</li>
                                    </ul>
                                    <hr>
                                    <p class="text-muted small">
                                        <i class="icon-info"></i> Puedes crear una nueva solicitud de servicio para este cliente
                                    </p>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Usar estos datos',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.autocompletarDatos(data.persona);
                            }
                        });
                        return;
                    }

                    // Si no existe, buscar en RENIEC
                    this.buscarEnReniec(dni, dniLoading, btnBuscarDni);
                })
                .catch(error => {
                    dniLoading.style.display = 'none';
                    btnBuscarDni.disabled = false;
                    console.error('❌ Error al verificar DNI:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo conectar al servidor. Intenta de nuevo.',
                        confirmButtonColor: '#d33'
                    });
                });
        });

        // =========================================
        // ENTER SOLO EN DNI (no en otros campos)
        // =========================================
        dniInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnBuscarDni.click();
            }
        });

        // =========================================
        // VALIDACIÓN EN TIEMPO REAL - TELÉFONO
        // =========================================
        const telefonoInput = document.getElementById('telefono');
        if (telefonoInput) {
            telefonoInput.addEventListener('input', (e) => {
                // Solo permitir números
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                // Validar formato mientras escribe
                const valor = e.target.value;
                if (valor.length === 9) {
                    if (valor.startsWith('9')) {
                        e.target.classList.remove('is-invalid');
                        e.target.classList.add('is-valid');
                    } else {
                        e.target.classList.remove('is-valid');
                        e.target.classList.add('is-invalid');
                    }
                } else {
                    e.target.classList.remove('is-valid', 'is-invalid');
                }
            });
        }

        // =========================================
        // VALIDACIÓN EN TIEMPO REAL - DNI
        // =========================================
        dniInput.addEventListener('input', (e) => {
            // Solo permitir números
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }

    // =========================================
    // BUSCAR EN RENIEC
    // =========================================
    buscarEnReniec(dni, dniLoading, btnBuscarDni) {
        fetch(`${this.baseUrl}/api/personas/buscar?dni=${dni}`)
            .then(response => response.json())
            .then(data => {
                dniLoading.style.display = 'none';
                btnBuscarDni.disabled = false;
                
                if (data.success && data.persona) {
                    document.getElementById('nombres').value = data.persona.nombres || '';
                    document.getElementById('apellidos').value = data.persona.apellidos || '';
                    
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Datos encontrados en RENIEC',
                        text: 'Ahora completa teléfono y demás información',
                        timer: 2500,
                        showConfirmButton: false,
                        timerProgressBar: true
                    });
                    
                    // Focus en teléfono después del toast
                    setTimeout(() => {
                        document.getElementById('telefono')?.focus();
                    }, 2600);
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'DNI no encontrado en RENIEC',
                        text: 'Puedes registrar los datos manualmente',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#3085d6'
                    });
                    document.getElementById('nombres')?.focus();
                }
            })
            .catch(error => {
                dniLoading.style.display = 'none';
                btnBuscarDni.disabled = false;
                console.error('❌ Error al consultar RENIEC:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al consultar RENIEC',
                    text: 'Puedes registrar los datos manualmente',
                    confirmButtonColor: '#d33'
                });
            });
    }

    // =========================================
    // AUTOCOMPLETAR DATOS DE PERSONA EXISTENTE
    // =========================================
    autocompletarDatos(persona) {
        const nombresEl = document.getElementById('nombres');
        const apellidosEl = document.getElementById('apellidos');
        const telefonoEl = document.getElementById('telefono');
        const correoEl = document.getElementById('correo');
        const idpersonaEl = document.getElementById('idpersona');

        if (nombresEl) nombresEl.value = escapeHtml(persona.nombres || '');
        if (apellidosEl) apellidosEl.value = escapeHtml(persona.apellidos || '');
        if (telefonoEl) telefonoEl.value = escapeHtml(persona.telefono || '');
        if (correoEl) correoEl.value = escapeHtml(persona.correo || '');
        
        // IMPORTANTE: Guardar ID de persona para no duplicar
        if (idpersonaEl) {
            idpersonaEl.value = persona.idpersona;
        }

        // Agregar indicador visual
        const indicador = document.createElement('div');
        indicador.className = 'alert alert-success mt-3 alert-cliente-existente';
        indicador.innerHTML = `
            <i class="icon-check"></i> <strong>Cliente existente cargado</strong><br>
            <small>Se creará una nueva solicitud de servicio para este cliente</small>
        `;
        
        const cardBody = nombresEl.closest('.card-body');
        if (cardBody) {
            // Remover indicador anterior si existe
            const indicadorAnterior = cardBody.querySelector('.alert-cliente-existente');
            if (indicadorAnterior) {
                indicadorAnterior.remove();
            }
            
            cardBody.insertBefore(indicador, cardBody.firstChild);
        }

        Swal.fire({
            icon: 'success',
            title: '✅ Cliente Cargado',
            text: 'Ahora completa la información de la solicitud de servicio',
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true
        });
    }
    
    // =========================================
    // VERIFICAR COBERTURA DE ZONAS
    // =========================================
    initVerificarCobertura() {
        // Evitar doble inicialización
        if (this.coberturaInicializada) {
            return;
        }
        
        const distritoSelect = document.getElementById('iddistrito');
        
        if (!distritoSelect) {
            return;
        }
        
        this.coberturaInicializada = true;
        
        distritoSelect.addEventListener('change', async () => {
            const distrito = distritoSelect.value;
            
            if (!distrito) {
                return;
            }
            
            try {
                const url = `${this.baseUrl}/leads/verificar-cobertura?distrito=${distrito}`;
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                // Verificar si SweetAlert está disponible
                if (typeof Swal === 'undefined') {
                    console.error('SweetAlert2 no está cargado!');
                    alert(`Cobertura: ${result.mensaje || 'Verificación completada'}`);
                    return;
                }
                
                if (result.success) {
                    this.mostrarAlertaCobertura(result);
                } else {
                    this.mostrarAlertaCobertura(result);
                }
            } catch (error) {
                console.error('Error al verificar cobertura:', error);
                console.error('Stack:', error.stack);
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Error al verificar cobertura',
                        text: error.message,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    alert('Error al verificar cobertura: ' + error.message);
                }
            }
        });
    }

    // =========================================
    // MOSTRAR ALERTA DE COBERTURA
    // =========================================
    mostrarAlertaCobertura(result) {
        // Mostrar alerta de cobertura en UI
        
        const alertaContainer = document.getElementById('alerta-cobertura-zona');
        
        if (!alertaContainer) {
            console.error('❌ Contenedor #alerta-cobertura-zona no encontrado');
            return;
        }
        
        if (result.tiene_cobertura) {
            const totalZonas = result.zonas_activas || 0;
            
            // Construir lista de zonas con sus campañas
            let zonasListaHtml = '';
            if (result.zonas && result.zonas.length > 0) {
                zonasListaHtml = result.zonas.map(z => {
                    return `<li><strong>${escapeHtml(z.nombre_zona)}</strong> (${escapeHtml(z.campania_nombre)})</li>`;
                }).join('');
            }
            
            // Mostrar mensaje de cobertura positiva
            alertaContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="icon-check"></i> ¡Excelente! Tenemos ${totalZonas} zona(s) activa(s) en campañas
                    </h6>
                    <p class="mb-2">El lead será asignado automáticamente a una zona al guardar.</p>
                    <hr>
                    <p class="mb-1"><strong>Zonas activas:</strong></p>
                    <ul class="mb-0">
                        ${zonasListaHtml}
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            alertaContainer.style.display = 'block';
        } else {
            const distrito = result.distrito_nombre || 'esta zona';
            
            // Mostrar mensaje de sin cobertura
            alertaContainer.innerHTML = `
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="icon-info"></i> Sin zonas activas
                    </h6>
                    <p class="mb-0">
                        <strong>${distrito}</strong> no tiene zonas de campaña activas en este momento.
                        El lead se registrará normalmente.
                    </p>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            alertaContainer.style.display = 'block';
        }
    }
}

// =========================================
// INICIALIZAR

document.addEventListener('DOMContentLoaded', () => {
    if (typeof BASE_URL !== 'undefined') {
        window.personaManager = new PersonaManager(BASE_URL);
    } else {
        console.error('BASE_URL no está definida');
    }
});

// MEJORAS PARA EL FORMULARIO DE LEADS

document.addEventListener('DOMContentLoaded', () => {
    // 1. MAPA EMBEBIDO EN PASO 2 

    let mapaInicializado = false;
    const slcTipoServicio = document.getElementById('slcTipoServicio');
    const mapContainer = document.getElementById('mapContainer');
    const coordInput = document.getElementById('coordenadas_manual');
    const btnValidarCoberturaCoord = document.getElementById('btnValidarCoberturaCoord');

    async function initMapaCoberturaPaso2() {
        if (mapaInicializado) {
            return;
        }

        if (!mapContainer) {
            console.warn('mapContainer no encontrado para mapa de cobertura');
            return;
        }

        const tipo = (slcTipoServicio && slcTipoServicio.value === '2') ? 'Antenas' : 'Cajas';

        try {
            const mapa = await import(`${BASE_URL}js/api/Mapa.js`);
            await mapa.iniciarMapa(tipo, 'mapContainer', 'inline');
            await mapa.eventoMapa(true);
            mapa.obtenerCoordenadasClick();
            mapaInicializado = true;
        } catch (err) {
            console.error('Error al iniciar mapa embebido:', err);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar el mapa',
                    text: 'No se pudo inicializar el mapa de cobertura. Por favor, recarga la página.'
                });
            } else {
                alert('No se pudo inicializar el mapa de cobertura. Por favor, recarga la página.');
            }
        }
    }

    // Exponer al ámbito global para que wizard.js pueda invocarlo al entrar al Paso 2
    window.initMapaCoberturaPaso2 = initMapaCoberturaPaso2;

    // Cambiar entre Cajas y Antenas cuando el usuario cambie el select
    if (slcTipoServicio) {
        slcTipoServicio.addEventListener('change', async () => {
            if (!mapContainer) return;

            const tipoSeleccionado = slcTipoServicio.value === '2' ? 'Antenas' : 'Cajas';
            try {
                const mapa = await import(`${BASE_URL}js/api/Mapa.js`);
                await mapa.iniciarMapa(tipoSeleccionado, 'mapContainer', 'inline');
                await mapa.eventoMapa(true);
                mapa.obtenerCoordenadasClick();
            } catch (err) {
                console.error('Error al cambiar tipo de red en el mapa:', err);
            }
        });
    }

    // Botón "Validar cobertura" usando coordenadas manuales (solo centra el mapa por ahora)
    if (btnValidarCoberturaCoord && coordInput) {
        btnValidarCoberturaCoord.addEventListener('click', async () => {
            const valor = (coordInput.value || '').trim();
            if (!valor) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Coordenadas requeridas',
                        text: 'Ingresa o pega unas coordenadas en formato lat,lng para validar visualmente.',
                    });
                }
                return;
            }

            const partes = valor.split(',');
            if (partes.length !== 2) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Formato inválido',
                        text: 'Usa el formato latitud,longitud. Ejemplo: -13.4123,-76.1324',
                    });
                }
                return;
            }

            const lat = parseFloat(partes[0]);
            const lng = parseFloat(partes[1]);
            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Coordenadas inválidas',
                        text: 'No se pudieron interpretar las coordenadas ingresadas.',
                    });
                }
                return;
            }

            try {
                const mapa = await import(`${BASE_URL}js/api/Mapa.js`);
                // Centrar y marcar las coordenadas en el mapa
                await mapa.buscarCoordenadassinMapa(lat, lng);

                // Guardar también las coordenadas para que se persistan en el lead
                const inputCoordsServicio = document.getElementById('coordenadas_servicio');
                if (inputCoordsServicio) {
                    inputCoordsServicio.value = `${lat},${lng}`;
                }

                // Actualizar bloque informativo de coordenadas, si existe
                const infoDiv = document.getElementById('coordenadas-info');
                const textoDiv = document.getElementById('coordenadas-texto');
                if (infoDiv && textoDiv) {
                    textoDiv.innerHTML = `
                        <strong>Ubicación capturada:</strong><br>
                        Latitud: ${lat.toFixed(6)}, Longitud: ${lng.toFixed(6)}
                    `;
                    infoDiv.style.display = 'block';
                }

                const resultado = await mapa.verificarCoberturaCoordenadas(lat, lng);
                const alerta = document.getElementById('alerta-cobertura-ubicacion');
                if (alerta) {
                    alerta.style.display = 'block';
                    if (resultado && resultado.tieneCobertura) {
                        alerta.className = 'alert alert-success mt-2';
                        alerta.textContent = 'Zona CON cobertura para ' + (resultado.tipo || 'la red seleccionada') + '.\nRevisa también el mapa para confirmar detalles.';
                    } else {
                        alerta.className = 'alert alert-danger mt-2';
                        alerta.textContent = 'Zona SIN cobertura detectada para ' + (resultado.tipo || 'la red seleccionada') + '.\nPuedes igualmente registrar el lead, pero quedará fuera de cobertura.';
                    }
                }
            } catch (err) {
                console.error('Error al usar coordenadas manuales en el mapa:', err);
            }
        });
    }

    // ============================================
    // 2. CONFIGURACIÓN MEJORADA DE SELECT2

    function initSelect2(selector, options = {}) {
        const element = document.querySelector(selector);
        if (!element) {
            console.warn(`Elemento no encontrado: ${selector}`);
            return;
        }

        // Verificar que jQuery y Select2 estén cargados
        if (typeof $ === 'undefined' || !$.fn.select2) {
            console.error('jQuery o Select2 no están cargados');
            return;
        }

        const $element = $(element);

        // Destruir instancia anterior si existe
        if ($element.hasClass('select2-hidden-accessible')) {
            try {
                $element.select2('destroy');
            } catch (e) {
                // Ignorar errores al destruir
            }
        }

        // Configuración por defecto
        const defaultConfig = {
            theme: 'bootstrap-5',
            width: '100%',
            dropdownAutoWidth: false,
            language: {
                noResults: function () {
                    return 'No se encontraron resultados';
                },
                searching: function () {
                    return 'Buscando...';
                }
            },
            placeholder: options.placeholder || 'Seleccione una opción',
            allowClear: true,
            escapeMarkup: function (markup) {
                return markup; // Permite HTML en las opciones
            }
        };

        // Si está dentro de un modal, configurar dropdownParent
        const $modal = $element.closest('.modal');
        if ($modal.length > 0) {
            defaultConfig.dropdownParent = $modal;
        }

        // Combinar configuraciones
        const finalConfig = { ...defaultConfig, ...options };

        try {
            $element.select2(finalConfig);
            console.log(`Select2 inicializado correctamente: ${selector}`);
        } catch (error) {
            console.error(`Error inicializando Select2 en ${selector}:`, error);
        }
    }

    // Inicializar Select2 cuando el DOM esté listo
    if (typeof $ !== 'undefined') {
        $(document).ready(function () {
            setTimeout(function () {
                // #iddistrito y #tipo_servicio se mantienen como selects nativos
                initSelect2('#idorigen', { placeholder: 'Seleccione origen' });
                initSelect2('#idmodalidad', { placeholder: 'Seleccione modalidad' });
                initSelect2('#plan_interes', {
                    placeholder: 'Buscar plan...',
                    minimumResultsForSearch: 0,
                    width: '100%'
                });
            }, 100);
        });
    }

    // ============================================
    // 3. CARGA DINÁMICA DE PLANES 

    const selServicio = document.getElementById('tipo_servicio');
    const selPlan = document.getElementById('plan_interes');
    const planInfo = document.getElementById('plan_info');

    let cargandoPlanes = false;

    async function cargarPlanes() {
        if (cargandoPlanes || !selPlan) return;

        try {
            cargandoPlanes = true;
            selPlan.disabled = true;

            if (planInfo) {
                planInfo.innerHTML = '<i class="icon-refresh rotating"></i> Cargando planes...';
            }

            const opt = selServicio?.selectedOptions?.[0];
            const tipoRaw = opt ? (opt.getAttribute('data-tipo') || '').trim() : '';

            const mapaTipos = {
                'Fibra Óptica': 'FIBR',
                'Fibra Optica': 'FIBR',
                'Fibra': 'FIBR',
                'Cable': 'CABL',
                'Wireless Internet Service Provider': 'WISP',
            };

            let tipo = mapaTipos[tipoRaw] || (tipoRaw || '').split(':')[0].trim();

            const url = tipo
                ? `${BASE_URL}api/catalogo/planes?tipo=${encodeURIComponent(tipo)}`
                : `${BASE_URL}api/catalogo/planes`;

            const res = await fetch(url);

            if (!res.ok) throw new Error(`Error HTTP: ${res.status}`);

            const planes = await res.json();

            selPlan.innerHTML = '<option value="">Seleccione un plan</option>';

            if (Array.isArray(planes) && planes.length > 0) {
                planes.forEach(p => {
                    const vel = (p.velocidad && p.velocidad !== '[]')
                        ? ` - ${p.velocidad} Mbps`
                        : '';
                    const precio = p.precio ? ` - S/ ${p.precio}` : '';
                    const nombre = `${p.nombre}${vel}${precio}`;

                    const option = document.createElement('option');
                    option.value = (p.id ?? p.codigo ?? p.nombre ?? '').toString();
                    option.textContent = nombre || 'Plan';
                    option.dataset.velocidad = p.velocidad || '';
                    option.dataset.precio = p.precio || '';

                    selPlan.appendChild(option);
                });

                if (planInfo) {
                    planInfo.textContent = `${planes.length} planes disponibles`;
                }
            } else {
                selPlan.innerHTML = '<option value="">No hay planes disponibles</option>';
                if (planInfo) {
                    planInfo.textContent = 'No hay planes disponibles para este servicio';
                }
            }
            selPlan.disabled = false;

        } catch (error) {
            console.error('Error cargando planes:', error);
            if (selPlan) selPlan.innerHTML = '<option value="">Error al cargar planes</option>';
            if (planInfo) {
                planInfo.innerHTML = '<span class="text-danger">No se pudo cargar los planes</span>';
            }
            if (selPlan) selPlan.disabled = false;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar planes',
                    text: 'No se pudieron cargar los planes. Por favor, intenta de nuevo.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                alert('No se pudieron cargar los planes. Por favor, intenta de nuevo.');
            }
        } finally {
            cargandoPlanes = false;
        }
    }

    if (selServicio) {
        if (typeof $ !== 'undefined') {
            $(selServicio).on('select2:select select2:clear change', function () {
                const valor = $(this).val();
                if (!valor && selPlan) {
                    selPlan.innerHTML = '<option value="">Primero seleccione un servicio</option>';
                    if (planInfo) {
                        planInfo.textContent = '';
                    }
                    return;
                }
                cargarPlanes();
            });
        } else {
            selServicio.addEventListener('change', function () {
                if (!this.value && selPlan) {
                    selPlan.innerHTML = '<option value="">Primero seleccione un servicio</option>';
                    if (planInfo) {
                        planInfo.textContent = '';
                    }
                    return;
                }
                cargarPlanes();
            });
        }
    }
});