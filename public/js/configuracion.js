function editarEtapa(etapa) {
    $('#etapa_id').val(etapa.idetapa);
    $('#etapa_nombre').val(etapa.nombre);
    $('#etapa_descripcion').val(etapa.descripcion);
    $('#etapa_orden').val(etapa.orden);
    $('#etapa_activo').prop('checked', etapa.activo == 1);
    $('#etapaTitle').text('Editar Etapa');
    $('#formEtapa').attr('action', BASE_URL + 'configuracion/etapas/update/' + etapa.idetapa);
    $('#modalEtapa').modal('show');
}

function editarOrigen(origen) {
    $('#origen_id').val(origen.idorigen);
    $('#origen_nombre').val(origen.nombre);
    $('#origen_descripcion').val(origen.descripcion);
    $('#origen_activo').prop('checked', origen.activo == 1);
    $('#origenTitle').text('Editar Origen');
    $('#formOrigen').attr('action', BASE_URL + 'configuracion/origenes/update/' + origen.idorigen);
    $('#modalOrigen').modal('show');
}

function editarModalidad(modalidad) {
    $('#modalidad_id').val(modalidad.idmodalidad);
    $('#modalidad_nombre').val(modalidad.nombre);
    $('#modalidad_descripcion').val(modalidad.descripcion);
    $('#modalidad_activo').prop('checked', modalidad.activo == 1);
    $('#modalidadTitle').text('Editar Modalidad');
    $('#formModalidad').attr('action', BASE_URL + 'configuracion/modalidades/update/' + modalidad.idmodalidad);
    $('#modalModalidad').modal('show');
}

function editarUsuario(usuario) {
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
    $('#formUsuario').attr('action', BASE_URL + 'configuracion/usuarios/update/' + usuario.idusuario);
    $('#modalUsuario').modal('show');
}

function eliminar(tipo, id) {
    if (confirm('¿Estás seguro de eliminar este registro?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = BASE_URL + 'configuracion/' + tipo + 's/delete/' + id;
        form.innerHTML = CSRF_FIELD;
        document.body.appendChild(form);
        form.submit();
    }
}

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
        $('#formEtapa').attr('action', BASE_URL + 'configuracion/etapas/store');
    }
});
$('#modalOrigen').on('show.bs.modal', function(e) {
    if (!$(e.relatedTarget).data('edit')) {
        $('#formOrigen').attr('action', BASE_URL + 'configuracion/origenes/store');
    }
});
$('#modalModalidad').on('show.bs.modal', function(e) {
    if (!$(e.relatedTarget).data('edit')) {
        $('#formModalidad').attr('action', BASE_URL + 'configuracion/modalidades/store');
    }
});
$('#modalUsuario').on('show.bs.modal', function(e) {
    if (!$(e.relatedTarget).data('edit')) {
        $('#formUsuario').attr('action', BASE_URL + 'configuracion/usuarios/store');
    }
});

// Define BASE_URL y CSRF_FIELD si no existen
if (typeof BASE_URL === 'undefined') {
    window.BASE_URL = window.location.origin + '/';
}
if (typeof CSRF_FIELD === 'undefined') {
    window.CSRF_FIELD = '<?= csrf_field() ?>';
}
