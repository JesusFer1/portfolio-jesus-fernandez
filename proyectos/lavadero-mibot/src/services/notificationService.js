const config = require('../config');

/**
 * Servicio de Notificaciones Dobles (Cliente + Dueño)
 */
const procesarNotificacionTurno = async (turnoData) => {
  const { cliente_nombre, cliente_telefono, modelo_auto, medidas_auto, servicio, fecha, hora } = turnoData;
  const { address, name } = config.carWashInfo;
  const mapLink = `https://www.openstreetmap.org/?mlat=${config.carWashInfo.coords.lat}&mlon=${config.carWashInfo.coords.lng}`;

  // 1. Mensaje de Confirmación para el Cliente
  const clienteNotificacion = {
    destinatario: cliente_telefono,
    tipo: 'CONFIRMACION_CLIENTE',
    titulo: '✅ ¡Turno Confirmado en No limits Park!',
    mensaje: `Hola ${cliente_nombre}, tu turno ha sido agendado exitosamente:\n\n` +
      `🚗 **Vehículo**: ${modelo_auto} (${medidas_auto || 'Categoría Estándar'})\n` +
      `🧼 **Servicio**: ${servicio}\n` +
      `📅 **Fecha**: ${fecha}\n` +
      `⏰ **Hora**: ${hora}\n` +
      `📍 **Ubicación**: ${address}\n` +
      `🗺️ **Mapa OpenStreetMap**: ${mapLink}\n\n` +
      `¡Te esperamos en ${name}! Si necesitas reprogramar, responde a este chat.`,
    timestamp: new Date().toISOString()
  };

  // 2. Alerta de Notificación para el Dueño / Creador
  const duenoNotificacion = {
    destinatario: config.ownerPhone,
    tipo: 'ALERTA_DUEÑO',
    titulo: '🔔 ¡NUEVO TURNO AGENDADO!',
    mensaje: `🚨 **Atención No limits Park - Nuevo Turno Agendado**:\n\n` +
      `👤 **Cliente**: ${cliente_nombre} (${cliente_telefono})\n` +
      `🚘 **Auto**: ${modelo_auto}\n` +
      `📐 **Medidas**: ${medidas_auto || 'Consultadas'}\n` +
      `🧽 **Servicio**: ${servicio}\n` +
      `📆 **Fecha y Hora**: ${fecha} a las ${hora} hs.\n`,
    timestamp: new Date().toISOString()
  };

  console.log(`[NOTIFICACIÓN EMITIDA] Cliente: ${cliente_nombre} | Dueño Notificado.`);

  return {
    clienteNotificacion,
    duenoNotificacion
  };
};

module.exports = {
  procesarNotificacionTurno
};
