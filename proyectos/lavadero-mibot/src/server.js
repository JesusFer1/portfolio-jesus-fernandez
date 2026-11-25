const express = require('express');
const cors = require('cors');
const path = require('path');
const multer = require('multer');
const config = require('./config');
const { requireLocalAdminAuth } = require('./middleware/localAuthMiddleware');

// Importar servicios Multi-Tenant y Baileys WhatsApp
const { procesarRespuestaIAMultiTenant } = require('./ai_handler');
const { consultarMedidasVehiculo, analizarFotoVehiculo } = require('./services/aiService');
const { obtenerUbicacionOSM } = require('./osm_service');
const { generarCodigoQRWhatsApp } = require('./qr_generator');
const { enviarNotificacionesDoblesMultiTenant } = require('./notification_service');
const { iniciarWhatsAppBaileys, getBaileysStatus } = require('./whatsapp_baileys');
const { 
  agendarTurnoDB, 
  obtenerTurnosDB, 
  obtenerTokenStatsDB,
  obtenerTenantPorIdOSlug,
  obtenerTenantPorPhoneId,
  obtenerServiciosTenant,
  guardarServicioTenant,
  actualizarPrecioServicio,
  actualizarEstadoPagoTenant,
  obtenerTodosTenants
} = require('./db/database');

const app = express();

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 10 * 1024 * 1024 }
});

app.use('/client', express.static(path.join(__dirname, '../public/client')));
app.use('/admin', express.static(path.join(__dirname, '../public/admin')));
app.use(express.static(path.join(__dirname, '../public')));

app.get('/', (req, res) => {
  res.redirect('/client/index.html');
});

// ===================================================
// 💳 WEBHOOK MERCADO PAGO
// ===================================================

app.post('/api/payments/webhook/mercadopago', async (req, res) => {
  try {
    const { type, action, data } = req.body;
    const tenantId = req.query.tenantId || req.body.external_reference || 1;
    const status = req.body.status || (data && data.status) || 'approved';

    if (action === 'payment.created' || action === 'subscription_preapproval' || type === 'payment') {
      if (status === 'approved' || status === 'authorized') {
        await actualizarEstadoPagoTenant(tenantId, 'activo');
      } else if (status === 'rejected' || status === 'cancelled') {
        await actualizarEstadoPagoTenant(tenantId, 'inactivo');
      }
    }

    res.status(200).json({ received: true });
  } catch (error) {
    res.status(500).json({ error: 'Error al procesar webhook de Mercado Pago' });
  }
});

// ===================================================
// 🌐 REST API MULTI-TENANT
// ===================================================

app.post('/api/chat', async (req, res) => {
  try {
    const { message, sessionId, tenantId } = req.body;
    const clientIp = req.ip || req.connection.remoteAddress;

    if (!message) return res.status(400).json({ error: 'El mensaje es requerido' });

    const resultado = await procesarRespuestaIAMultiTenant(message, tenantId || 1, sessionId || 'web_client', clientIp);
    res.json(resultado);
  } catch (error) {
    res.status(500).json({ error: 'Error al procesar mensaje con la IA' });
  }
});

app.post('/api/car-dimensions', async (req, res) => {
  try {
    const { model, sessionId } = req.body;
    const clientIp = req.ip || req.connection.remoteAddress;

    if (!model) return res.status(400).json({ error: 'Debe ingresar el modelo' });

    const medidas = await consultarMedidasVehiculo(model, sessionId || 'web_client', clientIp);
    res.json(medidas);
  } catch (error) {
    res.status(500).json({ error: 'Error al consultar medidas' });
  }
});

app.post('/api/analyze-photo', upload.single('photo'), async (req, res) => {
  try {
    const sessionId = req.body.sessionId || 'web_client';
    const clientIp = req.ip || req.connection.remoteAddress;

    if (!req.file) return res.status(400).json({ error: 'No se adjuntó imagen' });

    const resultado = await analizarFotoVehiculo(req.file.buffer, req.file.mimetype, sessionId, clientIp);
    res.json(resultado);
  } catch (error) {
    res.status(500).json({ error: 'Error al analizar foto' });
  }
});

app.get('/api/location', async (req, res) => {
  try {
    const tenantId = req.query.tenantId || 1;
    const tenant = await obtenerTenantPorIdOSlug(tenantId);
    const osmInfo = await obtenerUbicacionOSM();
    if (tenant) {
      osmInfo.negocio = tenant.nombre_empresa;
      osmInfo.direccion = tenant.direccion;
    }
    res.json(osmInfo);
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener ubicación OSM' });
  }
});

