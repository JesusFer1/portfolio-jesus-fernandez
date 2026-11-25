let adminToken = localStorage.getItem('adminToken') || '';
let currentTenant = null;

document.addEventListener('DOMContentLoaded', () => {
  initAuthForm();
  if (adminToken) {
    verifyAndLoadDashboard();
  }
});

function initAuthForm() {
  const form = document.getElementById('auth-form');
  const errDiv = document.getElementById('auth-error');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const token = document.getElementById('input-admin-token').value.trim();
    errDiv.classList.add('hidden');

    try {
      const res = await fetch('/api/admin/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'x-admin-token': token
        }
      });

      const data = await res.json();

      if (res.ok && data.success) {
        adminToken = token;
        currentTenant = data.tenant;
        localStorage.setItem('adminToken', token);
        document.getElementById('auth-modal').classList.add('hidden');
        document.getElementById('admin-content').classList.remove('hidden');
        updateHeaderInfo();
        loadDashboardData();
      } else {
        errDiv.innerText = data.message || 'Token de administrador no válido.';
        errDiv.classList.remove('hidden');
      }
    } catch (err) {
      errDiv.innerText = 'Error al conectar con el servidor.';
      errDiv.classList.remove('hidden');
    }
  });
}

async function verifyAndLoadDashboard() {
  try {
    const res = await fetch('/api/admin/verify', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-admin-token': adminToken
      }
    });
    const data = await res.json();

    if (res.ok && data.success) {
      currentTenant = data.tenant;
      document.getElementById('auth-modal').classList.add('hidden');
      document.getElementById('admin-content').classList.remove('hidden');
      updateHeaderInfo();
      loadDashboardData();
    } else {
      logoutAdmin();
    }
  } catch (err) {
    logoutAdmin();
  }
}

function updateHeaderInfo() {
  if (currentTenant) {
    document.getElementById('tenant-name').innerText = currentTenant.nombre_empresa || 'Empresa';
    document.getElementById('tenant-address').innerText = currentTenant.direccion || '';
    
    const statusSpan = document.getElementById('tenant-status');
    const isActivo = currentTenant.estado_pago === 'activo';
    statusSpan.innerText = isActivo ? 'Suscripción Activa' : 'Suscripción Inactiva';
    statusSpan.className = isActivo 
      ? 'text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-medium'
      : 'text-xs bg-red-500/20 text-red-400 border border-red-500/30 px-2 py-0.5 rounded-full font-medium';

    document.getElementById('stat-plan-status').innerText = `Plan ${currentTenant.plan || 'Pro'} - ${isActivo ? 'Activo' : 'Inactivo'}`;
  }
}

function logoutAdmin() {
  adminToken = '';
  currentTenant = null;
  localStorage.removeItem('adminToken');
  document.getElementById('auth-modal').classList.remove('hidden');
  document.getElementById('admin-content').classList.add('hidden');
}

function refreshDashboard() {
  if (adminToken) loadDashboardData();
}

async function loadDashboardData() {
  const tenantId = currentTenant ? currentTenant.id : 1;
  await Promise.all([
    loadServices(tenantId),
    loadTurnos(tenantId),
    loadTokenStats(tenantId)
  ]);
}

// 🏷️ 1. Cargar y Editar Servicios
async function loadServices(tenantId) {
  try {
    const res = await fetch(`/api/tenant/${tenantId}/services`, {
      headers: { 'x-admin-token': adminToken }
    });
    if (!res.ok) return;
    const servicios = await res.json();

    document.getElementById('stat-total-servicios').innerText = servicios.length;
    const tbody = document.getElementById('tabla-servicios');

    if (servicios.length > 0) {
      tbody.innerHTML = servicios.map((s, index) => {
        const bgClass = index % 2 === 0 ? 'bg-slate-900/40' : 'bg-slate-900/80';
        return `
          <tr class="${bgClass} hover:bg-slate-800/80 transition">
            <td class="p-3.5 font-mono text-slate-400">#${s.id}</td>
            <td class="p-3.5 font-bold text-white">${escapeHtml(s.nombre_servicio)}</td>
            <td class="p-3.5 text-slate-300">${escapeHtml(s.descripcion || 'Servicio estándar')}</td>
            <td class="p-3.5 font-bold text-emerald-400">$${s.precio.toLocaleString('es-AR')}</td>
            <td class="p-3.5 text-center">
              <button onclick="editarPrecioServicio(${s.id}, '${escapeHtml(s.nombre_servicio)}', ${s.precio})" class="bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 px-3 py-1.5 rounded-lg font-semibold transition">
                <i class="fa-solid fa-pen-to-square"></i> Editar Precio
              </button>
            </td>
          </tr>
        `;
      }).join('');
    } else {
      tbody.innerHTML = `<tr><td colspan="5" class="p-4 text-center text-slate-500">No hay servicios configurados.</td></tr>`;
    }
  } catch (err) {
    console.error('Error al cargar servicios:', err);
  }
}

