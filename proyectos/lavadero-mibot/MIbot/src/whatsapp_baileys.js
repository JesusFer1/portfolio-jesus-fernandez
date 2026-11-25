const { 
  default: makeWASocket, 
  useMultiFileAuthState, 
  DisconnectReason, 
  fetchLatestBaileysVersion, 
  Browsers,
  downloadMediaMessage 
} = require('@whiskeysockets/baileys');
const qrcodeTerminal = require('qrcode-terminal');
const QRCode = require('qrcode');
const path = require('path');
const fs = require('fs');
const pino = require('pino');
const config = require('./config');
const { procesarRespuestaIAMultiTenant } = require('./ai_handler');
const { consultarMedidasVehiculo, analizarFotoVehiculo } = require('./services/aiService');
const { obtenerUbicacionOSM } = require('./osm_service');
const { agendarTurnoDB } = require('./db/database');

let sock = null;
let currentQrDataUrl = null;
let isConnected = false;

/**
 * Módulo Principal de WhatsApp Web con Baileys (100% WhatsApp Native)
 */
async function iniciarWhatsAppBaileys() {
  console.log('\n==================================================');
  console.log('⚡ INICIANDO BOT 100% INTEGRADO A WHATSAPP');
  console.log('==================================================');

  const authFolder = path.join(__dirname, '../auth_info_baileys');
  const { state, saveCreds } = await useMultiFileAuthState(authFolder);
  const { version } = await fetchLatestBaileysVersion();

  sock = makeWASocket({
    version,
    logger: pino({ level: 'silent' }),
    printQRInTerminal: false,
    auth: state,
    browser: Browsers.ubuntu('Chrome'),
    generateHighQualityLinkPreview: true
  });

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', async (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      console.log('\n📱 [CÓDIGO QR DE WHATSAPP DETECTADO]');
      console.log('Escaneá este código QR con WhatsApp (Teléfono: 1158959978):');
      
      qrcodeTerminal.generate(qr, { small: true });

      try {
        currentQrDataUrl = await QRCode.toDataURL(qr);
        const qrPath = path.join(__dirname, '../public/whatsapp_qr.png');
        await QRCode.toFile(qrPath, qr);
      } catch (e) {
        console.warn('Error al guardar QR:', e.message);
      }
    }

    if (connection === 'close') {
      const statusCode = (lastDisconnect?.error)?.output?.statusCode;
      const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
      isConnected = false;

      console.warn(`⚠️ Conexión de WhatsApp cerrada. Reintentando: ${shouldReconnect}`);
      if (shouldReconnect) {
        setTimeout(iniciarWhatsAppBaileys, 3000);
      }
    } else if (connection === 'open') {
      isConnected = true;
      console.log('\n==================================================');
      console.log('✅ WHATSAPP WEB CONECTADO Y LISTO');
      console.log(`📱 Número Vinculado: ${config.ownerPhone}`);
      console.log('==================================================\n');
    }
  });

  // 💬 PROCESAMIENTO DE MENSAJES DE WHATSAPP (TEXTO, FOTOS, MEDIDAS, UBICACIÓN Y TURNOS)
  sock.ev.on('messages.upsert', async (m) => {
    try {
      if (m.type !== 'notify') return;

      for (const msg of m.messages) {
        if (msg.key.fromMe) continue;
        const fromJid = msg.key.remoteJid;

        if (fromJid.endsWith('@g.us') || fromJid === 'status@broadcast') continue;

        const senderPhone = fromJid.replace(/[^0-9]/g, '');

        // 📷 1. MANEJO DE FOTOS ENVIADAS POR EL CLIENTE VÍA WHATSAPP
        if (msg.message?.imageMessage) {
          console.log(`📷 [FOTO RECIBIDA EN WHATSAPP] De ${senderPhone}`);

          try {
            // Descargar el buffer de la foto directamente del mensaje de WhatsApp
            const imageBuffer = await downloadMediaMessage(msg, 'buffer', {});
            const mimeType = msg.message.imageMessage.mimetype || 'image/jpeg';

            await sock.sendMessage(fromJid, { 
              text: '📸 *Foto recibida.* Analizando el estado de tu vehículo con nuestra Inteligencia Artificial...' 
            });

            // Analizar la foto con Gemini Vision AI
            const resFoto = await analizarFotoVehiculo(imageBuffer, mimeType, `wa_${senderPhone}`, senderPhone);

            if (resFoto && resFoto.analisis) {
              const respuestaFormateada = `🤖 *Análisis Visual de tu Vehículo (No Limits Park):*\n\n${resFoto.analisis}\n\n¿Te gustaría agendar un turno para realizarle este tratamiento a tu auto?`;
              await sock.sendMessage(fromJid, { text: respuestaFormateada });
            }
          } catch (errFoto) {
            console.error('Error al procesar foto en WhatsApp:', errFoto.message);
            await sock.sendMessage(fromJid, { 
              text: '¡Foto recibida correctamente! Te sugerimos nuestro *Lavado Completo* o *Encerado Acrílico* en **No Limits Park**. ¿Para qué día te gustaría agendar turno?' 
            });
          }
          continue;
        }

        // 💬 2. MANEJO DE MENSAJES DE TEXTO Y CONSULTAS
        const text = msg.message?.conversation || 
                     msg.message?.extendedTextMessage?.text || 
                     '';

        if (!text.trim()) continue;

        const lowerText = text.toLowerCase().trim();
        console.log(`\n💬 [MENSAJE ENTRANTE WHATSAPP] De ${senderPhone}: "${text}"`);

        // 📍 2.1 CONSULTA DE UBICACIÓN Y MAPA EN WHATSAPP
        if (lowerText.includes('donde') || lowerText.includes('ubicacion') || lowerText.includes('direccion') || lowerText.includes('mapa') || lowerText.includes('como llegar')) {
          const osm = await obtenerUbicacionOSM();
          const respuestaMapa = `📍 *Ubicación de No Limits Park:*\n\n` +
            `🏢 *Dirección*: ${osm.direccion}\n` +
            `⏰ *Horarios*: Lunes a Sábados de 08:30 a 18:30 hs.\n\n` +
            `🗺️ *Ver Mapa Interactivo en OpenStreetMap*:\n${osm.osmWebUrl}\n\n` +
            `¿Deseas pedir un turno para hoy o mañana?`;
          
          await sock.sendMessage(fromJid, { text: respuestaMapa });
          continue;
        }

        // 📐 2.2 CONSULTA DE MEDIDAS Y DIMENSIONES EN WHATSAPP
        if (lowerText.startsWith('medida') || lowerText.startsWith('medidas') || lowerText.includes('cuanto mide') || lowerText.includes('dimensiones')) {
          const modeloCoincidencia = text.replace(/medida(s)?|cuanto mide|dimensiones|del|de|mi|auto/gi, '').trim();
          
          if (modeloCoincidencia.length > 2) {
            await sock.sendMessage(fromJid, { text: `📐 Consultando especificaciones y medidas para *${modeloCoincidencia}*...` });
            
            const medidas = await consultarMedidasVehiculo(modeloCoincidencia, `wa_${senderPhone}`, senderPhone);
            const respuestaMedidas = `📐 *Especificaciones de ${medidas.modelo}:*\n\n` +
              `📏 *Largo*: ${medidas.largo_m || 'N/A'}\n` +
              `📐 *Ancho*: ${medidas.ancho_m || 'N/A'}\n` +
              `📐 *Alto*: ${medidas.alto_m || 'N/A'}\n` +
              `🚘 *Categoría*: ${medidas.categoria || 'Vehículo'}\n\n` +
              `🧼 *Lavado Recomendado*: ${medidas.lavado_recomendado || 'Lavado Completo'}\n` +
              `💡 *Detalle*: ${medidas.descripcion || ''}\n\n` +
              `¿Te gustaría agendar un turno para este auto? Indícanos el día y horario preferido.`;
            
            await sock.sendMessage(fromJid, { text: respuestaMedidas });
            continue;
          }
        }

        // 🤖 2.3 CHAT INTELIGENTE CON CONTEXTO E HISTORIAL (Groq Llama-3.3-70b / Gemini)
        const resIA = await procesarRespuestaIAMultiTenant(text, 1, `wa_${senderPhone}`, senderPhone);

        if (resIA && resIA.respuesta) {
          console.log(`🤖 [ENVIANDO RESPUESTA IA A ${senderPhone}]: "${resIA.respuesta.substring(0, 70)}..."`);
          await sock.sendMessage(fromJid, { text: resIA.respuesta });

          // Detectar si la respuesta de la IA confirmó un turno para enviar la alerta automática al dueño
          const respLower = resIA.respuesta.toLowerCase();
          if (respLower.includes('agendado') || respLower.includes('confirmado el turno') || respLower.includes('turno quedó')) {
            const alertaDueno = `🚨 *¡NUEVO TURNO CONFIRMADO VÍA WHATSAPP Y BOT!*\n\n` +
              `📱 *Cliente WhatsApp*: +${senderPhone}\n` +
              `💬 *Resumen del Bot*: ${resIA.respuesta}\n` +
              `📆 *Fecha de Registro*: ${new Date().toLocaleString('es-AR')}`;
            
            await enviarNotificacionDuenoBaileys(alertaDueno);
          }
        }
      }
    } catch (err) {
      console.error('Error en procesar mensaje Baileys:', err.message);
    }
  });
}

/**
 * Enviar notificación directa al dueño en WhatsApp (+5491158959978)
 */
async function enviarNotificacionDuenoBaileys(mensajeTexto) {
  if (!sock || !isConnected) {
    console.log(`[Notificación en consola]: ${mensajeTexto}`);
    return;
  }

  try {
    const cleanPhone = config.ownerPhone.replace(/[^0-9]/g, '');
    const ownerJid = `${cleanPhone}@s.whatsapp.net`;
    await sock.sendMessage(ownerJid, { text: mensajeTexto });
    console.log(`🚨 [NOTIFICACIÓN ENVIADA AL DUEÑO EN WHATSAPP]: +${cleanPhone}`);
  } catch (err) {
    console.warn('Error al enviar notificación al dueño por Baileys:', err.message);
  }
}

function getBaileysStatus() {
  return {
    isConnected,
    qrDataUrl: currentQrDataUrl
  };
}

if (require.main === module) {
  iniciarWhatsAppBaileys().catch(console.error);
}

module.exports = {
  iniciarWhatsAppBaileys,
  enviarNotificacionDuenoBaileys,
  getBaileysStatus,
  getSock: () => sock
};
