<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="mb-1">Mapa de Campañas</h3>
                <p class="text-muted mb-0">Gestión territorial de campañas con análisis geoespacial</p>
            </div>
            <div class="btn-group flex-wrap">
                <button type="button" class="btn btn-outline-primary" id="btnCargarProspectos">
                    <i class="icon-users"></i> Cargar Prospectos
                </button>
                <button type="button" class="btn btn-outline-warning" id="btnGeocodificar">
                    <i class="icon-map-pin"></i> Geocodificar
                </button>
                <button type="button" class="btn btn-outline-success" id="btnAsignarAutomatico">
                    <i class="icon-zap"></i> Asignar Automático
                </button>
                <a href="<?= base_url('crm-campanas/zonas-index/' . ($campana_seleccionada ?? '')) ?>" class="btn btn-outline-secondary">
                    <i class="icon-list"></i> Ver Lista
                </a>
            </div>
        </div>

        <!-- Selector de Campaña -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <label class="form-label mb-2"><strong>Seleccionar Campaña:</strong></label>
                        <select class="form-select" id="id_campana_select">
                            <option value="">-- Todas las campañas --</option>
                            <?php if (!empty($campanias)): ?>
                                <?php foreach ($campanias as $camp): ?>
                                    <option value="<?= $camp['idcampania'] ?>" 
                                            <?= ($campana_seleccionada == $camp['idcampania']) ? 'selected' : '' ?>>
                                        <?= esc($camp['nombre']) ?> 
                                        (<?= esc($camp['estado']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0 justify-content-start justify-content-md-end">
                            <button type="button" class="btn btn-sm btn-info" id="btnToggleProspectos">
                                <i class="icon-eye"></i> Mostrar/Ocultar Prospectos
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" id="btnAnalisisZonas">
                                <i class="icon-bar-chart"></i> Análisis de Zonas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <?php if (isset($campana) && isset($zonas)): ?>
        <div class="row mb-3">
            <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="mb-1">Zonas Activas</h6>
                        <h3 class="mb-0"><?= count($zonas) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="mb-1">Total Prospectos</h6>
                        <h3 class="mb-0" id="totalProspectos">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="mb-1">Área Total</h6>
                        <h3 class="mb-0" id="areaTotal">0 km²</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="mb-1">Densidad Promedio</h6>
                        <h3 class="mb-0" id="densidadPromedio">0/km²</h3>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php $googleMapsKey = env('GOOGLE_MAPS_KEY1') ?: env('google.maps.key'); ?>
        <!-- Mapa -->
        <div class="card">
            <div class="card-body p-0">
                <div id="mapCampanas" style="height: 60vh; min-height: 360px; width: 100%;"></div>
            </div>
        </div>
        <div id="mapa-zona-banner" class="alert alert-info mt-3 mx-3 d-none" role="presentation"></div>

        <?php if (empty($googleMapsKey)): ?>
            <div class="alert alert-warning mt-3" role="alert">
                <strong>Google Maps no está disponible.</strong> Configura la clave de API en tu archivo <code>.env</code> usando <code>GOOGLE_MAPS_KEY1</code> (ó <code>google.maps.key</code>). Sin una clave válida el mapa no puede cargarse.
            </div>
        <?php endif; ?>

        <!-- Leyenda -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="mb-3"><strong>Leyenda:</strong></h6>
                <div class="row">
                    <div class="col-12 col-md-4 mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <div style="width: 20px; height: 20px; background: #e74c3c; border-radius: 50%; margin-right: 10px;"></div>
                            <span>Prioridad Alta</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <div style="width: 20px; height: 20px; background: #f39c12; border-radius: 50%; margin-right: 10px;"></div>
                            <span>Prioridad Media</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <div style="width: 20px; height: 20px; background: #3498db; border-radius: 50%; margin-right: 10px;"></div>
                            <span>Prioridad Baja</span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="alert alert-info mb-0">
                    <strong> Instrucciones:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Dibujar:</strong> Usa la herramienta de polígono en el mapa</li>
                        <li><strong>Borrar:</strong> Usa el botón "Borrar Zona Actual" si te equivocas</li>
                        <li><strong>Editar:</strong> Haz clic en una zona y luego en "Editar" para modificarla</li>
                        <li><strong>Prospectos:</strong> Se asignan automáticamente según su ubicación</li>
                        <li><strong>Asignar Automático:</strong> Procesa prospectos sin zona asignada</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Google Maps API - CARGAR PRIMERO -->
<?php if (!empty($googleMapsKey)): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= esc($googleMapsKey) ?>&libraries=drawing,geometry"></script>
<?php endif; ?>

<script type="module">
    import { inicializarSistema } from '<?= base_url('js/mapa/mapa-init.js') ?>';
    
    const tieneClaveGoogleMaps = <?= json_encode(!empty($googleMapsKey)) ?>;
    
    // Inicializar sistema al cargar la página
    document.addEventListener('DOMContentLoaded', async function() {
        if (!tieneClaveGoogleMaps) {
            console.warn('Falta la clave de Google Maps. No se intentará inicializar el mapa.');
            return;
        }

        const idCampana = document.getElementById('id_campana_select')?.value || null;
        const zonas = <?= json_encode($zonas ?? []) ?>;
        
        try {
            await inicializarSistema(idCampana, zonas);
        } catch (error) {
            console.error('Error al inicializar sistema:', error);
            alert('Error al cargar el mapa: ' + error.message);
        }
    });
</script>

<?= $this->endSection() ?>
