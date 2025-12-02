<?= $this->extend('Layouts/base') ?>

<?php $googleMapsKey = env('GOOGLE_MAPS_KEY1') ?: env('google.maps.key'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row gap-3 align-items-start">
                    <div>
                        <h4 class="mb-1">Mapa de Campo</h4>
                        <p class="text-muted mb-0">Centra el mapa en la zona asignada para tu ruta del día.</p>
                    </div>
                    <div class="ms-md-auto">
                        <a href="<?= base_url('leads/campo') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="ti-arrow-left"></i> Volver al registro
                        </a>
                    </div>
                </div>
                <div class="row g-2 mt-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Campaña activa</label>
                        <select id="campo-campana-select" class="form-select">
                            <option value="">-- Todas las campañas --</option>
                            <?php foreach ($campanas as $campana): ?>
                                <option value="<?= esc($campana['idcampania']) ?>">
                                    <?= esc($campana['nombre']) ?> (<?= esc($campana['estado']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Prioridad</label>
                        <select id="campo-prioridad-select" class="form-select">
                            <option value="">Todas</option>
                            <option value="Alta">Alta</option>
                            <option value="Media">Media</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-5">
                        <label class="form-label">Buscar zona</label>
                        <input type="search" id="campo-buscar-zona" class="form-control" placeholder="Nombre o descripción">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0" style="height: 70vh; min-height: 360px;">
                <div id="campo-mapa" class="w-100 h-100" data-base-url="<?= base_url() ?>"></div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-3">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h6 class="mb-0">Zonas disponibles</h6>
            </div>
            <div class="card-body" id="campo-zonas-list">
                <p class="text-muted mb-0">Selecciona una campaña para cargar las zonas asignadas y poder filtrarlas.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    #campo-mapa {
        min-height: 360px;
        border-radius: 0;
    }
    .campo-zona-card {
        cursor: pointer;
        transition: transform 0.15s ease;
    }
    .campo-zona-card:hover {
        transform: translateY(-2px);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
</script>
<?php if (!empty($googleMapsKey)): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= esc($googleMapsKey) ?>&libraries=geometry"></script>
<?php else: ?>
    <div class="alert alert-warning mb-0">
        <strong>Google Maps no está disponible.</strong> Configura la clave en <code>.env</code> (GOOGLE_MAPS_KEY1).
    </div>
<?php endif; ?>
<script src="<?= base_url('js/leads/campo_mapa.js') ?>"></script>
<?= $this->endSection() ?>
