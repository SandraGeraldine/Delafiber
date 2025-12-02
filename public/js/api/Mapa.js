import * as turf from "https://esm.sh/@turf/turf@6.5.0";

let Map, Circle, Polygon, AdvancedMarkerElement, mapa, InfoWindow;
let marcador = null;
let marcadorCoordenada = null;
let marcadoresCajas = [];
let marcadoresVisibles = []; // Nuevos marcadores visibles en el mapa

// Separar por tipo
let circlesCajas = [];
let circlesAntenas = [];
let unionPolygonsCajas = [];
let unionPolygonsAntenas = [];
let unionCajas = null;
let unionAntenas = null;

const BATCH_SIZE = 150;
let RADIO_CAJAS = 1000;
let RADIO_ANTENAS = 1500;
const COLOR_CAJAS = "#6c5ce7";
const COLOR_ANTENAS = "#ee5253";

let puntosMarcadorCajas = turf.featureCollection([]);
let puntosMarcadorAntenas = turf.featureCollection([]);

// Variable para saber qué tipo está activo
let tipoActivo = null;

// IDs de timeouts para cancelarlos
let timeoutsCajas = [];
let timeoutsAntenas = [];

export let ultimaCoordenada = null;
export let marcadoresCercanos = [];
export let idCaja = null;
export let idSector = null;
export let nombreSector = null;
let ubicacionMarcador;

// Exportar mapa para uso externo
export { mapa };

export async function encontrarMarcadoresCercanos(coordenadaClick, radio = 1000) {

  
  // Usar los puntos del tipo activo
  const puntosMarcador = tipoActivo === "Cajas" ? puntosMarcadorCajas : puntosMarcadorAntenas;
  
  if (!puntosMarcador || !puntosMarcador.features) return turf.featureCollection([]);
  const puntoClick = turf.point([coordenadaClick.lng, coordenadaClick.lat]);

  const marcadoresEnRadio = puntosMarcador.features.filter(feat => {
    if (!feat || !feat.geometry || !Array.isArray(feat.geometry.coordinates)) return false;
    const punto = turf.point([feat.geometry.coordinates[0], feat.geometry.coordinates[1]]);

    const distanciaKm = turf.distance(puntoClick, punto, { units: 'kilometers' });
    const distanciaMeters = distanciaKm * 1000;
    return distanciaMeters <= radio;
  });

  return turf.featureCollection(marcadoresEnRadio);
}

async function iniciarRenderizadoPorLotes(tipo) {
  console.log(`Iniciando renderizado para ${tipo}`);
  
  // CANCELAR timeouts anteriores del mismo tipo
  const timeouts = tipo === "Cajas" ? timeoutsCajas : timeoutsAntenas;
  timeouts.forEach(id => clearTimeout(id));
  timeouts.length = 0;
  
  const circles = tipo === "Cajas" ? circlesCajas : circlesAntenas;
  const RADIO = tipo === "Cajas" ? RADIO_CAJAS : RADIO_ANTENAS;
  
  let index = 0;
  let unionLocal = null;

  const procesarLote = () => {

    if (tipoActivo !== tipo) return;

    const batch = circles.slice(index, index + BATCH_SIZE);

    const loteCirculos = batch.map(datos => {
      const steps = RADIO > 2000 ? 48 : 84;
      return turf.circle([datos.lng, datos.lat], RADIO, {
        steps: steps,
        units: 'meters'
      });
    });

    let unionLote = loteCirculos.reduce((acc, circ) => {
      if (!acc) return circ;
      try {
        return turf.union(acc, circ);
      } catch (err) {
        console.warn("Error al unir parte del lote:", err);
        return acc;
      }
    }, null);

    if (unionLote) {
      try {
        unionLocal = unionLocal ? turf.union(unionLocal, unionLote) : unionLote;
      } catch (err) {
        console.warn("Error al unir el lote con el global:", err);
      }
    }

    // Actualizar la unión correspondiente
    if (tipo === "Cajas") {
      unionCajas = unionLocal;
    } else {
      unionAntenas = unionLocal;
    }

    actualizarPoligonoEnMapa(unionLocal, tipo);

    index += BATCH_SIZE;
    const progress = Math.min((index / circles.length) * 100, 100);
    console.log(`Progreso ${tipo}: ${progress.toFixed(1)}%`);

    if (index < circles.length) {
      const timeoutId = setTimeout(procesarLote, 100);
      timeouts.push(timeoutId); // ✅ GUARDAR EL ID
    } else {
      console.log(`✅ Unión de ${tipo} completada`);
    }
  };

  procesarLote();
}

