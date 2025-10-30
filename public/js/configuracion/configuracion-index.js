/**
 * JavaScript para Configuración del Sistema
 */

const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

// Funciones para editar
window.editarEtapa = function(etapa) {
    $('#etapa_id').val(etapa.idetapa);
    $('#etapa_nombre').val(etapa.nombre);
    $('#etapa_descripcion').val(etapa.descripcion);
    $('#etapa_orden').val(etapa.orden);
    $('#etapa_activo').prop('checked', etapa.activo == 1);
    $('#etapaTitle').text('Editar Etapa');
    $('#formEtapa').attr('action', baseUrl + '/configuracion/etapas/update/' + etapa.idetapa);
    $('#modalEtapa').modal('show');
};

window.editarOrigen = function(origen) {
    $('#origen_id').val(origen.idorigen);
    $('#origen_nombre').val(origen.nombre);
    $('#origen_descripcion').val(origen.descripcion);
    $('#origen_activo').prop('checked', origen.activo == 1);
    $('#origenTitle').text('Editar Origen');
    $('#formOrigen').attr('action', baseUrl + '/configuracion/origenes/update/' + origen.idorigen);
    $('#modalOrigen').modal('show');
};

window.editarModalidad = function(modalidad) {
    $('#modalidad_id').val(modalidad.idmodalidad);
    $('#modalidad_nombre').val(modalidad.nombre);
    $('#modalidad_descripcion').val(modalidad.descripcion);
    $('#modalidad_activo').prop('checked', modalidad.activo == 1);
    $('#modalidadTitle').text('Editar Modalidad');
    $('#formModalidad').attr('action', baseUrl + '/configuracion/modalidades/update/' + modalidad.idmodalidad);
    $('#modalModalidad').modal('show');
};

window.editarUsuario = function(usuario) {
    $('#usuario_id').val(usuario.idusuario);
    $('#usuario_nombres').val(usuario.nombres);
    $('#usuario_apellidos').val(usuario.apellidos);
    $('#usuario_usuario').val(usuario.usuario);
    $('#usuario_correo').val(usuario.correo);
    $('#usuario_telefono').val(usuario.telefono);
    $('#usuario_rol').val(usuario.rol);
    $('#usuario_activo').prop('checked', usuario.activo == 1);
    $('#usuario_password').removeAttr('required');
    $('#usuarioTitle').text('Editar Usuario');
    $('#formUsuario').attr('action', baseUrl + '/configuracion/usuarios/update/' + usuario.idusuario);
    $('#modalUsuario').modal('show');
};

window.eliminar = function(tipo, id) {
    if (confirm('¿Estás seguro de eliminar este registro?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${baseUrl}/configuracion/${tipo}s/delete/${id}`;
        
        // Agregar CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_test_name';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
};

// Reset modals on close
$('.modal').on('hidden.bs.modal', function() {
    $(this).find('form')[0].reset();
    $(this).find('input[type="hidden"]').val('');
    $(this).find('.modal-title').text($(this).find('.modal-title').text().replace('Editar', 'Nuevo'));
    $('#usuario_password').attr('required', 'required');
});

// Set action on modal show for create
$('#modalEtapa').on('show.bs.modal', function(e) {
    if (!$(e.relatedTarget).data('edit')) {
        $('#formEtapa').attr('action', baseUrl + '/configuracion/etapas/store');
    }
});

$('#modalOrigen').on('show.bs.modal', function(e) {
    if (!$(e.relatedTarget).data('edit')) {
        $('#formOrigen').attr('action', baseUrl + '/configuracion/origenes/store');
    }
});

$('#modalModalidad').on('show.bs.modal', function(e) {
    if (!$(e.relatedTarget).data('edit')) {
        $('#formModalidad').attr('action', baseUrl + '/configuracion/modalidades/store');
    }
});

$('#modalUsuario').on('show.bs.modal', function(e) {
    if (!$(e.relatedTarget).data('edit')) {
        $('#formUsuario').attr('action', baseUrl + '/configuracion/usuarios/store');
    }
});
