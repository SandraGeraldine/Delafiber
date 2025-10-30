<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización #<?= $cotizacion['idcotizacion'] ?> - Delafiber</title>
    <link rel="stylesheet" href="<?= base_url('css/cotizaciones/cotizacion-pdf.css') ?>">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">DELAFIBER</div>
        <div class="company-tagline">Conectando tu mundo con fibra óptica</div>
        <div class="quote-title">COTIZACIÓN</div>
        <div class="quote-number">#<?= str_pad($cotizacion['idcotizacion'], 6, '0', STR_PAD_LEFT) ?></div>
    </div>

    <!-- Información del Cliente y Cotización -->
    <div class="info-section">
        <div class="info-box">
            <h3>Información del Cliente</h3>
            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <?= esc($cotizacion['cliente_nombre']) ?>
            </div>
            <div class="info-row">
                <span class="info-label">Teléfono:</span>
                <?= esc($cotizacion['cliente_telefono']) ?>
            </div>
            <?php if (!empty($cotizacion['cliente_correo'])): ?>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <?= esc($cotizacion['cliente_correo']) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="info-box">
            <h3>Datos de la Cotización</h3>
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <?= date('d/m/Y', strtotime($cotizacion['created_at'])) ?>
            </div>
            <div class="info-row">
                <span class="info-label">Vigencia:</span>
                <?= $cotizacion['vigencia_dias'] ?> días
            </div>
            <div class="info-row">
                <span class="info-label">Vence:</span>
                <?= date('d/m/Y', strtotime($cotizacion['created_at'] . ' + ' . $cotizacion['vigencia_dias'] . ' days')) ?>
            </div>
            <div class="info-row">
                <span class="info-label">Estado:</span>
                <span class="status-badge status-<?= $cotizacion['estado'] ?>">
                    <?= ucfirst($cotizacion['estado']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Detalles del Servicio -->
    <div class="service-details">
        <div class="service-name"><?= esc($cotizacion['servicio_nombre']) ?></div>
        <div class="service-speed">Velocidad: <?= esc($cotizacion['velocidad']) ?></div>
        <?php if (!empty($cotizacion['servicio_descripcion'])): ?>
            <p><?= esc($cotizacion['servicio_descripcion']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Tabla de Precios -->
    <table class="pricing-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Descripción</th>
                <th class="price">Precio (S/)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Servicio de Internet</td>
                <td>
                    <?= esc($cotizacion['servicio_nombre']) ?> - <?= esc($cotizacion['velocidad']) ?>
                    <br><small>Mensualidad</small>
                </td>
                <td class="price">
                    <?php
                    $precioConDescuento = $cotizacion['precio_cotizado'] ?? 0;
                    $descuentoAplicado = $cotizacion['descuento_aplicado'] ?? 0;
                    if ($descuentoAplicado > 0) {
                        $precioOriginal = $precioConDescuento / (1 - ($descuentoAplicado / 100));
                        echo '<s style="color: #999;">S/ ' . number_format($precioOriginal, 2) . '</s><br>';
                        echo '<span style="color: #28a745;">S/ ' . number_format($precioConDescuento, 2) . '</span>';
                        echo '<br><small style="color: #28a745;">(-' . $descuentoAplicado . '% desc.)</small>';
                    } else {
                        echo 'S/ ' . number_format($precioConDescuento, 2);
                    }
                    ?>
                </td>
            </tr>
            
            <?php if (($cotizacion['precio_instalacion'] ?? 0) > 0): ?>
                <tr>
                    <td>Instalación</td>
                    <td>Costo único de instalación y configuración</td>
                    <td class="price">S/ <?= number_format($cotizacion['precio_instalacion'] ?? 0, 2) ?></td>
                </tr>
            <?php endif; ?>
            
            <tr class="total-row total-highlight">
                <td colspan="2"><strong>TOTAL PRIMER MES</strong></td>
                <td class="price">
                    <strong>S/ <?= number_format(($cotizacion['precio_cotizado'] ?? 0) + ($cotizacion['precio_instalacion'] ?? 0), 2) ?></strong>
                </td>
            </tr>
            
            <tr class="total-row">
                <td colspan="2"><strong>Mensualidad siguiente</strong></td>
                <td class="price">
                    <strong>S/ <?= number_format($cotizacion['precio_cotizado'] ?? 0, 2) ?></strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Observaciones -->
    <?php if (!empty($cotizacion['observaciones'])): ?>
        <div style="margin-bottom: 20px;">
            <h4 style="color: #007bff; margin-bottom: 10px;">Observaciones:</h4>
            <p style="padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
                <?= nl2br(esc($cotizacion['observaciones'])) ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Términos y Condiciones -->
    <div class="terms">
        <h4>Términos y Condiciones:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Esta cotización tiene una vigencia de <?= $cotizacion['vigencia_dias'] ?> días calendario.</li>
            <li>Los precios incluyen IGV y están expresados en Soles Peruanos.</li>
            <li>La instalación se realizará en un plazo de 24 a 48 horas hábiles después de la confirmación.</li>
            <li>El servicio incluye soporte técnico 24/7 y mantenimiento preventivo.</li>
            <li>La velocidad contratada es la velocidad máxima disponible en condiciones óptimas.</li>
            <li>Para proceder con la instalación se requiere el 50% del costo total como adelanto.</li>
        </ul>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>DELAFIBER</strong> | Teléfono: (056) 123-4567 | Email: ventas@delafiber.com</p>
        <p>Chincha Alta, Ica - Perú | www.delafiber.com</p>
        <p style="margin-top: 10px; font-size: 10px;">
            Documento generado el <?= date('d/m/Y H:i:s') ?>
        </p>
    </div>

    <!-- Botón de impresión (solo en pantalla) -->
    <div class="no-print" style="position: fixed; top: 20px; right: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Imprimir / Guardar PDF
        </button>
    </div>
</body>
</html>