async function actualizarPoligonoEnMapa(geojson, tipo) {
  console.log(`Actualizado ${tipo}`);

  // Limpiar solo los polígonos del tipo correspondiente
  const unionPolygons = tipo === "Cajas" ? unionPolygonsCajas : unionPolygonsAntenas;
  const COLOR = tipo === "Cajas" ? COLOR_CAJAS : COLOR_ANTENAS;
  
  unionPolygons.forEach(p => p.setMap(null));
  unionPolygons.length = 0;

  if (!geojson) {
    console.log("No hay geojson para dibujar.");
    return;
  }

  let features = [];
  if (geojson.type === "FeatureCollection") {
    features = geojson.features || [];
  } else if (geojson.type === "Feature") {
    features = [geojson];
  } else if (geojson.type === "Polygon" || geojson.type === "MultiPolygon") {
    features = [{ type: "Feature", geometry: geojson }];
  } else {
    console.warn("GeoJSON con tipo inesperado:", geojson.type);
    return;
  }

  features.forEach((feat) => {
    const geom = feat.geometry;
    if (!geom) return;

    if (geom.type === "MultiPolygon") {
      geom.coordinates.forEach((polyCoords) => {
        const outerRing = polyCoords[0];
        const path = outerRing.map(coord => ({ lat: coord[1], lng: coord[0] }));
        const polygon = new google.maps.Polygon({
          paths: path,
          strokeColor: COLOR,
          strokeOpacity: 0.8,
          strokeWeight: 2,
          fillColor: COLOR + "55",
          fillOpacity: 0.2,
          map: mapa
        });
        unionPolygons.push(polygon);
        polygon.addListener('click', (e) => eventoPoligonos(e));
      });
    } else if (geom.type === "Polygon") {
      const outerRing = geom.coordinates[0];
      const path = outerRing.map(coord => ({ lat: coord[1], lng: coord[0] }));

      const polygon = new google.maps.Polygon({
        paths: path,
        strokeColor: COLOR,
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: COLOR + "55",
        fillOpacity: 0.25,
        map: mapa
      });
      unionPolygons.push(polygon);
      polygon.addListener('click', (e) => eventoPoligonos(e));
    } else {
      console.warn("Geometría no pintable:", geom.type);
    }
  });

  // Actualizar el array global correspondiente
  if (tipo === "Cajas") {
    unionPolygonsCajas = unionPolygons;
  } else {
    unionPolygonsAntenas = unionPolygons;
  }
}

async function eventoPoligonos(e) {
  console.log("Funcionando");
  marcadorCoordenada = { lat: e.latLng.lat(), lng: e.latLng.lng() };
  
  if (marcador == null) {
    marcador = new AdvancedMarkerElement({
      position: e.latLng,
      map: mapa,
      title: "Marcador"
    });
  } else {
    marcador.setMap(null);
    marcador = new AdvancedMarkerElement({
      position: e.latLng,
      map: mapa,
      title: "Marcador"
    });
  }

  const RADIO = tipoActivo === "Cajas" ? RADIO_CAJAS : RADIO_ANTENAS;
  const cercanos = await encontrarMarcadoresCercanos({ lat: e.latLng.lat(), lng: e.latLng.lng() }, RADIO);
  marcadoresCercanos = (cercanos && cercanos.features) ? cercanos.features : [];

  if (document.querySelector('#btnGuardarModalMapa')) {
    document.querySelector('#btnGuardarModalMapa').disabled = false;
  }
}

