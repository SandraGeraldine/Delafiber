(() => {
    const zonaDetalleBaseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

    function initZonaDetalle() {
        const tablaProspectos = document.getElementById('tablaProspectos');
        if (tablaProspectos) {
            $(document).ready(function() {
                $('#tablaProspectos').DataTable({
                    language: {
                        url: base_url + '/js/datatables/es-ES.json'
                    },
                    order: [[5, 'desc']]
                });
            });
        }

        const chartMetricas = document.getElementById('chartMetricas');
        if (chartMetricas) {
            const ctx = chartMetricas.getContext('2d');
            const metricasData = chartMetricas.dataset.metricas;
            
            if (metricasData) {
                try {
                    const metricas = JSON.parse(metricasData);
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: metricas.map(m => m.fecha),
                            datasets: [
                                {
                                    label: 'Contactados',
                                    data: metricas.map(m => m.contactados),
                                    borderColor: '#3498db',
                                    backgroundColor: 'rgba(52, 152, 219, 0.1)'
                                },
                                {
                                    label: 'Interesados',
                                    data: metricas.map(m => m.interesados),
                                    borderColor: '#f39c12',
                                    backgroundColor: 'rgba(243, 156, 18, 0.1)'
                                },
                                {
                                    label: 'Convertidos',
                                    data: metricas.map(m => m.convertidos),
                                    borderColor: '#27ae60',
                                    backgroundColor: 'rgba(39, 174, 96, 0.1)'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true
                        }
                    });
                } catch (error) {
                    console.error('Error al cargar métricas:', error);
                }
            }
        }

        window.verHistorial = function(idProspecto) {
            window.location.href = `${zonaDetalleBaseUrl}/personas/view/${idProspecto}`;
        };

        window.nuevaInteraccion = function(idProspecto) {
            $('#modalRegistrarInteraccion').modal('show');
            $('#id_prospecto_interaccion').val(idProspecto);
        };

        window.desasignarAgente = function(idAsignacion) {
            if (confirm('¿Estás seguro de desasignar este agente de la zona?')) {
                fetch(`${zonaDetalleBaseUrl}/crm-campanas/desasignar-zona-agente/${idAsignacion}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Agente desasignado');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                });
            }
        };

        const formEditarZona = document.getElementById('formEditarZona');
        const btnGuardarCambiosZona = document.getElementById('btnGuardarCambiosZona');

        if (formEditarZona && btnGuardarCambiosZona) {
            btnGuardarCambiosZona.addEventListener('click', async () => {
                const idZona = formEditarZona.dataset.id;
                if (!idZona) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Zona inválida',
                        text: 'No se pudo determinar la zona a editar.'
                    });
                    return;
                }

                const nombreZona = document.getElementById('editar-nombre-zona')?.value?.trim() ?? '';
                const descripcion = document.getElementById('editar-descripcion')?.value ?? '';
                const prioridad = document.getElementById('editar-prioridad')?.value ?? 'Media';
                const color = document.getElementById('editar-color')?.value ?? '#3498db';
                const fechaInicio = document.getElementById('editar-fecha-inicio')?.value ?? '';
                const fechaFin = document.getElementById('editar-fecha-fin')?.value ?? '';

                if (fechaInicio && fechaFin && fechaFin < fechaInicio) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Fechas inválidas',
                        text: 'La fecha de fin debe ser igual o posterior a la fecha de inicio.'
                    });
                    return;
                }

                try {
                    const response = await fetch(`${zonaDetalleBaseUrl}/crm-campanas/actualizar-zona/${idZona}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            nombre_zona: nombreZona,
                            descripcion,
                            prioridad,
                            color,
                            fecha_inicio: fechaInicio || null,
                            fecha_fin: fechaFin || null
                        })
                    });

                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.message || 'No se pudo actualizar la zona');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Zona actualizada',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#modalEditarZona').modal('hide');
                    setTimeout(() => location.reload(), 900);
                } catch (error) {
                    console.error('Error al actualizar zona:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo guardar la zona'
                    });
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initZonaDetalle);
    } else {
        initZonaDetalle();
    }
})();
