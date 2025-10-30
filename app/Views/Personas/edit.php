<?= $header ?>
<h4>Editar persona</h4>
<form method="post" action="/personas/edit/<?= esc($persona['idpersona']) ?>">
  <div class="mb-2">
    <label>Nombres</label>
    <input type="text" name="nombres" class="form-control" value="<?= esc($persona['nombres']) ?>" required>
  </div>
  <div class="mb-2">
    <label>Apellidos</label>
    <input type="text" name="apellidos" class="form-control" value="<?= esc($persona['apellidos']) ?>" required>
  </div>
  <div class="mb-2">
    <label>DNI</label>
    <input type="text" name="dni" class="form-control" value="<?= esc($persona['dni']) ?>">
  </div>
  <div class="mb-2">
    <label>Correo</label>
    <input type="email" name="correo" class="form-control" value="<?= esc($persona['correo']) ?>">
  </div>
  <div class="mb-2">
    <label>Teléfono</label>
    <input type="text" name="telefono" class="form-control" value="<?= esc($persona['telefono']) ?>">
  </div>
  <div class="mb-2">
    <label>Dirección</label>
    <input type="text" name="direccion" class="form-control" value="<?= esc($persona['direccion']) ?>">
  </div>
  <button type="submit" class="btn btn-primary">Actualizar</button>
  <a href="/personas" class="btn btn-secondary">Cancelar</a>
</form>
<?= $footer ?>
