document.addEventListener('DOMContentLoaded', function() {
  // Editar persona
  document.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      window.location.href = BASE_URL + 'personas/edit/' + id;
    });
  });

  // Eliminar persona
  document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      Swal.fire({
        title: '¿Eliminar persona?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = BASE_URL + 'personas/delete/' + id;
        }
      });
    });
  });

  // Convertir en lead
  document.querySelectorAll('.btn-convertir-lead').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      Swal.fire({
        title: '¿Convertir en Lead?',
        text: '¿Deseas convertir esta persona en un lead?',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, convertir',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = BASE_URL + 'leads/create?persona_id=' + id;
        }
      });
    });
  });
});
