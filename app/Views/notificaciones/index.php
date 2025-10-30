<?= $this->extend('layouts/base') ?>

<?php
// Cargar helper de tiempo
helper('time');
?>

<?= $this->section('styles') ?>
<style>
.notificacion-item {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s;
}

.notificacion-item:hover {
    background-color: #f8f9fa;
}

.notificacion-item.no-leida {
    background-color: #e3f2fd;
    border-left: 4px solid #2196F3;
}

.notificacion-icono {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
}

.notificacion-icono.lead_reasignado { background-color: #2196F3; }
.notificacion-icono.tarea_asignada { background-color: #4CAF50; }
.notificacion-icono.apoyo_urgente { background-color: #f44336; }
.notificacion-icono.solicitud_apoyo { background-color: #FF9800; }
.notificacion-icono.seguimiento_programado { background-color: #9C27B0; }
.notificacion-icono.transferencia_masiva { background-color: #00BCD4; }

.notificacion-tiempo {
    font-size: 12px;
    color: #6c757d;
}

.badge-no-leida {
    background-color: #2196F3;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><i class="ti-bell"></i> Notificaciones</h3>
                    <p class="text-muted mb-0">Gestiona tus notificaciones del sistema</p>
                </div>
                <div>
                    <button class="btn btn-outline-primary btn-sm" id="btnMarcarTodasLeidas">
                        <i class="ti-check"></i> Marcar todas como leídas
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary active" data-filter="todas">
                    Todas
                </button>
                <button type="button" class="btn btn-outline-secondary" data-filter="no-leidas">
                    No leídas
                </button>
                <button type="button" class="btn btn-outline-secondary" data-filter="leidas">
                    Leídas
                </button>
            </div>
        </div>
    </div>

    <!-- Lista de Notificaciones -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($notificaciones)): ?>
                        <div class="text-center py-5">
                            <i class="ti-bell" style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted mt-3">No tienes notificaciones</p>
                        </div>
                    <?php else: ?>
                        <div id="listaNotificaciones">
                            <?php foreach ($notificaciones as $notif): ?>
                                <div class="notificacion-item <?= $notif['leida'] ? '' : 'no-leida' ?>" 
                                     data-id="<?= $notif['idnotificacion'] ?>"
                                     data-leida="<?= $notif['leida'] ?>">
                                    <div class="d-flex align-items-start">
                                        <!-- Icono -->
                                        <div class="notificacion-icono <?= esc($notif['tipo']) ?> me-3">
                                            <?php
                                            $iconos = [
                                                'lead_reasignado' => 'ti-user',
                                                'tarea_asignada' => 'ti-check-box',
                                                'apoyo_urgente' => 'ti-alert',
                                                'solicitud_apoyo' => 'ti-help',
                                                'seguimiento_programado' => 'ti-time',
                                                'transferencia_masiva' => 'ti-exchange-vertical'
                                            ];
                                            $icono = $iconos[$notif['tipo']] ?? 'ti-bell';
                                            ?>
                                            <i class="<?= $icono ?>"></i>
                                        </div>

                                        <!-- Contenido -->
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <?= esc($notif['titulo']) ?>
                                                        <?php if (!$notif['leida']): ?>
                                                            <span class="badge-no-leida">Nueva</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted" style="font-size: 14px;">
                                                        <?= esc($notif['mensaje']) ?>
                                                    </p>
                                                    <small class="notificacion-tiempo">
                                                        <i class="ti-time"></i>
                                                        <?= timeAgo($notif['created_at']) ?>
                                                    </small>
                                                </div>

                                                <!-- Acciones -->
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-link text-muted" 
                                                            type="button" 
                                                            data-bs-toggle="dropdown">
                                                        <i class="ti-more-alt"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <?php if (!$notif['leida']): ?>
                                                            <li>
                                                                <a class="dropdown-item marcar-leida" 
                                                                   href="#" 
                                                                   data-id="<?= $notif['idnotificacion'] ?>">
                                                                    <i class="ti-check"></i> Marcar como leída
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if (!empty($notif['url_accion'])): ?>
                                                            <li>
                                                                <a class="dropdown-item" 
                                                                   href="<?= base_url($notif['url_accion']) ?>">
                                                                    <i class="ti-arrow-right"></i> Ver detalles
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger eliminar-notif" 
                                                               href="#" 
                                                               data-id="<?= $notif['idnotificacion'] ?>">
                                                                <i class="ti-trash"></i> Eliminar
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const baseUrl = '<?= base_url() ?>';

// Marcar todas como leídas
document.getElementById('btnMarcarTodasLeidas')?.addEventListener('click', async function() {
    if (!confirm('¿Marcar todas las notificaciones como leídas?')) return;
    
    try {
        const response = await fetch(`${baseUrl}/notificaciones/marcarTodasLeidas`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
});

// Marcar individual como leída
document.querySelectorAll('.marcar-leida').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        const id = this.dataset.id;
        
        try {
            const response = await fetch(`${baseUrl}/notificaciones/marcarLeida/${id}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            
            if (result.success) {
                const item = document.querySelector(`[data-id="${id}"]`);
                item.classList.remove('no-leida');
                item.querySelector('.badge-no-leida')?.remove();
                this.closest('li').remove();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});

// Eliminar notificación
document.querySelectorAll('.eliminar-notif').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        if (!confirm('¿Eliminar esta notificación?')) return;
        
        const id = this.dataset.id;
        
        try {
            const response = await fetch(`${baseUrl}/notificaciones/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.querySelector(`[data-id="${id}"]`).remove();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});

// Filtros
document.querySelectorAll('[data-filter]').forEach(btn => {
    btn.addEventListener('click', function() {
        // Actualizar botón activo
        document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        const items = document.querySelectorAll('.notificacion-item');
        
        items.forEach(item => {
            const leida = item.dataset.leida === '1';
            
            if (filter === 'todas') {
                item.style.display = '';
            } else if (filter === 'no-leidas') {
                item.style.display = leida ? 'none' : '';
            } else if (filter === 'leidas') {
                item.style.display = leida ? '' : 'none';
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
