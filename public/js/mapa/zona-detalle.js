/**
 * JavaScript para Detalle de Zona
 */

const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

// Inicializar DataTable
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

// Gráfico de métricas
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

// Funciones auxiliares
function verHistorial(idProspecto) {
    window.location.href = `${baseUrl}/personas/view/${idProspecto}`;
}

function nuevaInteraccion(idProspecto) {
    // Abrir modal de nueva interacción
    $('#modalRegistrarInteraccion').modal('show');
    $('#id_prospecto_interaccion').val(idProspecto);
}

function desasignarAgente(idAsignacion) {
    if (confirm('¿Estás seguro de desasignar este agente de la zona?')) {
        fetch(`${baseUrl}/crm-campanas/desasignar-zona-agente/${idAsignacion}`, {
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
}
