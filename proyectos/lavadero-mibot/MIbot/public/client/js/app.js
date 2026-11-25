// Generar ID de Sesión Único para el Cliente Web
const sessionId = 'session_' + Math.random().toString(36).substring(2, 9);
let mapInitialized = false;
let leafletMap = null;

document.addEventListener('DOMContentLoaded', () => {
  initChatForm();
  initMedidasForm();
  initFotoForm();
  initTurnoForm();
  loadQrCode();
  setTodayDateInTurno();
});

// Pestañas / Tabs
function switchTab(tabName) {
  const tabs = ['chat', 'medidas', 'foto', 'mapa', 'qr', 'turno'];
  tabs.forEach(tab => {
    const btn = document.getElementById(`tab-${tab}`);
    const sec = document.getElementById(`sec-${tab}`);
    if (tab === tabName) {
      btn.classList.add('active', 'bg-sky-600', 'text-white');
      sec.classList.remove('hidden');
      sec.classList.add('block');
    } else {
      btn.classList.remove('active', 'bg-sky-600', 'text-white');
      sec.classList.remove('block');
      sec.classList.add('hidden');
    }
  });

  if (tabName === 'mapa' && !mapInitialized) {
    initOpenStreetMap();
  }
}

// 💬 1. CHATBOT GEMINI
function initChatForm() {
  const form = document.getElementById('chat-form');
  const input = document.getElementById('chat-input');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = input.value.trim();
    if (!msg) return;

    appendUserMessage(msg);
    input.value = '';

    const typingId = appendTypingIndicator();

    try {
      const res = await fetch('/api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, sessionId })
      });
      const data = await res.json();
      removeTypingIndicator(typingId);

      if (data.respuesta) {
        appendBotMessage(data.respuesta);
      } else {
        appendBotMessage('Ocurrió un inconveniente al responder. Por favor reintenta.');
      }
    } catch (err) {
      removeTypingIndicator(typingId);
      appendBotMessage('No pudimos conectar con el servidor. Verifica tu conexión.');
    }
  });
}

function sendQuickMessage(text) {
  switchTab('chat');
  document.getElementById('chat-input').value = text;
  document.getElementById('chat-form').dispatchEvent(new Event('submit'));
}

function appendUserMessage(text) {
  const box = document.getElementById('chat-box');
  const div = document.createElement('div');
  div.className = 'flex justify-end';
  div.innerHTML = `
    <div class="user-bubble p-4 rounded-2xl text-sm shadow">
      ${escapeHtml(text)}
    </div>
  `;
  box.appendChild(div);
  box.scrollTop = box.scrollHeight;
}

function appendBotMessage(text) {
  const box = document.getElementById('chat-box');
  const div = document.createElement('div');
  div.className = 'flex items-start gap-3';
  div.innerHTML = `
    <div class="w-8 h-8 rounded-full bg-sky-600 flex items-center justify-center text-white text-sm shrink-0">
      <i class="fa-solid fa-robot"></i>
    </div>
    <div class="bot-bubble p-4 rounded-2xl text-sm shadow leading-relaxed whitespace-pre-line">
      ${formatMarkdown(text)}
    </div>
  `;
  box.appendChild(div);
  box.scrollTop = box.scrollHeight;
}

function appendTypingIndicator() {
  const box = document.getElementById('chat-box');
  const id = 'typing_' + Date.now();
  const div = document.createElement('div');
  div.id = id;
  div.className = 'flex items-start gap-3';
  div.innerHTML = `
    <div class="w-8 h-8 rounded-full bg-sky-600 flex items-center justify-center text-white text-sm shrink-0">
      <i class="fa-solid fa-robot"></i>
    </div>
    <div class="bot-bubble p-3 rounded-2xl text-xs shadow text-slate-400 flex items-center gap-2">
      <i class="fa-solid fa-circle-notch fa-spin text-sky-400"></i> Pensando respuesta...
    </div>
  `;
  box.appendChild(div);
  box.scrollTop = box.scrollHeight;
  return id;
}

function removeTypingIndicator(id) {
  const el = document.getElementById(id);
  if (el) el.remove();
}

