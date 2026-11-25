/* =========================================================
   PORTFOLIO ONE-PAGE — JESÚS FERNÁNDEZ (AERO GLASS)
   ========================================================= */

const PROJECTS = [
  {
    id: 'fym',
    category: 'web',
    categoryLabel: 'Web & E-Commerce',
    name: 'F&M ElectroHogar',
    thumb: 'fymelectrohogar/logo-fym.jpg',
    url: 'proyectos/fymelectrohogar/index.html',
    displayUrl: 'https://fymelectrohogar.com.ar/',
    extraUrl: 'proyectos/fymelectrohogar/tienda.html',
    extraLabel: 'Tienda Online',
    tech: ['HTML5', 'CSS3', 'JavaScript', 'Tienda Online'],
    desc: 'Plataforma comercial completa de electrodomésticos inteligentes, tienda online, documentación FODA/Gantt/Canvas y spots comerciales.'
  },
  {
    id: 'moda',
    category: 'web',
    categoryLabel: 'PWA & PHP',
    name: 'Moda Circular — Ropero Escolar',
    thumb: 'img/moda-circular.png',
    url: 'proyectos/moda-circular/index.html',
    displayUrl: 'https://tecnica5.edu.ar/moda-circular/',
    tech: ['PWA', 'PHP', 'MySQL', 'QR Reciclable'],
    desc: 'Aplicación web del ropero comunitario E.E.S.T. N° 5 con catálogo de prendas y útiles, trazabilidad QR y gestión de reservas.'
  },
  {
    id: 'bibliotec',
    category: 'web',
    categoryLabel: 'Repositorio Web',
    name: 'BiblioTec — E.E.S.T. N° 5',
    thumb: 'img/bibliotec.png',
    url: 'proyectos/bibliotec/index.html',
    displayUrl: 'https://bibliotec.tecnica5berazategui.com/',
    tech: ['PHP', 'MySQL', 'Buscador', 'Panel Docente'],
    desc: 'Biblioteca virtual y repositorio escolar organizado por año (4° a 7°) y especialidad con buscador instantáneo y panel docente.'
  },
  {
    id: 'skillhub',
    category: 'web',
    categoryLabel: 'Plataforma Educativa',
    name: 'SkillHub — Cursos Online',
    thumb: 'img/skillhub.png',
    url: 'proyectos/skillhub/index.php',
    displayUrl: 'http://localhost:8765/proyectos/skillhub/index.php',
    tech: ['PHP 8', 'SQLite / MySQL', 'Roles', 'Certificados'],
    desc: 'Plataforma educativa completa con 4 áreas de formación, entregas prácticas por clase, corrección docente, evaluación final, certificados y Modo Demo.'
  },
  {
    id: 'gimnasio',
    category: 'hardware',
    categoryLabel: 'Python & Arduino',
    name: 'GymControl Pro — Acceso RFID',
    thumb: 'img/gimnasio.png',
    url: 'proyectos/gimnasio/index.html',
    displayUrl: 'C:\\Program Files\\GymControlPro\\main.py',
    tech: ['Python', 'SQLite', 'Arduino Mega 2560', 'RFID + PIN'],
    desc: 'Control de acceso para gimnasio conectado por USB Serial a Arduino Mega 2560, con detección automática de tarjetas RFID y generación de PIN único.'
  },
  {
    id: 'lavadero',
    category: 'ia',
    categoryLabel: 'Chatbot IA & SaaS',
    name: 'MIbot — Lavadero No Limits Park',
    thumb: 'img/lavadero-mibot.png',
    url: 'proyectos/lavadero-mibot/index.html',
    displayUrl: 'https://nolimitspark.saas/mibot',
    tech: ['Node.js', 'WhatsApp IA', 'SQLite', 'Panel SaaS'],
    desc: 'Chatbot con IA para WhatsApp con simulador de conversación en vivo, agenda de turnos automática, panel administrativo y código QR.'
  },
  {
    id: 'travelagency',
    category: 'web',
    categoryLabel: 'Turismo & E-Commerce',
    name: 'TravelAgency — Paquetes Turísticos',
    thumb: 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&h=400&fit=crop',
    url: 'proyectos/travelagency/index.html',
    displayUrl: 'https://travelagency.com/paquetes',
    tech: ['HTML5', 'CSS3', 'JavaScript', 'Carrito & Reservas'],
    desc: 'Portal de agencia de viajes con buscador de destinos, 6 paquetes con galería interactiva, extras opcionales, cálculo de impuestos, carrito y checkout.'
  },
  {
    id: 'deltagym',
    category: 'web',
    categoryLabel: 'Web Comercial Local',
    name: 'Delta Gym — Gimnasio Local',
    thumb: 'proyectos/local/WhatsApp_Image_2026-03-21_at_5.31.41_PM.jpeg',
    url: 'proyectos/local/index.html',
    displayUrl: 'https://deltagym.com.ar/',
    tech: ['HTML5', 'CSS3', 'JavaScript', 'Tienda & Horarios'],
    desc: 'Sitio web institucional para gimnasio local en Ranelagh con modalidades (Gym, CrossFit y Kickboxing), horarios por pestañas, precios, tienda, galería interactiva y mapa.'
  }
];

