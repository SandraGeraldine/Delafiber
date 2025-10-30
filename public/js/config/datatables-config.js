/**
 * Configuración global de DataTables en Español
 * Archivo: public/js/config/datatables-config.js
 */

// Configuración de idioma español para DataTables
window.dataTablesSpanish = {
    "sProcessing": "Procesando...",
    "sLengthMenu": "Mostrar _MENU_ registros",
    "sZeroRecords": "No se encontraron resultados",
    "sEmptyTable": "Ningún dato disponible en esta tabla",
    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
    "sInfoPostFix": "",
    "sSearch": "Buscar:",
    "sUrl": "",
    "sInfoThousands": ",",
    "sLoadingRecords": "Cargando...",
    "oPaginate": {
        "sFirst": "Primero",
        "sLast": "Último",
        "sNext": "Siguiente",
        "sPrevious": "Anterior"
    },
    "oAria": {
        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
    }
};

// Función helper para inicializar DataTable con configuración estándar
window.initDataTable = function(selector, options = {}) {
    const defaultOptions = {
        language: window.dataTablesSpanish,
        pageLength: 25,
        responsive: true,
        order: [[0, "desc"]]
    };
    
    return $(selector).DataTable({...defaultOptions, ...options});
};
