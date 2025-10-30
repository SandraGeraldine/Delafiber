<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('css/personas.css') ?>">

<div class="container-fluid mt-4 custom-container">
  <div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-10">
      <!-- Encabezado -->
      <div class="mb-4 d-flex justify-content-between align-items-center">
        <h3 class="mb-0"><?= isset($persona) ? 'Editar Persona' : 'Registro De Personas' ?></h3>
        <a href="<?= base_url('personas') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="ti-arrow-left me-1"></i> Lista de personas
        </a>
      </div>

      <!-- Formulario -->
      <form action="<?= base_url('personas/guardar') ?>" id="form-persona" method="POST" autocomplete="off" class="w-100">
        <div class="card shadow-sm">
          <div class="card-body">
            <!-- Campo DNI con búsqueda -->
            <div class="form-group mb-3">
              <label for="dni" class="form-label">Buscar DNI</label>
              <small class="d-none text-muted" id="searching">Buscando...</small>
              <div class="input-group">
                <input type="text" class="form-control" name="dni" id="dni"
                       maxlength="8" minlength="8" required autofocus
                       value="<?= esc($persona['dni'] ?? '') ?>">
                <button class="btn btn-outline-success" type="button" id="buscar-dni">
                  <i class="ti-search"></i> Buscar
                </button>
              </div>
            </div>

            <!-- Campos de nombres y apellidos -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="apellidos" class="form-label">Apellidos</label>
                <input type="text" class="form-control" name="apellidos" id="apellidos" required
                       value="<?= esc($persona['apellidos'] ?? '') ?>" readonly>
              </div>
              <div class="col-md-6">
                <label for="nombres" class="form-label">Nombres</label>
                <input type="text" class="form-control" name="nombres" id="nombres" required
                       value="<?= esc($persona['nombres'] ?? '') ?>" readonly>
              </div>
            </div>

            <!-- Correo y teléfono -->
            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" name="correo" id="correo"
                       value="<?= esc($persona['correo'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="telefono" id="telefono"
                       maxlength="9" pattern="[0-9]*" inputmode="numeric"
                       title="Solo se permiten números" required
                       value="<?= esc($persona['telefono'] ?? '') ?>">
              </div>
            </div>

            <!-- Dirección y distrito -->
            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" name="direccion" id="direccion"
                       value="<?= esc($persona['direccion'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label for="iddistrito" class="form-label">Distrito</label>
                <select class="form-select" name="iddistrito" id="iddistrito" required>
                  <option value="">Seleccione...</option>
                  <?php foreach ($distritos as $d): ?>
                    <option value="<?= $d['iddistrito'] ?>"
                      <?= (isset($persona) && $persona['iddistrito'] == $d['iddistrito']) ? 'selected' : '' ?>>
                      <?= esc($d['nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Referencias -->
            <div class="row g-3 mb-3">
              <div class="col-12">
                <label for="referencias" class="form-label">Referencia</label>
                <input type="text" class="form-control" name="referencias" id="referencias"
                       value="<?= esc($persona['referencias'] ?? '') ?>">
              </div>
            </div>

            <!-- Campo oculto para ID -->
            <input type="hidden" name="idpersona" value="<?= esc($persona['idpersona'] ?? '') ?>">
          </div>

          <!-- Footer del card con botones -->
          <div class="card-footer text-end bg-transparent">
            <button class="btn btn-outline-secondary btn-sm me-2" type="reset">
              <i class="ti-trash me-1"></i> Limpiar
            </button>
            <button class="btn btn-primary btn-sm" type="submit">
              <i class="ti-save me-1"></i> Guardar
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Contenedor para modales -->
<div id="modalContainer"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= base_url('js/personas/personas-crear.js') ?>"></script>
<script src="<?= base_url('js/personas/personas.js') ?>"></script>
<?= $this->endSection() ?>
