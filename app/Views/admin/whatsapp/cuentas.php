<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?? 'Gestión de Cuentas WhatsApp' ?></h1>
        <a href="<?= base_url('admin/whatsapp/cuentas/nueva') ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Nueva Cuenta
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (session('success')): ?>
                <div class="alert alert-success">
                    <?= session('success') ?>
                </div>
            <?php endif; ?>

            <?php if (session('error')): ?>
                <div class="alert alert-danger">
                    <?= session('error') ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Número</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuentas as $cuenta): ?>
                        <tr>
                            <td><?= $cuenta['id_cuenta'] ?></td>
                            <td><?= esc($cuenta['nombre']) ?></td>
                            <td><?= esc($cuenta['numero_whatsapp']) ?></td>
                            <td>
                                <span class="badge badge-<?= $cuenta['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($cuenta['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url('admin/whatsapp/cuentas/editar/' . $cuenta['id_cuenta']) ?>" 
                                   class="btn btn-sm btn-primary" 
                                   title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-danger btn-eliminar" 
                                        data-id="<?= $cuenta['id_cuenta'] ?>"
                                        data-nombre="<?= esc($cuenta['nombre']) ?>"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="confirmarEliminar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar la cuenta <strong id="nombreCuenta"></strong>?
                <p class="text-danger mt-2">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="post" action="">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Configuración de DataTables
    $('#dataTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });

    // Manejar clic en botón de eliminar
    $('.btn-eliminar').on('click', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        
        $('#nombreCuenta').text(nombre);
        $('#formEliminar').attr('action', '<?= base_url('admin/whatsapp/cuentas/eliminar') ?>/' + id);
        $('#confirmarEliminar').modal('show');
    });
});
</script>
<?= $this->endSection() ?>
