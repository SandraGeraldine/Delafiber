<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header header-corporativo">
                <h4 class="card-title mb-0">
                    <i class="ti-clipboard"></i> Registro de Lead
                </h4>
            </div>
            <div class="card-body">

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php $errors = session()->getFlashdata('errors') ?? []; ?>

                <form action="<?= base_url('leads/store') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- DNI CON BOTÓN DE BÚSQUEDA -->
                    <div class="mb-3">
                        <label for="dni" class="form-label fw-bold">DNI</label>
                        <div class="input-group">
                            <input type="text" name="dni" id="dni" maxlength="8" 
                                   class="form-control <?= isset($errors['dni']) ? 'is-invalid' : '' ?>" 
                                   value="<?= old('dni') ?>" required>
                            <button type="button" class="btn btn-outline-secondary" id="btn-buscar-dni">
                                BUSCAR
                            </button>
                        </div>
                        <?php if (isset($errors['dni'])): ?>
                            <div class="invalid-feedback d-block"><?= $errors['dni'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- DIRECCIÓN -->
                    <div class="mb-3">
                        <label for="direccion" class="form-label fw-bold">DIRECCION</label>
                        <input type="text" name="direccion" id="direccion" 
                               class="form-control <?= isset($errors['direccion']) ? 'is-invalid' : '' ?>" 
                               value="<?= old('direccion') ?>" required>
                        <?php if (isset($errors['direccion'])): ?>
                            <div class="invalid-feedback"><?= $errors['direccion'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- NÚMEROS DE CONTACTOS -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">NUMEROS DE CONTACTOS</label>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <input type="text" name="telefono1" id="telefono1" maxlength="9" 
                                       class="form-control" placeholder="Teléfono 1" 
                                       value="<?= old('telefono1') ?>" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" name="telefono2" id="telefono2" maxlength="9" 
                                       class="form-control" placeholder="Teléfono 2" 
                                       value="<?= old('telefono2') ?>">
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" name="telefono3" id="telefono3" maxlength="9" 
                                       class="form-control" placeholder="Teléfono 3" 
                                       value="<?= old('telefono3') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- POSIBLE PLAN -->
                    <div class="mb-3">
                        <label for="plan_interes" class="form-label fw-bold">POSIBLE PLAN</label>
                        <select name="plan_interes" id="plan_interes" 
                                class="form-control <?= isset($errors['plan_interes']) ? 'is-invalid' : '' ?>" required>
                            <option value="">Seleccione un plan</option>
                            <?php if (!empty($paquetes)): ?>
                                <?php foreach ($paquetes as $plan): ?>
                                    <option value="<?= esc($plan['id'] ?? $plan['idpaquete'] ?? '') ?>" 
                                            <?= old('plan_interes') == ($plan['id'] ?? $plan['idpaquete'] ?? '') ? 'selected' : '' ?>>
                                        <?= esc($plan['servicio'] ?? $plan['nombre'] ?? '') ?> - S/ <?= esc(number_format($plan['precio'] ?? 0, 2)) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['plan_interes'])): ?>
                            <div class="invalid-feedback"><?= $errors['plan_interes'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- DETALLES -->
                    <div class="mb-3">
                        <label for="detalles" class="form-label fw-bold">DETALLES</label>
                        <textarea name="detalles" id="detalles" rows="4" 
                                  class="form-control" 
                                  placeholder="Ingrese detalles adicionales..."><?= old('detalles') ?></textarea>
                    </div>

                    <!-- UBICACIÓN CON MAPA -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">UBICACION</label>
                        <div class="input-group mb-2">
                            <input type="text" id="coordenadas_mostrar" class="form-control" 
                                   placeholder="Coordenadas GPS" readonly>
                            <button type="button" class="btn btn-outline-secondary" id="btn-obtener-coordenada">
                                BUSCAR
                            </button>
                        </div>
                        <input type="hidden" name="coordenadas_servicio" id="coordenadas_servicio" 
                               value="<?= old('coordenadas_servicio') ?>">
                        
                        <!-- Área para mostrar mapa o previsualización -->
                        <div id="mapa-preview" class="border rounded p-3 text-center bg-light" style="height: 180px; display: flex; align-items: center; justify-content: center;">
                            <span class="text-muted">Mapa de ubicación</span>
                        </div>
                    </div>

                    <!-- FOTO -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btn-foto">
                            <i class="ti-camera"></i> FOTO
                        </button>
                        <input type="file" name="foto" id="foto" class="d-none" accept="image/*">
                        <div id="foto-preview" class="mt-2"></div>
                    </div>

                    <!-- BOTÓN ENVIAR -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary px-5">
                            ENVIAR
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Botón para buscar DNI
        const btnBuscarDni = document.getElementById('btn-buscar-dni');
        if (btnBuscarDni) {
            btnBuscarDni.addEventListener('click', function () {
                const dni = document.getElementById('dni').value;
                if (dni.length === 8) {
                    // Aquí puedes hacer una petición AJAX para buscar datos del DNI
                    console.log('Buscando DNI:', dni);
                    alert('Funcionalidad de búsqueda de DNI - Implementar con API');
                } else {
                    alert('Por favor ingrese un DNI válido de 8 dígitos');
                }
            });
        }

        // Botón para obtener coordenadas GPS
        const btnCoord = document.getElementById('btn-obtener-coordenada');
        const inputCoord = document.getElementById('coordenadas_servicio');
        const inputCoordMostrar = document.getElementById('coordenadas_mostrar');
        const mapaPreview = document.getElementById('mapa-preview');

        if (btnCoord) {
            btnCoord.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    alert('La geolocalización no está soportada en este dispositivo');
                    return;
                }

                btnCoord.disabled = true;
                btnCoord.innerText = 'Obteniendo...';

                navigator.geolocation.getCurrentPosition(function (position) {
                    const lat = position.coords.latitude.toFixed(6);
                    const lng = position.coords.longitude.toFixed(6);
                    const value = lat + ',' + lng;

                    inputCoord.value = value;
                    inputCoordMostrar.value = value;

                    // Actualizar preview del mapa
                    mapaPreview.innerHTML = `
                        <div>
                            <i class="ti-location-pin" style="font-size: 24px;"></i>
                            <p class="mb-0 mt-2"><small>${value}</small></p>
                            <small class="text-success">Ubicación capturada</small>
                        </div>
                    `;

                    btnCoord.disabled = false;
                    btnCoord.innerText = 'BUSCAR';
                }, function (error) {
                    console.error(error);
                    alert('No se pudo obtener la ubicación. Asegúrate de otorgar los permisos necesarios.');
                    btnCoord.disabled = false;
                    btnCoord.innerText = 'BUSCAR';
                });
            });
        }

        // Botón para capturar foto
        const btnFoto = document.getElementById('btn-foto');
        const inputFoto = document.getElementById('foto');
        const fotoPreview = document.getElementById('foto-preview');

        if (btnFoto) {
            btnFoto.addEventListener('click', function () {
                inputFoto.click();
            });
        }

        if (inputFoto) {
            inputFoto.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        fotoPreview.innerHTML = `
                            <img src="${e.target.result}" class="img-fluid rounded" 
                                 style="max-height: 200px;" alt="Preview">
                            <p class="text-success mt-2 mb-0"><small>Foto cargada: ${file.name}</small></p>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>