export async function iniciarMapa(objetoRender = "Cajas", id = "map", renderizado = "modal", coordenadaCualquiera = false) {
  const posicionInicial = { lat: -13.417077, lng: -76.136585 };
  ({ Map, Circle, Polygon, InfoWindow } = await google.maps.importLibrary("maps"));
  ({ AdvancedMarkerElement } = await google.maps.importLibrary("marker"));

  let infoWindow = new InfoWindow();
  
  // Si el mapa ya existe y cambiamos de tipo, limpiar el tipo anterior
  if (mapa && tipoActivo && tipoActivo !== objetoRender) {
    console.log(`🔄 Cambiando de ${tipoActivo} a ${objetoRender}`);
    limpiarTipo(tipoActivo);
  }
  tipoActivo = objetoRender;

  // Si no existe el mapa, crearlo
  if (!mapa) {
    mapa = new Map(document.getElementById(id), {
      zoom: 13,
      center: posicionInicial,
      mapId: "DEMO_MAP_ID",
    });
  }

  // Limpiar marcadores visibles del tipo anterior
  marcadoresVisibles.forEach(m => m.setMap(null));
  marcadoresVisibles = [];

  tipoActivo = objetoRender;

  if (objetoRender === "Cajas") {
    // Limpiar datos anteriores de cajas
    circlesCajas = [];
    marcadoresCajas = [];
    puntosMarcadorCajas = turf.featureCollection([]);
    unionCajas = null;

    const response = await fetch(`/api/mapa/listarCajas`);
    const datosCajas = await response.json();
    
    datosCajas.forEach(caja => {
      const coordenada = caja.coordenadas.split(',');
      const latitud = parseFloat(coordenada[0]);
      const longitud = parseFloat(coordenada[1]);
      const img = document.createElement('img');
      img.src = `https://gst.delafiber.com/app/assets/image/cajaNAP.png`;
      
      const marker = new AdvancedMarkerElement({
        position: { lat: latitud, lng: longitud },
        map: mapa,
        title: caja.idCaja,
        content: img,
      });
      
      marcadoresVisibles.push(marker); // Guardar referencia
      
      marker.addListener('click', async () => {
        if (infoWindow) infoWindow.close();
        const contentString = `
          <div id="content">
            <h1>${caja.nombre}</h1>
            <div><p><b>Id:</b> ${caja.id_caja}</p>
            <p><b>Descripcion :</b> ${caja.descripcion}</p>
            <p><b>Sector:</b> ${caja.sector}</p>
            <p><b>Coordenadas:</b> ${caja.coordenadas}</p></div>
          </div>
        `;
        infoWindow.setContent(contentString);
        infoWindow.open(mapa, marker);
      });
      
      circlesCajas.push({ lat: latitud, lng: longitud });
      marcadoresCajas.push({ id: caja.id_caja, lat: latitud, lng: longitud, idSector: caja.id_sector });
      puntosMarcadorCajas.features.push(turf.point([longitud, latitud], { id: caja.id_caja, idSector: caja.id_sector }));
    });
    
    await iniciarRenderizadoPorLotes("Cajas");
    
  } else if (objetoRender === "Antenas") {
    // Limpiar datos anteriores de antenas
    circlesAntenas = [];
    puntosMarcadorAntenas = turf.featureCollection([]);
    unionAntenas = null;

    const response = await fetch(`/api/mapa/listarAntenas`);
    const datosAntenas = await response.json();
    
    datosAntenas.forEach(antena => {
      const coordenada = antena.coordenadas.split(',');
      const latitud = parseFloat(coordenada[0]);
      const longitud = parseFloat(coordenada[1]);
      const img = document.createElement('img');
      img.src = "https://gst.delafiber.com/app/assets/image/antena.png";
      
      const marker = new AdvancedMarkerElement({
        position: { lat: latitud, lng: longitud },
        map: mapa,
        title: antena.idAntena,
        content: img,
      });
      
      marcadoresVisibles.push(marker); // Guardar referencia
      
      marker.addListener('click', async () => {
        if (infoWindow) infoWindow.close();
        const contentString = `
          <div id="content">
            <h1>${antena.nombre}</h1>
            <div><p><b>Id:</b> ${antena.idAntena}</p>
            <p><b>Descripcion :</b> ${antena.descripcion}</p>
            <p><b>Coordenadas:</b> ${antena.coordenadas}</p></div>
          </div>
        `;
        infoWindow.setContent(contentString);
        infoWindow.open(mapa, marker);
      });
      
      circlesAntenas.push({ lat: latitud, lng: longitud });
      puntosMarcadorAntenas.features.push(turf.point([longitud, latitud], { id: antena.idAntena }));
    });
    
    await iniciarRenderizadoPorLotes("Antenas");
  }
}

function limpiarTipo(tipo) {
  console.log(`🧹 Limpiando ${tipo}`);
  
  // ✅ CANCELAR timeouts pendientes PRIMERO
  const timeouts = tipo === "Cajas" ? timeoutsCajas : timeoutsAntenas;
  timeouts.forEach(id => clearTimeout(id));
  timeouts.length = 0;
  
  if (tipo === "Cajas") {
    unionPolygonsCajas.forEach(p => p.setMap(null));
    unionPolygonsCajas = [];
    circlesCajas = [];
    unionCajas = null;
    puntosMarcadorCajas = turf.featureCollection([]);
  } else if (tipo === "Antenas") {
    unionPolygonsAntenas.forEach(p => p.setMap(null));
    unionPolygonsAntenas = [];
    circlesAntenas = [];
    unionAntenas = null;
    puntosMarcadorAntenas = turf.featureCollection([]);
  }
}

