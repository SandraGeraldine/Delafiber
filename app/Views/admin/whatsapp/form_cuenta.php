<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?? 'Formulario de Cuenta WhatsApp' ?></h1>
        <a href="<?= base_url('admin/whatsapp/cuentas') ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Volver al listado
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (session('errors')): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach (session('errors') as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('admin/whatsapp/cuentas/guardar') ?>" method="post">
                <?= csrf_field() ?>
                
                <?php if (isset($cuenta['id_cuenta'])): ?>
                    <input type="hidden" name="id_cuenta" value="<?= $cuenta['id_cuenta'] ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre">Nombre de la cuenta *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= old('nombre', $cuenta['nombre'] ?? '') ?>" required>
                            <small class="form-text text-muted">Ej: Soporte Técnico, Ventas, etc.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="numero_whatsapp">Número de WhatsApp *</label>
                            <input type="text" class="form-control" id="numero_whatsapp" name="numero_whatsapp" 
                                   value="<?= old('numero_whatsapp', $cuenta['numero_whatsapp'] ?? '') ?>" required>
                            <small class="form-text text-muted">Formato: +1234567890</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="account_sid">Account SID (Twilio)</label>
                            <input type="text" class="form-control" id="account_sid" name="account_sid" 
                                   value="<?= old('account_sid', $cuenta['account_sid'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="auth_token">Auth Token (Twilio)</label>
                            <input type="password" class="form-control" id="auth_token" name="auth_token" 
                                   value="<?= old('auth_token', $cuenta['auth_token'] ?? '') ?>">
                            <small class="form-text text-muted">Solo se muestra una vez</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="whatsapp_number">Número de WhatsApp (Twilio)</label>
                            <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" 
                                   value="<?= old('whatsapp_number', $cuenta['whatsapp_number'] ?? '') ?>">
                            <small class="form-text text-muted">Formato: whatsapp:+1234567890</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="custom-control custom-switch mt-4 pt-2">
                                <input type="checkbox" class="custom-control-input" id="estado" name="estado" value="1" 
                                    <?= (!isset($cuenta['estado']) || $cuenta['estado'] === 'activo') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="estado">Cuenta activa</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Usuarios con acceso</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($usuarios as $usuario): ?>
                            <div class="col-md-4 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="usuario_<?= $usuario['idusuario'] ?>" 
                                           name="usuarios[]" 
                                           value="<?= $usuario['idusuario'] ?>"
                                           <?= in_array($usuario['idusuario'], $usuariosAsignados ?? []) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="usuario_<?= $usuario['idusuario'] ?>">
                                        <?= esc($usuario['nombre']) ?> <?= esc($usuario['apellido'] ?? '') ?>
                                        <small class="text-muted d-block"><?= esc($usuario['usuario'] ?? '') ?></small>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="<?= base_url('admin/whatsapp/cuentas') ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Formato de número de teléfono
    $('#numero_whatsapp').inputmask({
        mask: '+\9\96 999 999 999',
        placeholder: ' ',
        showMaskOnHover: false,
        showMaskOnFocus: true,
        autoUnmask: true
    });
});
</script>
<?= $this->endSection() ?>
