// JS para formulario de registro de lead de campo


document.addEventListener('DOMContentLoaded', function () {
    // Mensaje de éxito después de registrar desde Campo
    if (window.leadCampoSwalSuccess) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Registro guardado',
                text: 'El lead de campo se registró correctamente y ya está en el sistema central.',
                timer: 2500,
                showConfirmButton: false
            });
        } else {
            alert('Lead de campo registrado correctamente.');
        }
    }

    // Función para limpiar campos cuando se cambia / borra el DNI
    function limpiarCamposPersonaPorDni() {
        const campos = [
            'nombres',
            'apellidos',
            'telefono1',
            'telefono2',
            'telefono3',
            'direccion',
            'detalles',
            'coordenadas_servicio',
            'coordenadas_mostrar'
        ];

        campos.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.value = '';
            }
        });
    }

    // Limpiar automáticamente cuando se borre o modifique el DNI
    const dniInputGlobal = document.getElementById('dni');
    if (dniInputGlobal) {
        dniInputGlobal.addEventListener('input', function () {
            const valor = this.value.trim();
            // Si el DNI queda vacío o con menos de 8 dígitos, limpiamos los demás campos
            if (valor.length < 8) {
                limpiarCamposPersonaPorDni();
            }
        });
    }

    // Botón para buscar DNI (búsqueda rápida en BD interna)
    const btnBuscarDni = document.getElementById('btn-buscar-dni');
    if (btnBuscarDni) {
        btnBuscarDni.addEventListener('click', function () {
            const dniInput = document.getElementById('dni');
            const dni = dniInput ? dniInput.value.trim() : '';

            if (dni.length !== 8) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'DNI inválido',
                        text: 'Por favor ingrese un DNI válido de 8 dígitos.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('Por favor ingrese un DNI válido de 8 dígitos');
                }
                return;
            }

            if (typeof BASE_URL === 'undefined') {
                console.error('BASE_URL no está definida para lead_form.js');
                return;
            }

            const url = BASE_URL.replace(/\/$/, '') + '/leads/campoBuscarDni?dni=' + encodeURIComponent(dni);

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data || data.success === false) {
                    const msg = data && data.message ? data.message : 'No se pudo buscar el DNI.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                    } else {
                        alert(msg);
                    }
                    return;
                }

                if (data.encontrado && data.persona) {
                    const p = data.persona;
                    const nombresInput = document.getElementById('nombres');
                    const apellidosInput = document.getElementById('apellidos');
                    const tel1Input = document.getElementById('telefono1');
                    const direccionInput = document.getElementById('direccion');

                    // Siempre sobreescribir los datos al buscar un DNI,
                    // para que al ingresar un nuevo DNI se reemplacen los valores anteriores
                    if (nombresInput) nombresInput.value = p.nombres || '';
                    if (apellidosInput) apellidosInput.value = p.apellidos || '';
                    if (tel1Input) tel1Input.value = p.telefono || '';
                    if (direccionInput) direccionInput.value = p.direccion || '';

                    if (typeof Swal !== 'undefined') {
                        const mensaje = data.registrado === false
                            ? (data.message || 'Datos obtenidos de RENIEC. Revisa y completa la información.')
                            : (data.message || 'Se cargaron los datos del cliente asociado a este DNI.');

                        Swal.fire({
                            icon: 'success',
                            title: 'Datos encontrados',
                            text: mensaje,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Sin coincidencias',
                            text: 'No se encontró una persona registrada con este DNI. Puedes continuar llenando los datos.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    } else {
                        alert('No se encontró una persona registrada con este DNI. Puedes continuar llenando los datos.');
                    }
                }
            })
            .catch(err => {
                console.error('Error buscando DNI desde campo:', err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al buscar el DNI.'
                    });
                } else {
                    alert('Ocurrió un error al buscar el DNI.');
                }
            });
        });
    }

    // Botón para obtener coordenadas GPS + integración con mapa
    const btnCoord = document.getElementById('btn-obtener-coordenada');
    const inputCoord = document.getElementById('coordenadas_servicio');
    const inputCoordMostrar = document.getElementById('coordenadas_mostrar');
    const mapaPreview = document.getElementById('mapa-preview');

    // Referencia al módulo del mapa (js/api/Mapa.js)
    let mapaModulo = null;

    async function initMapaCampo() {
        if (!mapaPreview) return;

        try {
            if (!mapaModulo) {
                mapaModulo = await import(`${BASE_URL}js/api/Mapa.js`);
            }

            // Mapa ligero para formulario de campo: solo mapa base + click para marcar coordenadas
            if (typeof mapaModulo.iniciarMapaSimple === 'function') {
                await mapaModulo.iniciarMapaSimple('mapa-preview');
            } else {
                // Fallback: usar iniciarMapa tradicional si no existe la versión simple
                await mapaModulo.iniciarMapa('Cajas', 'mapa-preview', 'inline');
            }
            await mapaModulo.eventoMapa(true);

            // Si ya hay coordenadas guardadas, centramos y marcamos
            const value = (inputCoord && inputCoord.value) ? inputCoord.value.trim() : '';
            if (value) {
                const partes = value.split(',');
                if (partes.length === 2) {
                    const lat = parseFloat(partes[0]);
                    const lng = parseFloat(partes[1]);
                    if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                        await mapaModulo.buscarCoordenadassinMapa(lat, lng);
                    }
                }
            }
        } catch (err) {
            console.error('Error al inicializar mapa en lead de campo:', err);
        }
    }

    // Inicializar mapa al cargar la página
    initMapaCampo();

    if (btnCoord) {
        btnCoord.addEventListener('click', async function () {
            btnCoord.disabled = true;
            btnCoord.innerText = 'Obteniendo...';

            const valorManual = (inputCoord && inputCoord.value) ? inputCoord.value.trim() : '';

            try {
                // Siempre intentamos primero geolocalización en cada clic
                if (!navigator.geolocation) {
                    console.warn('Geolocalización no soportada, usando valor manual si existe');
                } else {
                    navigator.geolocation.getCurrentPosition(async function (position) {
                        const lat = position.coords.latitude.toFixed(6);
                        const lng = position.coords.longitude.toFixed(6);
                        const value = lat + ',' + lng;

                        if (inputCoord) inputCoord.value = value;
                        if (inputCoordMostrar) inputCoordMostrar.value = value;

                        try {
                            if (!mapaModulo) {
                                mapaModulo = await import(`${BASE_URL}js/api/Mapa.js`);
                                await mapaModulo.iniciarMapa('Cajas', 'mapa-preview', 'inline');
                                await mapaModulo.eventoMapa(true);
                            }

                            await mapaModulo.buscarCoordenadassinMapa(parseFloat(lat), parseFloat(lng));
                        } catch (err) {
                            console.error('Error al actualizar mapa con coordenadas de campo:', err);
                        }

                        btnCoord.disabled = false;
                        btnCoord.innerText = 'BUSCAR';
                    }, async function (error) {
                        console.error('Error geolocalización, usando valor manual si hay:', error);

                        // Si geolocalización falla, usamos como respaldo el valor manual
                        if (valorManual) {
                            const partes = valorManual.split(',');
                            if (partes.length === 2) {
                                const lat = parseFloat(partes[0]);
                                const lng = parseFloat(partes[1]);

                                if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                                    if (inputCoordMostrar) inputCoordMostrar.value = `${lat.toFixed(6)},${lng.toFixed(6)}`;

                                    try {
                                        if (!mapaModulo) {
                                            mapaModulo = await import(`${BASE_URL}js/api/Mapa.js`);
                                            await mapaModulo.iniciarMapa('Cajas', 'mapa-preview', 'inline');
                                            await mapaModulo.eventoMapa(true);
                                        }

                                        await mapaModulo.buscarCoordenadassinMapa(lat, lng);
                                    } catch (err) {
                                        console.error('Error al actualizar mapa con coordenadas manuales:', err);
                                    }
                                }
                            }
                        } else {
                            alert('No se pudo obtener la ubicación y no hay coordenadas configuradas.');
                        }

                        btnCoord.disabled = false;
                        btnCoord.innerText = 'BUSCAR';
                    });

                    return; // Salimos aquí, el resto es solo para caso sin geolocalización
                }

                // Si llegamos aquí es porque no hay geolocalización disponible
                if (valorManual) {
                    const partes = valorManual.split(',');
                    if (partes.length === 2) {
                        const lat = parseFloat(partes[0]);
                        const lng = parseFloat(partes[1]);

                        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                            if (inputCoordMostrar) inputCoordMostrar.value = `${lat.toFixed(6)},${lng.toFixed(6)}`;

                            if (!mapaModulo) {
                                mapaModulo = await import(`${BASE_URL}js/api/Mapa.js`);
                                await mapaModulo.iniciarMapa('Cajas', 'mapa-preview', 'inline');
                                await mapaModulo.eventoMapa(true);
                            }

                            await mapaModulo.buscarCoordenadassinMapa(lat, lng);
                        }
                    }
                } else {
                    alert('No se pudo obtener la ubicación y no hay coordenadas configuradas.');
                }

                btnCoord.disabled = false;
                btnCoord.innerText = 'BUSCAR';
            } catch (e) {
                console.error('Error general al manejar coordenadas en campo:', e);
                btnCoord.disabled = false;
                btnCoord.innerText = 'BUSCAR';
            }
        });
    }

    function inicializarZonaTrabajo() {
        const zonaItems = document.querySelectorAll('.zona-trabajo-item');
        zonaItems.forEach(item => {
            const iframe = item.querySelector('.zona-trabajo-map-iframe');
            const url = item.dataset.zonaUrl;
            if (iframe && url && !iframe.src) {
                iframe.src = url;
            }
        });

        const botones = document.querySelectorAll('.zona-trabajo-open');
        botones.forEach(boton => {
            boton.addEventListener('click', () => {
                const url = boton.dataset.url;
                if (url) {
                    window.open(url, '_blank');
                }
            });
        });
    }

    // Inicializar el módulo de Zona de Trabajo si existe
    inicializarZonaTrabajo();

    // Botones para capturar / elegir foto en cada documento
    function configurarCapturaFoto(idBtnTomar, idBtnGaleria, idInput, idPreview) {
        const btnTomar = document.getElementById(idBtnTomar);
        const btnGaleria = document.getElementById(idBtnGaleria);
        const input = document.getElementById(idInput);
        const preview = document.getElementById(idPreview);

        if (!input) return;

        if (btnTomar) {
            btnTomar.addEventListener('click', function () {
                input.setAttribute('accept', 'image/*');
                input.setAttribute('capture', 'environment');
                input.click();
            });
        }

        if (btnGaleria) {
            btnGaleria.addEventListener('click', function () {
                input.setAttribute('accept', 'image/*,.pdf');
                input.removeAttribute('capture');
                input.click();
            });
        }

        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !preview) return;

            // Solo previsualizar imágenes
            if (!file.type.startsWith('image/')) {
                preview.innerHTML = `<p class="text-info mb-0"><small>Archivo seleccionado: ${file.name}</small></p>`;
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" class="img-fluid rounded" 
                         style="max-height: 200px;" alt="Preview">
                    <p class="text-success mt-2 mb-0"><small>Foto cargada: ${file.name}</small></p>
                `;
            };
            reader.readAsDataURL(file);
        });
    }

    configurarCapturaFoto('btn-tomar-foto-dni-frontal', 'btn-elegir-foto-dni-frontal', 'foto_dni_frontal', 'preview_dni_frontal');
    configurarCapturaFoto('btn-tomar-foto-dni-reverso', 'btn-elegir-foto-dni-reverso', 'foto_dni_reverso', 'preview_dni_reverso');
    configurarCapturaFoto('btn-tomar-foto-fachada', 'btn-elegir-foto-fachada', 'foto_fachada', 'preview_fachada');

    // Marcar notificación de zona como leída al abrir el mapa
    document.querySelectorAll('.zona-notificacion-link').forEach(link => {
        link.addEventListener('click', function () {
            const notifId = this.dataset.notificacionId;
            if (!notifId) return;

            fetch(`${BASE_URL}/notificaciones/marcarLeida/${notifId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            }).catch(err => console.warn('No se pudo marcar la notificación como leída', err));
        });
    });
});
