<?= $this->extend('Layouts/base') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/whatsapp/whatsapp.css?v=' . time()) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h3 class="page-title">
        <a href="<?= base_url('whatsapp') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="ti-arrow-left"></i>
        </a>
        <i class="fab fa-whatsapp text-success"></i> Chat - <?= esc($conversacion['nombre_contacto'] ?? $conversacion['numero_whatsapp']) ?>
    </h3>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="chat-container">
                    <!-- Header del Chat -->
                    <div class="chat-header">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle">
                                <i class="ti-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0"><?= esc($conversacion['nombre_contacto'] ?? 'Cliente') ?></h6>
                                <small><?= esc($conversacion['numero_whatsapp']) ?></small>
                            </div>
                        </div>
                        <div>
                            <?php if ($conversacion['idlead']): ?>
                                <a href="<?= base_url('leads/view/' . $conversacion['idlead']) ?>" class="btn btn-sm btn-light" target="_blank">
                                    <i class="ti-eye"></i> Ver Lead
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Mensajes -->
                    <div class="chat-messages" id="chat-messages">
                        <?php if (empty($mensajes)): ?>
                            <div class="text-center py-5">
                                <i class="fab fa-whatsapp" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No hay mensajes en esta conversación</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($mensajes as $mensaje): ?>
                                <div class="mensaje <?= $mensaje['direccion'] ?>" data-id="<?= $mensaje['id_mensaje'] ?>">
                                    <div class="mensaje-bubble">
                                        <p class="mensaje-texto"><?= nl2br(esc($mensaje['contenido'])) ?></p>
                                        
                                        <?php if ($mensaje['media_url']): ?>
                                            <div class="mensaje-media">
                                                <?php if ($mensaje['tipo_mensaje'] == 'image'): ?>
                                                    <img src="<?= esc($mensaje['media_url']) ?>" alt="Imagen" onclick="window.open(this.src)">
                                                <?php else: ?>
                                                    <div class="documento">
                                                        <i class="ti-file"></i>
                                                        <a href="<?= esc($mensaje['media_url']) ?>" target="_blank">Ver archivo</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mensaje-hora">
                                            <?= date('H:i', strtotime($mensaje['created_at'])) ?>
                                            <?php if ($mensaje['direccion'] == 'saliente'): ?>
                                                <span class="mensaje-estado">
                                                    <?php if ($mensaje['estado_envio'] == 'entregado'): ?>
                                                        <i class="ti-check"></i><i class="ti-check"></i>
                                                    <?php elseif ($mensaje['estado_envio'] == 'leido'): ?>
                                                        <i class="ti-check" style="color: #4FC3F7;"></i><i class="ti-check" style="color: #4FC3F7;"></i>
                                                    <?php else: ?>
                                                        <i class="ti-check"></i>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Input de Mensaje -->
                    <div class="chat-input">
                        <button type="button" class="btn btn-light" id="btn-plantillas" title="Plantillas">
                            <i class="ti-layout"></i>
                        </button>
                        
                        <button type="button" class="btn btn-light" title="Adjuntar">
                            <i class="ti-clip"></i>
                        </button>
                        
                        <textarea 
                            id="mensaje-input" 
                            class="form-control" 
                            placeholder="Escribe un mensaje..." 
                            rows="1"
                            onkeypress="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); enviarMensaje(); }"
                        ></textarea>
                        
                        <button type="button" class="btn btn-whatsapp" onclick="enviarMensaje()">
                            <i class="ti-location-arrow"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel de Plantillas (oculto por defecto) -->
<div id="plantillas-panel" class="plantillas-panel" style="display: none;">
    <h6><i class="ti-layout"></i> Plantillas Rápidas</h6>
    <div class="row">
        <?php foreach ($plantillas as $plantilla): ?>
            <div class="col-md-6">
                <div class="plantilla-item" onclick="usarPlantilla('<?= esc($plantilla['contenido']) ?>')">
                    <h6><?= esc($plantilla['nombre']) ?></h6>
                    <p><?= esc(substr($plantilla['contenido'], 0, 100)) ?>...</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const idConversacion = <?= $conversacion['id_conversacion'] ?>;
const numeroDestino = '<?= $conversacion['numero_whatsapp'] ?>';
let ultimoIdMensaje = <?= !empty($mensajes) ? end($mensajes)['id_mensaje'] : 0 ?>;

