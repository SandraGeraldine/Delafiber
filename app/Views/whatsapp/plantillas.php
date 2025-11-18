<?= $this->extend('Layouts/base') ?>


<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Plantillas de WhatsApp</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12 text-right">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaPlantilla">
                                <i class="fas fa-plus"></i> Nueva Plantilla
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Contenido</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($plantillas)): ?>
                                    <?php foreach ($plantillas as $plantilla): ?>
                                        <tr>
                                            <td><?= esc($plantilla['nombre']) ?></td>
                                            <td><?= esc($plantilla['contenido']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-editar" 
                                                        data-id="<?= $plantilla['id_plantilla'] ?>"
                                                        data-nombre="<?= esc($plantilla['nombre']) ?>"
                                                        data-contenido="<?= esc($plantilla['contenido']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-eliminar" 
                                                        data-id="<?= $plantilla['id_plantilla'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No hay plantillas guardadas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Plantilla -->
<div class="modal fade" id="modalNuevaPlantilla" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPlantilla" action="<?= base_url('whatsapp/guardarPlantilla') ?>" method="post">
                <input type="hidden" name="id_plantilla" id="id_plantilla" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre de la plantilla</label>
                        <input type="text" class="form-control" name="nombre" id="nombre_plantilla" required>
                    </div>
                    <div class="form-group">
                        <label>Contenido</label>
                        <textarea class="form-control" name="contenido" id="contenido_plantilla" rows="5" required></textarea>
                        <small class="form-text text-muted">
                            Usa {{nombre}} para variables. Ej: "Hola {{nombre}}, ¿cómo estás?"
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Editar plantilla
    $('.btn-editar').on('click', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const contenido = $(this).data('contenido');
        
        $('#id_plantilla').val(id);
        $('#nombre_plantilla').val(nombre);
        $('#contenido_plantilla').val(contenido);
        $('.modal-title').text('Editar Plantilla');
        $('#modalNuevaPlantilla').modal('show');
    });

    // Nueva plantilla
    $('[data-bs-target="#modalNuevaPlantilla"]').on('click', function() {
        $('#formPlantilla')[0].reset();
        $('#id_plantilla').val('');
        $('.modal-title').text('Nueva Plantilla');
    });

    // Eliminar plantilla
    $('.btn-eliminar').on('click', function() {
        if (confirm('¿Estás seguro de eliminar esta plantilla?')) {
            const id = $(this).data('id');
            window.location.href = `<?= base_url('whatsapp/eliminarPlantilla/') ?>${id}`;
        }
    });
});
</script>
<?= $this->endSection() ?>