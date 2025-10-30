/**
 * JavaScript para el Dashboard
 * Archivo: public/js/dashboard/dashboard-main.js
 */

const base_url = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

function scrollToSection(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

function completarTarea(idTarea) {
    if (confirm('¿Marcar esta tarea como completada?')) {
        fetch(`${base_url}/dashboard/completarTarea`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ idtarea: idTarea })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al completar la tarea');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al completar la tarea');
        });
    }
}

// Helper para tiempo transcurrido
function time_elapsed_string(datetime) {
    const now = new Date();
    const fecha = new Date(datetime);
    const diff = Math.floor((now - fecha) / 1000);
    
    if (diff < 60) return 'hace unos momentos';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} minutos`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)} horas`;
    return `hace ${Math.floor(diff / 86400)} días`;
}