// 📐 2. BÚSQUEDA DE MEDIDAS DE AUTO
function initMedidasForm() {
  const form = document.getElementById('form-medidas');
  const input = document.getElementById('input-modelo-medidas');
  const resultDiv = document.getElementById('resultado-medidas');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const model = input.value.trim();
    if (!model) return;

    resultDiv.classList.remove('hidden');
    resultDiv.innerHTML = `
      <div class="text-center py-4 text-sky-400 text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-circle-notch fa-spin text-lg"></i> Consultando dimensiones de ${escapeHtml(model)} en Gemini API...
      </div>
    `;

    try {
      const res = await fetch('/api/car-dimensions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ model, sessionId })
      });
      const data = await res.json();

      resultDiv.innerHTML = `
        <div class="flex items-center justify-between border-b border-slate-700 pb-3">
          <div>
            <h3 class="font-bold text-lg text-sky-400 flex items-center gap-2">
              <i class="fa-solid fa-car"></i> ${escapeHtml(data.modelo)}
            </h3>
            <span class="text-xs bg-sky-500/20 text-sky-300 px-2 py-0.5 rounded-full border border-sky-500/30">
              ${escapeHtml(data.categoria || 'Vehículo')}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3 text-center my-3">
          <div class="bg-slate-800 p-3 rounded-xl border border-slate-700">
            <div class="text-xs text-slate-400 font-semibold">Largo</div>
            <div class="text-sm font-bold text-slate-100">${escapeHtml(data.largo_m || 'N/A')}</div>
          </div>
          <div class="bg-slate-800 p-3 rounded-xl border border-slate-700">
            <div class="text-xs text-slate-400 font-semibold">Ancho</div>
            <div class="text-sm font-bold text-slate-100">${escapeHtml(data.ancho_m || 'N/A')}</div>
          </div>
          <div class="bg-slate-800 p-3 rounded-xl border border-slate-700">
            <div class="text-xs text-slate-400 font-semibold">Alto</div>
            <div class="text-sm font-bold text-slate-100">${escapeHtml(data.alto_m || 'N/A')}</div>
          </div>
        </div>

        <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700 text-xs text-slate-300 leading-relaxed">
          <div class="font-semibold text-sky-300 mb-1">🧼 Recomendación de Servicio No limits Park:</div>
          <div>${escapeHtml(data.lavado_recomendado || '')}</div>
          <p class="mt-2 text-slate-400">${escapeHtml(data.descripcion || '')}</p>
        </div>

        <button onclick="usarMedidasEnTurno('${escapeHtml(data.modelo)}', '${escapeHtml(data.categoria)}')" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-semibold py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-2">
          <i class="fa-regular fa-calendar-check"></i> Agendar Turno para este Auto
        </button>
      `;
    } catch (err) {
      resultDiv.innerHTML = `<div class="text-red-400 text-xs p-3 text-center">Error al consultar las medidas. Intenta nuevamente.</div>`;
    }
  });
}

function usarMedidasEnTurno(modelo, categoria) {
  switchTab('turno');
  document.getElementById('turno-modelo').value = modelo;
  document.getElementById('turno-medidas').value = categoria;
}

// 📷 3. ANÁLISIS DE FOTO CON GEMINI VISION
function initFotoForm() {
  const form = document.getElementById('form-foto');
  const input = document.getElementById('input-foto');
  const resultDiv = document.getElementById('resultado-foto');
  const textoAnalisis = document.getElementById('texto-analisis-foto');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!input.files || !input.files[0]) {
      alert('Por favor selecciona una foto de tu auto antes de enviar.');
      return;
    }

    const formData = new FormData();
    formData.append('photo', input.files[0]);
    formData.append('sessionId', sessionId);

    resultDiv.classList.remove('hidden');
    textoAnalisis.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin text-sky-400"></i> Analizando la fotografía con la visión de Gemini IA...`;

    try {
      const res = await fetch('/api/analyze-photo', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (data.analisis) {
        textoAnalisis.innerHTML = formatMarkdown(data.analisis);
      } else {
        textoAnalisis.innerText = 'No se pudo analizar la foto enviada.';
      }
    } catch (err) {
      textoAnalisis.innerText = 'Error al enviar la imagen. Asegúrate de seleccionar una imagen válida.';
    }
  });
}

