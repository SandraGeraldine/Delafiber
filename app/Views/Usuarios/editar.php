<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Editar Usuario</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard/index') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('usuarios') ?>">Usuarios</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <a href="<?= base_url('usuarios') ?>" class="btn btn-secondary">
            Volver a la lista
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Datos del Usuario</h5>
        </div>
        <div class="card-body">
            <?php if (isset($usuario) && $usuario): ?>
                <form id="formEditarUsuario" method="post" action="<?= base_url('usuarios/editar/' . $usuario['idusuario']) ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre completo</label>
                            <input type="text" class="form-control" value="<?= esc($usuario['nombre'] ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" value="<?= esc($usuario['email'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Usuario/Username *</label>
                            <input type="text" name="usuario" id="usuario" class="form-control" required value="<?= esc($usuario['usuario'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rol *</label>
                            <select class="form-select" name="idrol" id="idrol" required>
                                <option value="">Seleccionar rol</option>
                                <?php if (!empty($roles)): ?>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?= $rol['idrol'] ?>" <?= ($usuario['idrol'] ?? null) == $rol['idrol'] ? 'selected' : '' ?>>
                                            <?= esc($rol['nombre']) ?> - <?= esc($rol['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nueva contraseña (opcional)</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Dejar en blanco para no cambiar">
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="activo" id="activoSwitch" <?= ($usuario['activo'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="activoSwitch">Usuario activo</label>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-danger mb-0">
                    No se encontró la información del usuario.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formEditarUsuario');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Éxito', data.message || 'Usuario actualizado correctamente', 'success')
                        .then(() => window.location.href = '<?= base_url('usuarios') ?>');
                } else {
                    Swal.fire('Error', data.message || 'No se pudo actualizar el usuario', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión al actualizar el usuario', 'error');
            });
        });
    });
</script>
<?= $this->endSection() ?>
