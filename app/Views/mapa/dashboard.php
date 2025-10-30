<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1"> Dashboard CRM</h3>
                <p class="text-muted mb-0">Panel de control de campañas territoriales</p>
            </div>
            <div class="btn-group">
                <a href="<?= base_url('crm-campanas/mapa-campanas') ?>" class="btn btn-primary">
                    <i class="icon-map"></i> Ver Mapa
                </a>
                <a href="<?= base_url('campanias/create') ?>" class="btn btn-success">
                    <i class="icon-plus"></i> Nueva Campaña
                </a>
            </div>
        </div>

        <!-- Campañas Activas -->
        <?php if (!empty($campanias_activas)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3">Campañas Activas</h5>
            </div>
            <?php foreach ($campanias_activas as $campana): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="mb-0"><?= esc($campana['nombre']) ?></h6>
                            <span class="badge badge-success">Activa</span>
                        </div>
                        <p class="text-muted small mb-3">
                            <?= esc($campana['descripcion'] ?? 'Sin descripción') ?>
                        </p>
                        <div class="d-flex justify-content-between text-sm mb-2">
                            <span>Inicio:</span>
                            <strong><?= date('d/m/Y', strtotime($campana['fecha_inicio'])) ?></strong>
                        </div>
                        <?php if (!empty($campana['fecha_fin'])): ?>
                        <div class="d-flex justify-content-between text-sm mb-3">
                            <span>Fin:</span>
                            <strong><?= date('d/m/Y', strtotime($campana['fecha_fin'])) ?></strong>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('crm-campanas/mapa-campanas/' . $campana['idcampania']) ?>" 
                               class="btn btn-sm btn-primary flex-fill">
                                <i class="icon-map"></i> Mapa
                            </a>
                            <a href="<?= base_url('crm-campanas/zonas-index/' . $campana['idcampania']) ?>" 
                               class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="icon-list"></i> Zonas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="icon-info"></i> No hay campañas activas. 
            <a href="<?= base_url('campanias/create') ?>">Crear una nueva campaña</a>
        </div>
        <?php endif; ?>

        <!-- Mis Zonas Asignadas (para agentes) -->
        <?php if (isset($mis_asignaciones) && !empty($mis_asignaciones)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3">Mis Zonas Asignadas</h5>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Zona</th>
                                        <th>Campaña</th>
                                        <th>Prioridad</th>
                                        <th>Prospectos</th>
                                        <th>Interacciones</th>
                                        <th>Conversiones</th>
                                        <th>Meta</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mis_asignaciones as $asignacion): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($asignacion['nombre_zona']) ?></strong>
                                        </td>
                                        <td><?= esc($asignacion['campana_nombre']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $asignacion['prioridad'] === 'Alta' ? 'danger' : ($asignacion['prioridad'] === 'Media' ? 'warning' : 'info') ?>">
                                                <?= esc($asignacion['prioridad']) ?>
                                            </span>
                                        </td>
                                        <td><?= $asignacion['total_prospectos'] ?? 0 ?></td>
                                        <td><?= $asignacion['interacciones_realizadas'] ?? 0 ?></td>
                                        <td>
                                            <strong class="text-success">
                                                <?= $asignacion['conversiones_logradas'] ?? 0 ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php 
                                            $meta = $asignacion['meta_conversiones'] ?? 0;
                                            $logrado = $asignacion['conversiones_logradas'] ?? 0;
                                            $porcentaje = $meta > 0 ? ($logrado / $meta) * 100 : 0;
                                            ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar <?= $porcentaje >= 100 ? 'bg-success' : ($porcentaje >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                     style="width: <?= min($porcentaje, 100) ?>%">
                                                    <?= number_format($porcentaje, 0) ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('crm-campanas/zona-detalle/' . $asignacion['id_zona']) ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="icon-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Próximas Acciones -->
        <?php if (isset($proximas_acciones) && !empty($proximas_acciones)): ?>
        <div class="row">
            <div class="col-12">
                <h5 class="mb-3">Próximas Acciones</h5>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($proximas_acciones as $accion): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?= esc($accion['prospecto_nombre']) ?>
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            <i class="icon-phone"></i> <?= esc($accion['prospecto_telefono']) ?> |
                                            <i class="icon-map-pin"></i> <?= esc($accion['nombre_zona']) ?>
                                        </p>
                                        <p class="mb-0 small">
                                            <strong>Última interacción:</strong> 
                                            <?= esc($accion['tipo_interaccion']) ?> - 
                                            <?= esc($accion['resultado']) ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge badge-warning">
                                            <?= date('d/m/Y', strtotime($accion['proxima_accion'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ranking de Agentes (para admin/supervisor) -->
        <?php if (isset($ranking_agentes) && !empty($ranking_agentes)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <h5 class="mb-3">🏆 Ranking de Agentes</h5>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Agente</th>
                                        <th>Zonas</th>
                                        <th>Interacciones</th>
                                        <th>Conversiones</th>
                                        <th>Meta</th>
                                        <th>Cumplimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ranking_agentes as $index => $agente): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index === 0): ?>
                                                🥇
                                            <?php elseif ($index === 1): ?>
                                                🥈
                                            <?php elseif ($index === 2): ?>
                                                🥉
                                            <?php else: ?>
                                                <?= $index + 1 ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= esc($agente['agente_nombre']) ?></strong></td>
                                        <td><?= $agente['zonas_asignadas'] ?></td>
                                        <td><?= $agente['interacciones_realizadas'] ?></td>
                                        <td class="text-success">
                                            <strong><?= $agente['conversiones_logradas'] ?></strong>
                                        </td>
                                        <td><?= $agente['meta_conversiones_total'] ?></td>
                                        <td>
                                            <?php 
                                            $cumplimiento = $agente['porcentaje_cumplimiento'] ?? 0;
                                            ?>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-fill me-2" style="height: 20px;">
                                                    <div class="progress-bar <?= $cumplimiento >= 100 ? 'bg-success' : ($cumplimiento >= 70 ? 'bg-warning' : 'bg-danger') ?>" 
                                                         style="width: <?= min($cumplimiento, 100) ?>%">
                                                        <?= number_format($cumplimiento, 0) ?>%
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
