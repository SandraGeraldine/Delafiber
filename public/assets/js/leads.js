document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.kanban-column').forEach(function(column) {
    Sortable.create(column, {
      group: 'leads-kanban',
      animation: 150,
      onEnd: function(evt) {
        var idlead = evt.item.dataset.idlead;
        var idetapa = evt.to.dataset.etapa;
        // Actualiza la etapa por AJAX
        fetch('/leads/updateEtapa', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ idlead, idetapa })
        });
      }
    });
  });
});