export async function eliminarMapa() {
  limpiarTipo("Cajas");
  limpiarTipo("Antenas");

  // Limpiar marcadores visibles
  marcadoresVisibles.forEach(m => m.setMap(null));
  marcadoresVisibles = [];

  mapa = null;
  marcadoresCajas = [];
  tipoActivo = null;

  if (marcador) {
    marcador.setMap(null);
    marcador = null;
  }
}

// Fuerza la limpieza de marcadores visibles (útil al cambiar filtros/tipo desde la UI)
export function limpiarMarcadoresVisibles() {
  try {
    if (Array.isArray(marcadoresVisibles)) {
      marcadoresVisibles.forEach(m => {
        try { if (m && typeof m.setMap === 'function') m.setMap(null); } catch(e){}
      });
    }
  } finally {
    marcadoresVisibles = [];
  }
}

export async function eventoMapa(valor) {
  if (!mapa) {
    console.error('eventoMapa llamado pero el mapa aún no está inicializado');
    return marcadorCoordenada;
  }

  if (valor) {
    mapa.addListener('click', async (e) => {
      marcadorCoordenada = { lat: e.latLng.lat(), lng: e.latLng.lng() };

      // Mover/crear marcador principal en el mapa
      if (marcador) marcador.setMap(null);
      marcador = new AdvancedMarkerElement({
        position: e.latLng,
        map: mapa,
        title: "Marcador"
      });

      // Si existen inputs de coordenadas en el DOM, actualizarlos
      try {
        const lat = marcadorCoordenada.lat.toFixed(6);
        const lng = marcadorCoordenada.lng.toFixed(6);
        const value = `${lat},${lng}`;

        const inputCoord = document.getElementById('coordenadas_servicio');
        const inputCoordMostrar = document.getElementById('coordenadas_mostrar');

        if (inputCoord) inputCoord.value = value;
        if (inputCoordMostrar) inputCoordMostrar.value = value;
      } catch (err) {
        console.warn('No se pudieron actualizar los inputs de coordenadas desde eventoMapa:', err);
      }
    });
  }
  return marcadorCoordenada;
}

export async function verificarCoberturaCoordenadas(latitud, longitud) {
  if (typeof latitud !== 'number' || typeof longitud !== 'number') {
    return { tieneCobertura: false, tipo: tipoActivo, lat: latitud, lng: longitud };
  }

  const punto = turf.point([longitud, latitud]);
  const union = tipoActivo === "Cajas" ? unionCajas : unionAntenas;

  if (!union) {
    return { tieneCobertura: false, tipo: tipoActivo, lat: latitud, lng: longitud };
  }

  const dentro = turf.booleanPointInPolygon(punto, union);
  return {
    tieneCobertura: dentro,
    tipo: tipoActivo,
    lat: latitud,
    lng: longitud,
  };
}

let marcadorActivo = null;

export async function buscarCoordenadassinMapa(latitud, longitud) {
  if (!mapa) return;

  const posicion = new google.maps.LatLng(latitud, longitud);
  mapa.setCenter(posicion);
  mapa.setZoom(15);

  if (marcadorActivo) marcadorActivo.setMap(null);
  marcadorActivo = new AdvancedMarkerElement({
    position: posicion,
    map: mapa,
    title: "Ubicación buscada"
  });
}

export function obtenerCoordenadasClick() {
  if (!mapa) return;

  mapa.setOptions({ draggableCursor: "default" });
  mapa.addListener("click", (e) => {
    const latitud = e.latLng.lat();
    const longitud = e.latLng.lng();
    const punto = turf.point([longitud, latitud]);

    // Verificar contra la unión del tipo activo
    const union = tipoActivo === "Cajas" ? unionCajas : unionAntenas;

    if (!union || !turf.booleanPointInPolygon(punto, union)) {
      ultimaCoordenada = { latitud, longitud };
      if (marcadorActivo) marcadorActivo.setMap(null);
      marcadorActivo = new AdvancedMarkerElement({
        position: { lat: latitud, lng: longitud },
        map: mapa,
        title: "Coordenada Seleccionada"
      });
    }
  });
}