let activeFilter = 'all';

function renderProjects(filter = 'all') {
  const grid = document.getElementById('projectsGrid');
  if (!grid) return;

  const list = filter === 'all' ? PROJECTS : PROJECTS.filter(p => p.category === filter);

  grid.innerHTML = list.map(p => `
    <article class="proj-card">
      <div class="proj-thumb">
        <img src="${p.thumb}" alt="${p.name}" loading="lazy">
        <span class="proj-cat">${p.categoryLabel}</span>
      </div>
      <div class="proj-body">
        <h3>${p.name}</h3>
        <p>${p.desc}</p>
        <div class="proj-tech">
          ${p.tech.map(t => `<span>${t}</span>`).join('')}
        </div>
        <div class="proj-actions">
          <button type="button" class="aero-btn primary sm" onclick="openPreview('${p.id}')">Vista Previa</button>
          <a href="${p.url}" target="_blank" rel="noopener" class="aero-btn light sm">Abrir &nearr;</a>
        </div>
      </div>
    </article>
  `).join('');
}

function filterProjects(cat, btn) {
  activeFilter = cat;
  document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  renderProjects(cat);
}

/* =========================================================
   MODAL DE VISTA PREVIA
   ========================================================= */
function openPreview(projectId) {
  const p = PROJECTS.find(x => x.id === projectId);
  if (!p) return;

  document.getElementById('modalTitle').textContent = p.name;
  document.getElementById('modalUrl').textContent = p.displayUrl;
  document.getElementById('modalOpenNew').href = p.url;
  document.getElementById('previewIframe').src = p.url;

  const extra = document.getElementById('modalExtraBtn');
  if (p.extraUrl) {
    extra.style.display = 'inline-flex';
    extra.href = p.extraUrl;
    extra.textContent = p.extraLabel;
  } else {
    extra.style.display = 'none';
  }

  document.getElementById('previewModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closePreview() {
  const modal = document.getElementById('previewModal');
  modal.classList.remove('open');
  document.getElementById('previewIframe').src = 'about:blank';
  document.body.style.overflow = '';
}

function reloadPreview() {
  const f = document.getElementById('previewIframe');
  if (f && f.src) f.src = f.src;
}

/* =========================================================
   REPRODUCTOR MULTIMEDIA
   ========================================================= */
function selectTrack(btn, src, poster) {
  const video = document.getElementById('mainPlayer');
  if (!video) return;
  video.poster = poster || '';
  video.src = src;
  video.play().catch(() => {});
  document.querySelectorAll('.track-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

/* =========================================================
   INIT
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  renderProjects('all');

  const modal = document.getElementById('previewModal');
  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closePreview();
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePreview();
  });
});
