<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-4">
        <!-- Tarjeta de Perfil -->
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="avatar-xl bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:120px;height:120px;font-size:48px;">
                        <?= strtoupper(substr($usuario['nombrePersona'] ?? 'U', 0, 1)) ?>
                    </div>
                </div>
                <h4><?= esc($usuario['nombrePersona'] ?? 'Usuario') ?></h4>
                <p class="text-muted"><?= esc($usuario['correo'] ?? 'Sin correo') ?></p>
                <span class="badge badge-<?= ($usuario['estadoActivo'] ?? 1) ? 'success' : 'secondary' ?>">
                    <?= ($usuario['estadoActivo'] ?? 1) ? 'Activo' : 'Inactivo' ?>
                </span>

                <hr>

                <div class="text-left">
                    <p class="mb-2">
                        <strong>Usuario:</strong> <?= esc($usuario['nombreUsuario'] ?? $usuario['usuario'] ?? 'N/A') ?>
                    </p>
                    <p class="mb-2">
                        <strong>Rol:</strong> 
                        <span class="badge badge-info"><?= esc($usuario['nombreRol'] ?? 'Sin rol') ?></span>
                    </p>
                    <p class="mb-2">
                        <strong>Teléfono:</strong> <?= esc($usuario['telefono'] ?? 'Sin teléfono') ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Estadísticas Personales -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Mis Estadísticas</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Leads Asignados</span>
                        <strong class="text-primary"><?= $estadisticas['leads_asignados'] ?? 0 ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Conversiones</span>
                        <strong class="text-success"><?= $estadisticas['conversiones'] ?? 0 ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Tasa de Conversión</span>
                        <strong class="text-info"><?= $estadisticas['tasa_conversion'] ?? 0 ?>%</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Tareas Pendientes</span>
                        <strong class="text-warning"><?= $estadisticas['tareas_pendientes'] ?? 0 ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Mensajes Flash -->
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#datosPersonales">
                    <i class="icon-user"></i> Datos Personales
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#cambiarPassword">
                    <i class="icon-lock"></i> Cambiar Contraseña
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#actividad">
                    <i class="icon-activity"></i> Actividad Reciente
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab Datos Personales -->
            <div class="tab-pane fade show active p-4" id="datosPersonales">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Actualizar Información Personal</h5>
                        
                        <form action="<?= base_url('perfil/actualizar') ?>" method="POST">
                            <?= csrf_field() ?>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Nombre Completo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nombre" 
                                               value="<?= esc($usuario['nombre']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?= esc($usuario['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Teléfono</label>
                                        <input type="tel" class="form-control" name="telefono" 
                                               value="<?= esc($usuario['telefono'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Rol</label>
                                <input type="text" class="form-control" 
                                       value="<?= esc($usuario['nombreRol'] ?? 'Usuario') ?>" disabled>
                                <small class="text-muted">El rol es asignado por el administrador</small>
                            </div>

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="icon-check"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab Cambiar Contraseña -->
            <div class="tab-pane fade p-4" id="cambiarPassword">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Cambiar Contraseña</h5>
                        
                        <form action="<?= base_url('perfil/cambiar-password') ?>" method="POST">
                            <?= csrf_field() ?>
                            
                            <div class="form-group">
                                <label>Contraseña Actual <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password_actual" required>
                            </div>

                            <div class="form-group">
                                <label>Nueva Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password_nueva" 
                                       id="password_nueva" required minlength="6">
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label>Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password_confirmar" 
                                       id="password_confirmar" required>
                            </div>

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="icon-lock"></i> Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab Actividad Reciente -->
            <div class="tab-pane fade p-4" id="actividad">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Actividad Reciente</h5>
                        
                        <?php if (!empty($actividad_reciente)): ?>
                        <div class="timeline">
                            <?php foreach ($actividad_reciente as $actividad): ?>
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="timeline-badge bg-<?= $actividad['tipo_badge'] ?> text-white rounded-circle d-flex align-items-center justify-content-center mr-3" 
                                         style="width:40px;height:40px;flex-shrink:0;">
                                        <i class="<?= $actividad['icono'] ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?= esc($actividad['descripcion']) ?></strong>
                                        <p class="text-muted mb-0">
                                            <small>
                                                <i class="icon-clock"></i> 
                                                <?= date('d/m/Y H:i', strtotime($actividad['fecha'])) ?>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="icon-info text-muted" style="font-size: 48px;"></i>
                            <p class="text-muted mt-2">No hay actividad reciente</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan
document.querySelector('form[action*="cambiar-password"]')?.addEventListener('submit', function(e) {
    const nueva = document.getElementById('password_nueva').value;
    const confirmar = document.getElementById('password_confirmar').value;
    
    if (nueva !== confirmar) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
    }
});
</script>

<?= $this->endsection() ?>