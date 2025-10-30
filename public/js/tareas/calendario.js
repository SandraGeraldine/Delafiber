/**
 * JavaScript para Calendario de Tareas con FullCalendar
 */

// baseUrl ya está declarado globalmente en otros archivos JS

document.addEventListener('DOMContentLoaded', function() {
    console.log('Calendario JS cargado');
    console.log('baseUrl:', baseUrl);
    console.log('FullCalendar disponible:', typeof FullCalendar !== 'undefined');
    
    const calendarEl = document.getElementById('calendar');
    console.log('Elemento calendar encontrado:', calendarEl);
    
    if (!calendarEl) {
        console.error('No se encontró el elemento #calendar');
        return;
    }
    
    if (typeof FullCalendar === 'undefined') {
        console.error('FullCalendar no está cargado');
        return;
    }
    
    const modalTarea = new bootstrap.Modal(document.getElementById('modalTarea'));
    const formTarea = document.getElementById('formTarea');
    let tareaEditando = null;

    // Inicializar FullCalendar
    console.log('Inicializando FullCalendar...');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día',
            list: 'Lista'
        },
        height: 'auto',
        navLinks: true,
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        
        // Cargar eventos
        events: function(info, successCallback, failureCallback) {
            fetch(`${baseUrl}/tareas/getTareasCalendario?start=${info.startStr}&end=${info.endStr}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => successCallback(data))
            .catch(error => {
                console.error('Error:', error);
                failureCallback(error);
            });
        },

        // Click en fecha vacía - Crear nueva tarea
        select: function(info) {
            abrirModalNuevaTarea(info.startStr);
        },

        // Click en evento existente - Ver/Editar
        eventClick: function(info) {
            abrirModalEditarTarea(info.event);
        },

        // Drag & Drop - Cambiar fecha
        eventDrop: function(info) {
            actualizarFechaTarea(info.event.id, info.event.startStr);
        },

        // Resize evento
        eventResize: function(info) {
            actualizarFechaTarea(info.event.id, info.event.startStr);
        }
    });

    console.log('Renderizando calendario...');
    calendar.render();
    console.log('Calendario renderizado exitosamente');

    // Botón nueva tarea
    document.getElementById('btnNuevaTarea')?.addEventListener('click', function() {
        abrirModalNuevaTarea();
    });

    // Guardar tarea
    document.getElementById('btnGuardarTarea')?.addEventListener('click', function() {
        if (!formTarea.checkValidity()) {
            formTarea.reportValidity();
            return;
        }

        const idtarea = document.getElementById('idtarea').value;
        const data = {
            idtarea: idtarea || null,
            titulo: document.getElementById('titulo').value,
            descripcion: document.getElementById('descripcion').value,
            tipo_tarea: document.getElementById('tipo_tarea').value,
            prioridad: document.getElementById('prioridad').value,
            fecha_vencimiento: document.getElementById('fecha_vencimiento').value,
            idlead: document.getElementById('idlead').value || null,
            estado: document.getElementById('estado').value || 'pendiente'
        };

        const url = idtarea 
            ? `${baseUrl}/tareas/actualizarTarea`
            : `${baseUrl}/tareas/crearTareaCalendario`;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                modalTarea.hide();
                calendar.refetchEvents();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo guardar la tarea'
            });
        });
    });

    // Eliminar tarea
    document.getElementById('btnEliminarTarea')?.addEventListener('click', function() {
        const idtarea = document.getElementById('idtarea').value;
        
        Swal.fire({
            title: '¿Eliminar tarea?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${baseUrl}/tareas/eliminarTarea/${idtarea}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminada',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        modalTarea.hide();
                        calendar.refetchEvents();
                    }
                });
            }
        });
    });

    // Funciones auxiliares
    function abrirModalNuevaTarea(fecha = null) {
        tareaEditando = null;
        formTarea.reset();
        document.getElementById('modalTareaTitle').textContent = 'Nueva Tarea';
        document.getElementById('idtarea').value = '';
        document.getElementById('estadoContainer').style.display = 'none';
        document.getElementById('btnEliminarTarea').style.display = 'none';
        
        if (fecha) {
            const fechaLocal = new Date(fecha + 'T09:00:00');
            document.getElementById('fecha_vencimiento').value = 
                fechaLocal.toISOString().slice(0, 16);
        }
        
        modalTarea.show();
    }

    function abrirModalEditarTarea(event) {
        tareaEditando = event;
        const props = event.extendedProps;
        
        document.getElementById('modalTareaTitle').textContent = 'Editar Tarea';
        document.getElementById('idtarea').value = event.id;
        document.getElementById('titulo').value = event.title;
        document.getElementById('descripcion').value = props.descripcion || '';
        document.getElementById('tipo_tarea').value = props.tipo_tarea;
        document.getElementById('prioridad').value = props.prioridad;
        document.getElementById('estado').value = props.estado;
        document.getElementById('idlead').value = props.idlead || '';
        
        const fechaLocal = new Date(event.start);
        document.getElementById('fecha_vencimiento').value = 
            fechaLocal.toISOString().slice(0, 16);
        
        document.getElementById('estadoContainer').style.display = 'block';
        document.getElementById('btnEliminarTarea').style.display = 'inline-block';
        
        modalTarea.show();
    }

    function actualizarFechaTarea(id, nuevaFecha) {
        fetch(`${baseUrl}/tareas/actualizarFechaTarea`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id: id,
                fecha_vencimiento: nuevaFecha
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Fecha actualizada',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                calendar.refetchEvents();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            calendar.refetchEvents();
        });
    }
});
