const config = require('../config');

/**
 * Servicio para integrar mapas de OpenStreetMap
 */
const getOsmLocation = () => {
  const { address, coords, name } = config.carWashInfo;
  const zoom = 17;

  const osmWebUrl = `https://www.openstreetmap.org/?mlat=${coords.lat}&mlon=${coords.lng}#map=${zoom}/${coords.lat}/${coords.lng}`;
  const staticTileUrl = `https://tile.openstreetmap.org/${zoom}/12345/67890.png`; // Plantilla estándar OSM

  return {
    name,
    address,
    coords,
    zoom,
    osmWebUrl,
    leafletConfig: {
      center: [coords.lat, coords.lng],
      zoom: zoom,
      tileLayer: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      marker: {
        lat: coords.lat,
        lng: coords.lng,
        popupText: `<b>${name}</b><br>${address}`
      }
    }
  };
};

module.exports = {
  getOsmLocation
};
