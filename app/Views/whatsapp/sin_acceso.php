<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?? 'Acceso no autorizado' ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body text-center py-5">
            <div class="py-5">
                <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
                <h3>Acceso no autorizado</h3>
                <p class="lead"><?= $message ?? 'No tienes acceso a ninguna cuenta de WhatsApp. Por favor, contacta al administrador.' ?></p>
                
                <div class="mt-4">
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Volver al inicio
                    </a>
                    
                    <?php if (in_array('administrador', session('usuario.roles') ?? [])): ?>
                        <a href="<?= base_url('admin/whatsapp/cuentas') ?>" class="btn btn-success ml-2">
                            <i class="fas fa-cog"></i> Configurar cuentas
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
