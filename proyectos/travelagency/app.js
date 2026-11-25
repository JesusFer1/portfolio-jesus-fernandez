/* =========================================================
   TRAVELAGENCY — LÓGICA INTERACTIVA COMPLETA
   ========================================================= */

const PACKAGES = [
  {
    id: 1,
    title: "Lo Mejor de Europa",
    location: "París, Roma, Barcelona",
    duration: "10 Días / 9 Noches",
    shortDuration: "10 Días",
    rating: 4.8,
    reviews: 324,
    basePrice: 2499,
    image: "https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&h=400&fit=crop",
    images: [
      "https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1511739001486-6bfe10ce785f?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1549144511-f099e773c147?w=1200&h=800&fit=crop"
    ],
    description: "Experimenta lo mejor de Europa con este viaje cuidadosamente curado a través de tres ciudades icónicas. Desde las románticas calles de París hasta las ruinas antiguas de Roma y la vibrante cultura de Barcelona, este paquete ofrece una exploración inolvidable de la cultura, gastronomía e historia europea.",
    highlights: [
      "Tour guiado de la Torre Eiffel y Museo del Louvre",
      "Acceso sin filas al Coliseo y Vaticano",
      "Tour a pie del Barrio Gótico en Barcelona",
      "Experiencias de gastronomía tradicional en cada ciudad",
      "Tren de alta velocidad entre destinos",
      "Guías profesionales de habla hispana"
    ],
    included: [
      "9 noches de alojamiento en hoteles 4 estrellas",
      "Desayuno buffet diario",
      "Todo el transporte entre ciudades",
      "Traslados al aeropuerto",
      "Tours guiados como se menciona",
      "Tarifas de entrada a atracciones"
    ],
    notIncluded: [
      "Vuelos internacionales",
      "Almuerzo y cena (a menos que se especifique)",
      "Gastos personales",
      "Seguro de viaje",
      "Propinas para guías y conductores"
    ],
    addOns: [
      { id: 1, name: "Seguro de Viaje", price: 149 },
      { id: 2, name: "Cena Crucero por el Río Sena", price: 95 },
      { id: 3, name: "Espectáculo Flamenco en Barcelona", price: 75 },
      { id: 4, name: "Traslado Privado al Aeropuerto", price: 120 },
      { id: 5, name: "Mejora de Habitación de Hotel", price: 299 }
    ]
  },
  {
    id: 2,
    title: "Aventura Asiática",
    location: "Tokio, Bangkok, Singapur",
    duration: "12 Días / 11 Noches",
    shortDuration: "12 Días",
    rating: 4.9,
    reviews: 278,
    basePrice: 2899,
    image: "https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600&h=400&fit=crop",
    images: [
      "https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=1200&h=800&fit=crop"
    ],
    description: "Sumérgete en el contraste fascinante entre tradición milenaria y futurismo en Tokio, los templos dorados y mercados de Bangkok, y la arquitectura vanguardista de Singapur.",
    highlights: [
      "Recorrido guiado por Shibuya, Asakusa y Monte Fuji",
      "Visita al Gran Palacio y mercados flotantes en Bangkok",
      "Entrada a Gardens by the Bay y Marina Bay en Singapur",
      "Degustación de gastronomía callejera con chef local",
      "Vuelos internos incluidos entre capitales",
      "Asistencia 24/7 en español"
    ],
    included: [
      "11 noches en hoteles céntricos 4 estrellas",
      "Desayuno diario y 3 cenas temáticas",
      "Vuelos internos Tokio-Bangkok-Singapur",
      "Traslados aeropuerto-hotel",
      "Excursiones y entradas principales"
    ],
    notIncluded: [
      "Vuelos internacionales de llegada/salida",
      "Almuerzos no especificados",
      "Seguro médico internacional",
      "Gastos personales"
    ],
    addOns: [
      { id: 1, name: "Seguro de Viaje Integral", price: 169 },
      { id: 2, name: "Excursión de día completo a Kioto en Shinkansen", price: 210 },
      { id: 3, name: "Cena en Crucero por el Río Chao Phraya", price: 85 },
      { id: 4, name: "SIM / eSIM con Datos Ilimitados Asia", price: 45 }
    ]
  },
  {
    id: 3,
    title: "Paraíso Caribeño",
    location: "Jamaica, Bahamas, Aruba",
    duration: "7 Días / 6 Noches",
    shortDuration: "7 Días",
    rating: 4.7,
    reviews: 412,
    basePrice: 1799,
    image: "https://images.unsplash.com/photo-1519046904884-53103b34b206?w=600&h=400&fit=crop",
    images: [
      "https://images.unsplash.com/photo-1519046904884-53103b34b206?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?w=1200&h=800&fit=crop"
    ],
    description: "Relájate en playas de arena blanca y aguas turquesas con régimen All-Inclusive, navegación en catamarán y snorkel en arrecifes de coral por las islas más exclusivas del Caribe.",
    highlights: [
      "Resorts frente al mar con régimen All-Inclusive",
      "Navegación en catamarán al atardecer",
      "Snorkel guiado en arrecifes tropicales",
      "Traslados privados en todas las islas"
    ],
    included: [
      "6 noches en resorts All-Inclusive 4/5 estrellas",
      "Todas las comidas y bebidas incluidas",
      "2 excursiones náuticas guiadas",
      "Traslados aeropuerto-resort"
    ],
    notIncluded: [
      "Vuelos internacionales",
      "Actividades acuáticas motorizadas opcionales",
      "Seguro de viaje"
    ],
    addOns: [
      { id: 1, name: "Seguro de Viaje", price: 119 },
      { id: 2, name: "Upgrade a Suite Vista al Mar", price: 260 },
      { id: 3, name: "Sesión de Spa para 2 personas", price: 140 }
    ]
  },
  {
    id: 4,
    title: "Safari Africano",
    location: "Kenia, Tanzania",
    duration: "8 Días / 7 Noches",
    shortDuration: "8 Días",
    rating: 4.9,
    reviews: 195,
    basePrice: 3299,
    image: "https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&h=400&fit=crop",
    images: [
      "https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1523805009345-7448845a9e53?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1534177616072-ef7dc120449d?w=1200&h=800&fit=crop"
    ],
    description: "Vive la emoción de la sabana africana recorriendo Masái Mara y el Parque Nacional Serengeti en vehículos 4x4 con guías naturalistas expertos y lodges de lujo.",
    highlights: [
      "Safaris diarios en vehículo 4x4 con techo panorámico",
      "Avistamiento de los Cinco Grandes (Big Five)",
      "Visita cultural respetuosa a aldea Masái",
      "Alojamiento en Eco-Lodges con pensión completa"
    ],
    included: [
      "7 noches en Safari Lodges seleccionados",
      "Pensión completa durante todo el recorrido",
      "Entradas a parques nacionales Masái Mara y Serengeti",
      "Guía naturalista de habla hispana"
    ],
    notIncluded: [
      "Vuelos internacionales",
      "Visas de ingreso a Kenia/Tanzania",
      "Vuelo en globo aerostático opcional"
    ],
    addOns: [
      { id: 1, name: "Seguro de Viaje Aventura", price: 185 },
      { id: 2, name: "Vuelo en Globo al Amanecer sobre Serengeti", price: 420 },
      { id: 3, name: "Kit Fotográfico + Binoculares Premium", price: 65 }
    ]
  },
  {
    id: 5,
    title: "Explorador Sudamericano",
    location: "Perú, Chile, Argentina",
    duration: "14 Días / 13 Noches",
    shortDuration: "14 Días",
    rating: 4.9,
    reviews: 356,
    basePrice: 3599,
    image: "https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=600&h=400&fit=crop",
    images: [
      "https://images.unsplash.com/photo-1587595431973-160d0d94add1?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1526392060635-9d6019884377?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1518182170546-07661fd94144?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1589909202802-8f4aadce1849?w=1200&h=800&fit=crop"
    ],
    description: "Un circuito épico por los paisajes más impresionantes de Sudamérica: Machu Picchu y Valle Sagrado en Perú, el Desierto de Atacama en Chile y la Patagonia y Buenos Aires en Argentina.",
    highlights: [
      "Tren panorámico y entrada guiada a Machu Picchu",
      "Excursión al Valle de la Luna en Atacama",
      "Navegación frente al Glaciar Perito Moreno",
      "City tour y cena show de tango en Buenos Aires"
    ],
    included: [
      "13 noches en hoteles boutique 4 estrellas",
      "Desayuno buffet diario",
      "Vuelos regionales entre Cusco, Santiago y Patagonia",
      "Todas las excursiones y entradas mencionadas"
    ],
    notIncluded: [
      "Vuelos internacionales de llegada",
      "Almuerzos y cenas no indicados",
      "Seguro de viaje"
    ],
    addOns: [
      { id: 1, name: "Seguro de Viaje Regional", price: 155 },
      { id: 2, name: "Trekking sobre Hielo en Perito Moreno", price: 190 },
      { id: 3, name: "Cena Show de Tango VIP en Buenos Aires", price: 110 }
    ]
  },
  {
    id: 6,
    title: "Crucero Mediterráneo",
    location: "Grecia, Croacia, Italia",
    duration: "9 Días / 8 Noches",
    shortDuration: "9 Días",
    rating: 4.8,
    reviews: 289,
    basePrice: 2199,
    image: "https://images.unsplash.com/photo-1530841377377-3ff06c0ca713?w=600&h=400&fit=crop",
    images: [
      "https://images.unsplash.com/photo-1530841377377-3ff06c0ca713?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1555990538-1e6c89d9b72f?w=1200&h=800&fit=crop",
      "https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=1200&h=800&fit=crop"
    ],
    description: "Navega por las aguas cristalinas del Mediterráneo descubriendo los atardeceres de Santorini, las murallas medievales de Dubrovnik y los canales históricos de Venecia.",
    highlights: [
      "Camarote exterior con balcón privado",
      "Escalas en Santorini, Mykonos, Dubrovnik y Venecia",
      "Gastronomía mediterránea y espectáculos a bordo",
      "Excursiones guiadas en cada puerto"
    ],
    included: [
      "8 noches a bordo en camarote con balcón",
      "Pensión completa a bordo",
      "Tasas portuarias incluidas",
      "Acceso a piscinas, gimnasio y teatro"
    ],
    notIncluded: [
      "Vuelos hacia el puerto de embarque",
      "Paquete de bebidas premium",
      "Excursiones opcionales en tierra"
    ],
    addOns: [
      { id: 1, name: "Seguro de Crucero y Viaje", price: 139 },
      { id: 2, name: "Paquete de Bebidas All-Inclusive a Bordo", price: 245 },
      { id: 3, name: "Tour Privado al Atardecer en Oia (Santorini)", price: 115 }
    ]
  }
];

