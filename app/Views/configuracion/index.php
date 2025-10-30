<?= $this->extend('Layouts/base') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-12">
        <h4 class="mb-4">Configuración del Sistema</h4>

        <!-- Mensajes Flash -->
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Tabs de Configuración -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#etapas">
                    <i class="icon-layers"></i> Etapas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#origenes">
                    <i class="icon-map-pin"></i> Orígenes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#modalidades">
                    <i class="icon-grid"></i> Modalidades
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#usuarios">
                    <i class="icon-users"></i> Usuarios
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab Etapas -->
            <div class="tab-pane fade show active p-4" id="etapas">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gestión de Etapas</h5>
                        <button type="button" class="btn btn-sm btn-primary" 
                                data-toggle="modal" data-target="#modalEtapa">
                            <i class="icon-plus"></i> Nueva Etapa
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Orden</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="sortableEtapas">
                                    <?php if (!empty($etapas)): ?>
                                        <?php foreach ($etapas as $etapa): ?>
                                        <tr data-id="<?= $etapa['idetapa'] ?>">
                                            <td>
                                                <i class="icon-menu text-muted" style="cursor:move;"></i>
                                                <?= $etapa['orden'] ?>
                                            </td>
                                            <td><strong><?= esc($etapa['nombre']) ?></strong></td>
                                            <td><?= esc($etapa['descripcion']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $etapa['activo'] ? 'success' : 'secondary' ?>">
                                                    <?= $etapa['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick='editarEtapa(<?= json_encode($etapa) ?>)'>
                                                    <i class="icon-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="eliminar('etapa', <?= $etapa['idetapa'] ?>)">
                                                    <i class="icon-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No hay etapas configuradas</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Orígenes -->
            <div class="tab-pane fade p-4" id="origenes">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gestión de Orígenes</h5>
                        <button type="button" class="btn btn-sm btn-primary" 
                                data-toggle="modal" data-target="#modalOrigen">
                            <i class="icon-plus"></i> Nuevo Origen
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($origenes)): ?>
                                        <?php foreach ($origenes as $origen): ?>
                                        <tr>
                                            <td><strong><?= esc($origen['nombre']) ?></strong></td>
                                            <td><?= esc($origen['descripcion']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $origen['activo'] ? 'success' : 'secondary' ?>">
                                                    <?= $origen['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick='editarOrigen(<?= json_encode($origen) ?>)'>
                                                    <i class="icon-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="eliminar('origen', <?= $origen['idorigen'] ?>)">
                                                    <i class="icon-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No hay orígenes configurados</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Modalidades -->
            <div class="tab-pane fade p-4" id="modalidades">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gestión de Modalidades</h5>
                        <button type="button" class="btn btn-sm btn-primary" 
                                data-toggle="modal" data-target="#modalModalidad">
                            <i class="icon-plus"></i> Nueva Modalidad
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($modalidades)): ?>
                                        <?php foreach ($modalidades as $modalidad): ?>
                                        <tr>
                                            <td><strong><?= esc($modalidad['nombre']) ?></strong></td>
                                            <td><?= esc($modalidad['descripcion']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $modalidad['activo'] ? 'success' : 'secondary' ?>">
                                                    <?= $modalidad['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick='editarModalidad(<?= json_encode($modalidad) ?>)'>
                                                    <i class="icon-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="eliminar('modalidad', <?= $modalidad['idmodalidad'] ?>)">
                                                    <i class="icon-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No hay modalidades configuradas</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Usuarios -->
            <div class="tab-pane fade p-4" id="usuarios">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gestión de Usuarios</h5>
                        <button type="button" class="btn btn-sm btn-primary" 
                                data-toggle="modal" data-target="#modalUsuario">
                            <i class="icon-plus"></i> Nuevo Usuario
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Nombre Completo</th>
                                        <th>Correo</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($usuarios)): ?>
                                        <?php foreach ($usuarios as $user): ?>
                                        <tr>
                                            <td><strong><?= esc($user['usuario']) ?></strong></td>
                                            <td><?= esc($user['nombres'] . ' ' . $user['apellidos']) ?></td>
                                            <td><?= esc($user['correo']) ?></td>
                                            <td>
                                                <span class="badge badge-info"><?= esc($user['rol']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $user['activo'] ? 'success' : 'secondary' ?>">
                                                    <?= $user['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick='editarUsuario(<?= json_encode($user) ?>)'>
                                                    <i class="icon-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="eliminar('usuario', <?= $user['idusuario'] ?>)">
                                                    <i class="icon-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No hay usuarios registrados</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Etapa -->
<div class="modal fade" id="modalEtapa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEtapa" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idetapa" id="etapa_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="etapaTitle">Nueva Etapa</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="etapa_nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="descripcion" id="etapa_descripcion" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Orden</label>
                        <input type="number" class="form-control" name="orden" id="etapa_orden" min="1" value="1">
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="etapa_activo" name="activo" value="1" checked>
                        <label class="custom-control-label" for="etapa_activo">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Origen -->
<div class="modal fade" id="modalOrigen" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formOrigen" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idorigen" id="origen_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="origenTitle">Nuevo Origen</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="origen_nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="descripcion" id="origen_descripcion" rows="2"></textarea>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="origen_activo" name="activo" value="1" checked>
                        <label class="custom-control-label" for="origen_activo">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modalidad -->
<div class="modal fade" id="modalModalidad" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formModalidad" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idmodalidad" id="modalidad_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalidadTitle">Nueva Modalidad</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="modalidad_nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="descripcion" id="modalidad_descripcion" rows="2"></textarea>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="modalidad_activo" name="activo" value="1" checked>
                        <label class="custom-control-label" for="modalidad_activo">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formUsuario" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="idusuario" id="usuario_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="usuarioTitle">Nuevo Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombres <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombres" id="usuario_nombres" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Apellidos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="apellidos" id="usuario_apellidos" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="usuario" id="usuario_usuario" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Correo <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="correo" id="usuario_correo" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="tel" class="form-control" name="telefono" id="usuario_telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Rol <span class="text-danger">*</span></label>
                                <select class="form-control" name="rol" id="usuario_rol" required>
                                    <option value="Vendedor">Vendedor</option>
                                    <option value="Supervisor">Supervisor</option>
                                    <option value="Administrador">Administrador</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="passwordGroup">
                        <label>Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" id="usuario_password" minlength="6">
                        <small class="text-muted">Mínimo 6 caracteres. Dejar en blanco para no cambiar (al editar)</small>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="usuario_activo" name="activo" value="1" checked>
                        <label class="custom-control-label" for="usuario_activo">Usuario Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/configuracion/configuracion-index.js') ?>"></script>
<?= $this->endSection() ?>