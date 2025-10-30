/**
 * JavaScript para Listado de Personas
 */

const BASE_URL = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

// Configuración para SweetAlert2
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

// Manejo de eliminación de personas
document.querySelectorAll('.btn-eliminar').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const nombre = this.getAttribute('data-nombre');

        Swal.fire({
            title: '¿Eliminar contacto?',
            text: `¿Estás seguro de eliminar a ${nombre}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${BASE_URL}/personas/eliminar/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Toast.fire({
                            icon: 'success',
                            title: 'Contacto eliminado correctamente'
                        });
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: data.message || 'Error al eliminar'
                        });
                    }
                })
                .catch(error => {
                    Toast.fire({
                        icon: 'error',
                        title: 'Error de conexión'
                    });
                });
            }
        });
    });
});

// Manejo de conversión a Lead
document.querySelectorAll('.btn-convertir-lead').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const nombre = this.getAttribute('data-nombre');

        Swal.fire({
            title: '¿Convertir a Lead?',
            html: `
                <p>Vas a convertir a <strong>${nombre}</strong> en un Lead.</p>
                <p class="text-muted small">Los datos personales se autocompletarán y solo necesitarás agregar la información comercial del lead.</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ti-check"></i> Sí, convertir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `${BASE_URL}/leads/crear?persona_id=${id}`;
            }
        });
    });
});