const FAQS = [
  {
    question: "¿Qué incluye el precio del paquete?",
    answer: "Nuestros paquetes típicamente incluyen alojamiento, transporte entre destinos, comidas seleccionadas y tours guiados. Las inclusiones específicas varían según el paquete y se detallan en cada página."
  },
  {
    question: "¿Puedo personalizar mi paquete de viaje?",
    answer: "¡Sí! Ofrecemos opciones de personalización flexibles. Puedes agregar excursiones opcionales, mejorar el alojamiento o extender tu estadía. Contacta a nuestros especialistas para asistencia personalizada."
  },
  {
    question: "¿Cuál es su política de cancelación?",
    answer: "Las cancelaciones realizadas 30+ días antes de la salida reciben un reembolso completo menos una tarifa de procesamiento de $150. 15-29 días: 50% de reembolso. Menos de 15 días: sin reembolso. Recomendamos seguro de viaje."
  },
  {
    question: "¿Necesito seguro de viaje?",
    answer: "Aunque no es obligatorio, recomendamos enfáticamente un seguro de viaje para protegerse contra circunstancias imprevistas incluyendo cancelación de viaje, emergencias médicas y pérdida de equipaje."
  },
  {
    question: "¿Qué documentos necesito para viajar internacionalmente?",
    answer: "Necesitarás un pasaporte válido con al menos 6 meses de vigencia. Algunos destinos requieren visas - proporcionaremos los requisitos específicos para tu paquete elegido durante la reserva."
  }
];

