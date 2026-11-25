const config = require('../config');

/**
 * Middleware para restringir el acceso al Panel de Administración únicamente
 * a la computadora local (localhost) o direcciones IP en la lista blanca y con token de seguridad.
 */
const requireLocalAdminAuth = (req, res, next) => {
  const clientIp = req.ip || req.connection.remoteAddress || '';
  const adminToken = req.headers['x-admin-token'] || req.query.token || (req.body && req.body.token);

  // Normalizar IP para localhost IPv4 e IPv6
  const isLocalhost = 
    clientIp === '127.0.0.1' || 
    clientIp === '::1' || 
    clientIp === '::ffff:127.0.0.1' || 
    config.allowedIps.includes(clientIp);

  // Si no es IP local autorizada
  if (!isLocalhost) {
    console.warn(`[ACCESO BLOQUEADO] Intento de acceso no autorizado al panel local desde la IP: ${clientIp}`);
    return res.status(403).json({
      error: 'Acceso Denegado',
      message: 'El Panel de Administración es únicamente accesible desde la computadora local autorizada.'
    });
  }

  // Verificar Token de Administrador / Creador
  if (adminToken !== config.adminSecretToken) {
    return res.status(401).json({
      error: 'No Autorizado',
      message: 'Token de administrador no válido. Inicie sesión en la computadora local.'
    });
  }

  // Acceso concedido
  next();
};

module.exports = {
  requireLocalAdminAuth
};
