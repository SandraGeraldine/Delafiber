<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header header-corporativo d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="ti-package me-2"></i>Catálogo de Servicios
                    </h4>
                    <div class="d-flex align-items-center">
                        <form action="<?= base_url('servicios/sincronizar-gst') ?>" method="post" class="me-2">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-light btn-sm" onclick="return confirm('¿Sincronizar catálogo desde GST? Esto creará o actualizará servicios según los planes disponibles.');">
                                <i class="ti-cloud-down me-1"></i>Sincronizar GST
                            </button>
                        </form>
                        <a href="<?= base_url('servicios/estadisticas') ?>" class="btn btn-light btn-sm me-2">
                            <i class="ti-bar-chart me-1"></i>Estadísticas
                        </a>
                        <button type="button" class="btn btn-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalPromociones">
                            <i class="ti-gift me-1"></i>Promociones
                        </button>
                        <a href="<?= base_url('servicios/create') ?>" class="btn btn-light btn-sm">
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

<!-- Modal Promociones -->
<div class="modal fade" id="modalPromociones" tabindex="-1" aria-labelledby="modalPromocionesLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalPromocionesLabel"><i class="ti-gift me-2"></i>Promociones disponibles</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info mb-4">
          <h6 class="mb-2">¿Cómo usar esta sección?</h6>
          <ul class="mb-0 small">
            <li>Revisa la columna <strong>Promociones vigentes</strong> para ver lo que ya está activo.</li>
            <li>Agrega un nombre y precio claro; puedes usar la descripción para indicar duración o restricciones.</li>
            <li>Incluye la fecha de inicio y la fecha de caducidad para que las promotoras sepan hasta cuándo pueden ofrecerla.</li>
            <li>Una vez guardada, la promoción aparecerá en el catálogo de servicios y podrá asignarse a campañas.</li>
          </ul>
          <p class="mb-0 mt-2"><small>Si necesitas desactivarla después, usa el botón de estado dentro del catálogo.</small></p>
        </div>
        <?php if (session()->getFlashdata('promo_success')): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('promo_success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php $promoErrors = session()->getFlashdata('promo_errors') ?? [] ?>
        <?php if (!empty($promoErrors)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= esc(implode(' ', array_values($promoErrors))) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-6">
            <h6 class="mb-3 text-uppercase small text-secondary">Promociones vigentes</h6>
            <?php if (!empty($promociones)): ?>
              <?php foreach ($promociones as $promo): ?>
                <?php
                  $fechaInicio = $promo['caracteristicas']['fecha_inicio_promocion'] ?? null;
                  $fechaFin = $promo['caracteristicas']['fecha_fin_promocion'] ?? null;
                ?>
                <div class="mb-3 p-3 border rounded bg-white shadow-sm">
                  <div class="d-flex justify-content-between align-items-baseline">
                    <strong class="fs-6 text-dark"><?= esc($promo['nombre']) ?></strong>
                    <span class="text-success fw-bold">S/ <?= number_format($promo['precio'], 2) ?></span>
                  </div>
                  <div class="d-flex gap-2 flex-wrap mb-2">
                    <?php if ($fechaInicio): ?>
                      <span class="badge bg-success text-white">Inicia <?= date('d/m/Y', strtotime($fechaInicio)) ?></span>
                    <?php endif; ?>
                    <?php if ($fechaFin): ?>
                      <span class="badge bg-danger text-white">Caduca <?= date('d/m/Y', strtotime($fechaFin)) ?></span>
                    <?php endif; ?>
                  </div>
                  <p class="mb-1 text-muted small"><?= !empty($promo['descripcion']) ? esc($promo['descripcion']) : 'Sin descripción.' ?></p>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-muted small">No hay promociones activas.</div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <h6 class="mb-3 text-uppercase small text-secondary">Crear nueva promoción</h6>
            <form action="<?= base_url('servicios/guardar-promocion') ?>" method="post">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre_promocion" class="form-control" value="<?= old('nombre_promocion') ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion_promocion" class="form-control" rows="3"><?= old('descripcion_promocion') ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Precio</label>
                <input type="number" name="precio_promocion" class="form-control" step="0.01" value="<?= old('precio_promocion') ?>" required>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Fecha de inicio</label>
                  <input type="date" name="fecha_inicio_promocion" class="form-control" value="<?= old('fecha_inicio_promocion') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Fecha de caducidad</label>
                  <input type="date" name="fecha_fin_promocion" class="form-control" value="<?= old('fecha_fin_promocion') ?>">
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100">Guardar promoción</button>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
