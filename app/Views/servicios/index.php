<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="ti-package me-2"></i>Catálogo de Servicios
                    </h4>
                    <div>
                        <a href="<?= base_url('servicios/estadisticas') ?>" class="btn btn-outline-info me-2">
                            <i class="ti-bar-chart me-1"></i>Estadísticas
                        </a>
                        <a href="<?= base_url('servicios/create') ?>" class="btn btn-primary">
                            <i class="ti-plus me-1"></i>Nuevo Servicio
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= session()->getFlashdata('success') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= session()->getFlashdata('error') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="filtro-estado">
                                <option value="">Todos los estados</option>
                                <option value="activo">Activos</option>
                                <option value="inactivo">Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="buscar-servicio" placeholder="Buscar servicio...">
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                                <i class="ti-refresh me-1"></i>Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- Grid de servicios -->
                    <div class="row" id="servicios-grid">
                        <?php if (empty($servicios)): ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="ti-package" style="font-size: 64px; color: #ccc;"></i>
                                    <h4 class="mt-3 text-muted">No hay servicios registrados</h4>
                                    <p class="text-muted">Comience agregando servicios a su catálogo</p>
                                    <a href="<?= base_url('servicios/create') ?>" class="btn btn-primary">
                                        <i class="ti-plus me-1"></i>Crear primer servicio
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($servicios as $servicio): 
                                // Verificar si existe la columna estado, si no, asumir activo por defecto
                                $estado = isset($servicio['estado']) ? strtolower($servicio['estado']) : 'activo';
                                $activo = $estado === 'activo';
                            ?>
                                <div class="col-lg-4 col-md-6 mb-4 servicio-card" 
                                     data-estado="<?= $activo ? 'activo' : 'inactivo' ?>"
                                     data-nombre="<?= strtolower(esc($servicio['nombre'])) ?>">
                                    <div class="card h-100 <?= $activo ? '' : 'opacity-75' ?>">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="card-title mb-0 fw-bold">
                                                <?= esc($servicio['nombre']) ?>
                                            </h6>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" data-bs-toggle="dropdown">
                                                    <i class="ti-more-alt"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="<?= base_url('servicios/edit/' . $servicio['idservicio']) ?>">
                                                            <i class="ti-pencil me-2"></i>Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           onclick="toggleEstado(<?= $servicio['idservicio'] ?>, <?= $activo ? 'false' : 'true' ?>)">
                                                            <i class="ti-power-off me-2"></i>
                                                            <?= $activo ? 'Desactivar' : 'Activar' ?>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <!-- Categoría -->
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="ti-tag text-primary me-2"></i>
                                                <span class="fw-bold"><?= esc($servicio['categoria'] ?? 'Sin categoría') ?></span>
                                            </div>

                                            <!-- Precio -->
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="ti-money text-success me-2"></i>
                                                <span class="fw-bold text-success">S/ <?= number_format($servicio['precio'], 2) ?></span>
                                            </div>

                                            <!-- Velocidad -->
                                            <?php if (!empty($servicio['velocidad'])): ?>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="ti-bolt text-warning me-2"></i>
                                                    <span class="text-muted"><?= esc($servicio['velocidad']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Descripción -->
                                            <?php if (!empty($servicio['descripcion'])): ?>
                                                <p class="text-muted small mb-3">
                                                    <?= esc(substr($servicio['descripcion'], 0, 100)) ?>
                                                    <?= strlen($servicio['descripcion']) > 100 ? '...' : '' ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <!-- Características -->
                                            <?php if (!empty($servicio['caracteristicas'])): ?>
                                                <?php 
                                                $caracteristicas = is_string($servicio['caracteristicas']) 
                                                    ? json_decode($servicio['caracteristicas'], true) 
                                                    : $servicio['caracteristicas'];
                                                ?>
                                                <?php if (!empty($caracteristicas['incluye'])): ?>
                                                    <div class="mb-3">
                                                        <small class="text-muted fw-bold d-block mb-1">Incluye:</small>
                                                        <ul class="list-unstyled mb-0">
                                                            <?php foreach (array_slice($caracteristicas['incluye'], 0, 3) as $item): ?>
                                                                <li class="small">
                                                                    <i class="ti-check text-success me-1"></i>
                                                                    <?= esc($item) ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                            <?php if (count($caracteristicas['incluye']) > 3): ?>
                                                                <li class="small text-muted">
                                                                    + <?= count($caracteristicas['incluye']) - 3 ?> más...
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Estadísticas -->
                                            <div class="row text-center mt-3">
                                                <div class="col-4">
                                                    <div class="text-center">
                                                        <h6 class="text-info mb-0"><?= $servicio['total_cotizaciones'] ?? 0 ?></h6>
                                                        <small class="text-muted">Cotizaciones</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-center">
                                                        <h6 class="text-success mb-0"><?= $servicio['cotizaciones_aceptadas'] ?? 0 ?></h6>
                                                        <small class="text-muted">Aceptadas</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-center">
                                                        <?php 
                                                        $conversion = 0;
                                                        if (($servicio['total_cotizaciones'] ?? 0) > 0) {
                                                            $conversion = round((($servicio['cotizaciones_aceptadas'] ?? 0) / $servicio['total_cotizaciones']) * 100, 1);
                                                        }
                                                        ?>
                                                        <h6 class="text-warning mb-0"><?= $conversion ?>%</h6>
                                                        <small class="text-muted">Conversión</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge <?= $activo ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $activo ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                                <small class="text-muted">
                                                    Precio: S/ <?= number_format($servicio['precio'], 2) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Filtros en tiempo real
document.getElementById('filtro-estado').addEventListener('change', function() {
    filtrarServicios();
});

document.getElementById('buscar-servicio').addEventListener('keyup', function() {
    filtrarServicios();
});

function filtrarServicios() {
    const estadoFiltro = document.getElementById('filtro-estado').value.toLowerCase();
    const nombreFiltro = document.getElementById('buscar-servicio').value.toLowerCase();
    const tarjetas = document.querySelectorAll('.servicio-card');

    tarjetas.forEach(function(tarjeta) {
        const estado = tarjeta.dataset.estado;
        const nombre = tarjeta.dataset.nombre;
        
        const mostrarEstado = !estadoFiltro || estado === estadoFiltro;
        const mostrarNombre = !nombreFiltro || nombre.includes(nombreFiltro);
        
        tarjeta.style.display = (mostrarEstado && mostrarNombre) ? '' : 'none';
    });
}

function limpiarFiltros() {
    document.getElementById('filtro-estado').value = '';
    document.getElementById('buscar-servicio').value = '';
    filtrarServicios();
}

// Cambiar estado de servicio
function toggleEstado(idservicio, activar) {
    const accion = activar ? 'activar' : 'desactivar';
    
    if (!confirm(`¿Está seguro de ${accion} este servicio?`)) {
        return;
    }

    fetch(`<?= base_url('servicios/toggleEstado') ?>/${idservicio}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al cambiar estado del servicio:', error);
        alert('No se pudo cambiar el estado del servicio. Por favor, inténtelo nuevamente.');
    });
}
</script>

<?= $this->endSection() ?>
