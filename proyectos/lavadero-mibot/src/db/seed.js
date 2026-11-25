const { db, dbReadyPromise } = require('./database');

async function runSeed() {
  await dbReadyPromise;
  console.log('\n==================================================');
  console.log('🌱 INICIANDO SEMBRADO DE DATOS SAAS MULTI-TENANT');
  console.log('==================================================');

  // 1. Tenant 1: No Limits Park (Ranelagh)
  const tenant1 = {
    slug: 'no-limits-park',
    nombre_empresa: 'No Limits Park',
    direccion: 'C. 137 4213, B1886 Ranelagh, Provincia de Buenos Aires',
    lat: -34.7849469,
    lng: -58.2076322,
    plan: 'Pro',
    estado_pago: 'activo',
    whatsapp_phone_id: '5491158959978',
    owner_phone: '+5491158959978',
    system_prompt_custom: 'Sos la recepcionista virtual de No Limits Park en Ranelagh. Atiende amigablemente, brinda cotizaciones y agenda turnos.',
    admin_secret_token: 'nolimits2026secretadmin'
  };

  // 2. Tenant 2: Lavadero VIP Quilmes (Demo Tenant)
  const tenant2 = {
    slug: 'vip-quilmes',
    nombre_empresa: 'Lavadero VIP Quilmes',
    direccion: 'Peatonal Rivadavia 250, Quilmes, Buenos Aires',
    lat: -34.7214,
    lng: -58.2561,
    plan: 'Free',
    estado_pago: 'activo',
    whatsapp_phone_id: '5491199998888',
    owner_phone: '+5491199998888',
    system_prompt_custom: 'Sos la recepcionista virtual de Lavadero VIP Quilmes. Ofrece nuestros lavados artesanales.',
    admin_secret_token: 'vipquilmes2026secret'
  };

  // Actualizar tenant 1 si ya existía
  db.run(`UPDATE tenants SET whatsapp_phone_id = '5491158959978', owner_phone = '+5491158959978' WHERE slug = 'no-limits-park'`);

  const seedTenant = (t) => new Promise((resolve) => {
    db.get(`SELECT id FROM tenants WHERE slug = ?`, [t.slug], (err, row) => {
      if (!row) {
        db.run(
          `INSERT INTO tenants (slug, nombre_empresa, direccion, lat, lng, plan, estado_pago, whatsapp_phone_id, owner_phone, system_prompt_custom, admin_secret_token)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
          [t.slug, t.nombre_empresa, t.direccion, t.lat, t.lng, t.plan, t.estado_pago, t.whatsapp_phone_id, t.owner_phone, t.system_prompt_custom, t.admin_secret_token],
          function (err) {
            if (err) console.error('Error seeding tenant:', err.message);
            else console.log(`✅ Tenant Creado: ${t.nombre_empresa} (ID: ${this.lastID})`);
            resolve(this ? this.lastID : null);
          }
        );
      } else {
        console.log(`ℹ️ Tenant actualizado: ${t.nombre_empresa} (ID: ${row.id})`);
        resolve(row.id);
      }
    });
  });

  const id1 = await seedTenant(tenant1);
  const id2 = await seedTenant(tenant2);

  if (id1) {
    db.run(`INSERT OR IGNORE INTO servicios (tenant_id, nombre_servicio, descripcion, precio) VALUES (${id1}, 'Lavado Simple', 'Exterior + Cristales', 8000)`);
    db.run(`INSERT OR IGNORE INTO servicios (tenant_id, nombre_servicio, descripcion, precio) VALUES (${id1}, 'Lavado Completo', 'Exterior + Interior + Aspirado', 14000)`);
    db.run(`INSERT OR IGNORE INTO servicios (tenant_id, nombre_servicio, descripcion, precio) VALUES (${id1}, 'Lavado VIP + Encerado Acrílico', 'Protección acrílica de pintura', 22000)`);
    db.run(`INSERT OR IGNORE INTO servicios (tenant_id, nombre_servicio, descripcion, precio) VALUES (${id1}, 'Limpieza de Tapizados', 'Higienización profunda de asientos', 35000)`);
    db.run(`INSERT OR IGNORE INTO servicios (tenant_id, nombre_servicio, descripcion, precio) VALUES (${id1}, 'Tratamiento Cerámico', 'Detailing cerámico completo', 65000)`);
  }

  console.log('==================================================');
  console.log('✅ SEMBRADO Y TELÉFONO DE WHATSAPP ACTUALIZADO');
  console.log('==================================================\n');
}

if (require.main === module) {
  runSeed().then(() => process.exit(0)).catch(console.error);
}

module.exports = { runSeed };
