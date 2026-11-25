const QRCode = require('qrcode');
const config = require('../config');

/**
 * Generador de Códigos QR para abrir el chat de WhatsApp con el bot
 */
const generarQrWhatsApp = async (phone = config.ownerPhone, mensajeInicial = 'Hola No limits Park, quiero pedir un turno para mi auto') => {
  try {
    // Sanitizar número telefónico
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const encodedText = encodeURIComponent(mensajeInicial);
    const whatsappLink = `https://wa.me/${cleanPhone}?text=${encodedText}`;

    // Generar DataURL PNG para mostrar directamente en tags <img src="..."> en la web
    const qrDataUrl = await QRCode.toDataURL(whatsappLink, {
      errorCorrectionLevel: 'H',
      type: 'image/png',
      margin: 2,
      color: {
        dark: '#1e293b',  // Color oscuro elegante
        light: '#ffffff'
      }
    });

    return {
      whatsappLink,
      qrDataUrl,
      phone: cleanPhone
    };
  } catch (error) {
    console.error('Error al generar código QR:', error);
    throw error;
  }
};

module.exports = {
  generarQrWhatsApp
};
