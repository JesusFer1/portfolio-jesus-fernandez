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

let sock = null;
let currentQrDataUrl = null;
let isConnected = false;

/**
 * Módulo Principal de WhatsApp Web (Baileys + Gemini 1.5 Flash Vision + Mapeo de Ficha Técnica Real)
 */
async function iniciarWhatsAppBaileys() {
  console.log('\n==================================================');
  console.log('⚡ INICIANDO BOT DE WHATSAPP CON MAPEO DE FICHA TÉCNICA REAL');
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

  // 💬 ESCUCHADOR DE MENSAJES DE WHATSAPP (MESSAGES.UPSERT)
  sock.ev.on('messages.upsert', async (m) => {
    try {
      if (m.type !== 'notify') return;

      for (const msg of m.messages) {
        if (msg.key.fromMe) continue;
        const fromJid = msg.key.remoteJid;

        if (fromJid.endsWith('@g.us') || fromJid === 'status@broadcast') continue;

        const senderPhone = fromJid.replace(/[^0-9]/g, '');

        // 📷 1. PROCESAMIENTO DE IMÁGENES ENTRANTES (imageMessage)
        if (msg.message?.imageMessage) {
          console.log(`📷 [IMAGEN RECIBIDA EN WHATSAPP] De ${senderPhone}`);

          try {
            await sock.sendMessage(fromJid, { 
              text: '📸 *Imagen recibida.* Analizando el vehículo con Gemini 1.5 Flash y consultando ficha técnica oficial...' 
            });

            // Descargar el buffer de la foto enviada por el cliente
            const imageBuffer = await downloadMediaMessage(msg, 'buffer', {});
            const mimeType = msg.message.imageMessage.mimetype || 'image/jpeg';

            // Procesar la imagen con Gemini 1.5 Flash (Prompt Estricto JSON)
            const resVision = await analizarFotoVehiculo(imageBuffer, mimeType, `wa_${senderPhone}`, senderPhone);

            if (resVision && resVision.identificado) {
              const { marca, modelo, categoria, fichaTecnica } = resVision;
              console.log(`✅ [VEHÍCULO IDENTIFICADO]: ${marca} ${modelo} (${categoria})`);

              // Respuesta estructurada con ficha técnica real
              const respuestaWhatsapp = `🚘 *Vehículo Identificado:* ${fichaTecnica.marca || marca} ${fichaTecnica.modelo || modelo}\n` +
                `📐 *Categoría:* ${fichaTecnica.categoria_nombre || categoria.toUpperCase()}\n\n` +
                `📏 *Ficha Técnica Oficial:*\n` +
                `• *Largo*: ${fichaTecnica.largo_m || 'N/A'}\n` +
                `• *Ancho*: ${fichaTecnica.ancho_m || 'N/A'}\n` +
                `• *Alto*: ${fichaTecnica.alto_m || 'N/A'}\n\n` +
                `🧼 *Lavado Recomendado:* ${fichaTecnica.lavado_recomendado || 'Lavado Completo - $14.000'}\n\n` +
                `¿Te gustaría agendar un turno para tu *${fichaTecnica.marca || marca} ${fichaTecnica.modelo || modelo}*? Indícanos el día y horario que prefieras.`;

              // Insertar el contexto en el motor conversacional de IA
              const promptContexto = `El cliente envió una foto de su auto. Identificamos que tiene un ${marca} ${modelo} (${categoria}), con largo ${fichaTecnica.largo_m} y ancho ${fichaTecnica.ancho_m}. Ofrece agendar el turno.`;
              await procesarRespuestaIAMultiTenant(promptContexto, 1, `wa_${senderPhone}`, senderPhone);

              await sock.sendMessage(fromJid, { text: respuestaWhatsapp });
            } else {
              // Si no se identificó un vehículo claro
              const respuestaNoIdentificado = `📷 Recibimos tu imagen, pero no pudimos identificar con claridad la marca y modelo del vehículo.\n\nPor favor envíanos una foto frontal o lateral del auto o indícanos por mensaje la marca y modelo (ej: *Peugeot 208*, *Toyota Hilux*).`;
              await sock.sendMessage(fromJid, { text: respuestaNoIdentificado });
            }
          } catch (errFoto) {
            console.error('Error al procesar la imagen con Gemini:', errFoto.message);
            await sock.sendMessage(fromJid, { 
              text: '¡Foto recibida correctamente! Te sugerimos nuestro *Lavado Completo* o *Encerado Acrílico* en **No Limits Park**. ¿Para qué día te gustaría agendar turno?' 
            });
          }
          continue;
        }

        // 💬 2. MANEJO DE MENSAJES DE TEXTO ENTRANTES
        const text = msg.message?.conversation || 
                     msg.message?.extendedTextMessage?.text || 
                     '';

        if (!text.trim()) continue;

        const lowerText = text.toLowerCase().trim();
        console.log(`\n💬 [MENSAJE ENTRANTE WHATSAPP] De ${senderPhone}: "${text}"`);

        // 📍 Ubicación OpenStreetMap
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

        // 📐 Consulta de Medidas de Ficha Técnica
        if (lowerText.startsWith('medida') || lowerText.startsWith('medidas') || lowerText.includes('cuanto mide') || lowerText.includes('dimensiones')) {
          const modeloCoincidencia = text.replace(/medida(s)?|cuanto mide|dimensiones|del|de|mi|auto/gi, '').trim();
          
          if (modeloCoincidencia.length > 2) {
            await sock.sendMessage(fromJid, { text: `📐 Consultando ficha técnica para *${modeloCoincidencia}*...` });
            
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

        // 🤖 Chatbot Conversacional con IA (Groq Llama-3.3-70b / Gemini)
        const resIA = await procesarRespuestaIAMultiTenant(text, 1, `wa_${senderPhone}`, senderPhone);

        if (resIA && resIA.respuesta) {
          console.log(`🤖 [ENVIANDO RESPUESTA IA A ${senderPhone}]: "${resIA.respuesta.substring(0, 70)}..."`);
          await sock.sendMessage(fromJid, { text: resIA.respuesta });

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
 * Enviar notificación directa al dueño en WhatsApp
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
