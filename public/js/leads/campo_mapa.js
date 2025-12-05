const mapaCampoBar = {
    mapa: null,
    zonas: []
};

function obtenerCoordenadaDePoligono(punto) {
    if (!punto) {
        return null;
    }

    if (typeof punto.lat === 'number' && typeof punto.lng === 'number') {
        return punto;
    }

    if (typeof punto.latitude === 'number' && typeof punto.longitude === 'number') {
        return { lat: punto.latitude, lng: punto.longitude };
    }

    return null;
}

async function cargarMapaCampo(idCampana = null) {
    if (!window.google || !window.google.maps) {
        console.warn('Google Maps no está cargado.');
        return;
    }

    mapaCampoBar.mapa = new google.maps.Map(document.getElementById('campo-mapa'), {
        zoom: 14,
        center: { lat: -12.046374, lng: -77.042793 },
        mapTypeControl: false,
        fullscreenControl: true
    });

    await cargarZonasCampo(idCampana);
}

async function cargarZonasCampo(idCampana = null) {
    const selector = document.getElementById('campo-campana-select');
    const prioridad = document.getElementById('campo-prioridad-select');
    const campanaId = idCampana ?? selector?.value;

    let url = `${BASE_URL}/crm-campanas/api-zonas-mapa`;
    if (campanaId) {
        url += `/${campanaId}`;
    }

    try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        if (!data.success) {
            throw new Error(data.message);
        }

        mapaCampoBar.zonas = data.data;
        document.getElementById('campo-zonas-list').innerHTML = '';
        limpiarCapasCampo();

        mapaCampoBar.zonas.forEach(zona => renderizarZonaCampo(zona));
        const textoBusqueda = document.getElementById('campo-buscar-zona')?.value ?? '';
        filtrarZonasPorTexto(textoBusqueda);
    } catch (error) {
        console.error('Error al cargar zonas de campo:', error);
    }
}

function limpiarCapasCampo() {
    mapaCampoBar.zonas.forEach(zona => {
        if (zona.referenciaMapa) {
            zona.referenciaMapa.setMap(null);
        }
    });
    mapaCampoBar.zonas = mapaCampoBar.zonas.map(z => ({ ...z, referenciaMapa: null }));
}