// Scroll al final al cargar
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    
    // Polling cada 5 segundos para nuevos mensajes
    setInterval(obtenerNuevosMensajes, 5000);
});

// Enviar mensaje
function enviarMensaje() {
    const input = document.getElementById('mensaje-input');
    const mensaje = input.value.trim();
    
    if (!mensaje) return;
    
    // Deshabilitar input
    input.disabled = true;
    
    // Agregar mensaje temporalmente (optimistic UI)
    agregarMensajeTemp(mensaje);
    
    // Enviar al servidor
    fetch('<?= base_url('whatsapp/enviarMensaje') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            'id_conversacion': idConversacion,
            'numero_destino': numeroDestino,
            'mensaje': mensaje
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            input.style.height = 'auto';
            
            // Recargar mensajes
            setTimeout(obtenerNuevosMensajes, 1000);
        } else {
            alert('Error al enviar: ' + data.message);
            // Remover mensaje temporal
            document.querySelector('.mensaje-temp')?.remove();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
        document.querySelector('.mensaje-temp')?.remove();
    })
    .finally(() => {
        input.disabled = false;
        input.focus();
    });
}

// Agregar mensaje temporal
function agregarMensajeTemp(texto) {
    const chatMessages = document.getElementById('chat-messages');
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = 'mensaje saliente mensaje-temp';
    mensajeDiv.innerHTML = `
        <div class="mensaje-bubble">
            <p class="mensaje-texto">${texto.replace(/\n/g, '<br>')}</p>
            <div class="mensaje-hora">
                ${new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit'})}
                <span class="mensaje-estado"><i class="ti-time"></i></span>
            </div>
        </div>
    `;
    chatMessages.appendChild(mensajeDiv);
    scrollToBottom();
}

// Obtener nuevos mensajes
function obtenerNuevosMensajes() {
    fetch(`<?= base_url('whatsapp/obtenerNuevosMensajes') ?>/${idConversacion}/${ultimoIdMensaje}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > 0) {
                // Remover mensajes temporales
                document.querySelectorAll('.mensaje-temp').forEach(el => el.remove());
                
                // Agregar nuevos mensajes
                data.mensajes.forEach(mensaje => {
                    agregarMensaje(mensaje);
                    ultimoIdMensaje = Math.max(ultimoIdMensaje, mensaje.id_mensaje);
                });
                
                scrollToBottom();
            }
        })
        .catch(error => console.error('Error al obtener mensajes:', error));
}

// Agregar mensaje al chat
function agregarMensaje(mensaje) {
    const chatMessages = document.getElementById('chat-messages');
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `mensaje ${mensaje.direccion}`;
    mensajeDiv.setAttribute('data-id', mensaje.id_mensaje);
    
    const fecha = new Date(mensaje.created_at);
    const hora = fecha.toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit'});
    
    let estadoHtml = '';
    if (mensaje.direccion === 'saliente') {
        if (mensaje.estado_envio === 'entregado') {
            estadoHtml = '<i class="ti-check"></i><i class="ti-check"></i>';
        } else if (mensaje.estado_envio === 'leido') {
            estadoHtml = '<i class="ti-check" style="color: #4FC3F7;"></i><i class="ti-check" style="color: #4FC3F7;"></i>';
        } else {
            estadoHtml = '<i class="ti-check"></i>';
        }
    }
    
    mensajeDiv.innerHTML = `
        <div class="mensaje-bubble">
            <p class="mensaje-texto">${mensaje.contenido.replace(/\n/g, '<br>')}</p>
            <div class="mensaje-hora">
                ${hora}
                ${mensaje.direccion === 'saliente' ? `<span class="mensaje-estado">${estadoHtml}</span>` : ''}
            </div>
        </div>
    `;
    
    chatMessages.appendChild(mensajeDiv);
}

// Scroll al final
function scrollToBottom() {
    const chatMessages = document.getElementById('chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Auto-resize textarea
document.getElementById('mensaje-input').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// Toggle plantillas
document.getElementById('btn-plantillas').addEventListener('click', function() {
    const panel = document.getElementById('plantillas-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
});

// Usar plantilla
function usarPlantilla(contenido) {
    document.getElementById('mensaje-input').value = contenido;
    document.getElementById('plantillas-panel').style.display = 'none';
    document.getElementById('mensaje-input').focus();
}
</script>
<?= $this->endSection() ?>
