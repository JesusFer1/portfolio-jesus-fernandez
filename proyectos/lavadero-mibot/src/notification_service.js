const axios = require('axios');
const config = require('./config');
const { obtenerTenantPorIdOSlug } = require('./db/database');
const { enviarNotificacionDuenoBaileys } = require('./whatsapp_baileys');

/**
 * Sistema de Notificaciones Dobles Multi-Tenant (Cliente + Dueño por Baileys)
 */
async function enviarNotificacionesDoblesMultiTenant(turnoData) {
  const { tenant_id, cliente_nombre, cliente_telefono, modelo_auto, medidas_auto, servicio, fecha, hora } = turnoData;

  // Cargar datos de la empresa desde la BD
  const tenant = await obtenerTenantPorIdOSlug(tenant_id || 1);
  const nombreEmpresa = tenant ? tenant.nombre_empresa : 'No Limits Park';
  const direccionEmpresa = tenant ? tenant.direccion : config.carWashInfo.address;
  const ownerPhone = tenant ? tenant.owner_phone : config.ownerPhone;
  const lat = tenant ? tenant.lat : config.carWashInfo.coords.lat;
  const lng = tenant ? tenant.lng : config.carWashInfo.coords.lng;

  const mapLink = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}`;

  // 1. Mensaje para el Cliente
  const clienteMensaje = {
    to: cliente_telefono,
    type: 'CONFIRMACION_CLIENTE',
    body: `✅ *¡Turno Confirmado en ${nombreEmpresa}!*\n\n` +
      `👤 *Cliente*: ${cliente_nombre}\n` +
      `🚗 *Vehículo*: ${modelo_auto} (${medidas_auto || 'Categoría Estándar'})\n` +
      `🧼 *Servicio*: ${servicio}\n` +
      `📅 *Fecha*: ${fecha}\n` +
      `⏰ *Hora*: ${hora} hs.\n` +
      `📍 *Ubicación*: ${direccionEmpresa}\n` +
      `🗺️ *OpenStreetMap*: ${mapLink}\n\n` +
      `¡Te esperamos en ${nombreEmpresa}!`
  };

  // 2. Alerta para el Dueño / Administrador
  const duenoMensajeTexto = `🚨 *¡NUEVO TURNO AGENDADO EN ${nombreEmpresa.toUpperCase()}!*\n\n` +
    `👤 *Cliente*: ${cliente_nombre} (${cliente_telefono})\n` +
    `🚘 *Auto*: ${modelo_auto}\n` +
    `📐 *Medidas*: ${medidas_auto || 'Consultadas'}\n` +
    `🧽 *Servicio*: ${servicio}\n` +
    `📆 *Cita*: ${fecha} a las ${hora} hs.`;

  const duenoMensaje = {
    to: ownerPhone,
    type: 'ALERTA_DUEÑO',
    body: duenoMensajeTexto
  };

  // Enviar alerta directa al celular del dueño usando Baileys
  await enviarNotificacionDuenoBaileys(duenoMensajeTexto);

  return { clienteMensaje, duenoMensaje };
}

module.exports = {
  enviarNotificacionesDoblesMultiTenant,
  enviarNotificacionesDobles: enviarNotificacionesDoblesMultiTenant
};
