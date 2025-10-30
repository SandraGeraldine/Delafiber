<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="<?= base_url('campanias') ?>" class="btn btn-outline-secondary">
                    <i class="icon-arrow-left"></i> Volver
                </a>
            </div>
            <div>
                <a href="<?= base_url('campanias/edit/' . $campania['idcampania']) ?>" class="btn btn-warning">
                    <i class="icon-pencil"></i> Editar
                </a>
                <button class="btn btn-<?= ($campania['estado'] ?? 'Inactiva') == 'Activa' ? 'danger' : 'success' ?>" 
                        onclick="toggleEstado(<?= $campania['idcampania'] ?? 0 ?>)">
                    <?= ($campania['estado'] ?? 'Inactiva') == 'Activa' ? 'Desactivar' : 'Activar' ?>
                </button>
            </div>
        </div>

        <!-- Información de la Campaña -->
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="mb-2"><?= esc($campania['nombre']) ?></h3>
                                <span class="badge badge-<?= ($campania['estado'] ?? 'Inactiva') == 'Activa' ? 'success' : 'secondary' ?>">
                                    <?= esc($campania['estado'] ?? 'Inactiva') ?>
                                </span>
                            </div>
                        </div>

                        <p class="text-muted"><?= esc($campania['descripcion'] ?? 'Sin descripción') ?></p>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Fecha Inicio:</strong><br>
                                <?php
                                $fechaInicio = $campania['fecha_inicio'] ?? null;
                                echo ($fechaInicio && strtotime($fechaInicio)) 
                                    ? date('d/m/Y', strtotime($fechaInicio)) 
                                    : 'Sin definir';
                                ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Fecha Fin:</strong><br>
                                <?php
                                $fechaFin = $campania['fecha_fin'] ?? null;
                                echo ($fechaFin && strtotime($fechaFin)) 
                                    ? date('d/m/Y', strtotime($fechaFin)) 
                                    : 'Sin definir';
                                ?>
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Presupuesto:</strong><br>
                                S/ <?= number_format($campania['presupuesto'] ?? 0, 2) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tipo:</strong><br>
                                <?= esc($campania['tipo'] ?? 'Sin definir') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas de la Campaña -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h2 class="text-primary"><?= $estadisticas['total_leads'] ?? 0 ?></h2>
                                    <p class="text-muted">Leads Generados</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h2 class="text-success"><?= $estadisticas['convertidos'] ?? 0 ?></h2>
                                    <p class="text-muted">Convertidos</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h2 class="text-warning"><?= $estadisticas['activos'] ?? 0 ?></h2>
                                    <p class="text-muted">En Proceso</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <?php 
                                    $tasa = $estadisticas['tasa_conversion'] ?? 0;
                                    ?>
                                    <h2 class="text-info"><?= number_format($tasa, 1) ?>%</h2>
                                    <p class="text-muted">Tasa Conversión</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico o información adicional -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Evolución de Leads</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-center text-muted py-4">
                            <i class="icon-bar-graph" style="font-size: 2rem;"></i><br>
                            Gráfico de evolución próximamente
                        </p>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha -->
            <div class="col-md-4">
                <!-- Leads de la Campaña -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Leads Recientes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($leads_recientes) && is_array($leads_recientes)): ?>
                        <div class="list-group">
                            <?php foreach ($leads_recientes as $lead): ?>
                            <a href="<?= base_url('leads/view/' . ($lead['idlead'] ?? '')) ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <strong><?= esc($lead['cliente'] ?? $lead['nombre'] ?? 'Sin nombre') ?></strong>
                                    <small>
                                        <?php 
                                        $fecha = $lead['fecha_registro'] ?? $lead['created_at'] ?? null;
                                        echo $fecha && strtotime($fecha) ? date('d/m', strtotime($fecha)) : 'S/F';
                                        ?>
                                    </small>
                                </div>
                                <small class="text-muted"><?= esc($lead['telefono'] ?? 'Sin teléfono') ?></small>
                                <br>
                                <span class="badge badge-info"><?= esc($lead['etapa_actual'] ?? $lead['estado'] ?? 'Nuevo') ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?= base_url('leads?campania=' . $campania['idcampania']) ?>" 
                           class="btn btn-sm btn-outline-primary btn-block mt-3">
                            Ver todos los leads
                        </a>
                        <?php else: ?>
                        <p class="text-center text-muted py-4">
                            <i class="icon-target" style="font-size: 2rem; color: #ddd;"></i><br>
                            Sin leads aún
                        </p>
                        <a href="<?= base_url('leads/create?campania=' . $campania['idcampania']) ?>" 
                           class="btn btn-sm btn-primary btn-block">
                            <i class="icon-plus"></i> Crear primer lead
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resumen del Presupuesto -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Resumen Financiero</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Presupuesto Total:</span>
                                <strong>S/ <?= number_format($campania['presupuesto'] ?? 0, 2) ?></strong>
                            </div>
                            
                            <?php 
                            $totalLeads = $estadisticas['total_leads'] ?? 0;
                            $costoPorLead = ($totalLeads > 0 && $campania['presupuesto'] > 0) 
                                ? $campania['presupuesto'] / $totalLeads 
                                : 0;
                            ?>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Costo por Lead:</span>
                                <strong class="text-primary">S/ <?= number_format($costoPorLead, 2) ?></strong>
                            </div>

                            <?php 
                            $convertidos = $estadisticas['convertidos'] ?? 0;
                            $costoPorConversion = ($convertidos > 0 && $campania['presupuesto'] > 0) 
                                ? $campania['presupuesto'] / $convertidos 
                                : 0;
                            ?>
                            
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Costo por Conversión:</span>
                                <strong class="text-success">S/ <?= number_format($costoPorConversion, 2) ?></strong>
                            </div>
                        </div>

                        <hr>

                        <div class="text-center">
                            <small class="text-muted">Campaña creada el</small><br>
                            <?php
                            $fechaCreacion = $campania['created_at'] ?? $campania['fecha_inicio'] ?? null;
                            echo ($fechaCreacion && strtotime($fechaCreacion)) 
                                ? '<strong>' . date('d/m/Y', strtotime($fechaCreacion)) . '</strong>' 
                                : '<strong>Sin definir</strong>';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/campaniasJS/campanias-view.js') ?>"></script>
<?= $this->endSection() ?>