app.get('/api/qr', async (req, res) => {
  try {
    const baileysStatus = getBaileysStatus();
    if (baileysStatus.qrDataUrl) {
      return res.json({
        whatsappLink: `https://wa.me/${config.ownerPhone.replace(/[^0-9]/g, '')}`,
        qrDataUrl: baileysStatus.qrDataUrl,
        isConnected: baileysStatus.isConnected
      });
    }

    const tenantId = req.query.tenantId || 1;
    const tenant = await obtenerTenantPorIdOSlug(tenantId);
    const phone = tenant ? tenant.owner_phone : config.ownerPhone;
    const message = req.query.message || `Hola, quiero agendar un turno en ${tenant ? tenant.nombre_empresa : 'No Limits Park'}`;
    
    const qrData = await generarCodigoQRWhatsApp(phone, message);
    res.json({ ...qrData, isConnected: baileysStatus.isConnected });
  } catch (error) {
    res.status(500).json({ error: 'Error al generar QR' });
  }
});

app.post('/api/turnos', async (req, res) => {
  try {
    const { tenant_id, cliente_nombre, cliente_telefono, modelo_auto, medidas_auto, servicio, fecha, hora } = req.body;

    if (!cliente_nombre || !cliente_telefono || !modelo_auto || !servicio || !fecha || !hora) {
      return res.status(400).json({ error: 'Faltan campos obligatorios' });
    }

    const nuevoTurno = await agendarTurnoDB({
      tenant_id: tenant_id || 1,
      cliente_nombre,
      cliente_telefono,
      modelo_auto,
      medidas_auto,
      servicio,
      fecha,
      hora
    });

    const notificaciones = await enviarNotificacionesDoblesMultiTenant(nuevoTurno);

    res.status(201).json({
      success: true,
      mensaje: '¡Turno agendado con éxito!',
      turno: nuevoTurno,
      notificaciones
    });
  } catch (error) {
    res.status(500).json({ error: 'Error interno al agendar turno' });
  }
});

// ===================================================
// 🔒 PANEL DE CONTROL
// ===================================================

app.get('/api/tenant/:tenantId/services', async (req, res) => {
  try {
    const servicios = await obtenerServiciosTenant(req.params.tenantId);
    res.json(servicios);
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener servicios' });
  }
});

app.put('/api/tenant/:tenantId/services/:serviceId', requireLocalAdminAuth, async (req, res) => {
  try {
    const { serviceId } = req.params;
    const { precio } = req.body;
    const actualizado = await actualizarPrecioServicio(serviceId, parseFloat(precio));
    res.json({ success: true, servicio: actualizado });
  } catch (error) {
    res.status(500).json({ error: 'Error al actualizar el precio' });
  }
});

app.get('/api/tenant/:tenantId/turnos', requireLocalAdminAuth, async (req, res) => {
  try {
    const turnos = await obtenerTurnosDB(req.params.tenantId);
    res.json(turnos);
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener turnos' });
  }
});

app.post('/api/admin/verify', requireLocalAdminAuth, async (req, res) => {
  const token = req.headers['x-admin-token'] || req.query.token || req.body.token;
  const tenant = await obtenerTenantPorIdOSlug(token);
  res.json({ 
    success: true, 
    message: 'Autenticación Válida', 
    tenant: tenant || { id: 1, nombre_empresa: 'No Limits Park', slug: 'no-limits-park', admin_secret_token: token } 
  });
});

// Iniciar Servidor Express y Conexión de Baileys WhatsApp Web
app.listen(config.port, async () => {
  console.log(`\n==================================================`);
  console.log(`🚀 CHATBOT WHATSAPP BAILEYS & WEB - NO LIMITS PARK`);
  console.log(`==================================================`);
  console.log(`🌐 Cliente Web:      http://localhost:${config.port}/client/index.html`);
  console.log(`🔒 Admin Panel:       http://localhost:${config.port}/admin/index.html`);
  console.log(`📱 Número Vinculado: ${config.ownerPhone}`);
  console.log(`==================================================\n`);

  // Iniciar la sesión de WhatsApp Web con Baileys
  iniciarWhatsAppBaileys().catch(err => {
    console.warn('Error al iniciar Baileys en segundo plano:', err.message);
  });
});
