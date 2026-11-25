require('dotenv').config();

module.exports = {
  port: process.env.PORT || 3000,
  geminiApiKey: process.env.GEMINI_API_KEY || '',
  adminSecretToken: process.env.ADMIN_SECRET_TOKEN || 'nolimits2026secretadmin',
  allowedIps: (process.env.ALLOWED_IPS || '127.0.0.1,::1,::ffff:127.0.0.1').split(','),
  ownerPhone: process.env.OWNER_PHONE || '+5491158959978',
  carWashInfo: {
    name: 'No Limits Park',
    address: 'C. 137 4213, B1886 Ranelagh, Provincia de Buenos Aires',
    coords: {
      lat: -34.7849469,
      lng: -58.2076322
    },
    services: [
      { id: 'simple', name: 'Lavado Simple (Exterior + Cristales)', price: 8000 },
      { id: 'completo', name: 'Lavado Completo (Exterior + Interior + Aspirado)', price: 14000 },
      { id: 'encerado', name: 'Lavado VIP + Encerado Acrílico Protegido', price: 22000 },
      { id: 'tapizados', name: 'Limpieza e Higienización Profunda de Tapizados', price: 35000 },
      { id: 'ceramico', name: 'Tratamiento Cerámico / Detailing', price: 65000 }
    ]
  }
};