async function editarPrecioServicio(serviceId, nombre, precioActual) {
  const nuevoPrecioStr = prompt(`Ingresa el nuevo precio para "${nombre}":`, precioActual);
  if (!nuevoPrecioStr) return;
  const nuevoPrecio = parseFloat(nuevoPrecioStr);
  if (isNaN(nuevoPrecio) || nuevoPrecio < 0) {
    alert('Precio no válido.');
    return;
  }

  const tenantId = currentTenant ? currentTenant.id : 1;
  try {
    const res = await fetch(`/api/tenant/${tenantId}/services/${serviceId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'x-admin-token': adminToken
      },
      body: JSON.stringify({ precio: nuevoPrecio })
    });
    const data = await res.json();
    if (data.success) {
      alert(`¡Precio de "${nombre}" actualizado a $${nuevoPrecio.toLocaleString('es-AR')}!`);
      loadServices(tenantId);
    } else {
      alert(data.error || 'Error al actualizar el precio.');
    }
  } catch (err) {
    alert('Error al conectar con el servidor.');
  }
}

// 📅 2. Cargar Turnos
async function loadTurnos(tenantId) {
  try {
    const res = await fetch(`/api/tenant/${tenantId}/turnos`, {
      headers: { 'x-admin-token': adminToken }
    });
    if (!res.ok) return;
    const turnos = await res.json();

    document.getElementById('stat-total-turnos').innerText = turnos.length;
    const tbody = document.getElementById('tabla-turnos');

    if (turnos.length > 0) {
      tbody.innerHTML = turnos.map((t, index) => {
        const bgClass = index % 2 === 0 ? 'bg-slate-900/40' : 'bg-slate-900/80';
        return `
          <tr class="${bgClass} hover:bg-slate-800/80 transition">
            <td class="p-3.5 font-mono text-slate-400">#${t.id}</td>
            <td class="p-3.5 font-bold text-white">${escapeHtml(t.cliente_nombre)}</td>
            <td class="p-3.5 font-mono text-sky-400">${escapeHtml(t.cliente_telefono)}</td>
            <td class="p-3.5 text-slate-200">${escapeHtml(t.modelo_auto)}</td>
            <td class="p-3.5 text-slate-400">${escapeHtml(t.medidas_auto || 'Estándar')}</td>
            <td class="p-3.5 text-slate-300 font-medium">${escapeHtml(t.servicio)}</td>
            <td class="p-3.5 whitespace-nowrap text-slate-200 font-semibold">${escapeHtml(t.fecha)}</td>
            <td class="p-3.5 whitespace-nowrap text-emerald-400 font-bold">${escapeHtml(t.hora)} hs</td>
            <td class="p-3.5">
              <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full text-xs font-semibold">
                ${t.estado || 'CONFIRMADO'}
              </span>
            </td>
          </tr>
        `;
      }).join('');
    } else {
      tbody.innerHTML = `<tr><td colspan="9" class="p-4 text-center text-slate-500">No hay turnos agendados aún.</td></tr>`;
    }
  } catch (err) {
    console.error('Error al cargar turnos:', err);
  }
}

// 🧠 3. Cargar Telemetría de Tokens de IA
async function loadTokenStats(tenantId) {
  try {
    const res = await fetch(`/api/tenant/${tenantId}/token-stats`, {
      headers: { 'x-admin-token': adminToken }
    });
    if (!res.ok) return;
    const data = await res.json();
    const totals = data.totals || {};
    document.getElementById('stat-total-tokens').innerText = (totals.total_tokens || 0).toLocaleString();
  } catch (err) {
    console.error('Error al cargar telemetría de tokens:', err);
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
