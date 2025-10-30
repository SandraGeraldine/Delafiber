<?= $this->extend('Layouts/base') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/personas.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/personas/personas-index.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-center py-4">
  <div class="main-card p-4 mx-auto">
    <!-- Encabezado con título y botón -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="mb-0">Personas</h3>
        <div class="small-muted">Listado de contactos registrados</div>
      </div>
      <a href="<?= base_url('personas/crear') ?>" class="btn btn-primary">
        <i class="ti-plus me-1"></i> Crear persona
      </a>
    </div>

    <!-- Contador de registros -->
    <?php if (!empty($personas)): ?>
    <div class="records-counter">
      <i class="ti-user"></i>
      Mostrando <strong><?= count($personas) ?></strong> persona<?= count($personas) != 1 ? 's' : '' ?> registrada<?= count($personas) != 1 ? 's' : '' ?>
    </div>
    <?php endif; ?>

    <!-- Formulario de búsqueda -->
    <form class="mb-4" method="get" action="<?= base_url('personas') ?>">
      <div class="input-group">
        <input name="q" value="<?= esc($q ?? '') ?>" class="form-control"
               placeholder="Buscar por nombre, DNI, teléfono o correo" autocomplete="off">
        <button class="btn btn-outline-secondary" type="submit">
          <i class="ti-search"></i> Buscar
        </button>
      </div>
    </form>

    <!-- Tabla de personas -->
    <div class="table-responsive">
      <table class="table table-sm table-striped table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th width="5%">#</th>
            <th width="30%">Contacto</th>
            <th width="10%">DNI</th>
            <th width="15%">Teléfono</th>
            <th width="20%">Correo</th>
            <th width="20%" class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($personas)):
            $colors = ['#8e44ad','#2980b9','#16a085','#e67e22','#c0392b'];
            foreach ($personas as $p):
              $color = $colors[$p['idpersona'] % count($colors)];
          ?>
            <tr>
              <td><?= esc($p['idpersona']) ?></td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <div class="person-avatar" style="background:<?= $color ?>">
                      <?= strtoupper(substr($p['nombres'], 0, 1) . (isset($p['apellidos'][0]) ? substr($p['apellidos'], 0, 1) : '')) ?>
                    </div>
                  </div>
                  <div>
                    <div class="fw-bold"><?= esc($p['nombres']) . ' ' . esc($p['apellidos']) ?></div>
                    <div class="small-muted"><?= esc($p['direccion'] ?? '') ?></div>
                  </div>
                </div>
              </td>
              <td><?= esc($p['dni']) ?></td>
              <td>
                <?php if (!empty($p['telefono'])): ?>
                  <a href="tel:<?= esc($p['telefono']) ?>" class="text-decoration-none">
                    <?= esc($p['telefono']) ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted">Sin teléfono</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($p['correo'])): ?>
                  <a href="mailto:<?= esc($p['correo']) ?>" class="text-decoration-none">
                    <?= esc($p['correo']) ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted">Sin correo</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="btn-group btn-group-actions" role="group">
                  <!-- Botón Editar -->
                  <a href="<?= base_url('personas/editar/' . $p['idpersona']) ?>"
                     class="btn btn-sm btn-outline-warning">
                    <i class="ti-pencil-alt"></i> Editar
                  </a>

                  <!-- Botón Eliminar (con SweetAlert) -->
                  <button type="button"
                          class="btn btn-sm btn-outline-danger btn-eliminar"
                          data-id="<?= $p['idpersona'] ?>"
                          data-nombre="<?= esc($p['nombres'] . ' ' . $p['apellidos']) ?>">
                    <i class="ti-trash"></i> Eliminar
                  </button>

                  <!-- Botón Convertir a Lead -->
                  <button type="button"
                          class="btn btn-sm btn-success btn-convertir-lead"
                          data-id="<?= $p['idpersona'] ?>"
                          data-nombre="<?= esc($p['nombres'] . ' ' . $p['apellidos']) ?>"
                          title="Convertir a Lead">
                    <i class="ti-arrow-right"></i> Convertir a Lead
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr>
              <td colspan="6" class="text-center py-4 small-muted">
                <i class="ti-face-sad me-1"></i> No hay personas registradas.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Contenedor para modales dinámicos -->
<div id="modalContainer"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/personas/personas-index.js') ?>"></script>
<script src="<?= base_url('js/personas/personas.js') ?>"></script>
<?= $this->endSection() ?>
