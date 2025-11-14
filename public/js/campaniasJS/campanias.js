// Asegurar una constante BASE_URL basada en la variable global definida en el layout
const BASE_URL = (typeof baseUrl !== 'undefined') ? baseUrl : '';

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar DataTable
    let tabla = null;
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        tabla = $('#tablaCampanias').DataTable({
            language: {
                url: BASE_URL + '/js/datatables/es-ES.json'
            },
            order: [[4, 'desc']],
            pageLength: 25,
            columnDefs: [
                { orderable: false, targets: -1 }
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });
    }

    // Filtros personalizados
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroTipo = document.getElementById('filtroTipo');
    const busquedaRapida = document.getElementById('busquedaRapida');
    const limpiarFiltros = document.getElementById('limpiarFiltros');

    function aplicarFiltros() {
        const estadoFiltro = (filtroEstado?.value || '').toLowerCase();
        const tipoFiltro = (filtroTipo?.value || '').toLowerCase();
        const busqueda = (busquedaRapida?.value || '').toLowerCase();

        const filas = document.querySelectorAll('#tablaCampanias tbody tr');
        filas.forEach(fila => {
            let estadoFila = (fila.dataset.estado || '').toLowerCase();
            const tipoFila = (fila.dataset.tipo || '').toLowerCase();
            const textoFila = fila.textContent.toLowerCase();

            // Normalizar estados: tratar 'pausada' como 'inactiva'
            if (estadoFila === 'pausada') {
                estadoFila = 'inactiva';
            }

            const coincideEstado = !estadoFiltro || estadoFila === estadoFiltro;
            const coincideTipo = !tipoFiltro || tipoFila === tipoFiltro;
            const coincideBusqueda = !busqueda || textoFila.includes(busqueda);

            if (coincideEstado && coincideTipo && coincideBusqueda) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }

    if (filtroEstado) filtroEstado.addEventListener('change', aplicarFiltros);
    if (filtroTipo) filtroTipo.addEventListener('change', aplicarFiltros);
    if (busquedaRapida) {
        busquedaRapida.addEventListener('input', aplicarFiltros);
    }

    if (limpiarFiltros) {
        limpiarFiltros.addEventListener('click', () => {
            if (filtroEstado) filtroEstado.value = '';
            if (filtroTipo) filtroTipo.value = '';
            if (busquedaRapida) busquedaRapida.value = '';
            aplicarFiltros();
        });
    }
});

// Modal y función de eliminación
let idCampaniaEliminar = null;
const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

function confirmarEliminacion(id, nombre) {
    idCampaniaEliminar = id;
    document.getElementById('nombreCampaniaEliminar').textContent = nombre;
    modalEliminar.show();
}

document.getElementById('btnConfirmarEliminar')?.addEventListener('click', function() {
    if (idCampaniaEliminar) {
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Eliminando...';
        this.disabled = true;
        window.location.href = BASE_URL + '/campanias/delete/' + idCampaniaEliminar;
    }
});