const STORAGE_CART = "ta_cart_v1";
const STORAGE_USER = "ta_user_v1";
const STORAGE_USERS_DB = "ta_users_db_v1";
const STORAGE_BOOKINGS = "ta_bookings_v1";

let cart = [];
let currentPackage = PACKAGES[0];
let currentImgIdx = 0;
let detailTravelers = 2;
let selectedAddOns = [];
let paymentMethod = "credit";

function loadCart() {
  try {
    const raw = localStorage.getItem(STORAGE_CART);
    if (raw) {
      cart = JSON.parse(raw);
    } else {
      cart = [
        { id: 1, title: PACKAGES[0].title, location: PACKAGES[0].location, duration: PACKAGES[0].shortDuration, price: PACKAGES[0].basePrice, addOnsTotal: 0, addOnNames: [], quantity: 2, image: PACKAGES[0].image },
        { id: 2, title: PACKAGES[1].title, location: PACKAGES[1].location, duration: PACKAGES[1].shortDuration, price: PACKAGES[1].basePrice, addOnsTotal: 0, addOnNames: [], quantity: 1, image: PACKAGES[1].image }
      ];
      saveCart();
    }
  } catch (e) {
    cart = [];
  }
  updateCartBadge();
}

function saveCart() {
  localStorage.setItem(STORAGE_CART, JSON.stringify(cart));
  updateCartBadge();
}

function updateCartBadge() {
  const badge = document.getElementById("cartBadge");
  if (!badge) return;
  const totalCount = cart.reduce((s, i) => s + i.quantity, 0);
  badge.textContent = totalCount;
  badge.style.display = totalCount > 0 ? "flex" : "none";
}

function showToast(msg) {
  const t = document.getElementById("taToast");
  if (!t) return;
  t.textContent = msg;
  t.classList.add("show");
  clearTimeout(window._taToastT);
  window._taToastT = setTimeout(() => t.classList.remove("show"), 3200);
}

/* =========================================================
   NAVEGACIÓN ENTRE VISTAS
   ========================================================= */
