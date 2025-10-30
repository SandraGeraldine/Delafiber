/**
 * JavaScript para Reportes
 */

const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

// Funciones básicas para reportes
function imprimirReporte() {
    window.print();
}

function exportarExcel() {
    const periodo = document.getElementById('periodoSelect').value;
    const fechaInicio = document.querySelector('input[name="fecha_inicio"]')?.value || '';
    const fechaFin = document.querySelector('input[name="fecha_fin"]')?.value || '';
    
    let url = `${baseUrl}/reportes/exportar-excel?periodo=${periodo}`;
    
    if (periodo === 'personalizado' && fechaInicio && fechaFin) {
        url += '&fecha_inicio=' + fechaInicio + '&fecha_fin=' + fechaFin;
    }
    
    window.location.href = url;
}

// Cambio de período
const periodoSelect = document.getElementById('periodoSelect');
if (periodoSelect) {
    periodoSelect.addEventListener('change', function() {
        const rangoFechas = document.getElementById('rangoFechas');
        if (this.value === 'personalizado') {
            rangoFechas.style.display = 'inline-flex';
        } else {
            rangoFechas.style.display = 'none';
        }
    });
}

// Gráficos con datos reales del backend
$(document).ready(function() {
    // Gráfico de Etapas
    const chartEtapasEl = document.getElementById('chartEtapas');
    if (chartEtapasEl) {
        const datosEtapas = chartEtapasEl.dataset.etapas;
        if (datosEtapas) {
            try {
                const datos = JSON.parse(datosEtapas);
                const ctxEtapas = chartEtapasEl.getContext('2d');
                new Chart(ctxEtapas, {
                    type: 'doughnut',
                    data: {
                        labels: datos.labels,
                        datasets: [{
                            data: datos.data,
                            backgroundColor: ['#007bff', '#28a745', '#ffc107', '#fd7e14', '#dc3545', '#6f42c1', '#17a2b8']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            } catch (error) {
                console.error('Error al cargar gráfico de etapas:', error);
            }
        }
    }
    
    // Gráfico de Orígenes
    const chartOrigenesEl = document.getElementById('chartOrigenes');
    if (chartOrigenesEl) {
        const datosOrigenes = chartOrigenesEl.dataset.origenes;
        if (datosOrigenes) {
            try {
                const datos = JSON.parse(datosOrigenes);
                const ctxOrigenes = chartOrigenesEl.getContext('2d');
                new Chart(ctxOrigenes, {
                    type: 'bar',
                    data: {
                        labels: datos.labels,
                        datasets: [{
                            label: 'Leads',
                            data: datos.data,
                            backgroundColor: '#007bff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            } catch (error) {
                console.error('Error al cargar gráfico de orígenes:', error);
            }
        }
    }

    // Gráfico de Tendencia
    const chartTendenciaEl = document.getElementById('chartTendencia');
    if (chartTendenciaEl) {
        const datosTendencia = chartTendenciaEl.dataset.tendencia;
        if (datosTendencia) {
            try {
                const datos = JSON.parse(datosTendencia);
                const ctxTendencia = chartTendenciaEl.getContext('2d');
                new Chart(ctxTendencia, {
                    type: 'line',
                    data: {
                        labels: datos.labels,
                        datasets: [
                            {
                                label: 'Leads',
                                data: datos.leads,
                                borderColor: '#007bff',
                                backgroundColor: 'rgba(0,123,255,0.1)',
                                fill: true
                            },
                            {
                                label: 'Conversiones',
                                data: datos.conversiones,
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40,167,69,0.1)',
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            } catch (error) {
                console.error('Error al cargar gráfico de tendencia:', error);
            }
        }
    }
});
