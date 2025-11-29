$(document).ready(function() {
    // La variable base_url se define en la vista HTML (index.php)
    
    // NOTA: El handler de búsqueda por DNI está en index.php (vanilla JS)
    // para evitar duplicación de eventos
    
    // Cambiar estado (activo/inactivo/suspendido)
    $(document).on('change', '.estado-select', function() {
        const usuarioId = $(this).data('id');
        const nuevoEstado = $(this).val();
        const selectElement = $(this);
        const estadoAnterior = selectElement.data('estado-anterior') || 'activo';
        
        console.log('Cambiando estado del usuario:', usuarioId, 'a:', nuevoEstado);
        
        // Guardar el estado anterior
        selectElement.data('estado-anterior', nuevoEstado);
        
        // Obtener el token CSRF
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        console.log('Token CSRF:', csrfToken);
        console.log('URL:', `${base_url}/usuarios/cambiarEstado/${usuarioId}`);
        
        $.ajax({
            url: `${base_url}/usuarios/cambiarEstado/${usuarioId}`,
            method: 'POST',
            data: { 
                estado: nuevoEstado,
                csrf_test_name: csrfToken
            },
            dataType: 'json'
        })
        .done(function(response) {
            console.log('Respuesta del servidor:', response);
            
            if (response.success) {
                console.log('Estado actualizado exitosamente');
                Swal.fire({
                    icon: 'success',
                    title: 'Estado actualizado',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    // Recargar la página para reflejar los cambios
                    console.log('Recargando página...');
                    location.reload();
                });
            } else {
                console.error('Error al actualizar estado:', response.message);
                // Revertir al estado anterior si falla
                selectElement.val(estadoAnterior);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'No se pudo cambiar el estado'
                });
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Error AJAX:', status, error);
            console.error('Respuesta completa:', xhr.responseText);
            
            // Revertir al estado anterior si falla
            selectElement.val(estadoAnterior);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión al cambiar el estado'
            });
        });
    });

    function actualizarBotonesPaso() {
        const datosPersonaActiva = $('#tabDatosPersona').hasClass('show active');
        if (datosPersonaActiva) {
            $('#btnSiguientePaso').removeClass('d-none');
            $('#btnGuardarUsuario').addClass('d-none');
        } else {
            $('#btnSiguientePaso').addClass('d-none');
            $('#btnGuardarUsuario').removeClass('d-none');
        }
    }

    actualizarBotonesPaso();

    $('a[href="#tabDatosPersona"], #tab-datos-usuario-tab').on('shown.bs.tab', function() {
        actualizarBotonesPaso();
    });

    $('#btnSiguientePaso').on('click', function() {
        const dni = $('#dni').val().trim();
        const telefono = $('#telefono').val().trim();
        const nombres = $('#nombres').val().trim();
        const apellidos = $('#apellidos').val().trim();

        if (!dni || dni.length !== 8) {
            Swal.fire('DNI inválido', 'El DNI debe tener 8 dígitos numéricos.', 'warning');
            $('#dni').focus();
            return;
        }

        if (!telefono || telefono.length !== 9) {
            Swal.fire('Teléfono inválido', 'El teléfono debe tener 9 dígitos.', 'warning');
            $('#telefono').focus();
            return;
        }

        if (!nombres) {
            Swal.fire('Campo requerido', 'Ingresa los nombres de la persona.', 'warning');
            $('#nombres').focus();
            return;
        }

        if (!apellidos) {
            Swal.fire('Campo requerido', 'Ingresa los apellidos de la persona.', 'warning');
            $('#apellidos').focus();
            return;
        }

        $('#tab-datos-usuario-tab').tab('show');
        $('#tabDatosUsuario').find('input:visible, select:visible').first().focus();
    });

    // Crear/Editar usuario
    $('#formUsuario').submit(function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        const usuarioId = $('#idusuario').val();
        const url = usuarioId ? `${base_url}/usuarios/editar/${usuarioId}` : `${base_url}/usuarios/crear`;
        
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                $('#modalUsuario').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(() => location.reload(), 2000);
            } else {
                Swal.fire('Error', response.message || 'Error al guardar usuario', 'error');
            }
        })
        .fail(function(xhr) {
            console.log('Error:', xhr.responseText);
            Swal.fire('Error', 'Error de conexión', 'error');
        });
    });

    // Eliminar usuario
    $(document).on('click', '.btn-eliminar', function() {
        const usuarioId = $(this).data('id');
        
        Swal.fire({
            title: '¿Eliminar usuario?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${base_url}/usuarios/eliminar/${usuarioId}`,
                    method: 'DELETE',
                    dataType: 'json'
                })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', 'Usuario eliminado correctamente', 'success');
                        setTimeout(() => location.reload(), 2000);
                    }
                })
                .fail(function() {
                    Swal.fire('Error', 'No se pudo eliminar el usuario', 'error');
                });
            }
        });
    });

    // Buscar usuarios
    $('#buscarUsuario').on('keyup', function() {
        const valor = $(this).val().toLowerCase();
        $('tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(valor) > -1);
        });
    });

    // Resetear contraseña
    $(document).on('click', '.btn-resetear-password', function() {
        const usuarioId = $(this).data('id');
        
        Swal.fire({
            title: 'Resetear contraseña',
            input: 'password',
            inputLabel: 'Nueva contraseña',
            inputPlaceholder: 'Ingresa la nueva contraseña',
            showCancelButton: true,
            confirmButtonText: 'Cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $.post(`${base_url}/usuarios/resetearPassword/${usuarioId}`, {
                    nueva_password: result.value
                })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire('Éxito', 'Contraseña actualizada', 'success');
                    }
                });
            }
        });
    });
});

// Filtrar usuarios (función global para onclick)
window.filtrarUsuarios = function(filtro) {
    // Actualizar botones activos
    $('.btn-group button').removeClass('active');
    event.target.classList.add('active');
    
    $('tbody tr').show();
    
    switch(filtro) {
        case 'todos':
            // Mostrar todos
            break;
        case 'activos':
            // Ocultar los que no son activos
            $('tbody tr').each(function() {
                const select = $(this).find('.estado-select');
                if (select.val() !== 'activo') {
                    $(this).hide();
                }
            });
            break;
        case 'inactivos':
            // Ocultar los que no son inactivos
            $('tbody tr').each(function() {
                const select = $(this).find('.estado-select');
                if (select.val() !== 'inactivo') {
                    $(this).hide();
                }
            });
            break;
        case 'suspendidos':
            // Ocultar los que no son suspendidos
            $('tbody tr').each(function() {
                const select = $(this).find('.estado-select');
                if (select.val() !== 'suspendido') {
                    $(this).hide();
                }
            });
            break;
        case 'vendedores':
            $('tbody tr:not([data-rol="vendedor"])').hide();
            break;
        case 'admins':
            $('tbody tr:not([data-rol="admin"])').hide();
            break;
    }
}