<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="ti-package me-2"></i>
                        <?= isset($servicio) && $servicio ? 'Editar Servicio' : 'Nuevo Servicio' ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (session()->getFlashdata('errors')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= isset($servicio) && $servicio ? base_url('servicios/update/' . $servicio['idservicio']) : base_url('servicios/store') ?>" 
                          method="post" id="form-servicio">
                        
                        <div class="row">
                            <!-- Información básica -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">Información del Servicio</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Nombre del servicio -->
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre del Servicio <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                                   value="<?= old('nombre', $servicio['nombre'] ?? '') ?>" 
                                                   placeholder="Ej: Fibra Premium, Internet Básico..." required>
                                            <small class="form-text text-muted">
                                                Nombre comercial del servicio que verán los clientes
                                            </small>
                                        </div>

                                        <!-- Velocidad -->
                                        <div class="mb-3">
                                            <label for="velocidad" class="form-label">Velocidad <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="velocidad" name="velocidad" 
                                                   value="<?= old('velocidad', $servicio['velocidad'] ?? '') ?>" 
                                                   placeholder="Ej: 100 Mbps, 50/10 Mbps..." required>
                                            <small class="form-text text-muted">
                                                Velocidad de descarga/subida del servicio
                                            </small>
                                        </div>

                                        <!-- Descripción -->
                                        <div class="mb-3">
                                            <label for="descripcion" class="form-label">Descripción</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                                      placeholder="Descripción detallada del servicio, características, beneficios..."><?= old('descripcion', $servicio['descripcion'] ?? '') ?></textarea>
                                            <small class="form-text text-muted">
                                                Descripción que aparecerá en las cotizaciones
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Precios y configuración -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">Precios y Configuración</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Precio mensual -->
                                        <div class="mb-3">
                                            <label for="precio_referencial" class="form-label">Precio Mensual (S/) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" class="form-control" id="precio_referencial" name="precio_referencial" 
                                                       step="0.01" min="0" value="<?= old('precio_referencial', $servicio['precio_referencial'] ?? '') ?>" 
                                                       required>
                                            </div>
                                            <small class="form-text text-muted">
                                                Precio de referencia mensual
                                            </small>
                                        </div>

                                        <!-- Precio de instalación -->
                                        <div class="mb-3">
                                            <label for="precio_instalacion" class="form-label">Precio Instalación (S/)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" class="form-control" id="precio_instalacion" name="precio_instalacion" 
                                                       step="0.01" min="0" value="<?= old('precio_instalacion', $servicio['precio_instalacion'] ?? '0') ?>">
                                            </div>
                                            <small class="form-text text-muted">
                                                Costo único de instalación (opcional)
                                            </small>
                                        </div>

                                        <!-- Estado activo -->
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                                       <?= old('activo', $servicio['activo'] ?? '1') ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="activo">
                                                    Servicio activo
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Solo los servicios activos aparecen en cotizaciones
                                            </small>
                                        </div>

                                        <!-- Preview de precios -->
                                        <div class="card bg-light">
                                            <div class="card-body py-2">
                                                <h6 class="card-title mb-2">Vista Previa</h6>
                                                <div class="d-flex justify-content-between">
                                                    <span>Mensual:</span>
                                                    <span class="fw-bold" id="preview-mensual">S/ 0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Instalación:</span>
                                                    <span id="preview-instalacion">S/ 0.00</span>
                                                </div>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bold">Primer mes:</span>
                                                    <span class="fw-bold text-primary" id="preview-total">S/ 0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="<?= base_url('servicios') ?>" class="btn btn-secondary">
                                        <i class="ti-arrow-left me-1"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti-check me-1"></i>
                                        <?= isset($servicio) && $servicio ? 'Actualizar Servicio' : 'Crear Servicio' ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const precioMensualInput = document.getElementById('precio_referencial');
    const precioInstalacionInput = document.getElementById('precio_instalacion');

    // Actualizar preview cuando cambien los precios
    [precioMensualInput, precioInstalacionInput].forEach(input => {
        input.addEventListener('input', actualizarPreview);
    });

    function actualizarPreview() {
        const precioMensual = parseFloat(precioMensualInput.value) || 0;
        const precioInstalacion = parseFloat(precioInstalacionInput.value) || 0;
        const total = precioMensual + precioInstalacion;

        document.getElementById('preview-mensual').textContent = `S/ ${precioMensual.toFixed(2)}`;
        document.getElementById('preview-instalacion').textContent = `S/ ${precioInstalacion.toFixed(2)}`;
        document.getElementById('preview-total').textContent = `S/ ${total.toFixed(2)}`;
    }

    // Preview inicial
    actualizarPreview();

    // Validación del formulario
    document.getElementById('form-servicio').addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre').value.trim();
        const velocidad = document.getElementById('velocidad').value.trim();
        const precio = parseFloat(document.getElementById('precio_referencial').value);

        if (!nombre || !velocidad) {
            e.preventDefault();
            alert('Por favor complete todos los campos obligatorios');
            return false;
        }

        if (precio <= 0) {
            e.preventDefault();
            alert('El precio mensual debe ser mayor a 0');
            return false;
        }
    });

    // Auto-generar nombre basado en velocidad (opcional)
    document.getElementById('velocidad').addEventListener('blur', function() {
        const nombreInput = document.getElementById('nombre');
        const velocidad = this.value.trim();
        
        if (!nombreInput.value && velocidad) {
            // Sugerir nombre basado en velocidad
            let sugerencia = '';
            const mbps = parseInt(velocidad);
            
            if (mbps <= 50) {
                sugerencia = 'Fibra Básica';
            } else if (mbps <= 100) {
                sugerencia = 'Fibra Estándar';
            } else if (mbps <= 200) {
                sugerencia = 'Fibra Premium';
            } else {
                sugerencia = 'Fibra Ultra';
            }
            
            nombreInput.value = `${sugerencia} ${velocidad}`;
        }
    });
});
</script>

<?= $this->endSection() ?>