function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = (e) => {
      document.getElementById('upload-preview').classList.add('hidden');
      const img = document.getElementById('img-preview');
      img.src = e.target.result;
      img.classList.remove('hidden');
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// 📍 4. OPENSTREETMAP INTERACTIVO (LEAFLET)
async function initOpenStreetMap() {
  try {
    const res = await fetch('/api/location');
    const data = await res.json();

    document.getElementById('osm-direct-link').href = data.osmWebUrl;

    const lat = data.coords.lat;
    const lng = data.coords.lng;

    leafletMap = L.map('map').setView([lat, lng], data.zoom);

    L.tileLayer(data.leafletConfig.tileLayer, {
      attribution: data.leafletConfig.attribution
    }).addTo(leafletMap);

    const customIcon = L.icon({
      iconUrl: 'https://cdn-icons-png.flaticon.com/512/3005/3005358.png', // Icono de coche/lavadero
      iconSize: [38, 38],
      iconAnchor: [19, 38],
      popupAnchor: [0, -38]
    });

    L.marker([lat, lng], { icon: customIcon })
      .addTo(leafletMap)
      .bindPopup(data.leafletConfig.marker.popupText)
      .openPopup();

    mapInitialized = true;
  } catch (err) {
    console.error('Error al inicializar OpenStreetMap:', err);
  }
}

// 📱 5. QR CODE WHATSAPP
async function loadQrCode() {
  try {
    const res = await fetch('/api/qr');
    const data = await res.json();
    document.getElementById('qr-img').src = data.qrDataUrl;
    document.getElementById('qr-wa-link').href = data.whatsappLink;
  } catch (err) {
    console.error('Error al cargar código QR:', err);
  }
}

// 📅 6. AGENDAR TURNO CON NOTIFICACIÓN DOBLE
function initTurnoForm() {
  const form = document.getElementById('form-turno');
  const confirmDiv = document.getElementById('turno-confirmacion');
  const detallesDiv = document.getElementById('detalles-confirmacion-turno');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const turnoData = {
      cliente_nombre: document.getElementById('turno-nombre').value.trim(),
      cliente_telefono: document.getElementById('turno-telefono').value.trim(),
      modelo_auto: document.getElementById('turno-modelo').value.trim(),
      medidas_auto: document.getElementById('turno-medidas').value.trim() || 'Estándar',
      servicio: document.getElementById('turno-servicio').value,
      fecha: document.getElementById('turno-fecha').value,
      hora: document.getElementById('turno-hora').value
    };

    try {
      const res = await fetch('/api/turnos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(turnoData)
      });
      const data = await res.json();

      if (data.success) {
        confirmDiv.classList.remove('hidden');
        detallesDiv.innerHTML = `
          <div>👤 <b>Cliente</b>: ${escapeHtml(turnoData.cliente_nombre)} (${escapeHtml(turnoData.cliente_telefono)})</div>
          <div>🚘 <b>Vehículo</b>: ${escapeHtml(turnoData.modelo_auto)}</div>
          <div>🧼 <b>Servicio</b>: ${escapeHtml(turnoData.servicio)}</div>
          <div>📆 <b>Fecha y Hora</b>: ${escapeHtml(turnoData.fecha)} a las ${escapeHtml(turnoData.hora)} hs.</div>
          <div>📍 <b>Ubicación</b>: C. 137 4213, Ranelagh, Buenos Aires.</div>
          <div class="mt-2 text-xs text-emerald-400 font-semibold">
            🔔 Se ha enviado la confirmación al cliente y la notificación de aviso al dueño.
          </div>
        `;
        form.reset();
        setTodayDateInTurno();
      } else {
        alert(data.error || 'Error al agendar el turno.');
      }
    } catch (err) {
      alert('Error de conexión al agendar turno.');
    }
  });
}

function setTodayDateInTurno() {
  const inputFecha = document.getElementById('turno-fecha');
  if (inputFecha) {
    const today = new Date().toISOString().split('T')[0];
    inputFecha.value = today;
    inputFecha.min = today;
  }
}

// Helpers
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function formatMarkdown(text) {
  if (!text) return '';
  let formatted = escapeHtml(text);
  formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
  return formatted;
}
