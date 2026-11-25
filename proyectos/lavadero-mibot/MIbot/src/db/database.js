const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const dbPath = path.join(__dirname, '../../lavanderia.db');

const db = new sqlite3.Database(dbPath, (err) => {
  if (err) {
    console.error('Error al conectar con SQLite Multi-Tenant:', err.message);
  } else {
    console.log('Conectado exitosamente a la base de datos SQLite Multi-Tenant.');
  }
});

function initDB() {
  return new Promise((resolve, reject) => {
    db.serialize(() => {
      db.run(`
        CREATE TABLE IF NOT EXISTS tenants (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          slug TEXT UNIQUE NOT NULL,
          nombre_empresa TEXT NOT NULL,
          direccion TEXT NOT NULL,
          lat REAL,
          lng REAL,
          plan TEXT DEFAULT 'Free',
          estado_pago TEXT DEFAULT 'activo',
          whatsapp_phone_id TEXT,
          owner_phone TEXT NOT NULL,
          system_prompt_custom TEXT,
          admin_secret_token TEXT NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
      `);

      db.run(`
        CREATE TABLE IF NOT EXISTS servicios (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          tenant_id INTEGER NOT NULL,
          nombre_servicio TEXT NOT NULL,
          descripcion TEXT,
          precio REAL NOT NULL,
          FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        )
      `);

      db.run(`
        CREATE TABLE IF NOT EXISTS turnos (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          tenant_id INTEGER DEFAULT 1,
          cliente_nombre TEXT NOT NULL,
          cliente_telefono TEXT NOT NULL,
          modelo_auto TEXT NOT NULL,
          medidas_auto TEXT,
          servicio TEXT NOT NULL,
          fecha TEXT NOT NULL,
          hora TEXT NOT NULL,
          estado TEXT DEFAULT 'CONFIRMADO',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
      `);

      db.run(`
        CREATE TABLE IF NOT EXISTS token_logs (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          tenant_id INTEGER DEFAULT 1,
          session_id TEXT NOT NULL,
          prompt_type TEXT NOT NULL,
          input_tokens INTEGER DEFAULT 0,
          output_tokens INTEGER DEFAULT 0,
          total_tokens INTEGER DEFAULT 0,
          prompt_summary TEXT,
          user_identifier TEXT,
          timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )
      `);

      db.run(`ALTER TABLE token_logs ADD COLUMN tenant_id INTEGER DEFAULT 1`, () => {});
      db.run(`ALTER TABLE turnos ADD COLUMN tenant_id INTEGER DEFAULT 1`, () => {});

      db.get(`SELECT id FROM tenants WHERE slug = 'no-limits-park'`, [], (err, row) => {
        let tenantId = row ? row.id : null;

        const seedServices = (tid) => {
          db.get(`SELECT COUNT(*) as count FROM servicios WHERE tenant_id = ?`, [tid], (err, sRow) => {
            if (!sRow || sRow.count === 0) {
              const stmt = db.prepare(`INSERT INTO servicios (tenant_id, nombre_servicio, descripcion, precio) VALUES (?, ?, ?, ?)`);
              stmt.run(tid, 'Lavado Simple', 'Exterior + Cristales', 8000);
              stmt.run(tid, 'Lavado Completo', 'Exterior + Interior + Aspirado', 14000);
              stmt.run(tid, 'Lavado VIP + Encerado Acrílico', 'Protección acrílica de pintura', 22000);
              stmt.run(tid, 'Limpieza de Tapizados', 'Higienización profunda de asientos', 35000);
              stmt.run(tid, 'Tratamiento Cerámico', 'Detailing cerámico completo', 65000);
              stmt.finalize(() => resolve());
            } else {
              resolve();
            }
          });
        };

        if (!tenantId) {
          db.run(`
            INSERT INTO tenants (slug, nombre_empresa, direccion, lat, lng, plan, estado_pago, whatsapp_phone_id, owner_phone, system_prompt_custom, admin_secret_token)
            VALUES (
              'no-limits-park',
              'No Limits Park',
              'C. 137 4213, B1886 Ranelagh, Provincia de Buenos Aires',
              -34.7849469,
              -58.2076322,
              'Pro',
              'activo',
              '5491142130000',
              '+5491142130000',
              'Sos la recepcionista virtual de No Limits Park, el lavadero de autos de Ranelagh. Atiende amigablemente, recomienda servicios y agenda turnos.',
              'nolimits2026secretadmin'
            )
          `, function (err) {
            if (!err && this.lastID) {
              seedServices(this.lastID);
            } else {
              resolve();
            }
          });
        } else {
          seedServices(tenantId);
        }
      });
    });
  });
}

const dbReadyPromise = initDB();

// Helpers Promisificados
const obtenerTenantPorIdOSlug = async (idOrSlug = 1) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    const query = isNaN(idOrSlug) 
      ? `SELECT * FROM tenants WHERE slug = ? OR admin_secret_token = ?` 
      : `SELECT * FROM tenants WHERE id = ?`;
    db.get(query, [idOrSlug], (err, row) => {
      if (err) return reject(err);
      resolve(row);
    });
  });
};

const obtenerTenantPorPhoneId = async (phoneId) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    db.get(`SELECT * FROM tenants WHERE whatsapp_phone_id = ? OR owner_phone = ?`, [phoneId, phoneId], (err, row) => {
      if (err) return reject(err);
      resolve(row);
    });
  });
};

