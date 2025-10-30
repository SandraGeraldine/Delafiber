<?= $this->extend('Layouts/base') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/whatsapp/whatsapp.css?v=' . time()) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h3 class="page-title">
        <i class="fab fa-whatsapp text-success"></i> WhatsApp Business
    </h3>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">WhatsApp</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <!-- Lista de Conversaciones -->
                    <div class="col-md-4 border-end">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0">
                                <i class="ti-comments"></i> Conversaciones
                            </h4>
                            <div>
                                <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalEnviarMensaje">
                                    <i class="fas fa-paper-plane"></i> Nuevo Mensaje
                                </button>
                                <span class="badge badge-success"><?= count($conversaciones) ?></span>
                            </div>
                        </div>

                        <!-- Búsqueda -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti-search"></i></span>
                                <input type="text" class="form-control" id="buscar-conversacion" placeholder="Buscar conversación...">
                            </div>
                        </div>

                        <!-- Lista -->
                        <div class="conversaciones-lista" style="max-height: 600px; overflow-y: auto;">
                            <?php if (empty($conversaciones)): ?>
                                <div class="text-center py-5">
                                    <i class="fab fa-whatsapp" style="font-size: 4rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3">No hay conversaciones aún</p>
                                    <small class="text-muted">Las conversaciones aparecerán aquí cuando los clientes te escriban</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversaciones as $conv): ?>
                                    <div class="conversacion-item <?= $conv['no_leidos'] > 0 ? 'no-leido' : '' ?>" 
                                         data-id="<?= $conv['id_conversacion'] ?>"
                                         onclick="window.location.href='<?= base_url('whatsapp/conversacion/' . $conv['id_conversacion']) ?>'">
                                        <div class="d-flex align-items-start">
                                            <div class="avatar-circle me-3">
                                                <i class="ti-user"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-0"><?= esc($conv['nombre_contacto'] ?? $conv['numero_whatsapp']) ?></h6>
                                                    <small class="text-muted">
                                                        <?php
                                                        $fecha = new DateTime($conv['fecha_ultimo_mensaje']);
                                                        $ahora = new DateTime();
                                                        $diff = $ahora->diff($fecha);
                                                        
                                                        if ($diff->days == 0) {
                                                            echo $fecha->format('H:i');
                                                        } elseif ($diff->days == 1) {
                                                            echo 'Ayer';
                                                        } else {
                                                            echo $fecha->format('d/m/Y');
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                                <p class="mb-0 text-muted small text-truncate">
                                                    <?= esc(substr($conv['ultimo_mensaje'], 0, 50)) ?>...
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center mt-1">
                                                    <small class="text-muted">
                                                        <i class="ti-mobile"></i> <?= esc($conv['numero_whatsapp']) ?>
                                                    </small>
                                                    <?php if ($conv['no_leidos'] > 0): ?>
                                                        <span class="badge badge-success badge-pill"><?= $conv['no_leidos'] ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Área de Mensaje -->
                    <div class="col-md-8">
                        <div class="text-center py-5" style="margin-top: 150px;">
                            <i class="fab fa-whatsapp" style="font-size: 6rem; color: #25D366;"></i>
                            <h4 class="mt-4">WhatsApp Business</h4>
                            <p class="text-muted">Selecciona una conversación para comenzar a chatear</p>
                            
                            <div class="mt-4">
                                <a href="<?= base_url('whatsapp/test') ?>" class="btn btn-outline-success">
                                    <i class="ti-settings"></i> Página de Pruebas
                                </a>
                                <a href="<?= base_url('whatsapp/plantillas') ?>" class="btn btn-outline-primary">
                                    <i class="ti-layout"></i> Plantillas
                                </a>
                            </div>

                            <div class="alert alert-info mt-4 mx-5">
                                <i class="ti-info-alt"></i>
                                <strong>Número configurado:</strong> +51 994 276 946<br>
                                <small>Los mensajes que te envíen aparecerán aquí automáticamente</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para enviar mensaje inicial -->
<div class="modal fade" id="modalEnviarMensaje" tabindex="-1" aria-labelledby="modalEnviarMensajeLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEnviarMensajeLabel">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Mensaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formMensajeInicial">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="numeroDestino" class="form-label">Número de WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text">+51</span>
                            <input type="text" class="form-control" id="numeroDestino" name="numero" placeholder="987654321" required>
                        </div>
                        <small class="form-text text-muted">Ingresa el número sin el código de país (+51) ni espacios</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombreContacto" class="form-label">Nombre del contacto (opcional)</label>
                        <input type="text" class="form-control" id="nombreContacto" name="nombre" placeholder="Nombre del destinatario">
                    </div>
                    
                    <?php if (!empty($cuentas)): ?>
                    <div class="mb-3">
                        <label for="cuentaWhatsApp" class="form-label">Cuenta de WhatsApp</label>
                        <select class="form-select" id="cuentaWhatsApp" name="id_cuenta">
                            <?php foreach ($cuentas as $cuenta): ?>
                                <option value="<?= $cuenta['id_cuenta'] ?>">
                                    <?= esc($cuenta['nombre']) ?> (<?= $cuenta['numero_whatsapp'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="mensaje" class="form-label">Mensaje</label>
                        <textarea class="form-control" id="mensaje" name="mensaje" rows="4" required placeholder="Escribe tu mensaje aquí..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Enviar Mensaje
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Enviar mensaje inicial
const formMensajeInicial = document.getElementById('formMensajeInicial');
if (formMensajeInicial) {
    formMensajeInicial.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const btnSubmit = this.querySelector('button[type="submit"]');
        const btnOriginalText = btnSubmit.innerHTML;
        
        // Deshabilitar botón y mostrar carga
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
        
        try {
            const response = await fetch('<?= base_url('whatsapp/enviar-mensaje-inicial') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Mensaje enviado!',
                    text: 'El mensaje se ha enviado correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Cerrar modal y limpiar formulario
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEnviarMensaje'));
                modal.hide();
                this.reset();
                
                // Recargar la página después de 1.5 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Error al enviar el mensaje');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Ocurrió un error al enviar el mensaje. Por favor, inténtalo de nuevo.'
            });
        } finally {
            // Restaurar botón
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = btnOriginalText;
        }
    });
}


// Búsqueda de conversaciones
document.getElementById('buscar-conversacion')?.addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.conversacion-item');
    
    items.forEach(item => {
        const texto = item.textContent.toLowerCase();
        item.style.display = texto.includes(busqueda) ? 'block' : 'none';
    });
});

// Polling para nuevas conversaciones cada 10 segundos
setInterval(function() {
    fetch('<?= base_url('whatsapp/obtenerNoLeidos') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.no_leidos > 0) {
                const badge = document.getElementById('whatsapp-badge');
                if (badge) {
                    badge.textContent = data.no_leidos;
                    badge.style.display = 'inline-block';
                }
            }
        });
}, 10000);
</script>
<?= $this->endSection() ?>