function renderizarZonaCampo(zona) {
    const polygon = new google.maps.Polygon({
        paths: zona.poligono,
        strokeColor: zona.color || '#3498db',
        strokeWeight: 2,
        fillColor: zona.color || '#3498db',
        fillOpacity: 0.3,
        map: mapaCampoBar.mapa
    });

    zona.referenciaMapa = polygon;

    polygon.addListener('click', () => {
        alert(`Zona: ${zona.nombre_zona}\nCampaña: ${zona.nombre_campana}`);
    });

    const card = document.createElement('div');
    card.className = 'campo-zona-card card mb-2 p-3 shadow-sm border-0';
    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <strong>${zona.nombre_zona}</strong>
            <span class="badge bg-${zona.prioridad === 'Alta' ? 'danger' : zona.prioridad === 'Media' ? 'warning' : 'secondary'}">${zona.prioridad}</span>
        </div>
        <p class="mb-1 text-muted small">${zona.nombre_campana} · Area ${zona.area_km2 ? zona.area_km2.toFixed(2) : '0'} km²</p>
        <button class="btn btn-sm btn-outline-primary campo-zona-marcar" data-zona-id="${zona.id_zona}">Marcar recorrida</button>
    `;

    card.addEventListener('click', () => {
        const bounds = obtenerBoundsDePoligono(polygon);
        if (bounds) {
            mapaCampoBar.mapa.fitBounds(bounds);
        }
    });

    document.getElementById('campo-zonas-list').appendChild(card);
    zona.card = card;

    const btnMarcar = card.querySelector('.campo-zona-marcar');
    if (btnMarcar) {
        btnMarcar.addEventListener('click', event => {
            event.stopPropagation();
            registrarZonaRecorrida(zona, btnMarcar);
        });
    }
}

function obtenerFiltrosCampo() {
    const texto = document.getElementById('campo-buscar-zona')?.value ?? '';
    const prioridad = document.getElementById('campo-prioridad-select')?.value ?? '';
    const fechaDesde = document.getElementById('campo-fecha-desde')?.value ?? '';
    const fechaHasta = document.getElementById('campo-fecha-hasta')?.value ?? '';

    return {
        texto: texto.trim().toLowerCase(),
        prioridad,
        fechaDesde: fechaDesde ? new Date(fechaDesde) : null,
        fechaHasta: fechaHasta ? new Date(fechaHasta) : null
    };
}

function zonaCumpleFiltroFecha(zona, filtros) {
    const inicioZona = zona.fecha_inicio ? new Date(zona.fecha_inicio) : null;
    const finZona = zona.fecha_fin ? new Date(zona.fecha_fin) : null;

    if (filtros.fechaDesde && finZona && finZona < filtros.fechaDesde) {
        return false;
    }

    if (filtros.fechaHasta && inicioZona && inicioZona > filtros.fechaHasta) {
        return false;
    }

    return true;
}

function centrarMapaEnZonas() {
    if (!mapaCampoBar.mapa || !mapaCampoBar.zonas.length) {
        return;
    }

    const bounds = new google.maps.LatLngBounds();
    let tieneCoordenadas = false;

    mapaCampoBar.zonas.forEach(zona => {
        if (!Array.isArray(zona.poligono)) {
            return;
        }

        if (zona.referenciaMapa && typeof zona.referenciaMapa.getVisible === 'function' && !zona.referenciaMapa.getVisible()) {
            return;
        }

        zona.poligono.forEach(punto => {
            const coordenada = obtenerCoordenadaDePoligono(punto);
            if (coordenada) {
                bounds.extend(coordenada);
                tieneCoordenadas = true;
            }
        });
    });

    if (tieneCoordenadas) {
        mapaCampoBar.mapa.fitBounds(bounds);
    }
}

function filtrarZonasPorTexto(texto = '') {
    const filtros = obtenerFiltrosCampo();
    const termino = texto.trim().toLowerCase();
    let coincidencias = 0;

    mapaCampoBar.zonas.forEach(zona => {
        const contexto = `${zona.nombre_zona ?? ''} ${zona.nombre_campana ?? ''}`.toLowerCase();
        const coincideTexto = !termino || contexto.includes(termino);
        const coincidePrioridad = !filtros.prioridad || zona.prioridad === filtros.prioridad;
        const coincideFecha = zonaCumpleFiltroFecha(zona, filtros);
        const visible = coincideTexto && coincidePrioridad && coincideFecha;

        if (zona.referenciaMapa && typeof zona.referenciaMapa.setVisible === 'function') {
            zona.referenciaMapa.setVisible(visible);
        }

        if (zona.card) {
            zona.card.style.display = visible ? '' : 'none';
        }

        zona.visible = visible;

        if (visible) {
            coincidencias += 1;
        }
    });

    if (coincidencias) {
        centrarMapaEnZonas();
    }
}

function obtenerBoundsDePoligono(polygon) {
    if (!polygon || typeof polygon.getPath !== 'function') {
        return null;
    }

    const path = polygon.getPath();
    if (!path || typeof path.getLength !== 'function' || path.getLength() === 0) {
        return null;
    }

    const bounds = new google.maps.LatLngBounds();
    path.forEach(coord => bounds.extend(coord));
    return bounds;
}

async function registrarZonaRecorrida(zona, btn) {
    if (!zona || !btn || zona.visitada) {
        return;
    }

    btn.disabled = true;
    const textoOriginal = btn.textContent;
    btn.textContent = 'Registrando...';

    try {
        const coords = await obtenerCoordenadasUsuario();
        const res = await fetch(`${BASE_URL}/crm-campanas/confirmarZona`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id_zona: zona.id_zona,
                lat: coords.lat,
                lng: coords.lng
            })
        });

        const data = await res.json();
        if (!data.success) {
            throw new Error(data.message || 'No se pudo registrar la visita');
        }

        zona.visitada = true;
        btn.textContent = 'Zona confirmada';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');

        if (zona.card) {
            zona.card.remove();
        }

        if (zona.referenciaMapa) {
            zona.referenciaMapa.setMap(null);
        }

        mapaCampoBar.zonas = mapaCampoBar.zonas.filter(z => z.id_zona !== zona.id_zona);
    } catch (error) {
        console.error('Error al marcar zona recorrida:', error);
        btn.disabled = false;
        btn.textContent = textoOriginal;
        alert(error.message || 'No se pudo conectar con el servidor para registrar la visita.');
    }
}

function obtenerCoordenadasUsuario() {
    return new Promise(resolve => {
        if (!navigator.geolocation) {
            resolve({ lat: null, lng: null });
            return;
        }

        navigator.geolocation.getCurrentPosition(
            position => {
                resolve({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                });
            },
            () => resolve({ lat: null, lng: null }),
            { timeout: 5000 }
        );
    });
}

cargarMapaCampo();

document.addEventListener('DOMContentLoaded', () => {
    const selector = document.getElementById('campo-campana-select');
    if (selector) {
        selector.addEventListener('change', () => {
            cargarZonasCampo(selector.value || null);
        });
    }
    const search = document.getElementById('campo-buscar-zona');
    if (search) {
        search.addEventListener('input', () => {
            filtrarZonasPorTexto(search.value);
        });
    }
    const prioridad = document.getElementById('campo-prioridad-select');
    if (prioridad) {
        prioridad.addEventListener('change', () => {
            const valorBusqueda = document.getElementById('campo-buscar-zona')?.value ?? '';
            filtrarZonasPorTexto(valorBusqueda);
        });
    }
});
