document.addEventListener('DOMContentLoaded', function() {
    configurarAccionesRapidas();
    configurarClickEnCards();
});

// Configurar acciones rápidas (llamar y WhatsApp)
function configurarAccionesRapidas() {
    document.querySelectorAll('.quick-action').forEach(function(boton) {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const accion = this.getAttribute('data-action');
            const leadId = this.getAttribute('data-lead');
            if (accion === 'llamar') {
                const card = this.closest('.lead-card');
                const telefono = card.querySelector('.lead-phone').textContent.replace(/\D/g, '');
                window.location.href = 'tel:' + telefono;
                registrarAccion('llamada', leadId);
            } else if (accion === 'whatsapp') {
                const card = this.closest('.lead-card');
                const telefono = card.querySelector('.lead-phone').textContent.replace(/\D/g, '');
                const nombre = card.querySelector('.lead-name').textContent.trim();
                const mensaje = `Hola ${nombre}, te contacto de Delafiber para contarte sobre nuestros servicios de internet por fibra óptica. ¿Te gustaría conocer nuestros planes?`;
                const whatsappUrl = `https://wa.me/51${telefono}?text=${encodeURIComponent(mensaje)}`;
                window.open(whatsappUrl, '_blank');
                registrarAccion('whatsapp', leadId);
            }
        });
    });
}

// Click en las cards para ver detalles
function configurarClickEnCards() {
    document.querySelectorAll('.lead-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.btn, .dropdown-toggle')) {
                return;
            }
            const leadId = this.getAttribute('data-lead-id');
            window.location.href = pipelineBaseUrl + '/leads/view/' + leadId;
        });
    });
}

// Avanzar etapa
function avanzarEtapa(leadId) {
    if (!confirm('¿Estás seguro de avanzar este lead a la siguiente etapa?')) {
        return;
    }
    const boton = event.target.closest('button');
    const originalContent = boton.innerHTML;
    boton.innerHTML = '<i class="ti-reload"></i>';
    boton.disabled = true;
    fetch(pipelineBaseUrl + '/leads/avanzarEtapa', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `lead_id=${leadId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error al avanzar etapa');
            boton.innerHTML = originalContent;
            boton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
        boton.innerHTML = originalContent;
        boton.disabled = false;
    });
}

// Registrar acción (opcional)
function registrarAccion(tipo, leadId) {
    fetch(pipelineBaseUrl + '/leads/registrarAccion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=${tipo}&lead_id=${leadId}`
    })
    .catch(error => console.log('Error registrando acción:', error));
}

// Actualizar contadores cada 30 segundos
setInterval(function() {
    location.reload();
}, 30000);

// Define la base URL para el pipeline (ajusta según tu entorno)
const pipelineBaseUrl = window.location.origin;
