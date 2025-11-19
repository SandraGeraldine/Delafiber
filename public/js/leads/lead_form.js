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

                    if (nombresInput && !nombresInput.value) nombresInput.value = p.nombres || '';
                    if (apellidosInput && !apellidosInput.value) apellidosInput.value = p.apellidos || '';
                    if (tel1Input && !tel1Input.value) tel1Input.value = p.telefono || '';
                    if (direccionInput && !direccionInput.value) direccionInput.value = p.direccion || '';

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Datos encontrados',
                            text: 'Se cargaron los datos del cliente asociado a este DNI.',
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

    // Botón para obtener coordenadas GPS
    const btnCoord = document.getElementById('btn-obtener-coordenada');
    const inputCoord = document.getElementById('coordenadas_servicio');
    const inputCoordMostrar = document.getElementById('coordenadas_mostrar');
    const mapaPreview = document.getElementById('mapa-preview');

    if (btnCoord) {
        btnCoord.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('La geolocalización no está soportada en este dispositivo');
                return;
            }

            btnCoord.disabled = true;
            btnCoord.innerText = 'Obteniendo...';

            navigator.geolocation.getCurrentPosition(function (position) {
                const lat = position.coords.latitude.toFixed(6);
                const lng = position.coords.longitude.toFixed(6);
                const value = lat + ',' + lng;

                if (inputCoord) inputCoord.value = value;
                if (inputCoordMostrar) inputCoordMostrar.value = value;

                if (mapaPreview) {
                    mapaPreview.innerHTML = `
                        <div>
                            <i class="ti-location-pin" style="font-size: 24px;"></i>
                            <p class="mb-0 mt-2"><small>${value}</small></p>
                            <small class="text-success">Ubicación capturada</small>
                        </div>
                    `;
                }

                btnCoord.disabled = false;
                btnCoord.innerText = 'BUSCAR';
            }, function (error) {
                console.error(error);
                alert('No se pudo obtener la ubicación. Asegúrate de otorgar los permisos necesarios.');
                btnCoord.disabled = false;
                btnCoord.innerText = 'BUSCAR';
            });
        });
    }

    // Botón para capturar foto
    const btnFoto = document.getElementById('btn-foto');
    const inputFoto = document.getElementById('foto');
    const fotoPreview = document.getElementById('foto-preview');

    if (btnFoto) {
        btnFoto.addEventListener('click', function () {
            if (inputFoto) inputFoto.click();
        });
    }

    if (inputFoto) {
        inputFoto.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (fotoPreview) {
                        fotoPreview.innerHTML = `
                            <img src="${e.target.result}" class="img-fluid rounded" 
                                 style="max-height: 200px;" alt="Preview">
                            <p class="text-success mt-2 mb-0"><small>Foto cargada: ${file.name}</small></p>
                        `;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