const obtenerServiciosTenant = async (tenantId = 1) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    db.all(`SELECT * FROM servicios WHERE tenant_id = ? ORDER BY precio ASC`, [tenantId], (err, rows) => {
      if (err) return reject(err);
      resolve(rows || []);
    });
  });
};

const guardarServicioTenant = async (tenantId, nombre_servicio, descripcion, precio) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    db.run(
      `INSERT INTO servicios (tenant_id, nombre_servicio, descripcion, precio) VALUES (?, ?, ?, ?)`,
      [tenantId, nombre_servicio, descripcion, precio],
      function (err) {
        if (err) return reject(err);
        resolve({ id: this.lastID, tenant_id: tenantId, nombre_servicio, descripcion, precio });
      }
    );
  });
};

const actualizarPrecioServicio = async (servicioId, nuevoPrecio) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    db.run(
      `UPDATE servicios SET precio = ? WHERE id = ?`,
      [nuevoPrecio, servicioId],
      function (err) {
        if (err) return reject(err);
        resolve({ id: servicioId, precio: nuevoPrecio });
      }
    );
  });
};

const actualizarEstadoPagoTenant = async (tenantId, nuevoEstado) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    db.run(
      `UPDATE tenants SET estado_pago = ? WHERE id = ? OR slug = ?`,
      [nuevoEstado, tenantId, tenantId],
      function (err) {
        if (err) return reject(err);
        resolve({ tenant_id: tenantId, estado_pago: nuevoEstado });
      }
    );
  });
};

const agendarTurnoDB = async (turno) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    const tenantId = turno.tenant_id || 1;
    const query = `
      INSERT INTO turnos (tenant_id, cliente_nombre, cliente_telefono, modelo_auto, medidas_auto, servicio, fecha, hora, estado)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'CONFIRMADO')
    `;
    db.run(
      query,
      [
        tenantId,
        turno.cliente_nombre,
        turno.cliente_telefono,
        turno.modelo_auto,
        turno.medidas_auto || 'No especificadas',
        turno.servicio,
        turno.fecha,
        turno.hora
      ],
      function (err) {
        if (err) return reject(err);
        resolve({ id: this.lastID, tenant_id: tenantId, ...turno, estado: 'CONFIRMADO' });
      }
    );
  });
};

const obtenerTurnosDB = async (tenantId = 1) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    db.all(`SELECT * FROM turnos WHERE tenant_id = ? ORDER BY created_at DESC`, [tenantId], (err, rows) => {
      if (err) return reject(err);
      resolve(rows || []);
    });
  });
};

const registrarTokenLogDB = async (logData) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    const query = `
      INSERT INTO token_logs (tenant_id, session_id, prompt_type, input_tokens, output_tokens, total_tokens, prompt_summary, user_identifier)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    `;
    db.run(
      query,
      [
        logData.tenant_id || 1,
        logData.session_id || 'anon_session',
        logData.prompt_type || 'GENERAL',
        logData.input_tokens || 0,
        logData.output_tokens || 0,
        logData.total_tokens || 0,
        logData.prompt_summary || '',
        logData.user_identifier || 'Client_Web'
      ],
      function (err) {
        if (err) return reject(err);
        resolve({ id: this.lastID, ...logData });
      }
    );
  });
};

const obtenerTokenStatsDB = async (tenantId = 1) => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    const sqlTotal = `
      SELECT 
        SUM(total_tokens) as total_tokens,
        SUM(input_tokens) as total_input,
        SUM(output_tokens) as total_output,
        COUNT(*) as total_requests
      FROM token_logs
      WHERE tenant_id = ?
    `;

    const sqlBreakdown = `
      SELECT 
        prompt_type,
        COUNT(*) as count,
        SUM(total_tokens) as tokens
      FROM token_logs
      WHERE tenant_id = ?
      GROUP BY prompt_type
    `;

    const sqlLogs = `
      SELECT * FROM token_logs WHERE tenant_id = ? ORDER BY timestamp DESC LIMIT 100
    `;

    db.get(sqlTotal, [tenantId], (err, totals) => {
      if (err) return reject(err);

      db.all(sqlBreakdown, [tenantId], (err, breakdown) => {
        if (err) return reject(err);

        db.all(sqlLogs, [tenantId], (err, logs) => {
          if (err) return reject(err);

          resolve({
            totals: totals || { total_tokens: 0, total_input: 0, total_output: 0, total_requests: 0 },
            breakdown: breakdown || [],
            logs: logs || []
          });
        });
      });
    });
  });
};

const obtenerTodosTenants = async () => {
  await dbReadyPromise;
  return new Promise((resolve, reject) => {
    db.all(`SELECT id, slug, nombre_empresa, direccion, plan, estado_pago, owner_phone, created_at FROM tenants`, [], (err, rows) => {
      if (err) return reject(err);
      resolve(rows || []);
    });
  });
};

module.exports = {
  db,
  dbReadyPromise,
  obtenerTenantPorIdOSlug,
  obtenerTenantPorPhoneId,
  obtenerServiciosTenant,
  guardarServicioTenant,
  actualizarPrecioServicio,
  actualizarEstadoPagoTenant,
  agendarTurnoDB,
  obtenerTurnosDB,
  registrarTokenLogDB,
  obtenerTokenStatsDB,
  obtenerTodosTenants
};
