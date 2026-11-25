const QRCode = require('qrcode');
const path = require('path');
const fs = require('fs');
const config = require('./config');

/**
 * Script Generador de Código QR Dinámico para WhatsApp Bot
 */
async function generarCodigoQRWhatsApp(phone = config.ownerPhone, mensaje = 'Hola, quiero agendar un turno') {
  try {
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const encodedText = encodeURIComponent(mensaje);
    const whatsappLink = `https://wa.me/${cleanPhone}?text=${encodedText}`;

    // Generar DataURL PNG
    const qrDataUrl = await QRCode.toDataURL(whatsappLink, {
      errorCorrectionLevel: 'H',
      type: 'image/png',
      margin: 2,
      color: {
        dark: '#0f172a',
        light: '#ffffff'
      }
    });

    // Guardar archivo físico de imagen PNG
    const outputDir = path.join(__dirname, '../public');
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }

    const outputPath = path.join(outputDir, 'whatsapp_qr.png');
    await QRCode.toFile(outputPath, whatsappLink, {
      errorCorrectionLevel: 'H',
      margin: 2,
      color: {
        dark: '#0284c7',
        light: '#ffffff'
      }
    });

    return {
      whatsappLink,
      qrDataUrl,
      outputPath,
      cleanPhone
    };
  } catch (error) {
    console.error('Error al generar el Código QR:', error.message);
    throw error;
  }
}

if (require.main === module) {
  generarCodigoQRWhatsApp()
    .then((res) => {
      console.log('\n==================================================');
      console.log('✅ CÓDIGO QR GENERADO CON ÉXITO');
      console.log('==================================================');
      console.log(`📱 Enlace de WhatsApp: ${res.whatsappLink}`);
      console.log(`🖼️ Imagen guardada en: ${res.outputPath}`);
      console.log('==================================================\n');
    })
    .catch(console.error);
}

module.exports = {
  generarCodigoQRWhatsApp
};
