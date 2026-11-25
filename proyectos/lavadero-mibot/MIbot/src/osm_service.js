const axios = require('axios');
const config = require('./config');

/**
 * Servicio de OpenStreetMap (Nominatim & Leaflet Integration)
 * para el lavadero No Limits Park en Ranelagh, Bs. As.
 */

const ADDRESS_NO_LIMITS_PARK = 'C. 137 4213, B1886 Ranelagh, Provincia de Buenos Aires, Argentina';
const DEFAULT_COORDS = { lat: -34.789123, lng: -58.204567 };

/**
 * Resuelve las coordenadas mediante la API Nominatim de OpenStreetMap
 */
async function geocodificarDireccion(direccion = ADDRESS_NO_LIMITS_PARK) {
  try {
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}`;
    const response = await axios.get(url, {
      headers: { 'User-Agent': 'NoLimitsPark-CarWash-Bot/1.0' },
      timeout: 4000
    });

    if (response.data && response.data.length > 0) {
      const location = response.data[0];
      return {
        lat: parseFloat(location.lat),
        lng: parseFloat(location.lon),
        displayName: location.display_name
      };
    }
  } catch (error) {
    console.warn('Error al geocodificar con Nominatim, usando coordenadas predeterminadas de Ranelagh:', error.message);
  }
  return DEFAULT_COORDS;
}

/**
 * Obtiene la información completa del mapa para clientes
 */
async function obtenerUbicacionOSM() {
  const coords = await geocodificarDireccion();
  const zoom = 17;
  const osmWebUrl = `https://www.openstreetmap.org/?mlat=${coords.lat}&mlon=${coords.lng}#map=${zoom}/${coords.lat}/${coords.lng}`;

  return {
    negocio: 'No Limits Park',
    direccion: 'C. 137 4213, B1886 Ranelagh, Provincia de Buenos Aires',
    coords: coords,
    osmWebUrl: osmWebUrl,
    leafletConfig: {
      center: [coords.lat, coords.lng],
      zoom: zoom,
      tileLayer: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }
  };
}

module.exports = {
  geocodificarDireccion,
  obtenerUbicacionOSM,
  ADDRESS_NO_LIMITS_PARK,
  DEFAULT_COORDS
};