function showView(view) {
  document.querySelectorAll(".page-view").forEach(v => v.classList.remove("active"));
  const el = document.getElementById("view-" + view);
  if (el) el.classList.add("active");
  if (view === "cart") renderCart();
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function goToSection(sectionId) {
  showView("home");
  setTimeout(() => {
    const target = document.getElementById(sectionId);
    if (target) target.scrollIntoView({ behavior: "smooth" });
  }, 60);
}

function toggleMobileMenu() {
  document.getElementById("mobileMenu").classList.toggle("open");
}

/* =========================================================
   HOME: PAQUETES, BÚSQUEDA Y FAQS
   ========================================================= */
function renderPackages(list = PACKAGES) {
  const grid = document.getElementById("packagesGrid");
  if (!grid) return;

  if (!list.length) {
    grid.innerHTML = `<div class="card-box pad-box" style="grid-column:1/-1;text-align:center;">
      <p class="muted" style="margin-bottom:16px;">No encontramos paquetes para esa búsqueda.</p>
      <button type="button" class="btn-primary" onclick="clearSearch()">Ver todos los paquetes</button>
    </div>`;
    return;
  }

  grid.innerHTML = list.map(pkg => `
    <article class="pkg-card" onclick="openPackageDetail(${pkg.id})">
      <div class="pkg-img">
        <img src="${pkg.image}" alt="${pkg.title}" loading="lazy">
      </div>
      <div class="pkg-body">
        <h3>${pkg.title}</h3>
        <div class="pkg-meta">
          <span>Ubicación: ${pkg.location}</span>
          <span>Duración: ${pkg.shortDuration}</span>
        </div>
        <div class="pkg-footer">
          <span class="pkg-price">$${pkg.basePrice.toLocaleString("en-US")}</span>
          <span class="pkg-cta">Ver Detalles &rarr;</span>
        </div>
      </div>
    </article>
  `).join("");
}

function filterPackages(query) {
  const q = (query || "").trim().toLowerCase();
  const clearBtn = document.getElementById("clearSearchBtn");
  if (clearBtn) clearBtn.style.display = q ? "inline-block" : "none";
  if (!q) {
    renderPackages(PACKAGES);
    return;
  }
  const filtered = PACKAGES.filter(p =>
    p.title.toLowerCase().includes(q) ||
    p.location.toLowerCase().includes(q) ||
    p.description.toLowerCase().includes(q)
  );
  renderPackages(filtered);
}

function handleSearch(e) {
  e.preventDefault();
  const q = document.getElementById("searchInput").value;
  filterPackages(q);
  document.getElementById("paquetes").scrollIntoView({ behavior: "smooth" });
}

function quickFilter(term) {
  showView("home");
  const inp = document.getElementById("searchInput");
  if (inp) inp.value = term;
  filterPackages(term);
  setTimeout(() => document.getElementById("paquetes").scrollIntoView({ behavior: "smooth" }), 60);
}

function clearSearch() {
  const inp = document.getElementById("searchInput");
  if (inp) inp.value = "";
  filterPackages("");
}

function renderFaqs() {
  const wrap = document.getElementById("faqAccordion");
  if (!wrap) return;
  wrap.innerHTML = FAQS.map((f, idx) => `
    <div class="faq-item ${idx === 0 ? "open" : ""}">
      <button type="button" class="faq-trigger" onclick="this.parentElement.classList.toggle('open')">
        <span>${f.question}</span>
        <span class="faq-chevron">&#9662;</span>
      </button>
      <div class="faq-answer">${f.answer}</div>
    </div>
  `).join("");
}

/* =========================================================
   VISTA DETALLE DEL PAQUETE
   ========================================================= */
function openPackageDetail(id) {
  const pkg = PACKAGES.find(p => p.id === id) || PACKAGES[0];
  currentPackage = pkg;
  currentImgIdx = 0;
  detailTravelers = 2;
  selectedAddOns = [];

  document.getElementById("detailBreadcrumb").textContent = pkg.title;
  document.getElementById("detailTitle").textContent = pkg.title;
  document.getElementById("detailLocation").textContent = pkg.location;
  document.getElementById("detailDuration").textContent = pkg.duration;
  document.getElementById("detailRating").textContent = pkg.rating;
  document.getElementById("detailReviews").textContent = `(${pkg.reviews} reseñas)`;
  document.getElementById("detailDesc").textContent = pkg.description;

  document.getElementById("detailHighlights").innerHTML = pkg.highlights.map(h => `<li>${h}</li>`).join("");
  document.getElementById("detailIncluded").innerHTML = pkg.included.map(i => `<li>${i}</li>`).join("");
  document.getElementById("detailNotIncluded").innerHTML = pkg.notIncluded.map(n => `<li>${n}</li>`).join("");

  document.getElementById("detailAddOns").innerHTML = pkg.addOns.map(a => `
    <label class="addon-row">
      <span class="addon-left">
        <input type="checkbox" onchange="toggleDetailAddOn(${a.id}, this.checked)">
        <span>${a.name}</span>
      </span>
      <strong>+$${a.price}</strong>
    </label>
  `).join("");

  renderGallery();
  updateDetailPrice();
  showView("detail");
}

function renderGallery() {
  const imgs = currentPackage.images;
  document.getElementById("galleryMainImg").src = imgs[currentImgIdx];
  document.getElementById("galleryDots").innerHTML = imgs.map((_, i) => `
    <button type="button" class="g-dot ${i === currentImgIdx ? "active" : ""}" onclick="setGalleryImg(${i})"></button>
  `).join("");
  document.getElementById("galleryThumbs").innerHTML = imgs.map((img, i) => `
    <button type="button" class="g-thumb ${i === currentImgIdx ? "active" : ""}" onclick="setGalleryImg(${i})">
      <img src="${img}" alt="Vista ${i + 1}">
    </button>
  `).join("");
}

function setGalleryImg(i) { currentImgIdx = i; renderGallery(); }
function nextGalleryImg() { currentImgIdx = (currentImgIdx + 1) % currentPackage.images.length; renderGallery(); }
function prevGalleryImg() { currentImgIdx = currentImgIdx === 0 ? currentPackage.images.length - 1 : currentImgIdx - 1; renderGallery(); }

function changeDetailTravelers(delta) {
  detailTravelers = Math.max(1, detailTravelers + delta);
  updateDetailPrice();
}

function toggleDetailAddOn(addonId, checked) {
  if (checked && !selectedAddOns.includes(addonId)) selectedAddOns.push(addonId);
  if (!checked) selectedAddOns = selectedAddOns.filter(x => x !== addonId);
  updateDetailPrice();
}

function updateDetailPrice() {
  document.getElementById("detailTravelersVal").textContent = detailTravelers;
  const subtotal = currentPackage.basePrice * detailTravelers;
  const addOnsTotal = selectedAddOns.reduce((sum, id) => {
    const a = currentPackage.addOns.find(x => x.id === id);
    return sum + (a ? a.price : 0);
  }, 0);
  const taxes = (subtotal + addOnsTotal) * 0.08;
  const finalTotal = subtotal + addOnsTotal + taxes;

  document.getElementById("pbBaseLabel").textContent = `Precio base × ${detailTravelers}`;
  document.getElementById("pbBaseVal").textContent = `$${subtotal.toLocaleString("en-US")}`;
  const addRow = document.getElementById("pbAddOnsRow");
  if (selectedAddOns.length > 0) {
    addRow.style.display = "flex";
    document.getElementById("pbAddOnsLabel").textContent = `Complementos (${selectedAddOns.length})`;
    document.getElementById("pbAddOnsVal").textContent = `+$${addOnsTotal.toLocaleString("en-US")}`;
  } else {
    addRow.style.display = "none";
  }
  document.getElementById("pbTaxVal").textContent = `+$${taxes.toFixed(2)}`;
  document.getElementById("pbFinalVal").textContent = `$${finalTotal.toFixed(2)}`;
}

function addCurrentPackageToCart(goToCartNow) {
  const addOnsObjs = selectedAddOns
    .map(id => currentPackage.addOns.find(x => x.id === id))
    .filter(Boolean);
  const addOnsTotal = addOnsObjs.reduce((s, a) => s + a.price, 0);
  const addOnNames = addOnsObjs.map(a => a.name);

  const existing = cart.find(item => item.id === currentPackage.id);
  if (existing) {
    existing.quantity += detailTravelers;
    existing.addOnsTotal = (existing.addOnsTotal || 0) + addOnsTotal;
    existing.addOnNames = Array.from(new Set([...(existing.addOnNames || []), ...addOnNames]));
  } else {
    cart.push({
      id: currentPackage.id,
      title: currentPackage.title,
      location: currentPackage.location,
      duration: currentPackage.shortDuration,
      price: currentPackage.basePrice,
      addOnsTotal,
      addOnNames,
      quantity: detailTravelers,
      image: currentPackage.image
    });
  }
  saveCart();
  if (goToCartNow) {
    showView("cart");
  } else {
    showToast(`"${currentPackage.title}" agregado al carrito (${detailTravelers} viajeros)`);
  }
}

/* =========================================================
   CARRITO Y CHECKOUT
   ========================================================= */
function renderCart() {
  const empty = document.getElementById("cartEmptyState");
  const grid = document.getElementById("cartContentGrid");
  const list = document.getElementById("cartItemsList");

  if (!cart.length) {
    empty.style.display = "block";
    grid.style.display = "none";
    return;
  }

  empty.style.display = "none";
  grid.style.display = "grid";

  list.innerHTML = cart.map(item => {
    const lineTotal = item.price * item.quantity + (item.addOnsTotal || 0);
    const extrasHtml = item.addOnNames && item.addOnNames.length
      ? `<div style="font-size:0.82rem;color:#4A90E2;margin-top:4px;">Extras: ${item.addOnNames.join(", ")} (+$${item.addOnsTotal})</div>`
      : "";
    return `
      <div class="cart-item-card">
        <div class="cart-item-img"><img src="${item.image}" alt="${item.title}"></div>
        <div class="cart-item-body">
          <div class="cart-item-top">
            <div>
              <a class="cart-item-title" onclick="openPackageDetail(${item.id})">${item.title}</a>
              <p class="muted" style="font-size:0.9rem;margin-top:2px;">${item.location}</p>
              <p class="muted" style="font-size:0.88rem;">${item.duration}</p>
              ${extrasHtml}
            </div>
            <button type="button" class="trash-btn" onclick="removeCartItem(${item.id})">Eliminar</button>
          </div>
          <div class="cart-item-bottom">
            <div style="display:flex;align-items:center;gap:12px;">
              <span class="muted">Viajeros:</span>
              <div class="stepper">
                <button type="button" class="step-btn" onclick="updateCartQty(${item.id}, -1)">-</button>
                <span class="step-val">${item.quantity}</span>
                <button type="button" class="step-btn" onclick="updateCartQty(${item.id}, 1)">+</button>
              </div>
            </div>
            <div style="text-align:right;">
              <div class="muted" style="font-size:0.86rem;">$${item.price.toLocaleString("en-US")} × ${item.quantity}</div>
              <div style="font-size:1.25rem;font-weight:600;">$${lineTotal.toLocaleString("en-US")}</div>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join("");

  const totalTravelers = cart.reduce((s, i) => s + i.quantity, 0);
  const subtotal = cart.reduce((s, i) => s + i.price * i.quantity + (i.addOnsTotal || 0), 0);
  const taxes = subtotal * 0.08;
  const total = subtotal + taxes;

  document.getElementById("cartSubtotalLabel").textContent = `Subtotal (${totalTravelers} viajeros)`;
  document.getElementById("cartSubtotalVal").textContent = `$${subtotal.toLocaleString("en-US")}`;
  document.getElementById("cartTaxVal").textContent = `$${taxes.toFixed(2)}`;
  document.getElementById("cartTotalVal").textContent = `$${total.toFixed(2)}`;
}

function updateCartQty(id, delta) {
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.quantity = Math.max(1, item.quantity + delta);
  saveCart();
  renderCart();
}

function removeCartItem(id) {
  cart = cart.filter(i => i.id !== id);
  saveCart();
  renderCart();
  showToast("Paquete eliminado del carrito");
}

function selectPayment(method, el) {
  paymentMethod = method;
  document.querySelectorAll(".pay-option").forEach(o => o.classList.remove("active"));
  el.classList.add("active");
}

/* =========================================================
   PASARELA DE PAGO SEGURA
   ========================================================= */
function getCartTotals() {
  const totalTravelers = cart.reduce((s, i) => s + i.quantity, 0);
  const subtotal = cart.reduce((s, i) => s + i.price * i.quantity + (i.addOnsTotal || 0), 0);
  const taxes = subtotal * 0.08;
  const total = subtotal + taxes;
  return { totalTravelers, subtotal, taxes, total };
}

function openPaymentGateway() {
  if (!cart.length) {
    showToast("Tu carrito está vacío.");
    return;
  }
  const { totalTravelers, total } = getCartTotals();
  const user = JSON.parse(localStorage.getItem(STORAGE_USER) || "null");

  document.getElementById("gwBody").style.display = "block";
  document.getElementById("gwProcessing").style.display = "none";
  document.getElementById("gwErrorMsg").style.display = "none";

  document.getElementById("gwItemsDesc").textContent = `${cart.length} paquete(s) · ${totalTravelers} viajero(s)`;
  document.getElementById("gwAmountTotal").textContent = `$${total.toFixed(2)} USD`;

  if (user) {
    document.getElementById("gwHolder").value = user.name || "";
    document.getElementById("gwEmail").value = user.email || "";
    document.getElementById("gwPaypalEmail").value = user.email || "";
  }

  // Mostrar sección según método elegido
  document.getElementById("gwCreditSection").style.display = paymentMethod === "credit" ? "block" : "none";
  document.getElementById("gwBankSection").style.display = paymentMethod === "bank" ? "block" : "none";
  document.getElementById("gwPaypalSection").style.display = paymentMethod === "paypal" ? "block" : "none";

  const titles = {
    credit: "Pasarela de Pago — Tarjeta de Crédito / Débito",
    bank: "Pasarela de Pago — Transferencia Bancaria",
    paypal: "Pasarela de Pago — PayPal Express"
  };
  document.getElementById("gwTitle").textContent = titles[paymentMethod] || "Pasarela de Pago Segura";

  // Opciones de cuotas con cálculo real
  const sel = document.getElementById("gwInstallments");
  sel.innerHTML = `
    <option value="1 cuota sin interés">1 cuota sin interés de $${total.toFixed(2)} USD</option>
    <option value="3 cuotas sin interés">3 cuotas sin interés de $${(total / 3).toFixed(2)} USD</option>
    <option value="6 cuotas">6 cuotas de $${((total * 1.06) / 6).toFixed(2)} USD (6% recargo)</option>
    <option value="12 cuotas">12 cuotas de $${((total * 1.15) / 12).toFixed(2)} USD (15% recargo)</option>
  `;

  updateCardPreview();
  document.getElementById("gatewayModal").classList.add("open");
}

function closePaymentGateway() {
  document.getElementById("gatewayModal").classList.remove("open");
}

function formatCardNumber(inp) {
  let digits = inp.value.replace(/\D/g, "").slice(0, 16);
  inp.value = digits.replace(/(\d{4})(?=\d)/g, "$1 ");
  updateCardPreview();
}

function formatCardExp(inp) {
  let digits = inp.value.replace(/\D/g, "").slice(0, 4);
  if (digits.length >= 3) digits = digits.slice(0, 2) + "/" + digits.slice(2);
  inp.value = digits;
  updateCardPreview();
}

function updateCardPreview() {
  const num = (document.getElementById("gwCardNum").value || "").trim();
  const name = (document.getElementById("gwHolder").value || "").trim();
  const exp = (document.getElementById("gwCardExp").value || "").trim();

  document.getElementById("cardNumPreview").textContent = num || "•••• •••• •••• ••••";
  document.getElementById("cardNamePreview").textContent = (name || "NOMBRE DEL TITULAR").toUpperCase();
  document.getElementById("cardExpPreview").textContent = exp || "MM/AA";

  let brand = "VISA";
  if (num.startsWith("5")) brand = "MASTERCARD";
  else if (num.startsWith("3")) brand = "AMEX";
  document.getElementById("cardBrandBadge").textContent = brand;
}

function copyText(txt) {
  navigator.clipboard.writeText(txt).then(() => showToast("Copiado: " + txt)).catch(() => {});
}

function showGwError(msg) {
  const el = document.getElementById("gwErrorMsg");
  el.textContent = msg;
  el.style.display = "block";
}

function submitGatewayPayment(e) {
  e.preventDefault();
  document.getElementById("gwErrorMsg").style.display = "none";

  const holder = document.getElementById("gwHolder").value.trim();
  const email = document.getElementById("gwEmail").value.trim();
  if (!holder || !email) {
    showGwError("Completá el nombre del titular y tu correo electrónico.");
    return;
  }

  let methodSummary = "";
  if (paymentMethod === "credit") {
    const num = document.getElementById("gwCardNum").value.replace(/\s/g, "");
    const exp = document.getElementById("gwCardExp").value.trim();
    const cvv = document.getElementById("gwCardCvv").value.trim();
    const dni = document.getElementById("gwCardDni").value.trim();
    if (num.length < 13 || exp.length < 5 || cvv.length < 3 || !dni) {
      showGwError("Completá todos los datos de la tarjeta (número, vencimiento, CVV y DNI).");
      return;
    }
    const last4 = num.slice(-4);
    const cuotas = document.getElementById("gwInstallments").value;
    methodSummary = `Tarjeta terminada en •••• ${last4} (${cuotas})`;
  } else if (paymentMethod === "bank") {
    const ref = document.getElementById("gwBankRef").value.trim();
    if (!ref) {
      showGwError("Ingresá el número de comprobante o referencia de la transferencia.");
      return;
    }
    methodSummary = `Transferencia Bancaria (Ref: ${ref})`;
  } else {
    const ppEmail = document.getElementById("gwPaypalEmail").value.trim();
    if (!ppEmail) {
      showGwError("Ingresá el correo de tu cuenta PayPal.");
      return;
    }
    methodSummary = `PayPal (${ppEmail})`;
  }

  // Simular procesamiento seguro
  document.getElementById("gwBody").style.display = "none";
  document.getElementById("gwProcessing").style.display = "block";

  setTimeout(() => {
    const { total } = getCartTotals();
    const code = "TA-2026-" + Math.floor(1000 + Math.random() * 9000);
    const itemsList = cart.map(i => `${i.title} (×${i.quantity})`);

    const bookings = JSON.parse(localStorage.getItem(STORAGE_BOOKINGS) || "[]");
    bookings.unshift({
      code,
      date: new Date().toLocaleDateString("es-AR"),
      total: total.toFixed(2),
      method: methodSummary,
      holder,
      email,
      items: itemsList
    });
    localStorage.setItem(STORAGE_BOOKINGS, JSON.stringify(bookings));

    closePaymentGateway();
    document.getElementById("confirmCode").textContent = code;
    document.getElementById("confirmText").textContent =
      `Gracias ${holder}. Enviamos los vouchers electrónicos a ${email}.`;
    document.getElementById("confirmDetails").innerHTML = `
      <div><strong>Titular:</strong> ${holder}</div>
      <div><strong>Paquetes:</strong> ${itemsList.join(" · ")}</div>
      <div><strong>Método:</strong> ${methodSummary}</div>
      <div><strong>Total abonado:</strong> $${total.toFixed(2)} USD</div>
      <div><strong>Estado:</strong> APROBADO</div>
    `;
    document.getElementById("confirmModal").classList.add("open");

    cart = [];
    saveCart();
    renderCart();
  }, 1100);
}

function closeConfirmModal() {
  document.getElementById("confirmModal").classList.remove("open");
  showView("home");
}

/* =========================================================
   CUENTA DE USUARIO (REGISTRO + INICIO DE SESIÓN REAL)
   ========================================================= */
function getUsersDB() {
  let users = JSON.parse(localStorage.getItem(STORAGE_USERS_DB) || "[]");
  if (!users.some(u => u.email === "demo@travelagency.com")) {
    users.push({ name: "Viajero Demo", email: "demo@travelagency.com", phone: "+54 11 5555-0000", pass: "1234" });
    localStorage.setItem(STORAGE_USERS_DB, JSON.stringify(users));
  }
  return users;
}

function updateAuthUI() {
  const user = JSON.parse(localStorage.getItem(STORAGE_USER) || "null");
  const btnLogin = document.getElementById("authHeaderBtn");
  const btnReg = document.getElementById("registerHeaderBtn");
  if (btnLogin) btnLogin.textContent = user ? user.name : "Ingresar";
  if (btnReg) btnReg.style.display = user ? "none" : "";
}

function switchAuthTab(mode) {
  document.getElementById("authErrorMsg").style.display = "none";
  const isLogin = mode === "login";
  document.getElementById("tabLoginBtn").classList.toggle("active", isLogin);
  document.getElementById("tabRegisterBtn").classList.toggle("active", !isLogin);
  document.getElementById("loginForm").style.display = isLogin ? "flex" : "none";
  document.getElementById("registerForm").style.display = isLogin ? "none" : "flex";
  document.getElementById("authModalTitle").textContent = isLogin ? "Iniciar Sesión en TravelAgency" : "Crear Cuenta en TravelAgency";
}

function openAuthModal(mode = "login") {
  getUsersDB();
  const user = JSON.parse(localStorage.getItem(STORAGE_USER) || "null");
  const formsSec = document.getElementById("authFormsSection");
  const loggedWrap = document.getElementById("authLoggedWrap");

  if (user) {
    document.getElementById("authModalTitle").textContent = "Mi Cuenta — TravelAgency";
    formsSec.style.display = "none";
    loggedWrap.style.display = "block";
    document.getElementById("loggedUserLabel").textContent = `${user.name} (${user.email})`;
    const bookings = JSON.parse(localStorage.getItem(STORAGE_BOOKINGS) || "[]");
    document.getElementById("userBookingsList").innerHTML = bookings.length
      ? `<div style="font-size:0.86rem;border-top:1px solid #e0e0e0;padding-top:12px;">
          <strong>Tus Reservas Confirmadas:</strong>
          ${bookings.slice(0, 4).map(b => `<div style="margin-top:8px;padding:10px;background:#fafafa;border:1px solid #e0e0e0;"><strong>${b.code}</strong> · $${b.total} USD<br><span class="muted">${b.items.join(", ")}</span>${b.method ? `<br><small class="muted">${b.method}</small>` : ""}</div>`).join("")}
        </div>`
      : `<p class="muted" style="font-size:0.86rem;">Todavía no realizaste reservas.</p>`;
  } else {
    formsSec.style.display = "block";
    loggedWrap.style.display = "none";
    switchAuthTab(mode);
  }
  document.getElementById("authModal").classList.add("open");
}

function closeAuthModal() {
  document.getElementById("authModal").classList.remove("open");
}

function showAuthError(msg) {
  const el = document.getElementById("authErrorMsg");
  el.textContent = msg;
  el.style.display = "block";
}

function handleLoginSubmit(e) {
  e.preventDefault();
  const email = document.getElementById("loginEmail").value.trim().toLowerCase();
  const pass = document.getElementById("loginPass").value;
  const users = getUsersDB();
  const found = users.find(u => u.email.toLowerCase() === email && u.pass === pass);
  if (!found) {
    showAuthError("Correo o contraseña incorrectos. Si sos nuevo, creá tu cuenta en la pestaña 'Crear Cuenta'.");
    return;
  }
  localStorage.setItem(STORAGE_USER, JSON.stringify({ name: found.name, email: found.email, phone: found.phone || "" }));
  updateAuthUI();
  closeAuthModal();
  showToast(`Bienvenido de nuevo, ${found.name}`);
}

function handleRegisterSubmit(e) {
  e.preventDefault();
  const name = document.getElementById("regName").value.trim();
  const email = document.getElementById("regEmail").value.trim().toLowerCase();
  const phone = document.getElementById("regPhone").value.trim();
  const pass = document.getElementById("regPass").value;
  const pass2 = document.getElementById("regPass2").value;

  if (pass !== pass2) {
    showAuthError("Las contraseñas no coinciden.");
    return;
  }
  const users = getUsersDB();
  if (users.some(u => u.email.toLowerCase() === email)) {
    showAuthError("Ese correo ya está registrado. Probá iniciar sesión.");
    return;
  }
  users.push({ name, email, phone, pass });
  localStorage.setItem(STORAGE_USERS_DB, JSON.stringify(users));
  localStorage.setItem(STORAGE_USER, JSON.stringify({ name, email, phone }));
  updateAuthUI();
  closeAuthModal();
  showToast(`¡Cuenta creada! Bienvenido, ${name}`);
}

function logoutUser() {
  localStorage.removeItem(STORAGE_USER);
  updateAuthUI();
  closeAuthModal();
  showToast("Sesión cerrada");
}

/* INIT */
document.addEventListener("DOMContentLoaded", () => {
  loadCart();
  renderPackages(PACKAGES);
  renderFaqs();
  updateAuthUI();
});
