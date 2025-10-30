<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<!-- Mensajes Flash -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0"><i class="ti-target"></i> Gestión de Leads</h4>
                    <div>
                        <a href="<?= base_url('leads/pipeline') ?>" class="btn btn-outline-info">
                            <i class="ti-layout-grid2"></i> Pipeline
                        </a>
                        <a href="<?= base_url('leads/create') ?>" class="btn btn-primary">
                            <i class="ti-plus"></i> Nuevo Lead
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <form action="<?= base_url('leads') ?>" method="GET" id="filtrosForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Buscar</label>
                                        <input type="text" class="form-control" name="buscar" 
                                               placeholder="Nombre, DNI, teléfono..." 
                                               value="<?= $filtro_busqueda ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Etapa</label>
                                        <select class="form-control" name="etapa">
                                            <option value="">Todas</option>
                                            <?php if (isset($etapas)): foreach ($etapas as $etapa): ?>
                                            <option value="<?= $etapa['idetapa'] ?>" 
                                                    <?= ($filtro_etapa == $etapa['idetapa']) ? 'selected' : '' ?>>
                                                <?= esc($etapa['nombre']) ?>
                                            </option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Origen</label>
                                        <select class="form-control" name="origen">
                                            <option value="">Todos</option>
                                            <?php if (isset($origenes)): foreach ($origenes as $origen): ?>
                                            <option value="<?= $origen['idorigen'] ?>" 
                                                    <?= ($filtro_origen == $origen['idorigen']) ? 'selected' : '' ?>>
                                                <?= esc($origen['nombre']) ?>
                                            </option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="ti-search"></i> Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Leads -->
                <div class="table-responsive">
                    <table id="tableLeads" class="table table-striped table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Distrito</th>
                                <th>Campaña</th>
                                <th>Etapa</th>
                                <th>Origen</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($leads)): ?>
                                <?php foreach ($leads as $lead): ?>
                                <tr>
                                    <td><?= $lead['idlead'] ?></td>
                                    <td>
                                        <strong><?= esc($lead['nombre_completo']) ?></strong><br>
                                        <small class="text-muted"><?= esc($lead['dni'] ?? 'Sin DNI') ?></small>
                                    </td>
                                    <td>
                                        <a href="https://wa.me/51<?= esc($lead['telefono']) ?>" target="_blank" 
                                           class="btn btn-sm btn-success">
                                            <i class="ti-mobile"></i> <?= esc($lead['telefono']) ?>
                                        </a>
                                    </td>
                                    <td><?= esc($lead['distrito'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($lead['campania'])): ?>
                                            <span class="badge badge-info"><?= esc($lead['campania']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?= esc($lead['etapa']) ?></span>
                                    </td>
                                    <td><small><?= esc($lead['origen']) ?></small></td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($lead['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= base_url('leads/view/' . $lead['idlead']) ?>" 
                                               class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="ti-eye"></i>
                                            </a>
                                            <a href="<?= base_url('leads/edit/' . $lead['idlead']) ?>" 
                                               class="btn btn-sm btn-warning" title="Editar">
                                                <i class="ti-pencil"></i>
                                            </a>
                                            <?php if (!empty($lead['coordenadas'])): ?>
                                                <a href="<?= base_url('crm-campanas/mapa-campanas?lead=' . $lead['idlead']) ?>" 
                                                   class="btn btn-sm btn-secondary" title="Ver en Mapa">
                                                    <i class="ti-map-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (in_array($lead['etapa'], ['INTERES', 'COTIZACION', 'NEGOCIACION'])): ?>
                                                <a href="<?= base_url('cotizaciones/create?lead=' . $lead['idlead']) ?>" 
                                                   class="btn btn-sm btn-primary" title="Crear Cotización">
                                                    <i class="ti-receipt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="https://wa.me/51<?= esc($lead['telefono']) ?>?text=Hola%20<?= urlencode($lead['nombres'] ?? '') ?>,%20te%20contacto%20desde%20Delafiber" 
                                               target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
                                                <i class="ti-comment"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="ti-info-alt" style="font-size: 48px; opacity: 0.3;"></i>
                                        <p class="mt-3">No se encontraron leads con los filtros aplicados</p>
                                        <a href="<?= base_url('leads/create') ?>" class="btn btn-primary mt-2">
                                            <i class="ti-plus"></i> Crear Nuevo Lead
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Resumen -->
                <div class="mt-3">
                    <p class="text-muted">
                        <strong>Total de leads:</strong> <?= count($leads) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/leads/leads-index.js') ?>"></script>
<?= $this->endSection() ?>
