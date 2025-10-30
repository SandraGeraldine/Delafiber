<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

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

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> <strong>Importante:</strong><br>
                Al convertir este lead, se creará automáticamente:
                <ul class="mb-0 mt-2">
                    <li>Registro en tb_personas (si no existe)</li>
                    <li>Registro en tb_clientes</li>
                    <li>Contrato en tb_contratos</li>
                </ul>
            </div>
        </div>

        <!-- Acción de Conversión -->
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle"></i> Crear Contrato en Sistema de Gestión</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Proceso de Contratación:</strong><br>
                        Al hacer clic en el botón de abajo, se abrirá el formulario completo de contratos 
                        en el sistema de gestión con los datos del cliente pre-cargados.
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong><br>
                        Deberás completar la información adicional en el sistema de gestión:

                    </div>

                    <!-- Botón para abrir sistema de gestión -->
                    <div class="text-center p-4">
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
                        
                        <p class="text-muted mt-3">
                            <small>
                                <i class="fas fa-info-circle"></i> 
                                Se abrirá una nueva pestaña con el formulario completo de contratos.
                                Los datos del cliente ya estarán pre-cargados.
                            </small>
                        </p>
                    </div>

                    <hr>

                    <!-- Botón para marcar como convertido manualmente -->
                    <div class="alert alert-secondary">
                        <strong><i class="fas fa-check-circle"></i> Después de crear el contrato:</strong><br>
                        Una vez que hayas completado el contrato en el sistema de gestión, 
                        regresa aquí y marca el lead como convertido:
                        
                        <form action="<?= base_url('leads/marcarConvertido/' . $lead['idlead']) ?>" method="POST" class="mt-3">
                            <?= csrf_field() ?>
                            <input type="number" name="id_contrato" class="form-control mb-2" 
                                   placeholder="Número de contrato creado (opcional)" min="1">
                            <button type="submit" class="btn btn-primary btn-block">
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
