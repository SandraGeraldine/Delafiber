<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="<?= base_url('css/leads/create.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/leads/toast-notifications.css') ?>">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Estilos personalizados para Select2 */
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        color: #495057;
        padding-left: 12px;
        padding-right: 20px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 8px;
    }
    .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
    .select2-container {
        width: 100% !important;
    }
    .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        width: 100% !important;
        max-width: 100% !important;
    }
    .select2-container--open .select2-dropdown {
        left: 0 !important;
    }
    .select2-search--dropdown {
        padding: 12px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .select2-search--dropdown .select2-search__field {
        border: 2px solid #ced4da;
        border-radius: 0.25rem;
        padding: 10px 15px;
        font-size: 14px;
        width: 100%;
        background-color: white;
    }
    .select2-search--dropdown .select2-search__field:focus {
        border-color: #007bff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
    }
    .select2-search--dropdown .select2-search__field::placeholder {
        color: #6c757d;
        font-style: italic;
    }
    .select2-results {
        max-height: 300px;
        overflow-y: auto;
        padding-top: 0;
        margin-top: 0;
    }
    .select2-results__options {
        max-height: 300px;
        overflow-y: auto;
    }
    .select2-results__option {
        padding: 12px 15px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }
    .select2-results__option:last-child {
        border-bottom: none;
    }
    .select2-results__option--highlighted {
        background-color: #007bff !important;
        color: white !important;
    }
    .select2-results__option:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
</style>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Registrar Nuevo Lead</h4>
                    <a href="<?= base_url('leads') ?>" class="btn btn-outline-secondary">
                        <i class="icon-arrow-left"></i> Volver
                    </a>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Indicador de Progreso -->
                <div class="mb-4">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" id="progressBar" role="progressbar" style="width: 50%"></div>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge badge-primary" id="stepIndicator">Paso 1 de 2</span>
                    </div>
                </div>

                <form id="formLead" action="<?= base_url('leads/store') ?>" method="POST">
                    <?= csrf_field() ?>

                    <!-- ============================================ -->
                    <!-- PASO 1: CLIENTE -->
                    <!-- ============================================ -->
                    <div id="paso1">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="icon-user"></i> Paso 1: Datos del Cliente</h5>
                            </div>
                            <div class="card-body">
                                <!-- Búsqueda rápida -->
                                <div class="alert alert-info mb-4">
                                    <strong><i class="icon-magnifier"></i> ¿Cliente existente?</strong> Busca por teléfono o DNI para evitar duplicados
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="buscar_telefono">Buscar por Teléfono</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="buscar_telefono" 
                                                   placeholder="9 dígitos" maxlength="9">
                                            <div class="input-group-append">
                                                <button class="btn btn-success" type="button" id="btnBuscarTelefono">
                                                    <i class="icon-magnifier"></i> Buscar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="dni">O buscar por DNI</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="dni" name="dni" 
                                                   placeholder="8 dígitos" maxlength="8">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" id="btnBuscarDni">
                                                    <i class="icon-magnifier"></i> Buscar
                                                </button>
                                            </div>
                                        </div>
                                        <div id="dni-loading" class="text-primary mt-2" style="display:none;">
                                            <i class="icon-refresh rotating"></i> Consultando RENIEC...
                                        </div>
                                    </div>
                                </div>

                                <!-- Resultado de búsqueda -->
                                <div id="resultado-busqueda" style="display:none;"></div>

                                <hr>

                                <!-- Formulario de datos -->
                                <input type="hidden" id="idpersona" name="idpersona" value="">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombres">Nombres *</label>
                                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="apellidos">Apellidos *</label>
                                            <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="telefono">Teléfono *</label>
                                            <input type="text" class="form-control" id="telefono" name="telefono" 
                                                   maxlength="9" placeholder="9XXXXXXXX" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="correo">Correo Electrónico</label>
                                            <input type="email" class="form-control" id="correo" name="correo">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones Paso 1 -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= base_url('leads') ?>" class="btn btn-light">
                                <i class="icon-close"></i> Cancelar
                            </a>
                            <button type="button" class="btn btn-primary btn-lg" id="btnSiguiente">
                                Siguiente <i class="icon-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- PASO 2: SOLICITUD DE SERVICIO -->
                    <!-- ============================================ -->
                    <div id="paso2" style="display:none;">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="icon-home"></i> Paso 2: ¿Dónde Instalará el Servicio?</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning mb-4">
                                    <strong><i class="icon-info"></i> Importante:</strong> Un cliente puede tener múltiples solicitudes en diferentes ubicaciones (casa, negocio, etc.)
                                </div>

                                <!-- Primera fila: Dirección, Referencias, Distrito -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="direccion">Dirección de Instalación *</label>
                                            <input type="text" class="form-control" id="direccion" name="direccion"
                                                   placeholder="Ej: Av. Principal 123, Chincha Alta" required>
                                            <small class="text-muted">Dirección donde se instalará el servicio</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="referencias">Referencias de Ubicación</label>
                                            <input type="text" class="form-control" id="referencias" name="referencias"
                                                   placeholder="Ej: Frente al parque, cerca del mercado">
                                            <small class="text-muted">Puntos de referencia cercanos</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="iddistrito">Distrito</label>
                                            <select class="form-control" id="iddistrito" name="iddistrito">
                                                <option value="">Seleccione (opcional)</option>
                                                <?php if (!empty($distritos) && is_array($distritos)): ?>
                                                    <?php foreach ($distritos as $distrito): ?>
                                                        <option value="<?= $distrito['iddistrito'] ?>">
                                                            <?= esc($distrito['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Segunda fila: Tipo Instalación, Tipo Servicio, Plan -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tipo_solicitud">Tipo de Instalación</label>
                                            <select class="form-control" id="tipo_solicitud" name="tipo_solicitud">
                                                <option value="">Seleccione (opcional)</option>
                                                <option value="Casa">Casa / Hogar</option>
                                                <option value="Negocio">Negocio / Empresa</option>
                                                <option value="Oficina">Oficina</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tipo_servicio">Tipo de Servicio 📡</label>
                                            <select class="form-control" id="tipo_servicio" name="tipo_servicio">
                                                <option value="">Seleccione primero el servicio</option>
                                                <?php if (!empty($servicios) && is_array($servicios)): ?>
                                                    <?php foreach ($servicios as $servicio): ?>
                                                        <option value="<?= $servicio['id_servicio'] ?>" 
                                                                data-tipo="<?= esc($servicio['tipo_servicio']) ?>">
                                                            <?= esc($servicio['servicio']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                            <small class="text-muted">Ej: Fibra Óptica, Cable, Wireless</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="plan_interes">Plan de Interés 🌐</label>
                                            <select class="form-control" id="plan_interes" name="plan_interes" disabled>
                                                <option value="">Primero seleccione un servicio</option>
                                            </select>
                                            <small class="text-muted" id="plan_info">Seleccione un tipo de servicio primero</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contenedor para mensaje de cobertura de zonas -->
                                <div id="alerta-cobertura-zona" style="display: none;"></div>
                                <div class="card border-warning mb-4">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0">
                                            <i class="icon-map"></i> 
                                            <strong>PASO 1: Validar Cobertura</strong>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info mb-3">
                                            <i class="icon-info"></i> 
                                            <strong>Importante:</strong> Antes de registrar el lead, verifica que la dirección del cliente 
                                            esté dentro del área de cobertura usando el mapa del sistema de gestión.
                                        </div>
                                        
                                        <!-- Layout en dos columnas -->
                                        <div class="row">
                                            <!-- Columna izquierda: Botón del mapa -->
                                            <div class="col-md-6">
                                                <div class="text-center p-4" style="border: 2px solid #ffc107; border-radius: 5px; background: #fff3cd; height: 100%;">
                                                    <i class="icon-map" style="font-size: 40px; color: #856404;"></i>
                                                    <h6 class="mt-2 mb-2">Validar Cobertura en Sistema de Gestión</h6>
                                                    <p class="text-muted small mb-3">
                                                        Haz clic en el botón para abrir el mapa de cobertura en una nueva pestaña
                                                    </p>
                                                    <a href="http://gst.delafiber.com/public/views/" 
                                                       target="_blank" 
                                                       class="btn btn-warning">
                                                        <i class="icon-map"></i> Abrir Mapa de Cobertura
                                                    </a>
                                                    <p class="text-muted mt-2 mb-0">
                                                        <small>
                                                            <i class="icon-info"></i> 
                                                            El mapa se abrirá en una nueva pestaña. Valida la cobertura y regresa aquí para continuar.
                                                        </small>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <!-- Columna derecha: Instrucciones -->
                                            <div class="col-md-6">
                                                <div class="alert alert-warning mb-0" style="height: 100%;">
                                                    <strong><i class="icon-check"></i> Instrucciones:</strong>
                                                    <ol class="mb-0 mt-2">
                                                        <li>Busca la dirección del cliente en el mapa superior</li>
                                                        <li>Verifica que haya cajas/mufas cercanas (iconos en el mapa)</li>
                                                        <li>Si HAY cobertura → Continúa llenando el formulario abajo</li>
                                                        <li>Si NO HAY cobertura → Informa al cliente que no hay servicio disponible</li>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ubicación GPS -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Ubicación en Tiempo Real (Opcional)</label>
                                            <div class="btn-group d-block mb-2" role="group">
                                                <button type="button" class="btn btn-info btn-sm" id="btnObtenerUbicacion">
                                                    <i class="icon-location-pin"></i> Obtener mi ubicación
                                                </button>
                                                <button type="button" class="btn btn-success btn-sm" id="btnPegarUbicacionWhatsapp">
                                                    <i class="icon-social-whatsapp"></i> Pegar ubicación de WhatsApp
                                                </button>
                                            </div>
                                            <input type="hidden" id="coordenadas_servicio" name="coordenadas_servicio">
                                            <input type="hidden" id="ubicacion_compartida" name="ubicacion_compartida">
                                            <div id="coordenadas-info" class="alert alert-info" style="display:none;">
                                                <small id="coordenadas-texto"></small>
                                            </div>
                                            <div id="alerta-cobertura-ubicacion" style="display: none;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Documentos Requeridos -->
                                <div class="card bg-light mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="icon-doc"></i> Documentos Requeridos (Opcional - puede subirse después)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning">
                                            <strong><i class="icon-info"></i> Importante:</strong> 
                                            Para validar la dirección y verificar identidad, solicita al cliente:
                                            <ul class="mb-0 mt-2">
                                                <li>Foto del DNI (frontal y reverso)</li>
                                                <li>Foto del recibo de luz o agua (para validar dirección)</li>
                                            </ul>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="foto_dni_frontal">
                                                        <i class="icon-camera"></i> DNI - Frontal
                                                    </label>
                                                    <input type="file" class="form-control-file" id="foto_dni_frontal" 
                                                           name="foto_dni_frontal" accept="image/*,.pdf">
                                                    <small class="text-muted">Formatos: JPG, PNG, PDF (máx. 5MB)</small>
                                                    <div id="preview_dni_frontal" class="mt-2"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="foto_dni_reverso">
                                                        <i class="icon-camera"></i> DNI - Reverso
                                                    </label>
                                                    <input type="file" class="form-control-file" id="foto_dni_reverso" 
                                                           name="foto_dni_reverso" accept="image/*,.pdf">
                                                    <small class="text-muted">Formatos: JPG, PNG, PDF (máx. 3MB) - Se comprimirá automáticamente</small>
                                                    <div id="preview_dni_reverso" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="recibo_luz_agua">
                                                        <i class="icon-camera"></i> Recibo de Luz o Agua
                                                    </label>
                                                    <select class="form-control form-control-sm mb-2" id="tipo_recibo" name="tipo_recibo">
                                                        <option value="recibo_luz">Recibo de Luz</option>
                                                        <option value="recibo_agua">Recibo de Agua</option>
                                                    </select>
                                                    <input type="file" class="form-control-file" id="recibo_luz_agua" 
                                                           name="recibo_luz_agua" accept="image/*,.pdf">
                                                    <small class="text-muted">Para validar la dirección de instalación</small>
                                                    <div id="preview_recibo" class="mt-2"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="foto_domicilio">
                                                        <i class="icon-camera"></i> Foto del Domicilio (Opcional)
                                                    </label>
                                                    <input type="file" class="form-control-file" id="foto_domicilio" 
                                                           name="foto_domicilio" accept="image/*">
                                                    <small class="text-muted">Foto de la fachada o referencia visual</small>
                                                    <div id="preview_domicilio" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Origen y Campaña -->
                                <h6 class="mb-3"><i class="icon-chart"></i> Origen del Contacto</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="idorigen">¿Cómo nos conoció? *</label>
                                            <select class="form-control" id="idorigen" name="idorigen" required>
                                                <option value="">Seleccione</option>
                                                <?php foreach ($origenes as $origen): ?>
                                                <option value="<?= $origen['idorigen'] ?>" data-nombre="<?= esc($origen['nombre']) ?>">
                                                    <?= esc($origen['nombre']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Contenedor para campos dinámicos según origen -->
                                        <div id="campos-dinamicos-origen"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="idmodalidad">¿Cómo te contactó el cliente?</label>
                                            <select class="form-control" id="idmodalidad" name="idmodalidad">
                                                <option value="">Seleccione</option>
                                                <?php if (isset($modalidades) && is_array($modalidades)): ?>
                                                    <?php foreach ($modalidades as $modalidad): ?>
                                                        <option value="<?= $modalidad['idmodalidad'] ?>">
                                                            <?= esc($modalidad['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="medio_comunicacion">Detalle del Medio (opcional)</label>
                                            <input type="text" class="form-control" id="medio_comunicacion" name="medio_comunicacion"
                                                   placeholder="Ej: WhatsApp +51 999888777 (opcional)">
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Campo oculto: asignación automática al usuario actual -->
                                <input type="hidden" id="idusuario_asignado" name="idusuario_asignado" value="<?= session()->get('idusuario') ?>">
                                <input type="hidden" id="idetapa" name="idetapa" value="1">
                                <input type="hidden" id="idcampania" name="idcampania" value="<?= $campania_preseleccionada ?? '' ?>">

                                <?php if (!empty($campania_preseleccionada)): ?>
                                    <?php 
                                    // Buscar el nombre de la campaña
                                    $nombreCampania = 'Campaña seleccionada';
                                    foreach ($campanias as $camp) {
                                        if ($camp['idcampania'] == $campania_preseleccionada) {
                                            $nombreCampania = $camp['nombre'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="alert alert-success">
                                        <i class="icon-check"></i> <strong>Campaña:</strong> Este lead se vinculará automáticamente a la campaña <strong>"<?= esc($nombreCampania) ?>"</strong>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="nota_inicial">Nota del Primer Contacto (opcional)</label>
                                    <textarea class="form-control" id="nota_inicial" name="nota_inicial" rows="3"
                                              placeholder="Describe brevemente la conversación inicial (opcional)..."></textarea>
                                    <small class="text-muted"> Puedes agregar más detalles y asignar tareas después</small>
                                </div>

                                <div class="alert alert-info">
                                    <i class="icon-info"></i> <strong>Nota:</strong> Este lead se asignará automáticamente a ti. 
                                    Podrás crear tareas y asignarlas a otros usuarios después del registro.
                                </div>
                            </div>
                        </div>

                        <!-- Botones Paso 2 -->
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-light" id="btnAtras">
                                <i class="icon-arrow-left"></i> Atrás
                            </button>
                            <button type="submit" class="btn btn-success btn-lg" id="btnGuardar">
                                <i class="icon-check"></i> Guardar Lead
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Select2 JS - DEBE CARGARSE ANTES DE USARLO -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const BASE_URL = '<?= base_url() ?>';
// Campañas disponibles para campos dinámicos
const campanias = <?= json_encode($campanias ?? []) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= base_url('js/leads/wizard.js') ?>"></script>
<script src="<?= base_url('js/leads/buscar-cliente.js') ?>"></script>
<script src="<?= base_url('js/leads/create.js') ?>"></script>
<script src="<?= base_url('js/leads/campos-dinamicos-origen.js') ?>"></script>
<script src="<?= base_url('js/leads/documentos-geolocalizacion.js') ?>"></script>

<!-- Datos para filtrado de paquetes -->
<div id="filtro-paquetes-data" 
     data-paquetes='<?= json_encode($paquetes ?? []) ?>'
     data-servicios='<?= json_encode($servicios ?? []) ?>'
     style="display:none;"></div>

<!-- Script de filtrado de paquetes -->
<script src="<?= base_url('js/leads/filtrar-paquetes.js') ?>"></script>

<?= $this->endSection() ?>