<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header header-corporativo">
                    <h4 class="mb-0">
                        <i class="ti-receipt me-2"></i>Nueva Cotización
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

                    <form action="<?= base_url('cotizaciones/store') ?>" method="post" id="form-cotizacion">
                        <div class="row">
                            <!-- Selección de Lead -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="idlead" class="form-label">Cliente (Lead) <span class="text-danger">*</span></label>
                                    
                                    <?php if (isset($lead_seleccionado) && $lead_seleccionado): ?>
                                        <!-- Lead preseleccionado -->
                                        <div class="card bg-light">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?= esc($lead_seleccionado['nombres'] . ' ' . $lead_seleccionado['apellidos']) ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="ti-phone me-1"></i><?= esc($lead_seleccionado['telefono']) ?>
                                                            <?php if (!empty($lead_seleccionado['correo'])): ?>
                                                                | <i class="ti-email me-1"></i><?= esc($lead_seleccionado['correo']) ?>
                                                            <?php endif; ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-info">
                                                            Etapa: <?= esc($lead_seleccionado['etapa_nombre']) ?>
                                                        </small>
                                                        <?php if (!empty($lead_seleccionado['plan_interes'])): ?>
                                                        <br>
                                                        <small class="badge badge-success">
                                                            <i class="ti-star"></i> Interés: <?= esc($lead_seleccionado['plan_interes']) ?>
                                                        </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cambiarLead()">
                                                        <i class="ti-pencil"></i> Cambiar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="idlead" name="idlead" value="<?= $lead_seleccionado['idlead'] ?>">
                                        
                                        <!-- Select oculto para cambiar lead -->
                                        <select class="form-select d-none" id="idlead-select">
                                            <option value="">Seleccionar cliente...</option>
                                            <?php foreach ($leads as $lead): ?>
                                                <option value="<?= $lead['idlead'] ?>" 
                                                        <?= $lead['idlead'] == $lead_seleccionado['idlead'] ? 'selected' : '' ?>>
                                                    <?= esc($lead['lead_nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <!-- Selector con búsqueda Select2 -->
                                        <select class="form-select" id="idlead" name="idlead" required>
                                            <option value="">Seleccionar cliente...</option>
                                        </select>
                                    <?php endif; ?>
                                    
                                    <small class="form-text text-muted">
                                        <i class="ti-search"></i> Escribe para buscar por nombre, teléfono o DNI
                                    </small>
                                </div>
                            </div>

                            <!-- Selección de Servicio -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="idservicio" class="form-label">Servicio <span class="text-danger">*</span></label>
                                    <select class="form-select" id="idservicio" name="idservicio" required>
                                        <option value="">Seleccionar servicio...</option>
                                        <?php foreach ($servicios as $servicio): ?>
                                            <option value="<?= $servicio['idservicio'] ?>" 
                                                    data-precio="<?= $servicio['precio'] ?? 0 ?>"
                                                    data-categoria="<?= $servicio['categoria'] ?? '' ?>"
                                                    <?= old('idservicio') == $servicio['idservicio'] ? 'selected' : '' ?>>
                                                <?= esc($servicio['nombre']) ?> 
                                                <?php if (!empty($servicio['descripcion'])): ?>
                                                    - <?= esc($servicio['descripcion']) ?>
                                                <?php endif; ?>
                                                (S/ <?= number_format($servicio['precio'] ?? 0, 2) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Precio Cotizado -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="precio_cotizado" class="form-label">Precio Cotizado (S/) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="precio_cotizado" name="precio_cotizado" 
                                           step="0.01" min="0" value="<?= old('precio_cotizado') ?>" required>
                                    <small class="form-text text-muted">
                                        Se completará automáticamente al seleccionar servicio
                                    </small>
                                </div>
                            </div>

                            <!-- Descuento -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="descuento_aplicado" class="form-label">Descuento (%)</label>
                                    <input type="number" class="form-control" id="descuento_aplicado" name="descuento_aplicado" 
                                           step="0.01" min="0" max="100" value="<?= old('descuento_aplicado', 0) ?>">
                                    <small class="form-text text-muted">
                                        Porcentaje de descuento a aplicar
                                    </small>
                                </div>
                            </div>

                            <!-- Precio de Instalación -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="precio_instalacion" class="form-label">Precio Instalación (S/)</label>
                                    <input type="number" class="form-control" id="precio_instalacion" name="precio_instalacion" 
                                           step="0.01" min="0" value="<?= old('precio_instalacion', 0) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Vigencia -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vigencia_dias" class="form-label">Vigencia (días)</label>
                                    <input type="number" class="form-control" id="vigencia_dias" name="vigencia_dias" 
                                           min="1" value="<?= old('vigencia_dias', 30) ?>">
                                    <small class="form-text text-muted">
                                        Días de vigencia de la cotización (por defecto 30 días)
                                    </small>
                                </div>
                            </div>

                            <!-- Precio Total (calculado) -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio Total Estimado</label>
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between">
                                                <span>Servicio mensual:</span>
                                                <span id="precio-servicio">S/ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Instalación:</span>
                                                <span id="precio-instalacion-display">S/ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between text-success" id="descuento-display" style="display: none !important;">
                                                <span>Descuento:</span>
                                                <span id="descuento-monto">-S/ 0.00</span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between fw-bold">
                                                <span>Total primer mes:</span>
                                                <span id="precio-total">S/ 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                      placeholder="Notas adicionales sobre la cotización..."><?= old('observaciones') ?></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= base_url('cotizaciones') ?>" class="btn btn-secondary">
                                <i class="ti-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti-check me-1"></i>Crear Cotización
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Scripts de cotizaciones -->
<script src="<?= base_url('js/cotizaciones/cotizaciones-create.js') ?>"></script>

<!-- Script de auto-completado de servicio -->
<?php if (isset($lead_seleccionado) && !empty($lead_seleccionado['plan_interes'])): ?>
<div id="cotizacion-form-container" 
     data-plan-interes="<?= esc($lead_seleccionado['plan_interes']) ?>" 
     style="display:none;"></div>
<script src="<?= base_url('js/cotizaciones/auto-completar-servicio.js') ?>"></script>
<?php endif; ?>

<?= $this->endSection() ?>
