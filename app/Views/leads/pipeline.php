<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="<?= base_url('css/leads/pipeline.css') ?>">

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header header-corporativo">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="ti-layout-grid2"></i> Pipeline de Ventas</h3>
                    <div>
                        <a href="<?= base_url('leads') ?>" class="btn btn-outline-light">
                            <i class="ti-list"></i> Vista Lista
                        </a>
                        <a href="<?= base_url('leads/create') ?>" class="btn btn-light">
                            <i class="ti-plus"></i> Nuevo Lead
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">

                <?php
                // Calcular métricas simples del pipeline a partir de los datos ya cargados
                $totalLeadsPipeline = 0;
                $etapasConLeads = 0;

                if (!empty($pipeline)) {
                    foreach ($pipeline as $etapa) {
                        $totalLeadsPipeline += (int)($etapa['total_leads'] ?? 0);
                        if (!empty($etapa['total_leads'])) {
                            $etapasConLeads++;
                        }
                    }
                }
                ?>

                <?php if (!empty($pipeline)): ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-accent me-2">
                                <i class="ti-layers"></i>
                            </span>
                            <div>
                                <small class="text-muted d-block">Leads en el pipeline</small>
                                <strong><?= $totalLeadsPipeline ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-primary me-2">
                                <i class="ti-layout"></i>
                            </span>
                            <div>
                                <small class="text-muted d-block">Etapas con actividad</small>
                                <strong><?= $etapasConLeads ?> / <?= count($pipeline) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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
                                            <div class="lead-card" 
                                                 data-lead-id="<?= $lead['idlead'] ?>" 
                                                 data-direccion="<?= esc($lead['direccion_servicio'] ?? '') ?>"
                                                 data-coordenadas="<?= esc($lead['coordenadas_servicio'] ?? '') ?>"
                                                 data-origen="<?= esc($lead['origen'] ?? '') ?>"
                                                 data-nombre="<?= esc(trim(($lead['nombres'] ?? '') . ' ' . ($lead['apellidos'] ?? ''))) ?>"
                                                 data-telefono="<?= esc($lead['telefono'] ?? '') ?>"
                                                 draggable="true">
                                                <div class="lead-card-header">
                                                    <strong><?= esc($lead['nombres']) ?> <?= esc($lead['apellidos']) ?></strong>
                                                </div>
                                                <div class="lead-card-body">
                                                    <!-- Teléfono -->
                                                    <div class="lead-info">
                                                        <i class="ti-mobile text-success"></i>
                                                        <span><?= esc($lead['telefono']) ?></span>
                                                    </div>

                                                    <!-- Origen y fecha -->
                                                    <div class="lead-info mt-1">
                                                        <i class="ti-flag-alt text-accent"></i>
                                                        <span class="small">
                                                            <?= esc($lead['origen'] ?? 'Origen no definido') ?>
                                                            <?php if (!empty($lead['created_at'])): ?>
                                                                · <span class="text-muted">
                                                                    <?= date('d/m/Y', strtotime($lead['created_at'])) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </span>
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
        </div>
    </div>
</div>

<!-- Modal pequeño para detalles rápidos del lead (incluye datos de Campo) -->
<div class="modal fade" id="leadQuickViewModal" tabindex="-1" aria-labelledby="leadQuickViewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="leadQuickViewLabel">Detalle rápido del lead</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1"><strong id="modalLeadNombre"></strong></p>
                <p class="mb-1 small text-muted" id="modalLeadOrigen"></p>
                <p class="mb-1"><i class="ti-mobile text-success"></i> <span id="modalLeadTelefono"></span></p>
                <p class="mb-1"><i class="ti-location-pin text-primary"></i> <span id="modalLeadDireccion"></span></p>
                <p class="mb-1 small" id="modalLeadCoordenadas"></p>
                <div id="modalLeadFoto" class="mt-2">
                    <img src="" alt="Foto del lead" class="img-fluid">
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between">
                <a href="#" target="_blank" id="modalBtnMapa" class="btn btn-sm btn-outline-primary">
                    <i class="ti-map-alt"></i> Ver mapa
                </a>
                <a href="#" id="modalBtnVerLead" class="btn btn-sm btn-primary">
                    Ver lead
                </a>
            </div>
        </div>
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
