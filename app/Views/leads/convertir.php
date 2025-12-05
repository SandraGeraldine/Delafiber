<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<style>
    .conversion-card .card-header {
        background: linear-gradient(135deg, #5b0bd6, #b03dfb);
        color: #fff;
        border: none;
        box-shadow: 0 12px 30px rgba(91, 11, 214, 0.35);
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
    }
    .conversion-card .card-body {
        border: 1px solid #dcdcdc;
        border-radius: 0 0 12px 12px;
        background: #fff;
    }
    .conversion-card .info-block {
        border-radius: 12px;
        border: 1px solid #f0f0f0;
        padding: 18px;
        background: linear-gradient(180deg, #ffffff, #f7f1ff);
        color: #2c2c2c;
        margin-bottom: 1rem;
    }
    .conversion-card .step-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 0.25rem 0.9rem;
        font-weight: 600;
        font-size: 0.85rem;
        color: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .step-pill.primary {
        background: linear-gradient(135deg, #3c8dbc, #1e90ff);
    }
    .step-pill.secondary {
        background: linear-gradient(135deg, #6c63ff, #d23bed);
    }
    .conversion-card .btn-success {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        border-color: transparent;
        box-shadow: 0 10px 20px rgba(39, 174, 96, 0.35);
    }
    .conversion-card .btn-primary {
        background: #4b1487;
        border-color: transparent;
        font-weight: 600;
    }
    .conversion-card .details-summary {
        font-weight: 600;
        color: #6c63ff;
    }
    .conversion-card .map-fallback {
        border-radius: 8px;
        border: 1px dashed #bbb;
        padding: 12px;
        font-size: 0.85rem;
        background: #f9f9ff;
    }
</style>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('leads') ?>">Leads</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('leads/view/' . $lead['idlead']) ?>">Lead #<?= $lead['idlead'] ?></a></li>
                    <li class="breadcrumb-item active">Convertir a Cliente</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <!-- Información del Lead -->
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Información del Lead</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Cliente:</strong><br>
                        <?= esc($lead['nombres'] . ' ' . $lead['apellidos']) ?>
                    </div>
                    <div class="mb-3">
                        <strong>DNI:</strong><br>
                        <?= esc($lead['dni']) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Teléfono:</strong><br>
                        <?= esc($lead['telefono']) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Correo:</strong><br>
                        <?= esc($lead['correo'] ?? 'No especificado') ?>
                    </div>
                    <div class="mb-3">
                        <strong>Dirección:</strong><br>
                        <?= esc($lead['direccion']) ?>
                    </div>
                    <?php if (!empty($lead['referencias'])): ?>
                    <div class="mb-3">
                        <strong>Referencias:</strong><br>
                        <?= esc($lead['referencias']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($lead['coordenadas_servicio'])): ?>
                    <div class="mb-3">
                        <strong>Coordenadas GPS:</strong><br>
                        <small class="text-muted"><?= esc($lead['coordenadas_servicio']) ?></small>
                        <div id="mapPreview" style="height: 250px; margin-top: 10px; border-radius: 5px;"></div>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> Vista previa de ubicación. 
                            La validación de cobertura se realizará en el sistema de gestión.
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

           <!--  <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> <strong>Importante:</strong><br>
                Al convertir este lead, se creará automáticamente:
                <ul class="mb-0 mt-2">
                    <li>Registro en tb_personas (si no existe)</li>
                    <li>Registro en tb_clientes</li>
                    <li>Contrato en tb_contratos</li>
                </ul>
            </div> -->
        </div>

        <!-- Acción de Conversión -->
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle"></i> Crear Contrato en Sistema de Gestión</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>Proceso de contratación</strong>
                                <p class="mb-1 text-muted small">
                                    Al abrir el contrato en el sistema de gestión se precargarán tus datos; completa
                                    la información adicional ahí y regresa para confirmar la conversión.
                                </p>
                            </div>
                            <div class="text-end">
                                <span class="badge rounded-pill bg-primary text-white me-1">Paso 1</span>
                                <span class="badge rounded-pill bg-secondary text-white">Paso 2</span>
                            </div>
                        </div>
                        <details class="small text-muted mt-2">
                            <summary class="text-decoration-underline text-primary" role="button">
                                Más detalles del flujo
                            </summary>
                            <p class="mt-2 mb-0">
                                Completa la información adicional del contrato en el GST. Una vez listo, regresa a esta
                                pantalla y marca el lead como convertido para que pueda avanzar a cierre.
                            </p>
                        </details>
                    </div>

                    <!-- Botón para abrir sistema de gestión -->
                    <div class="text-center p-3 mb-3 bg-light rounded">
                        <?php
                        // Preparar datos para enviar al sistema de gestión
                        $params = http_build_query([
                            'dni' => $lead['dni'],
                            'nombres' => $lead['nombres'],
                            'apellidos' => $lead['apellidos'],
                            'telefono' => $lead['telefono'],
                            'correo' => $lead['correo'] ?? '',
                            'direccion' => $lead['direccion'],
                            'referencia' => $lead['referencias'] ?? '',
                            'coordenadas' => $lead['coordenadas_servicio'] ?? '',
                            'from_crm' => 'true',
                            'lead_id' => $lead['idlead']
                        ]);
                        $urlGestion = "http://gst.delafiber.com/public/views/Contratos/index?" . $params;
                        ?>
                        
                        <a href="<?= $urlGestion ?>" 
                           target="_blank" 
                           class="btn btn-success btn-lg"
                           style="font-size: 1.2rem; padding: 15px 40px;">
                            <i class="fas fa-external-link-alt"></i> 
                            Abrir Formulario de Contrato en Sistema de Gestión
                        </a>
                        
                        <p class="text-muted small mt-2">
                            <i class="fas fa-info-circle"></i> El formulario se abre en otra pestaña con los datos precargados.
                        </p>
                    </div>
                    <div class="mb-2 text-muted small">
                        <i class="fas fa-info-circle"></i> Después de cerrar el contrato en el GST, vuelve aquí y confirma.
                    </div>

                    <!-- Botón para marcar como convertido manualmente -->
                    <form action="<?= base_url('leads/marcarConvertido/' . $lead['idlead']) ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="form-floating mb-3">
                            <input type="number" name="id_contrato" class="form-control" 
                                   id="idContrato" placeholder="Contrato #" min="1">
                            <label for="idContrato">Número de contrato (opcional)</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-check"></i> Marcar Lead como Convertido
                        </button>
                    </form>
                </div>

                    <div class="form-group mt-3">
                        <a href="<?= base_url('leads/view/' . $lead['idlead']) ?>" class="btn btn-secondary btn-lg btn-block">
                            <i class="fas fa-arrow-left"></i> Volver al Lead
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAACo2qyElsl8RwIqW3x0peOA_20f7SEHA"></script>
<script>
$(document).ready(function() {
    // Inicializar mapa de vista previa si hay coordenadas
    <?php if (!empty($lead['coordenadas_servicio'])): ?>
    const coordenadas = '<?= $lead['coordenadas_servicio'] ?>';
    if (coordenadas) {
        const coords = coordenadas.split(',');
        const lat = parseFloat(coords[0].trim());
        const lng = parseFloat(coords[1].trim());
        
        if (!isNaN(lat) && !isNaN(lng)) {
            const mapPreview = new google.maps.Map(document.getElementById('mapPreview'), {
                center: { lat: lat, lng: lng },
                zoom: 16,
                mapTypeId: 'roadmap'
            });
            
            // Marcador de ubicación del cliente
            new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: mapPreview,
                title: 'Ubicación del cliente',
                icon: {
                    url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                }
            });
            
            // Círculo de referencia (radio aproximado de cobertura)
            new google.maps.Circle({
                strokeColor: '#4285F4',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#4285F4',
                fillOpacity: 0.15,
                map: mapPreview,
                center: { lat: lat, lng: lng },
                radius: 100 // 100 metros de referencia
            });
        }
    }
    <?php endif; ?>
    
    // Confirmación antes de enviar
    $('#formConvertir').on('submit', function(e) {
        e.preventDefault();
        
        const paquete = $('#id_paquete option:selected').text();
        const sector = $('#id_sector option:selected').text();
        
        Swal.fire({
            title: '¿Confirmar conversión?',
            html: `
                <div class="text-left">
                    <p><strong>Cliente:</strong> <?= esc($lead['nombres'] . ' ' . $lead['apellidos']) ?></p>
                    <p><strong>Paquete:</strong> ${paquete}</p>
                    <p><strong>Sector:</strong> ${sector}</p>
                    <hr>
                    <p class="text-danger"><small>Esta acción creará un contrato en el sistema de gestión y no se puede deshacer.</small></p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, convertir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Creando contrato en el sistema de gestión',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Enviar formulario
                this.submit();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
