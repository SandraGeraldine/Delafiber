/**
 * JavaScript para el listado de Leads
 * Archivo: public/js/leads/leads-index.js
 */

$(document).ready(function() {
    // Inicializar DataTable si existe la tabla
    const tableLeads = $('#tableLeads');
    
    if (tableLeads.length && tableLeads.find('tbody tr').length > 0) {
        // Usar la función global de configuración de DataTables
        if (typeof initDataTable === 'function') {
            initDataTable('#tableLeads', {
                order: [[0, "desc"]],
                pageLength: 25,
                columnDefs: [
                    { orderable: false, targets: 8 } // Columna de acciones no ordenable
                ]
            });
        } else {
            // Fallback si no está cargada la configuración global
            tableLeads.DataTable({
                language: window.dataTablesSpanish || {},
                order: [[0, "desc"]],
                pageLength: 25,
                columnDefs: [
                    { orderable: false, targets: 8 }
                ]
            });
        }
    }
});
