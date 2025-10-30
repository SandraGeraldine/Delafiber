<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="ti-receipt me-2"></i>Cotización #<?= $cotizacion['idcotizacion'] ?>
                    </h4>
                    <div>
                        <a href="<?= base_url('cotizaciones/pdf/' . $cotizacion['idcotizacion']) ?>" 
                           class="btn btn-outline-danger me-2" target="_blank">
                            <i class="ti-download me-1"></i>Descargar PDF
                        </a>
                        <?php if ($cotizacion['estado'] === 'Borrador' || $cotizacion['estado'] === 'Enviada'): ?>
                            <a href="<?= base_url('cotizaciones/edit/' . $cotizacion['idcotizacion']) ?>" 
                               class="btn btn-primary">
                                <i class="ti-pencil me-1"></i>Editar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Información del Cliente -->
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="ti-user me-2"></i>Información del Cliente
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Nombre:</strong></div>
                                        <div class="col-sm-8"><?= esc($cotizacion['cliente_nombre']) ?></div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Teléfono:</strong></div>
                                        <div class="col-sm-8">
                                            <a href="tel:<?= esc($cotizacion['cliente_telefono']) ?>" class="text-decoration-none">
                                                <?= esc($cotizacion['cliente_telefono']) ?>
                                            </a>
                                        </div>
                                    </div>
                                    <?php if (!empty($cotizacion['cliente_correo'])): ?>
                                        <hr class="my-2">
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Email:</strong></div>
                                            <div class="col-sm-8">
                                                <a href="mailto:<?= esc($cotizacion['cliente_correo']) ?>" class="text-decoration-none">
                                                    <?= esc($cotizacion['cliente_correo']) ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <hr class="my-2">
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Lead ID:</strong></div>
                                        <div class="col-sm-8">
                                            <a href="<?= base_url('leads/view/' . $cotizacion['idlead']) ?>" class="text-decoration-none">
                                                #<?= $cotizacion['idlead'] ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Servicio -->
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="ti-package me-2"></i>Servicio Cotizado
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Servicio:</strong></div>
                                        <div class="col-sm-8"><?= esc($cotizacion['servicio_nombre'] ?? ($cotizacion['detalles'][0]['servicio_nombre'] ?? '-')) ?></div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Velocidad:</strong></div>
                                        <div class="col-sm-8">
                                            <span class="badge bg-info"><?= esc($cotizacion['velocidad'] ?? ($cotizacion['detalles'][0]['servicio_velocidad'] ?? ($cotizacion['detalles'][0]['velocidad'] ?? '-'))) ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($cotizacion['servicio_descripcion'])): ?>
                                        <hr class="my-2">
                                        <div class="row">
                                            <div class="col-12">
                                                <strong>Descripción:</strong>
                                                <p class="mt-1 mb-0 text-muted small">
                                                    <?= esc($cotizacion['servicio_descripcion']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles de la Cotización -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="ti-calculator me-2"></i>Detalles de Precios
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>Precio del Servicio (mensual):</strong></td>
                                                    <td class="text-end">
                                                        S/ <?= number_format($cotizacion['precio_cotizado'] ?? 0, 2) ?>
                                                    </td>
                                                </tr>
                                                <?php if (($cotizacion['descuento_aplicado'] ?? 0) > 0): ?>
                                                    <tr class="text-success">
                                                        <td><strong>Descuento aplicado:</strong></td>
                                                        <td class="text-end">
                                                            -<?= $cotizacion['descuento_aplicado'] ?>%
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr>
                                                    <td><strong>Precio de Instalación:</strong></td>
                                                    <td class="text-end">
                                                        S/ <?= number_format($cotizacion['precio_instalacion'] ?? 0, 2) ?>
                                                    </td>
                                                </tr>
                                                <tr class="border-top">
                                                    <td><strong class="text-primary">Total Primer Mes:</strong></td>
                                                    <td class="text-end">
                                                        <strong class="text-primary fs-5">
                                                            S/ <?= number_format(($cotizacion['precio_cotizado'] ?? 0) + ($cotizacion['precio_instalacion'] ?? 0), 2) ?>
                                                        </strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Mensualidad siguiente:</strong></td>
                                                    <td class="text-end">
                                                        <strong>S/ <?= number_format($cotizacion['precio_cotizado'] ?? 0, 2) ?></strong>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body text-center">
                                                    <h3 class="card-title">
                                                        S/ <?= number_format(($cotizacion['precio_cotizado'] ?? 0) + ($cotizacion['precio_instalacion'] ?? 0), 2) ?>
                                                    </h3>
                                                    <p class="card-text">Total Primer Mes</p>
                                                    <small class="opacity-75">
                                                        Luego S/ <?= number_format($cotizacion['precio_cotizado'] ?? 0, 2) ?>/mes
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado y Vigencia -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="ti-info-alt me-2"></i>Estado de la Cotización
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $badgeClass = [
                                        'Borrador' => 'bg-secondary',
                                        'Enviada' => 'bg-info',
                                        'Aceptada' => 'bg-success',
                                        'Rechazada' => 'bg-danger'
                                    ];
                                    ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge <?= $badgeClass[$cotizacion['estado']] ?? 'bg-secondary' ?> me-2">
                                            <?= esc($cotizacion['estado']) ?>
                                        </span>
                                        <?php if ($cotizacion['estado'] === 'Borrador'): ?>
                                            <small class="text-muted">Cotización en borrador</small>
                                        <?php elseif ($cotizacion['estado'] === 'Enviada'): ?>
                                            <small class="text-info">Cotización enviada al cliente</small>
                                        <?php elseif ($cotizacion['estado'] === 'Aceptada'): ?>
                                            <small class="text-success">¡Cotización aceptada por el cliente!</small>
                                        <?php elseif ($cotizacion['estado'] === 'Rechazada'): ?>
                                            <small class="text-danger">Cotización rechazada</small>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($cotizacion['estado'] === 'Borrador' || $cotizacion['estado'] === 'Enviada'): ?>
                                        <div class="d-grid gap-2">
                                            <?php if ($cotizacion['estado'] === 'Borrador'): ?>
                                                <button class="btn btn-info" onclick="cambiarEstado('Enviada')">
                                                    <i class="ti-email me-1"></i>Marcar como Enviada
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($cotizacion['estado'] === 'Enviada'): ?>
                                                <button class="btn btn-success" onclick="cambiarEstado('Aceptada')">
                                                    <i class="ti-check me-1"></i>Marcar como Aceptada
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="cambiarEstado('Rechazada')">
                                                    <i class="ti-close me-1"></i>Marcar como Rechazada
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="ti-calendar me-2"></i>Fechas Importantes
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Creada:</strong></div>
                                        <div class="col-sm-7">
                                            <?= date('d/m/Y H:i', strtotime($cotizacion['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Vigencia:</strong></div>
                                        <div class="col-sm-7">
                                            <?= $cotizacion['vigencia_dias'] ?? 30 ?> días
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-5"><strong>Vence:</strong></div>
                                        <div class="col-sm-7">
                                            <?php
                                            $vigenciaDias = $cotizacion['vigencia_dias'] ?? 30;
                                            $fechaVencimiento = date('Y-m-d', strtotime($cotizacion['created_at'] . ' + ' . $vigenciaDias . ' days'));
                                            $diasRestantes = (strtotime($fechaVencimiento) - time()) / (60 * 60 * 24);
                                            ?>
                                            <?= date('d/m/Y', strtotime($fechaVencimiento)) ?>
                                            <?php if ($cotizacion['estado'] === 'Borrador' || $cotizacion['estado'] === 'Enviada'): ?>
                                                <br>
                                                <?php if ($diasRestantes > 0): ?>
                                                    <small class="text-warning">
                                                        (<?= ceil($diasRestantes) ?> días restantes)
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-danger">(Vencida)</small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <?php if (!empty($cotizacion['observaciones'])): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="ti-note me-2"></i>Observaciones
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0"><?= nl2br(esc($cotizacion['observaciones'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Botones de acción -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?= base_url('cotizaciones') ?>" class="btn btn-secondary">
                                    <i class="ti-arrow-left me-1"></i>Volver a Cotizaciones
                                </a>
                                <div>
                                    <a href="<?= base_url('leads/view/' . $cotizacion['idlead']) ?>" class="btn btn-outline-info me-2">
                                        <i class="ti-eye me-1"></i>Ver Lead
                                    </a>
                                    <a href="<?= base_url('cotizaciones/pdf/' . $cotizacion['idcotizacion']) ?>" 
                                       class="btn btn-outline-danger" target="_blank">
                                        <i class="ti-printer me-1"></i>Imprimir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cambiarEstado(nuevoEstado) {
    const acciones = {
        'Enviada': 'enviar',
        'Aceptada': 'aceptar',
        'Rechazada': 'rechazar'
    };
    const accion = acciones[nuevoEstado] || 'cambiar';
    
    if (!confirm(`¿Está seguro de ${accion} esta cotización?`)) {
        return;
    }

    fetch(`<?= base_url('cotizaciones/cambiarEstado/' . $cotizacion['idcotizacion']) ?>`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `estado=${nuevoEstado}`
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
        console.error('Error:', error);
        alert('Error al cambiar el estado');
    });
}
</script>

<?= $this->endSection() ?>
