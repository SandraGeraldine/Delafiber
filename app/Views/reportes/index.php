<?= $this->extend('layouts/base') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/reportes/reportes-index.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
// Inicializa variables si no existen para evitar error 500
$kpis = $kpis ?? [];
$rendimiento_vendedores = $rendimiento_vendedores ?? [];
$rendimiento_campanias = $rendimiento_campanias ?? [];
$periodo = $periodo ?? '';
$fecha_inicio = $fecha_inicio ?? '';
$fecha_fin = $fecha_fin ?? '';
$datos_etapas = $datos_etapas ?? [];
$datos_origenes = $datos_origenes ?? [];
$datos_tendencia = $datos_tendencia ?? [];
?>

<div class="container">
    <h2><?= esc($title ?? 'Reportes y Estadísticas') ?></h2>
    <p>Bienvenido al módulo de reportes. Aquí puedes ver tus KPIs y estadísticas.</p>

    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Reportes y Estadísticas</h4>
                <div>
                    <button type="button" class="btn btn-outline-primary" onclick="imprimirReporte()">
                        <i class="icon-printer"></i> Imprimir
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarExcel()">
                        <i class="icon-download"></i> Exportar Excel
                    </button>
                </div>
            </div>

            <!-- Filtros de Fecha -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="<?= base_url('reportes') ?>" method="GET" class="form-inline">
                        <label class="mr-2">Período:</label>
                        <select class="form-control mr-2" name="periodo" id="periodoSelect">
                            <option value="mes_actual" <?= ($periodo ?? 'mes_actual') == 'mes_actual' ? 'selected' : '' ?>>Mes Actual</option>
                            <option value="mes_anterior" <?= ($periodo ?? '') == 'mes_anterior' ? 'selected' : '' ?>>Mes Anterior</option>
                            <option value="trimestre" <?= ($periodo ?? '') == 'trimestre' ? 'selected' : '' ?>>Último Trimestre</option>
                            <option value="ano" <?= ($periodo ?? '') == 'ano' ? 'selected' : '' ?>>Año Actual</option>
                            <option value="personalizado" <?= ($periodo ?? '') == 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
                        </select>
                        
                        <div id="rangoFechas" style="display: <?= ($periodo ?? '') == 'personalizado' ? 'inline-flex' : 'none' ?>;">
                            <input type="date" class="form-control mr-2" name="fecha_inicio" value="<?= $fecha_inicio ?? '' ?>">
                            <input type="date" class="form-control mr-2" name="fecha_fin" value="<?= $fecha_fin ?? '' ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-search"></i> Filtrar
                        </button>
                    </form>
                </div>
            </div>

            <!-- KPIs Principales -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-gradient-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Leads</h6>
                                    <h2 class="mb-0 mt-2"><?= isset($kpis['total_leads']) ? $kpis['total_leads'] : 0 ?></h2>
                                    <small>
                                        <?php if (isset($kpis['variacion_leads']) && $kpis['variacion_leads'] > 0): ?>
                                            <i class="icon-arrow-up"></i> +<?= $kpis['variacion_leads'] ?>%
                                        <?php else: ?>
                                            <i class="icon-arrow-down"></i> <?= $kpis['variacion_leads'] ?? 0 ?>%
                                        <?php endif; ?>
                                        vs período anterior
                                    </small>
                                </div>
                                <i class="icon-users" style="font-size: 48px; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-gradient-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Conversiones</h6>
                                    <h2 class="mb-0 mt-2"><?= isset($kpis['conversiones']) ? $kpis['conversiones'] : 0 ?></h2>
                                    <small><?= isset($kpis['tasa_conversion']) ? $kpis['tasa_conversion'] : 0 ?>% de conversión</small>
                                </div>
                                <i class="icon-check-circle" style="font-size: 48px; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-gradient-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Ingresos</h6>
                                    <h2 class="mb-0 mt-2">S/ <?= number_format($kpis['ingresos'] ?? 0, 2) ?></h2>
                                    <small>Valor estimado</small>
                                </div>
                                <i class="icon-dollar-sign" style="font-size: 48px; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-gradient-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Ticket Promedio</h6>
                                    <h2 class="mb-0 mt-2">S/ <?= number_format($kpis['ticket_promedio'] ?? 0, 2) ?></h2>
                                    <small>Por conversión</small>
                                </div>
                                <i class="icon-trending-up" style="font-size: 48px; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row mt-4">
                <!-- Leads por Etapa -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Leads por Etapa</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartEtapas" height="200" 
                                    data-etapas='{"labels": <?= json_encode(array_column($datos_etapas, 'etapa')) ?>, "data": <?= json_encode(array_column($datos_etapas, 'total')) ?>}'></canvas>
                        </div>
                    </div>
                </div>

                <!-- Leads por Origen -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Leads por Origen</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartOrigenes" height="200" 
                                    data-origenes='{"labels": <?= json_encode(array_column($datos_origenes, 'origen')) ?>, "data": <?= json_encode(array_column($datos_origenes, 'total')) ?>}'></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tendencia de Leads -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Tendencia de Leads y Conversiones</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTendencia" height="80" 
                                    data-tendencia='{"labels": <?= json_encode(array_column($datos_tendencia, 'fecha')) ?>, "leads": <?= json_encode(array_column($datos_tendencia, 'leads')) ?>, "conversiones": <?= json_encode(array_column($datos_tendencia, 'conversiones')) ?>}'></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Rendimiento por Vendedor -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Rendimiento por Vendedor</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vendedor</th>
                                            <th>Leads Asignados</th>
                                            <th>Conversiones</th>
                                            <th>Tasa Conversión</th>
                                            <th>Ingresos Generados</th>
                                            <th>Ticket Promedio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($rendimiento_vendedores)): ?>
                                            <?php foreach ($rendimiento_vendedores as $vendedor): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= esc($vendedor['nombre'] ?? '') ?></strong>
                                                </td>
                                                <td><?= $vendedor['total_leads'] ?? 0 ?></td>
                                                <td>
                                                    <span class="badge badge-success"><?= $vendedor['conversiones'] ?? 0 ?></span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" 
                                                             style="width: <?= $vendedor['tasa_conversion'] ?? 0 ?>%">
                                                            <?= number_format($vendedor['tasa_conversion'] ?? 0, 1) ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>S/ <?= number_format($vendedor['ingresos'] ?? 0, 2) ?></td>
                                                <td>S/ <?= number_format($vendedor['ticket_promedio'] ?? 0, 2) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No hay datos disponibles</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campañas Performance -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Rendimiento de Campañas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Campaña</th>
                                            <th>Tipo</th>
                                            <th>Leads Generados</th>
                                            <th>Conversiones</th>
                                            <th>Tasa Conversión</th>
                                            <th>Presupuesto</th>
                                            <th>Costo por Lead</th>
                                            <th>ROI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($rendimiento_campanias)): ?>
                                            <?php foreach ($rendimiento_campanias as $campania): ?>
                                            <tr>
                                                <td><strong><?= esc($campania['nombre'] ?? '') ?></strong></td>
                                                <td><span class="badge badge-info"><?= esc($campania['tipo'] ?? '') ?></span></td>
                                                <td><?= $campania['total_leads'] ?? 0 ?></td>
                                                <td><span class="badge badge-success"><?= $campania['conversiones'] ?? 0 ?></span></td>
                                                <td><?= number_format($campania['tasa_conversion'] ?? 0, 1) ?>%</td>
                                                <td>S/ <?= number_format($campania['presupuesto'] ?? 0, 2) ?></td>
                                                <td>S/ <?= number_format($campania['costo_por_lead'] ?? 0, 2) ?></td>
                                                <td>
                                                    <?php 
                                                    $roi = $campania['roi'] ?? 0;
                                                    $roiClass = $roi > 0 ? 'text-success' : 'text-danger';
                                                    ?>
                                                    <span class="<?= $roiClass ?>">
                                                        <?= $roi > 0 ? '+' : '' ?><?= number_format($roi, 1) ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No hay campañas activas</td>
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
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="<?= base_url('js/reportes/reportes-index.js') ?>"></script>
<?= $this->endSection() ?>