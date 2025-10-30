<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Gestión de Campañas</h3>
                <p class="text-muted mb-0">Administra y monitorea tus campañas de marketing</p>
            </div>
            <a href="<?= base_url('campanias/create') ?>" class="btn btn-primary">
                <i class="icon-plus"></i> Nueva Campaña
            </a>
        </div>

        <!-- Alertas -->
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="icon-check-circle me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="icon-alert-circle me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="icon-alert-circle me-2"></i>
            <?= esc($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filtros rápidos -->
        <?php if (!empty($campanias)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Estado</label>
                        <select class="form-select form-select-sm" id="filtroEstado">
                            <option value="">Todos los estados</option>
                            <option value="Activa">Activa</option>
                            <option value="Inactiva">Inactiva</option>
                            <option value="Finalizada">Finalizada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Tipo</label>
                        <select class="form-select form-select-sm" id="filtroTipo">
                            <option value="">Todos los tipos</option>
                            <option value="Marketing Digital">Marketing Digital</option>
                            <option value="Email Marketing">Email Marketing</option>
                            <option value="Publicidad">Publicidad</option>
                            <option value="Redes Sociales">Redes Sociales</option>
                            <option value="Eventos">Eventos</option>
                            <option value="Telemarketing">Telemarketing</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Buscar</label>
                        <input type="text" class="form-control form-control-sm" id="busquedaRapida" 
                               placeholder="Buscar por nombre o descripción...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="limpiarFiltros">
                            <i class="icon-x"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Campañas</h6>
                                <h3 class="mb-0"><?= count($campanias) ?></h3>
                            </div>
                            <i class="icon-layers" style="font-size: 2rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Activas</h6>
                                <h3 class="mb-0">
                                    <?= count(array_filter($campanias, fn($c) => ($c['estado'] ?? '') == 'Activa')) ?>
                                </h3>
                            </div>
                            <i class="icon-activity" style="font-size: 2rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Leads</h6>
                                <h3 class="mb-0">
                                    <?= array_sum(array_column($campanias, 'total_leads')) ?>
                                </h3>
                            </div>
                            <i class="icon-users" style="font-size: 2rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Inversión Total</h6>
                                <h3 class="mb-0">
                                    S/ <?= number_format(array_sum(array_column($campanias, 'presupuesto')), 0) ?>
                                </h3>
                            </div>
                            <i class="icon-dollar-sign" style="font-size: 2rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabla de campañas -->
        <div class="card">
            <div class="card-body">
                <?php if (!empty($campanias)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tablaCampanias">
                        <thead class="table-light">
                            <tr>
                                <th>Campaña</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Periodo</th>
                                <th class="text-end">Presupuesto</th>
                                <th class="text-center">Leads</th>
                                <th class="text-end">CPL</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campanias as $campania): ?>
                            <tr data-tipo="<?= esc($campania['tipo'] ?? '') ?>" 
                                data-estado="<?= esc($campania['estado'] ?? 'Inactiva') ?>">
                                <td>
                                    <div>
                                        <strong class="d-block"><?= esc($campania['nombre']) ?></strong>
                                        <small class="text-muted">
                                            <?= esc(substr($campania['descripcion'] ?? 'Sin descripción', 0, 60)) ?>
                                            <?= strlen($campania['descripcion'] ?? '') > 60 ? '...' : '' ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= esc($campania['tipo'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $estado = $campania['estado'] ?? 'Inactiva';
                                    $badgeClass = match($estado) {
                                        'Activa' => 'success',
                                        'Finalizada' => 'secondary',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>">
                                        <?= esc($estado) ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <small class="d-block">
                                            <i class="icon-calendar me-1"></i>
                                            <?= date('d/m/Y', strtotime($campania['fecha_inicio'])) ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= $campania['fecha_fin'] ? 'hasta ' . date('d/m/Y', strtotime($campania['fecha_fin'])) : 'Sin fecha fin' ?>
                                        </small>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <strong>S/ <?= number_format($campania['presupuesto'] ?? 0, 2) ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-white">
                                        <?= $campania['total_leads'] ?? 0 ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    $presupuesto = $campania['presupuesto'] ?? 0;
                                    $leads = $campania['total_leads'] ?? 0;
                                    $cpl = $leads > 0 ? $presupuesto / $leads : 0;
                                    ?>
                                    <small class="text-<?= $cpl > 0 ? 'primary' : 'muted' ?>">
                                        <?= $cpl > 0 ? 'S/ ' . number_format($cpl, 2) : 'N/A' ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= base_url('campanias/view/' . $campania['idcampania']) ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="Ver detalles"
                                           data-bs-toggle="tooltip">
                                            <i class="icon-eye"></i>
                                        </a>
                                        <a href="<?= base_url('campanias/edit/' . $campania['idcampania']) ?>" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="Editar"
                                           data-bs-toggle="tooltip">
                                            <i class="icon-edit"></i>
                                        </a>
                                        <button onclick="confirmarEliminacion(<?= $campania['idcampania'] ?>, '<?= esc($campania['nombre']) ?>')" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Eliminar"
                                                data-bs-toggle="tooltip">
                                            <i class="icon-trash-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="icon-layers" style="font-size: 4rem; color: #dee2e6;"></i>
                    </div>
                    <h5 class="text-muted">No hay campañas registradas</h5>
                    <p class="text-muted mb-4">Comienza creando tu primera campaña de marketing</p>
                    <a href="<?= base_url('campanias/create') ?>" class="btn btn-primary btn-lg">
                        <i class="icon-plus"></i> Crear primera campaña
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="icon-alert-triangle me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">¿Estás seguro de que deseas eliminar la campaña:</p>
                <p class="fw-bold text-center fs-5" id="nombreCampaniaEliminar"></p>
                <div class="alert alert-warning">
                    <i class="icon-alert-circle me-2"></i>
                    <strong>Advertencia:</strong> Esta acción no se puede deshacer. Se eliminarán todos los datos asociados a esta campaña.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="icon-x"></i> Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
                    <i class="icon-trash-2"></i> Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Incluye CSS y JS externos -->
<link rel="stylesheet" href="<?= base_url('css/campanias.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/campaniasJS/campanias.js') ?>"></script>
<?= $this->endSection() ?>