<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="<?= base_url('css/leads/pipeline.css') ?>">

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="ti-layout-grid2"></i> Pipeline de Ventas</h3>
            <div>
                <a href="<?= base_url('leads') ?>" class="btn btn-outline-secondary">
                    <i class="ti-list"></i> Vista Lista
                </a>
                <a href="<?= base_url('leads/create') ?>" class="btn btn-primary">
                    <i class="ti-plus"></i> Nuevo Lead
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Pipeline Kanban -->
<div class="pipeline-container">
    <div class="pipeline-scroll">
        <?php if (!empty($pipeline)): ?>
            <?php foreach ($pipeline as $etapa): ?>
                <div class="pipeline-column" data-etapa-id="<?= $etapa['etapa_id'] ?>">
                    <div class="pipeline-header">
                        <h5 class="mb-0"><?= esc($etapa['etapa_nombre']) ?></h5>
                        <span class="badge badge-light"><?= $etapa['total_leads'] ?></span>
                    </div>
                    
                    <div class="pipeline-body" id="etapa-<?= $etapa['etapa_id'] ?>">
                        <?php if (!empty($etapa['leads'])): ?>
                            <?php foreach ($etapa['leads'] as $lead): ?>
                                <div class="lead-card" data-lead-id="<?= $lead['idlead'] ?>" draggable="true">
                                    <div class="lead-card-header">
                                        <strong><?= esc($lead['nombres']) ?> <?= esc($lead['apellidos']) ?></strong>
                                    </div>
                                    <div class="lead-card-body">
                                        <!-- Teléfono -->
                                        <div class="lead-info">
                                            <i class="ti-mobile text-success"></i>
                                            <span><?= esc($lead['telefono']) ?></span>
                                        </div>
                                    </div>
                                    <div class="lead-card-actions">
                                        <a href="<?= base_url('leads/view/' . $lead['idlead']) ?>" 
                                           class="btn btn-sm btn-light" title="Ver detalles">
                                            <i class="ti-eye"></i>
                                        </a>
                                        <a href="https://wa.me/51<?= esc($lead['telefono']) ?>?text=Hola%20<?= urlencode($lead['nombres']) ?>,%20te%20contacto%20desde%20Delafiber" 
                                           target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
                                            <i class="ti-comment"></i>
                                        </a>
                                        <a href="tel:<?= esc($lead['telefono']) ?>" 
                                           class="btn btn-sm btn-info" title="Llamar">
                                            <i class="ti-headphone-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="ti-info-alt"></i>
                                <p>Sin leads</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="ti-info-alt" style="font-size: 48px; opacity: 0.3;"></i>
                <p class="text-muted mt-3">No hay datos de pipeline disponibles</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Variable de configuración para los archivos JS externos
const BASE_URL = '<?= base_url() ?>';
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= base_url('js/leads/pipeline.js') ?>"></script>
<?= $this->endSection() ?>
