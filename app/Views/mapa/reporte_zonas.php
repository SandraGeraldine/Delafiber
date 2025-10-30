<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Reporte de Zonas</h3>
                <p class="text-muted mb-0">
                    Campaña: <strong><?= esc($campana['nombre']) ?></strong>
                </p>
            </div>
            <div class="btn-group">
                <a href="<?= base_url('crm-campanas/mapa-campanas/' . $campana['idcampania']) ?>" class="btn btn-primary">
                    <i class="icon-map"></i> Ver en Mapa
                </a>
                <a href="<?= base_url('crm-campanas/exportar-campana/' . $campana['idcampania'] . '/csv') ?>" class="btn btn-success">
                    <i class="icon-download"></i> Exportar CSV
                </a>
            </div>
        </div>

        <!-- Resumen General -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h2 class="mb-2 text-primary"><?= count($zonas) ?></h2>
                        <p class="text-muted mb-0">Total Zonas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <?php 
                            $totalProspectos = array_sum(array_column($zonas, 'total_prospectos'));
                        ?>
                        <h2 class="mb-2 text-success"><?= $totalProspectos ?></h2>
                        <p class="text-muted mb-0">Prospectos Totales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <?php 
                            $areaTotal = array_sum(array_column($zonas, 'area_m2'));
                            $areaKm2 = round($areaTotal / 1000000, 2);
                        ?>
                        <h2 class="mb-2 text-info"><?= $areaKm2 ?> km²</h2>
                        <p class="text-muted mb-0">Área Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <?php 
                            $zonasActivas = count(array_filter($zonas, fn($z) => empty($z['inactive_at'])));
                        ?>
                        <h2 class="mb-2 text-warning"><?= $zonasActivas ?></h2>
                        <p class="text-muted mb-0">Zonas Activas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Zonas -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Detalle por Zona</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($zonas)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Zona</th>
                                <th>Prioridad</th>
                                <th>Área</th>
                                <th>Prospectos</th>
                                <th>Densidad</th>
                                <th>Interacciones</th>
                                <th>Conversiones</th>
                                <th>Tasa</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($zonas as $zona): ?>
                            <?php 
                                $areaKm = round($zona['area_m2'] / 1000000, 2);
                                $densidad = $areaKm > 0 ? round($zona['total_prospectos'] / $areaKm, 1) : 0;
                                
                                // Estadísticas (si existen)
                                $stats = $zona['estadisticas'] ?? [];
                                $interacciones = $stats['total_interacciones'] ?? 0;
                                $conversiones = $stats['conversiones'] ?? 0;
                                $tasaConversion = $zona['total_prospectos'] > 0 
                                    ? round(($conversiones / $zona['total_prospectos']) * 100, 1) 
                                    : 0;
                                
                                // Color de prioridad
                                $prioridadClass = match($zona['prioridad']) {
                                    'Alta' => 'badge-danger',
                                    'Media' => 'badge-warning',
                                    'Baja' => 'badge-info',
                                    default => 'badge-secondary'
                                };
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="color-indicator mr-2" 
                                             style="width: 20px; height: 20px; border-radius: 4px; background-color: <?= esc($zona['color']) ?>;">
                                        </div>
                                        <div>
                                            <strong><?= esc($zona['nombre_zona']) ?></strong>
                                            <?php if (!empty($zona['descripcion'])): ?>
                                            <br><small class="text-muted"><?= esc($zona['descripcion']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $prioridadClass ?>">
                                        <?= esc($zona['prioridad']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= $areaKm ?> km²</strong>
                                    <br><small class="text-muted"><?= number_format($zona['area_m2'], 0) ?> m²</small>
                                </td>
                                <td class="text-center">
                                    <h5 class="mb-0"><?= $zona['total_prospectos'] ?></h5>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light"><?= $densidad ?>/km²</span>
                                </td>
                                <td class="text-center">
                                    <?= $interacciones ?>
                                </td>
                                <td class="text-center">
                                    <strong class="text-success"><?= $conversiones ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($tasaConversion > 0): ?>
                                    <span class="badge badge-<?= $tasaConversion >= 50 ? 'success' : ($tasaConversion >= 25 ? 'warning' : 'secondary') ?>">
                                        <?= $tasaConversion ?>%
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($zona['inactive_at'])): ?>
                                    <span class="badge badge-success">Activa</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('crm-campanas/zona-detalle/' . $zona['id_zona']) ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver Detalle">
                                            <i class="icon-eye"></i>
                                        </a>
                                        <a href="<?= base_url('crm-campanas/mapa-campanas/' . $campana['idcampania']) ?>?zona=<?= $zona['id_zona'] ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="Ver en Mapa">
                                            <i class="icon-map"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="icon-info-circle"></i>
                    No hay zonas registradas para esta campaña.
                    <br>
                    <a href="<?= base_url('crm-campanas/mapa-campanas/' . $campana['idcampania']) ?>" class="btn btn-primary btn-sm mt-2">
                        Crear Primera Zona
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Gráfico de Rendimiento (opcional) -->
        <?php if (!empty($zonas)): ?>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Top 5 Zonas por Prospectos</h6>
                    </div>
                    <div class="card-body">
                        <?php 
                            $topZonas = $zonas;
                            usort($topZonas, fn($a, $b) => $b['total_prospectos'] - $a['total_prospectos']);
                            $topZonas = array_slice($topZonas, 0, 5);
                            $maxProspectos = max(array_column($topZonas, 'total_prospectos'));
                        ?>
                        <?php foreach ($topZonas as $zona): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?= esc($zona['nombre_zona']) ?></span>
                                <strong><?= $zona['total_prospectos'] ?></strong>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" 
                                     style="width: <?= $maxProspectos > 0 ? ($zona['total_prospectos'] / $maxProspectos * 100) : 0 ?>%; background-color: <?= esc($zona['color']) ?>;">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Distribución por Prioridad</h6>
                    </div>
                    <div class="card-body">
                        <?php 
                            $prioridades = ['Alta' => 0, 'Media' => 0, 'Baja' => 0];
                            foreach ($zonas as $zona) {
                                if (isset($prioridades[$zona['prioridad']])) {
                                    $prioridades[$zona['prioridad']]++;
                                }
                            }
                            $totalZonas = count($zonas);
                        ?>
                        <?php foreach ($prioridades as $prioridad => $cantidad): ?>
                        <?php 
                            $porcentaje = $totalZonas > 0 ? round(($cantidad / $totalZonas) * 100, 1) : 0;
                            $colorClass = match($prioridad) {
                                'Alta' => 'danger',
                                'Media' => 'warning',
                                'Baja' => 'info',
                                default => 'secondary'
                            };
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Prioridad <?= $prioridad ?></span>
                                <strong><?= $cantidad ?> (<?= $porcentaje ?>%)</strong>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-<?= $colorClass ?>" 
                                     style="width: <?= $porcentaje ?>%;">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?= $this->endSection() ?>
