import { StorageManager } from './storage.js?v=5.0';

let currentTab = 'clothing'; // 'clothing' | 'supplies'
let currentSearch = '';
let html5QrScanner = null;
let currentScannedQRData = null;

async function startApp() {
  initTheme();
  setupEventListeners();
  setupQRListeners();
  setupReservationListeners();
  setupNotificationListeners();
  
  await checkAutoLoginURLParam();

  try {
    await StorageManager.init();
  } catch (err) {
    console.warn("StorageManager.init fallo:", err);
  }

  updateDbStatusUI();
  updateUserSessionUI();
  updateNotificationsBadge();
  updateAdminReservationsBadge();
  renderCatalog();
  checkURLQRParam();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', startApp);
} else {
  startApp();
}

/* CONFIGURACIÓN DE TEMA Y ESTADO DE ADMIN */
function initTheme() {
  const savedTheme = localStorage.getItem('app_theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);

  if (isDark) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
  updateThemeIcon(isDark);
}

function toggleTheme() {
  const isDark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('app_theme', isDark ? 'dark' : 'light');
  updateThemeIcon(isDark);
}

function updateThemeIcon(isDark) {
  const themeIcon = document.getElementById('themeIcon');
  if (themeIcon) {
    themeIcon.className = isDark ? 'fa-solid fa-sun text-amber-400' : 'fa-solid fa-moon text-slate-600';
  }
}

async function checkAutoLoginURLParam() {
  const urlParams = new URLSearchParams(window.location.search);
  const adminKey = urlParams.get('admin');

  if (adminKey === 'tec5' || adminKey === 'admin') {
    sessionStorage.setItem('is_admin_mode', 'true');
    const res = await StorageManager.login('admin', 'admin123');
    if (res.success) {
      showToast('¡Modo Administrador activado!', 'success');
      window.history.replaceState({}, document.title, window.location.pathname);
      updateUserSessionUI();
      renderCatalog();
    }
  } else if (sessionStorage.getItem('is_admin_mode') !== 'true') {
    // Si se accede normalmente sin el parámetro de admin, asegurar modo público
    if (StorageManager.isAdminLoggedIn()) {
      await StorageManager.logout();
      updateUserSessionUI();
      renderCatalog();
    }
  }
}

async function updateDbStatusUI() {
  const dot = document.getElementById('dbStatusDot');
  const text = document.getElementById('dbStatusText');
  const hostBadge = document.getElementById('dbHostBadge');

  const info = await StorageManager.checkDbConnection();

  if (info && info.success) {
    if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0';
    if (text) text.innerHTML = `Conectado a <span class="font-black text-emerald-600 dark:text-emerald-400">'${info.database}'</span>`;
    if (hostBadge) hostBadge.textContent = `Host: ${info.host} (${info.counts?.prendas || 0} prendas en DB)`;
  } else {
    if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0';
    if (text) text.innerHTML = `<span class="text-amber-600 dark:text-amber-400 font-bold">Modo local / Seguro (Datos listos para sincronizar)</span>`;
    if (hostBadge) hostBadge.textContent = `Configurado para InfinityFree`;
  }
}

function updateUserSessionUI() {
  const isAdmin = StorageManager.isAdminLoggedIn();
  const adminBanner = document.getElementById('adminBanner');

  if (adminBanner) {
    if (isAdmin) {
      adminBanner.classList.remove('hidden');
      updateAdminReservationsBadge();
    } else {
      adminBanner.classList.add('hidden');
    }
  }
}

/* EVENTOS PRINCIPALES Y MANEJO DE MODALES */
function setupEventListeners() {
  document.getElementById('btnThemeToggle')?.addEventListener('click', toggleTheme);

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
      closeModal(e.target.id);
    }
  });

  document.getElementById('btnRefreshDb')?.addEventListener('click', async () => {
    showToast('Actualizando datos...', 'info');
    await StorageManager.init();
    updateDbStatusUI();
    renderCatalog();
    showToast('Catálogo sincronizado', 'success');
  });

  document.getElementById('btnAdminBannerLogout')?.addEventListener('click', async () => {
    sessionStorage.removeItem('is_admin_mode');
    await StorageManager.logout();
    updateUserSessionUI();
    renderCatalog();
    showToast('Modo Administrador desactivado', 'info');
  });

  document.getElementById('btnCloseZoomViewer')?.addEventListener('click', closeZoomViewer);
  document.getElementById('btnToggleZoomScale')?.addEventListener('click', toggleZoomScale);

  document.getElementById('zoomImageWrapper')?.addEventListener('click', (e) => {
    if (e.target.closest('#btnZoomPrev') || e.target.closest('#btnZoomNext')) return;
    toggleZoomScale();
  });

  document.getElementById('btnZoomPrev')?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (currentZoomImages.length <= 1) return;
    currentZoomIndex = (currentZoomIndex - 1 + currentZoomImages.length) % currentZoomImages.length;
    isZoomedIn = false;
    updateZoomViewerUI();
  });

  document.getElementById('btnZoomNext')?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (currentZoomImages.length <= 1) return;
    currentZoomIndex = (currentZoomIndex + 1) % currentZoomImages.length;
    isZoomedIn = false;
    updateZoomViewerUI();
  });

  document.getElementById('modalImageZoom')?.addEventListener('click', (e) => {
    if (e.target.id === 'modalImageZoom') {
      closeZoomViewer();
    }
  });

  document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('modalImageZoom');
    if (!modal || modal.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeZoomViewer();
    if (e.key === 'ArrowLeft' && currentZoomImages.length > 1) {
      currentZoomIndex = (currentZoomIndex - 1 + currentZoomImages.length) % currentZoomImages.length;
      isZoomedIn = false;
      updateZoomViewerUI();
    }
    if (e.key === 'ArrowRight' && currentZoomImages.length > 1) {
      currentZoomIndex = (currentZoomIndex + 1) % currentZoomImages.length;
      isZoomedIn = false;
      updateZoomViewerUI();
    }
  });

  document.getElementById('tabClothing')?.addEventListener('click', () => switchTab('clothing'));
  document.getElementById('tabSupplies')?.addEventListener('click', () => switchTab('supplies'));

  document.getElementById('searchInput')?.addEventListener('input', (e) => {
    currentSearch = e.target.value.toLowerCase().trim();
    renderCatalog();
  });

  document.getElementById('productTypeSelect')?.addEventListener('change', async (e) => {
    const isClothing = e.target.value === 'clothing';
    const sizeWrapper = document.getElementById('wrapperProductSize');
    const colorWrapper = document.getElementById('wrapperProductColor');
    const qtyWrapper = document.getElementById('wrapperProductQuantity');
    const qrWrapper = document.getElementById('wrapperProductQR');
    const qrSelect = document.getElementById('productQrSelect');

    if (isClothing) {
      sizeWrapper?.classList.remove('hidden');
      colorWrapper?.classList.remove('hidden');
      qtyWrapper?.classList.add('hidden');
      qrWrapper?.classList.remove('hidden');
      if (qrSelect) qrSelect.required = true;
      await populateAvailableQRsDropdown();
    } else {
      sizeWrapper?.classList.add('hidden');
      colorWrapper?.classList.add('hidden');
      qtyWrapper?.classList.remove('hidden');
      qrWrapper?.classList.add('hidden');
      if (qrSelect) qrSelect.required = false;
    }
  });

  document.getElementById('productQrSelect')?.addEventListener('change', (e) => {
    const hiddenInput = document.getElementById('productAssignedQR');
    if (hiddenInput) hiddenInput.value = e.target.value;
  });

  document.getElementById('btnOpenAddModal')?.addEventListener('click', async () => {
    document.getElementById('formAddProduct').reset();
    document.getElementById('productPreviewContainer')?.classList.add('hidden');
    document.getElementById('productTypeSelect').value = 'clothing';
    document.getElementById('wrapperProductSize')?.classList.remove('hidden');
    document.getElementById('wrapperProductColor')?.classList.remove('hidden');
    document.getElementById('wrapperProductQuantity')?.classList.add('hidden');
    document.getElementById('wrapperProductQR')?.classList.remove('hidden');
    await populateAvailableQRsDropdown();
    openModal('modalAddProduct');
  });

  document.getElementById('btnQuickCreateBatchFromDonation')?.addEventListener('click', () => {
    closeModal('modalAddProduct');
    currentQrTab = 'disponibles';
    openModal('modalManageQRs');
    loadAndRenderQRManager();
  });

  document.getElementById('formAddProduct')?.addEventListener('submit', handleAddProduct);

  document.querySelectorAll('.btn-close-modal').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const modal = e.currentTarget.closest('.modal-container') || e.currentTarget.closest('[id^="modal"]');
      if (modal) closeModal(modal.id);
    });
  });

  setupImageHandlers();
}

// =========================================================
// VISOR DE IMÁGENES TIPO ZOOM (COMPACTO Y MODERNO)
// =========================================================
let currentZoomImages = [];
let currentZoomIndex = 0;
let isZoomedIn = false;

function openZoomViewer(images, startIndex = 0) {
  let list = [];
  if (Array.isArray(images)) {
    list = images.filter(Boolean);
  } else if (typeof images === 'string') {
    try {
      const parsed = JSON.parse(images);
      list = Array.isArray(parsed) ? parsed : [images];
    } catch {
      list = [images];
    }
  }

  if (list.length === 0) {
    list = ['imagenes/logo.jfif'];
  }

  currentZoomImages = list;
  currentZoomIndex = Math.min(Math.max(0, startIndex), list.length - 1);
  isZoomedIn = false;

  updateZoomViewerUI();

  const modal = document.getElementById('modalImageZoom');
  const card = document.getElementById('zoomModalCard');
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    requestAnimationFrame(() => {
      card?.classList.remove('scale-95', 'opacity-0');
      card?.classList.add('scale-100', 'opacity-100');
    });
  }
}

function closeZoomViewer() {
  const modal = document.getElementById('modalImageZoom');
  const card = document.getElementById('zoomModalCard');
  if (card) {
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
  }
  setTimeout(() => {
    if (modal) {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }
    isZoomedIn = false;
    const target = document.getElementById('zoomTargetImage');
    if (target) {
      target.style.transform = 'scale(1)';
    }
  }, 150);
}

function updateZoomViewerUI() {
  const img = document.getElementById('zoomTargetImage');
  const counter = document.getElementById('zoomCounter');
  const prevBtn = document.getElementById('btnZoomPrev');
  const nextBtn = document.getElementById('btnZoomNext');
  const dotsContainer = document.getElementById('zoomDotsContainer');
  const scaleText = document.getElementById('zoomScaleText');
  const wrapper = document.getElementById('zoomImageWrapper');

  if (img) {
    img.src = currentZoomImages[currentZoomIndex] || 'imagenes/logo.jfif';
    img.style.transform = isZoomedIn ? 'scale(1.85)' : 'scale(1)';
  }

  if (wrapper) {
    wrapper.style.cursor = isZoomedIn ? 'zoom-out' : 'zoom-in';
  }

  if (scaleText) {
    scaleText.textContent = isZoomedIn ? '1x' : 'Zoom 2x';
  }

  if (counter) {
    counter.textContent = `${currentZoomIndex + 1} / ${currentZoomImages.length}`;
  }

  const hasMultiple = currentZoomImages.length > 1;
  if (prevBtn) {
    if (hasMultiple) prevBtn.classList.remove('hidden');
    else prevBtn.classList.add('hidden');
  }
  if (nextBtn) {
    if (hasMultiple) nextBtn.classList.remove('hidden');
    else nextBtn.classList.add('hidden');
  }

  if (dotsContainer) {
    if (hasMultiple) {
      dotsContainer.classList.remove('hidden');
      dotsContainer.innerHTML = currentZoomImages.map((_, i) => `
        <button type="button" data-index="${i}" class="zoom-dot h-2 rounded-full transition-all ${i === currentZoomIndex ? 'bg-[rgb(232,125,53)] w-5' : 'bg-slate-300 dark:bg-slate-700 w-2'}"></button>
      `).join('');

      dotsContainer.querySelectorAll('.zoom-dot').forEach(btn => {
        btn.addEventListener('click', (e) => {
          currentZoomIndex = parseInt(e.currentTarget.dataset.index, 10);
          isZoomedIn = false;
          updateZoomViewerUI();
        });
      });
    } else {
      dotsContainer.classList.add('hidden');
    }
  }
}

function toggleZoomScale() {
  isZoomedIn = !isZoomedIn;
  updateZoomViewerUI();
}

// =========================================================
// SUBIDA MULTI-IMAGEN PARA DONACIONES
// =========================================================
let selectedProductImages = [];

function setupImageHandlers() {
  const fileInput = document.getElementById('productFileInput');
  const cameraInput = document.getElementById('productCameraInput');
  const previewContainer = document.getElementById('productPreviewContainer');
  const thumbnailsGrid = document.getElementById('productThumbnailsGrid');
  const badgeCount = document.getElementById('productImageCountBadge');
  const hiddenInput = document.getElementById('productImage');

  function renderThumbnails() {
    if (!thumbnailsGrid) return;
    thumbnailsGrid.innerHTML = '';

    if (selectedProductImages.length === 0) {
      previewContainer?.classList.add('hidden');
      badgeCount?.classList.add('hidden');
      if (hiddenInput) hiddenInput.value = '';
      return;
    }

    previewContainer?.classList.remove('hidden');
    if (badgeCount) {
      badgeCount.textContent = `${selectedProductImages.length} foto${selectedProductImages.length > 1 ? 's' : ''}`;
      badgeCount.classList.remove('hidden');
    }
    if (hiddenInput) {
      hiddenInput.value = selectedProductImages[0] || '';
    }

    selectedProductImages.forEach((imgSrc, idx) => {
      const wrap = document.createElement('div');
      wrap.className = 'relative w-14 h-14 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 group bg-slate-900';
      wrap.innerHTML = `
        <img src="${imgSrc}" class="w-full h-full object-cover cursor-pointer" data-thumb-idx="${idx}" />
        <button type="button" data-del-idx="${idx}" class="btn-remove-thumb absolute top-0.5 right-0.5 w-4 h-4 rounded-full bg-rose-600 text-white text-[9px] flex items-center justify-center shadow-md active:scale-90">
          <i class="fa-solid fa-xmark"></i>
        </button>
      `;
      thumbnailsGrid.appendChild(wrap);
    });

    thumbnailsGrid.querySelectorAll('.btn-remove-thumb').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const delIdx = parseInt(e.currentTarget.dataset.delIdx, 10);
        selectedProductImages.splice(delIdx, 1);
        renderThumbnails();
      });
    });

    thumbnailsGrid.querySelectorAll('img[data-thumb-idx]').forEach(img => {
      img.addEventListener('click', (e) => {
        const thumbIdx = parseInt(e.currentTarget.dataset.thumbIdx, 10);
        openZoomViewer(selectedProductImages, thumbIdx);
      });
    });
  }

  const processFile = (file) => {
    return new Promise((resolve) => {
      if (!file || !file.type.startsWith('image/')) return resolve(null);
      const reader = new FileReader();
      reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
          const canvas = document.createElement('canvas');
          const MAX_WIDTH = 700;
          const MAX_HEIGHT = 700;
          let width = img.width;
          let height = img.height;

          if (width > height) {
            if (width > MAX_WIDTH) {
              height *= MAX_WIDTH / width;
              width = MAX_WIDTH;
            }
          } else {
            if (height > MAX_HEIGHT) {
              width *= MAX_HEIGHT / height;
              height = MAX_HEIGHT;
            }
          }

          canvas.width = Math.round(width);
          canvas.height = Math.round(height);
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          
          const compressed = canvas.toDataURL('image/jpeg', 0.7);
          resolve(compressed);
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  };

  const handleMultipleFiles = async (files) => {
    if (!files || files.length === 0) return;
    for (let i = 0; i < files.length; i++) {
      const b64 = await processFile(files[i]);
      if (b64) {
        selectedProductImages.push(b64);
      }
    }
    renderThumbnails();
  };

  fileInput?.addEventListener('change', (e) => {
    handleMultipleFiles(e.target.files);
    e.target.value = '';
  });

  cameraInput?.addEventListener('change', (e) => {
    handleMultipleFiles(e.target.files);
    e.target.value = '';
  });
}

function switchTab(tab) {
  currentTab = tab;
  const tabClothing = document.getElementById('tabClothing');
  const tabSupplies = document.getElementById('tabSupplies');

  if (tab === 'clothing') {
    tabClothing?.classList.add('bg-[rgb(232,125,53)]', 'text-white');
    tabClothing?.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-300');
    tabSupplies?.classList.remove('bg-[rgb(232,125,53)]', 'text-white');
    tabSupplies?.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-300');
  } else {
    tabSupplies?.classList.add('bg-[rgb(232,125,53)]', 'text-white');
    tabSupplies?.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-300');
    tabClothing?.classList.remove('bg-[rgb(232,125,53)]', 'text-white');
    tabClothing?.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-300');
  }
  renderCatalog();
}

function renderCatalog() {
  const containerClothing = document.getElementById('clothingGrid');
  const containerSupplies = document.getElementById('suppliesGrid');
  const isAdmin = StorageManager.isAdminLoggedIn();

  if (currentTab === 'clothing') {
    containerSupplies?.classList.add('hidden');
    containerClothing?.classList.remove('hidden');

    let items = StorageManager.getClothing();

    if (currentSearch) {
      items = items.filter(i => 
        (i.name && i.name.toLowerCase().includes(currentSearch)) || 
        (i.category && i.category.toLowerCase().includes(currentSearch)) ||
        (i.color && i.color.toLowerCase().includes(currentSearch)) ||
        (i.size && i.size.toLowerCase().includes(currentSearch))
      );
    }

    if (items.length === 0) {
      containerClothing.innerHTML = `
        <div class="col-span-2 sm:col-span-3 md:col-span-4 text-center py-10 bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 p-4">
          <i class="fa-solid fa-shirt text-slate-300 dark:text-slate-700 text-3xl mb-2"></i>
          <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm font-semibold">No se encontraron prendas registradas.</p>
        </div>
      `;
      return;
    }

    containerClothing.innerHTML = items.map(item => createClothingCardHTML(item, isAdmin)).join('');
  } else {
    containerClothing?.classList.add('hidden');
    containerSupplies?.classList.remove('hidden');

    let items = StorageManager.getSupplies();

    if (currentSearch) {
      items = items.filter(i => 
        (i.name && i.name.toLowerCase().includes(currentSearch)) || 
        (i.category && i.category.toLowerCase().includes(currentSearch))
      );
    }

    if (items.length === 0) {
      containerSupplies.innerHTML = `
        <div class="col-span-2 sm:col-span-3 md:col-span-4 text-center py-10 bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 p-4">
          <i class="fa-solid fa-pen-ruler text-slate-300 dark:text-slate-700 text-3xl mb-2"></i>
          <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm font-semibold">No se encontraron útiles escolares registrados.</p>
        </div>
      `;
      return;
    }

    containerSupplies.innerHTML = items.map(item => createSupplyCardHTML(item, isAdmin)).join('');
  }

  attachCardEvents();
}

function createClothingCardHTML(item, isAdmin) {
  const status = item.status || item.estado || 'Disponible';
  const isAvailable = status === 'Disponible';
  const isReserved = status === 'Reservado';
  const imagesList = Array.isArray(item.images) && item.images.length > 0 ? item.images : [item.image || 'imagenes/logo.jfif'];
  const hasMultiple = imagesList.length > 1;

  return `
    <div class="card-product rounded-2xl overflow-hidden flex flex-col justify-between p-0">
      <div>
        <div class="relative h-32 sm:h-44 w-full bg-slate-100 dark:bg-slate-800 cursor-zoom-in overflow-hidden" data-action="expand-img" data-src="${item.image || 'imagenes/logo.jfif'}" data-images='${escapeHTML(JSON.stringify(imagesList))}'>
          <img src="${item.image || 'imagenes/logo.jfif'}" alt="${escapeHTML(item.name)}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-200" />

          ${isAdmin ? `
            <div class="absolute top-2 right-2">
              <button data-action="delete-item" data-id="${item.id}" data-qr="${escapeHTML(item.qrCode || item.codigo_qr || '')}" data-type="clothing" title="Eliminar de la base de datos" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center text-xs sm:text-sm shadow-md transition active:scale-95">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          ` : ''}

          <!-- Badge de disponibilidad en la esquina de la foto -->
          <div class="absolute top-2 left-2 pointer-events-none">
            ${isAvailable ? `
              <span class="bg-emerald-600/90 text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm">
                Disponible
              </span>
            ` : isReserved ? `
              <span class="bg-amber-500 text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm">
                Reservado
              </span>
            ` : `
              <span class="bg-slate-700/90 text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm">
                ${escapeHTML(status)}
              </span>
            `}
          </div>

          ${hasMultiple ? `
            <div class="absolute bottom-2 right-2 pointer-events-none">
              <span class="bg-slate-950/75 backdrop-blur-xs text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm flex items-center space-x-1">
                <i class="fa-solid fa-images text-[9px]"></i>
                <span>${imagesList.length}</span>
              </span>
            </div>
          ` : ''}
        </div>

        <div class="p-2.5 sm:p-3 space-y-1.5">
          <h3 class="font-black text-slate-900 dark:text-white text-xs sm:text-sm line-clamp-2 leading-tight">
            ${escapeHTML(item.name)}
          </h3>

          <div class="flex flex-wrap gap-1 pt-0.5">
            <span class="tag-badge tag-size">
              <i class="fa-solid fa-ruler-vertical text-[9px]"></i>
              Talle: ${escapeHTML(item.size || 'Único')}
            </span>
            <span class="tag-badge">
              <i class="fa-solid fa-tag text-[9px]"></i>
              ${escapeHTML(item.category || 'General')}
            </span>
            ${item.color ? `
              <span class="tag-badge">
                <span class="color-dot" style="background-color: ${item.colorHex || '#2563eb'};"></span>
                ${escapeHTML(item.color)}
              </span>
            ` : ''}
          </div>
        </div>
      </div>

      <!-- BOTÓN DE RESERVA DE PRENDA -->
      <div class="p-2.5 sm:p-3 pt-0">
        ${isAvailable ? `
          <button data-action="reserve-clothing" data-id="${item.id}" class="w-full bg-[rgb(232,125,53)] hover:bg-[rgb(212,105,33)] text-white font-black py-2.5 px-3 rounded-xl text-xs sm:text-sm shadow-sm transition flex items-center justify-center space-x-1.5 active:scale-95 min-h-[44px]">
            <i class="fa-solid fa-hand-holding-heart text-xs"></i>
            <span>Reservar</span>
          </button>
        ` : isReserved ? `
          <div class="w-full py-2.5 px-2 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-400 font-black text-xs text-center border border-amber-500/30 flex items-center justify-center space-x-1 min-h-[44px]">
            <i class="fa-solid fa-clock text-xs"></i>
            <span>Prenda Reservada</span>
          </div>
        ` : `
          <div class="w-full py-2.5 px-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold text-xs text-center flex items-center justify-center space-x-1 min-h-[44px]">
            <i class="fa-solid fa-circle-check text-xs"></i>
            <span>Entregado</span>
          </div>
        `}
      </div>
    </div>
  `;
}

function createSupplyCardHTML(item, isAdmin) {
  const imagesList = Array.isArray(item.images) && item.images.length > 0 ? item.images : [item.image || 'imagenes/logo.jfif'];
  const hasMultiple = imagesList.length > 1;

  return `
    <div class="card-product rounded-2xl overflow-hidden flex flex-col justify-between">
      <div>
        <div class="relative h-32 sm:h-44 w-full bg-slate-100 dark:bg-slate-800 cursor-zoom-in overflow-hidden" data-action="expand-img" data-src="${item.image || 'imagenes/logo.jfif'}" data-images='${escapeHTML(JSON.stringify(imagesList))}'>
          <img src="${item.image || 'imagenes/logo.jfif'}" alt="${escapeHTML(item.name)}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-200" />

          ${isAdmin ? `
            <div class="absolute top-2 right-2">
              <button data-action="delete-item" data-id="${item.id}" data-qr="${item.qrCode || item.codigo_qr || ''}" data-type="supplies" title="Eliminar de la base de datos" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center text-xs sm:text-sm shadow-md transition active:scale-95">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          ` : ''}

          ${hasMultiple ? `
            <div class="absolute bottom-2 right-2 pointer-events-none">
              <span class="bg-slate-950/75 backdrop-blur-xs text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm flex items-center space-x-1">
                <i class="fa-solid fa-images text-[9px]"></i>
                <span>${imagesList.length}</span>
              </span>
            </div>
          ` : ''}
        </div>

        <div class="p-2.5 sm:p-3 space-y-1.5">
          <h3 class="font-black text-slate-900 dark:text-white text-xs sm:text-sm line-clamp-2 leading-tight">
            ${escapeHTML(item.name)}
          </h3>

          <div class="flex flex-wrap gap-1 pt-0.5">
            <span class="tag-badge">
              <i class="fa-solid fa-box text-[9px]"></i>
              Cant: ${item.quantity || 1}
            </span>
            <span class="tag-badge">
              <i class="fa-solid fa-tag text-[9px]"></i>
              ${escapeHTML(item.category || 'Útiles')}
            </span>
          </div>
        </div>
      </div>
    </div>
  `;
}

function attachCardEvents() {
  document.querySelectorAll('[data-action="expand-img"]').forEach(div => {
    div.addEventListener('click', (e) => {
      const imagesAttr = e.currentTarget.dataset.images;
      let imgs = [];
      if (imagesAttr) {
        try { imgs = JSON.parse(imagesAttr); } catch {}
      }
      if (!imgs.length && e.currentTarget.dataset.src) {
        imgs = [e.currentTarget.dataset.src];
      }
      openZoomViewer(imgs);
    });
  });

  document.querySelectorAll('[data-action="delete-item"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const id = e.currentTarget.dataset.id;
      const type = e.currentTarget.dataset.type;
      const qrCode = e.currentTarget.dataset.qr || '';

      if (confirm('¿Deseas eliminar este registro de la base de datos MySQL?')) {
        let res;
        if (type === 'clothing') {
          res = await StorageManager.deleteClothingItem(id, qrCode);
        } else {
          res = await StorageManager.deleteSupplyItem(id, qrCode);
        }
        if (res && res.success) {
          renderCatalog();
          showToast(res.message || 'Elemento eliminado de la base de datos con éxito', 'success');
        } else {
          showToast(res?.error || 'Error: no se pudo eliminar de la base de datos', 'error');
        }
      }
    });
  });

  document.querySelectorAll('[data-action="reserve-clothing"]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = e.currentTarget.dataset.id;
      openReservationModal(id);
    });
  });
}

async function handleAddProduct(e) {
  e.preventDefault();

  const btnSubmit = document.getElementById('btnSubmitAddProduct');
  if (btnSubmit) {
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-xs"></i><span>Guardando en MySQL...</span>`;
  }

  const name = document.getElementById('productName').value.trim();
  const category = document.getElementById('productCategory')?.value.trim() || 'General';
  const size = document.getElementById('productSize')?.value.trim() || 'Único';
  const quantity = parseInt(document.getElementById('productQuantity')?.value || '1', 10);
  const color = document.getElementById('productColor')?.value.trim() || 'Varios';
  const colorHex = document.getElementById('productColorHex')?.value || '#2563eb';
  const condition = 'Excelente';
  const status = 'Disponible';
  const donor = 'Anónimo';
  const description = document.getElementById('productDescription')?.value.trim() || '';
  const image = selectedProductImages[0] || document.getElementById('productImage')?.value || '';
  const images = selectedProductImages.length > 0 ? selectedProductImages : (image ? [image] : []);
  const type = document.getElementById('productTypeSelect').value;

  const qrSelect = document.getElementById('productQrSelect');
  const assignedQR = (qrSelect ? qrSelect.value.trim() : '') || (document.getElementById('productAssignedQR')?.value.trim() || '');

  if (!name) {
    showToast('Ingresa el nombre del producto', 'error');
    if (btnSubmit) {
      btnSubmit.disabled = false;
      btnSubmit.innerHTML = `<i class="fa-solid fa-floppy-disk text-xs"></i><span>Guardar en MySQL</span>`;
    }
    return;
  }

  if (type === 'clothing' && !assignedQR) {
    showToast('Es obligatorio seleccionar un código QR disponible para la prenda', 'error');
    qrSelect?.focus();
    if (btnSubmit) {
      btnSubmit.disabled = false;
      btnSubmit.innerHTML = `<i class="fa-solid fa-floppy-disk text-xs"></i><span>Guardar en MySQL</span>`;
    }
    return;
  }

  try {
    if (type === 'clothing') {
      await StorageManager.saveClothingItem({
        name,
        category,
        size,
        color,
        colorHex,
        condition,
        status,
        donor,
        description,
        image,
        images,
        qrCode: assignedQR
      });
    } else {
      await StorageManager.saveSupplyItem({
        name,
        category,
        quantity,
        condition,
        status,
        donor,
        description,
        image,
        images
      });
    }

    closeModal('modalAddProduct');
    document.getElementById('formAddProduct').reset();
    selectedProductImages = [];
    document.getElementById('productThumbnailsGrid')?.replaceChildren();
    document.getElementById('productPreviewContainer')?.classList.add('hidden');
    document.getElementById('productImageCountBadge')?.classList.add('hidden');
    const qrInput = document.getElementById('productAssignedQR');
    if (qrInput) qrInput.value = '';

    renderCatalog();
    showToast('¡Guardado exitosamente en la base de datos MySQL!', 'success');
  } catch (err) {
    console.error("Error guardando donación:", err);
    showToast('Error al guardar en la base de datos', 'error');
  } finally {
    if (btnSubmit) {
      btnSubmit.disabled = false;
      btnSubmit.innerHTML = `<i class="fa-solid fa-floppy-disk text-xs"></i><span>Guardar en MySQL</span>`;
    }
  }
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
}

function closeModal(id) {
  if (id === 'modalScanQR') {
    stopQRScanner();
  }
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}

function showToast(message, type = 'info') {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  const bgColors = {
    success: 'bg-emerald-600 text-white',
    error: 'bg-rose-600 text-white',
    info: 'bg-slate-800 dark:bg-slate-700 text-white'
  };

  toast.className = `pointer-events-auto flex items-center space-x-2 px-3 py-2 rounded-xl shadow-lg ${bgColors[type] || bgColors.info} text-xs font-bold max-w-xs transition-all duration-200`;
  toast.innerHTML = `<span>${escapeHTML(message)}</span>`;

  container.appendChild(toast);
  setTimeout(() => toast.remove(), 2500);
}

function escapeHTML(str) {
  if (!str) return '';
  return String(str).replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag));
}

async function openFormForQR(code) {
  document.getElementById('formAddProduct').reset();
  document.getElementById('productPreviewContainer')?.classList.add('hidden');
  document.getElementById('productTypeSelect').value = 'clothing';
  document.getElementById('wrapperProductSize')?.classList.remove('hidden');
  document.getElementById('wrapperProductColor')?.classList.remove('hidden');
  document.getElementById('wrapperProductQuantity')?.classList.add('hidden');
  document.getElementById('wrapperProductQR')?.classList.remove('hidden');

  await populateAvailableQRsDropdown(code);
  openModal('modalAddProduct');
}

async function populateAvailableQRsDropdown(preselectedCode = '') {
  const select = document.getElementById('productQrSelect');
  const countBadge = document.getElementById('badgeQrCountAvailable');
  const warning = document.getElementById('noAvailableQrWarning');
  const helper = document.getElementById('productQrHelper');
  const btnSubmit = document.getElementById('btnSubmitAddProduct');
  const wrapperQr = document.getElementById('wrapperProductQR');
  if (!select) return;

  const type = document.getElementById('productTypeSelect')?.value || 'clothing';
  if (type !== 'clothing') {
    if (wrapperQr) wrapperQr.classList.add('hidden');
    select.required = false;
    return;
  } else {
    if (wrapperQr) wrapperQr.classList.remove('hidden');
    select.required = true;
  }

  select.innerHTML = '<option value="">-- Cargando QRs disponibles... --</option>';
  select.disabled = true;

  const res = await StorageManager.getQRs();
  const allQrs = (res && res.data) ? res.data : [];
  const freeQrs = allQrs.filter(qr => qr.estado === 'libre' || !qr.prenda_id || !qr.prenda_titulo);

  if (countBadge) {
    countBadge.textContent = `${freeQrs.length} disponibles`;
  }

  select.innerHTML = '';

  if (freeQrs.length === 0 && !preselectedCode) {
    select.innerHTML = '<option value="">-- No hay QRs libres disponibles --</option>';
    select.disabled = true;
    if (warning) warning.classList.remove('hidden');
    if (helper) helper.classList.add('hidden');
    if (btnSubmit) btnSubmit.disabled = true;
    return;
  }

  if (warning) warning.classList.add('hidden');
  if (helper) helper.classList.remove('hidden');
  if (btnSubmit) btnSubmit.disabled = false;
  select.disabled = false;

  const defaultOpt = document.createElement('option');
  defaultOpt.value = '';
  defaultOpt.textContent = '-- Seleccionar Código QR Disponible (Obligatorio) --';
  select.appendChild(defaultOpt);

  let preselectedFound = false;
  freeQrs.forEach(qr => {
    const opt = document.createElement('option');
    opt.value = qr.codigo;
    opt.textContent = `🏷️ ${qr.codigo} (Disponible)`;
    if (preselectedCode && qr.codigo === preselectedCode) {
      opt.selected = true;
      preselectedFound = true;
    }
    select.appendChild(opt);
  });

  if (preselectedCode && !preselectedFound) {
    const opt = document.createElement('option');
    opt.value = preselectedCode;
    opt.textContent = `🏷️ ${preselectedCode} (Seleccionado)`;
    opt.selected = true;
    select.appendChild(opt);
  }

  const hiddenInput = document.getElementById('productAssignedQR');
  if (hiddenInput) {
    hiddenInput.value = select.value;
  }
}

function checkURLQRParam() {
  const urlParams = new URLSearchParams(window.location.search);
  const code = urlParams.get('qr') || urlParams.get('codigo');
  if (code) {
    handleScannedCode(code);
  }
}

function setupQRListeners() {
  document.getElementById('btnOpenScanQR')?.addEventListener('click', startQRScanner);
  document.getElementById('btnCloseScanQR')?.addEventListener('click', stopQRScanner);

  document.getElementById('btnManualQrSubmit')?.addEventListener('click', () => {
    const input = document.getElementById('manualQrInput');
    const val = input ? input.value.trim() : '';
    if (val) {
      handleScannedCode(val);
      input.value = '';
    } else {
      showToast('Ingresa un código QR', 'error');
    }
  });

  document.getElementById('manualQrInput')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('btnManualQrSubmit')?.click();
    }
  });

  document.getElementById('btnOpenManageQRs')?.addEventListener('click', async () => {
    currentQrTab = 'disponibles';
    openModal('modalManageQRs');
    await loadAndRenderQRManager();
  });

  document.getElementById('tabQrDisponibles')?.addEventListener('click', () => {
    currentQrTab = 'disponibles';
    loadAndRenderQRManager();
  });

  document.getElementById('tabQrUsados')?.addEventListener('click', () => {
    currentQrTab = 'usados';
    loadAndRenderQRManager();
  });

  document.getElementById('btnGenerateBatchQRs')?.addEventListener('click', async () => {
    const countSelect = document.getElementById('batchQrCount');
    const count = countSelect ? parseInt(countSelect.value, 10) : 10;

    const btn = document.getElementById('btnGenerateBatchQRs');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-xs"></i><span>Generando...</span>`;
    }

    const res = await StorageManager.generateQRBatch(count, 'ROPERO-');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<i class="fa-solid fa-plus text-xs"></i><span>Crear Lote</span>`;
    }

    if (res && res.success) {
      showToast(res.message || 'Lote generado con éxito', 'success');
      currentQrTab = 'disponibles';
      await loadAndRenderQRManager();
    } else {
      showToast(res?.error || 'Error al generar lote', 'error');
    }
  });

  document.getElementById('btnPrintQrSheet')?.addEventListener('click', async () => {
    const res = await StorageManager.getQRs();
    if (res && res.success && res.data && res.data.length > 0) {
      const libres = res.data.filter(qr => qr.estado === 'libre' || !qr.prenda_id || !qr.prenda_titulo);
      const ocupados = res.data.filter(qr => qr.estado === 'ocupado' && qr.prenda_id && qr.prenda_titulo);
      const toPrint = currentQrTab === 'usados' ? ocupados : libres;
      if (toPrint.length === 0) {
        showToast(
          currentQrTab === 'usados' 
            ? 'No hay etiquetas QR asignadas a prendas para imprimir.' 
            : 'No hay etiquetas QR disponibles para imprimir. ¡Creá un lote primero!', 
          'error'
        );
        return;
      }
      printQRStickerSheet(toPrint);
    } else {
      showToast('No hay etiquetas QR para imprimir.', 'error');
    }
  });

  document.getElementById('btnClearAssignedQr')?.addEventListener('click', () => {
    const alertEl = document.getElementById('assignedQrAlert');
    const hiddenInput = document.getElementById('productAssignedQR');
    if (alertEl) alertEl.classList.add('hidden');
    if (hiddenInput) hiddenInput.value = '';
  });

  document.getElementById('btnSubmitChangeStatus')?.addEventListener('click', async () => {
    if (!currentScannedQRData || !currentScannedQRData.prenda) return;
    const prendaId = currentScannedQRData.prenda.raw_id || currentScannedQRData.prenda.id;
    const select = document.getElementById('selectQrChangeStatus');
    const newStatus = select ? select.value : 'Disponible';

    const btn = document.getElementById('btnSubmitChangeStatus');
    if (btn) btn.disabled = true;

    const res = await StorageManager.changePrendaStatus(prendaId, newStatus);
    if (btn) btn.disabled = false;

    if (res && res.success) {
      showToast(`Estado actualizado a '${newStatus}'`, 'success');
      currentScannedQRData.prenda.status = newStatus;
      const statusEl = document.getElementById('qrActionPrendaStatus');
      if (statusEl) statusEl.textContent = newStatus;
      renderCatalog();
    } else {
      showToast(res?.error || 'Error al actualizar estado', 'error');
    }
  });

  document.getElementById('btnInheritQR')?.addEventListener('click', async () => {
    if (!currentScannedQRData || !currentScannedQRData.prenda) return;
    const code = currentScannedQRData.code;
    const prendaId = currentScannedQRData.prenda.raw_id || currentScannedQRData.prenda.id;

    if (!confirm(`¿Confirmas que deseas heredar la etiqueta ${code} a una nueva prenda?\nLa prenda anterior se marcará como entregada y la etiqueta quedará libre para asignarse inmediatamente.`)) {
      return;
    }

    const btn = document.getElementById('btnInheritQR');
    if (btn) btn.disabled = true;

    const res = await StorageManager.inheritQR(code, prendaId);
    if (btn) btn.disabled = false;

    if (res && res.success) {
      showToast(res.message || `Etiqueta ${code} liberada para nueva prenda`, 'success');
      closeModal('modalQRAction');
      await StorageManager.init();
      renderCatalog();
      await openFormForQR(code);
    } else {
      showToast(res?.error || 'Error al heredar QR', 'error');
    }
  });

  document.getElementById('btnDeletePrendaFromQR')?.addEventListener('click', async () => {
    if (!currentScannedQRData || !currentScannedQRData.prenda) return;
    const code = currentScannedQRData.code;
    const prendaId = currentScannedQRData.prenda.raw_id || currentScannedQRData.prenda.id;
    const prendaName = currentScannedQRData.prenda.name || currentScannedQRData.prenda.titulo;

    if (!confirm(`¿Deseas eliminar la prenda "${prendaName}"?`)) {
      return;
    }

    const res = await StorageManager.deletePrendaAndFreeQR(prendaId, code);
    if (res && res.success) {
      showToast(res.message, 'info');
      closeModal('modalQRAction');
      renderCatalog();
    } else {
      showToast(res?.error || 'Error al eliminar prenda', 'error');
    }
  });
}

async function startQRScanner() {
  openModal('modalScanQR');
  const readerDiv = document.getElementById('qrReader');
  if (!readerDiv) return;

  if (typeof Html5Qrcode === 'undefined') {
    readerDiv.innerHTML = `<div class="p-4 text-center text-xs text-amber-400 font-bold">Cargando escáner...</div>`;
    return;
  }

  try {
    if (html5QrScanner) {
      await stopQRScanner();
    }
    html5QrScanner = new Html5Qrcode("qrReader");
    await html5QrScanner.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: { width: 200, height: 200 } },
      (decodedText) => {
        handleScannedCode(decodedText);
      },
      () => {}
    );
  } catch (err) {
    console.warn("No se pudo iniciar cámara:", err);
    readerDiv.innerHTML = `
      <div class="p-4 text-center space-y-1.5">
        <i class="fa-solid fa-camera-slash text-amber-400 text-xl"></i>
        <p class="text-xs text-white font-bold">Cámara no disponible</p>
      </div>
    `;
  }
}

async function stopQRScanner() {
  if (html5QrScanner) {
    try {
      await html5QrScanner.stop();
      html5QrScanner.clear();
    } catch (e) {}
    html5QrScanner = null;
  }
}

async function handleScannedCode(rawCode) {
  if (!rawCode) return;
  await stopQRScanner();
  closeModal('modalScanQR');

  showToast(`Consultando etiqueta: ${rawCode}...`, 'info');
  const result = await StorageManager.scanQR(rawCode);

  if (!result || !result.success) {
    showToast(result?.error || 'No se pudo leer la etiqueta QR', 'error');
    return;
  }

  currentScannedQRData = result;

  if (!result.is_assigned || !result.prenda) {
    showToast(`Etiqueta ${result.code} libre: Abriendo formulario...`, 'success');
    openFormForQR(result.code);
  } else {
    renderQRActionModal(result);
  }
}

function renderQRActionModal(data) {
  const codeBadge = document.getElementById('qrActionCodeBadge');
  const freeState = document.getElementById('qrActionFreeState');
  const assignedState = document.getElementById('qrActionAssignedState');

  if (codeBadge) codeBadge.textContent = data.code;

  if (!data.is_assigned || !data.prenda) {
    freeState?.classList.remove('hidden');
    assignedState?.classList.add('hidden');
  } else {
    freeState?.classList.add('hidden');
    assignedState?.classList.remove('hidden');

    const p = data.prenda;
    const imgEl = document.getElementById('qrActionPrendaImg');
    const titleEl = document.getElementById('qrActionPrendaTitle');
    const sizeEl = document.getElementById('qrActionPrendaSize');
    const catEl = document.getElementById('qrActionPrendaCat');
    const colorEl = document.getElementById('qrActionPrendaColor');
    const statusEl = document.getElementById('qrActionPrendaStatus');
    const selectStatus = document.getElementById('selectQrChangeStatus');

    if (imgEl) imgEl.src = p.image || 'imagenes/logo.jfif';
    if (titleEl) titleEl.textContent = p.name || p.titulo;
    if (sizeEl) sizeEl.textContent = `Talle: ${p.size || p.talle || 'Único'}`;
    if (catEl) catEl.textContent = p.category || p.categoria || 'General';
    if (colorEl) colorEl.textContent = p.color || 'Varios';
    if (statusEl) statusEl.textContent = p.status || p.estado || 'Disponible';
    if (selectStatus) selectStatus.value = p.status || p.estado || 'Disponible';
  }

  openModal('modalQRAction');
}

let currentQrTab = 'disponibles';

async function loadAndRenderQRManager() {
  const res = await StorageManager.getQRs();
  if (!res || !res.success) return;

  const countDispEl = document.getElementById('countQrDisponibles');
  const countUsadEl = document.getElementById('countQrUsados');
  const container = document.getElementById('qrsListContainer');
  const tabDisp = document.getElementById('tabQrDisponibles');
  const tabUsad = document.getElementById('tabQrUsados');

  const allQrs = res.data || [];
  const libres = allQrs.filter(qr => qr.estado === 'libre' || !qr.prenda_id || !qr.prenda_titulo);
const ocupados = allQrs.filter(qr => qr.estado === 'ocupado' && Boolean(qr.prenda_titulo));
  if (countDispEl) countDispEl.textContent = libres.length;
  if (countUsadEl) countUsadEl.textContent = ocupados.length;

  if (tabDisp && tabUsad) {
    if (currentQrTab === 'disponibles') {
      tabDisp.className = 'flex-1 py-3 px-3 rounded-xl font-black text-xs sm:text-sm bg-emerald-600 text-white shadow-sm flex items-center justify-center space-x-2 min-h-[46px] transition';
      tabUsad.className = 'flex-1 py-3 px-3 rounded-xl font-black text-xs sm:text-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center space-x-2 min-h-[46px] transition';
    } else {
      tabDisp.className = 'flex-1 py-3 px-3 rounded-xl font-black text-xs sm:text-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center space-x-2 min-h-[46px] transition';
      tabUsad.className = 'flex-1 py-3 px-3 rounded-xl font-black text-xs sm:text-sm bg-blue-600 text-white shadow-sm flex items-center justify-center space-x-2 min-h-[46px] transition';
    }
  }

  if (!container) return;

  const currentList = currentQrTab === 'disponibles' ? libres : ocupados;

  if (currentList.length === 0) {
    if (currentQrTab === 'disponibles') {
      container.innerHTML = `
        <div class="p-8 text-center text-slate-400 space-y-2 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
          <i class="fa-solid fa-qrcode text-3xl sm:text-4xl text-slate-300 dark:text-slate-600"></i>
          <p class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200">No hay códigos QR disponibles en este momento</p>
          <p class="text-[11px] text-slate-500">Creá un nuevo lote con el botón "Crear Lote" arriba para tener stickers listos.</p>
        </div>
      `;
    } else {
      container.innerHTML = `
        <div class="p-8 text-center text-slate-400 space-y-2 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
          <i class="fa-solid fa-box-open text-3xl sm:text-4xl text-slate-300 dark:text-slate-600"></i>
          <p class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200">No hay códigos QR usados todavía</p>
          <p class="text-[11px] text-slate-500">Los QRs asignados a prendas donadas aparecerán en esta sección.</p>
        </div>
      `;
    }
    return;
  }

  const originPath = window.location.href.split('?')[0];

  container.innerHTML = currentList.map((qr, index) => {
    const isFree = qr.estado === 'libre' || !qr.prenda_id || !qr.prenda_titulo;
    return `
      <div class="flex flex-col xs:flex-row items-center justify-between p-3 sm:p-4 rounded-2xl border ${isFree ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800'} text-xs gap-3">
        <div class="flex items-center space-x-3 min-w-0 w-full xs:w-auto">
          <div id="manage_qr_thumb_${index}" class="bg-white p-1.5 rounded-xl border border-slate-200 shrink-0 flex items-center justify-center shadow-sm" style="width: 84px; height: 84px;"></div>
          
          <div class="min-w-0 space-y-1">
            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
              <span class="font-mono font-black text-slate-900 dark:text-white text-sm sm:text-base shrink-0">${escapeHTML(qr.codigo)}</span>
              <span class="text-[10px] font-black px-2 py-0.5 rounded-md ${isFree ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-blue-500/20 text-blue-600 dark:text-blue-400'} shrink-0">
                ${isFree ? 'Disponible' : 'En Uso'}
              </span>
            </div>
            ${!isFree && qr.prenda_titulo ? `<p class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200 truncate max-w-[220px]">Prenda: ${escapeHTML(qr.prenda_titulo)}</p>` : ''}
            ${!isFree && (qr.prenda_categoria || qr.prenda_talle) ? `<p class="text-[11px] text-slate-500">Talle: ${escapeHTML(qr.prenda_talle || 'N/A')} • ${escapeHTML(qr.prenda_categoria || '')}</p>` : ''}
            ${isFree ? `<p class="text-[11px] text-slate-500 dark:text-slate-400">Listo para vincular a una prenda</p>` : ''}
          </div>
        </div>

        <div class="w-full xs:w-auto shrink-0 flex items-center space-x-2">
          ${isFree ? `
            <button data-action="use-in-donation" data-code="${qr.codigo}" class="w-full xs:w-auto px-4 py-2.5 rounded-xl bg-[rgb(232,125,53)] hover:bg-[rgb(212,105,33)] text-white text-xs sm:text-sm font-black transition flex items-center justify-center space-x-1.5 shadow-sm min-h-[40px]">
              <i class="fa-solid fa-circle-plus"></i>
              <span>Asignar a Prenda</span>
            </button>
            <button data-action="delete-qr" data-code="${qr.codigo}" data-occupied="false" title="Eliminar etiqueta QR" class="px-3 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 dark:border-rose-900/50 dark:bg-rose-950/30 text-xs sm:text-sm font-bold transition flex items-center justify-center space-x-1 min-h-[40px]">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          ` : `
            <button data-action="view-occupied-ficha" data-code="${qr.codigo}" class="w-full xs:w-auto px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-bold transition flex items-center justify-center space-x-1.5 min-h-[40px]">
              <i class="fa-solid fa-eye text-xs"></i>
              <span>Ver Ficha</span>
            </button>
            <button data-action="delete-qr" data-code="${qr.codigo}" data-occupied="true" title="Eliminar etiqueta QR" class="px-3 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 dark:border-rose-900/50 dark:bg-rose-950/30 text-xs sm:text-sm font-bold transition flex items-center justify-center space-x-1 min-h-[40px]">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          `}
        </div>
      </div>
    `;
  }).join('');

  currentList.forEach((qr, index) => {
    const thumbDiv = document.getElementById(`manage_qr_thumb_${index}`);
    if (thumbDiv && typeof QRCode !== 'undefined') {
      try {
        new QRCode(thumbDiv, {
          text: `${originPath}?qr=${encodeURIComponent(qr.codigo)}`,
          width: 76,
          height: 76,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.M
        });
      } catch (e) {}
    }
  });

  container.querySelectorAll('[data-action="use-in-donation"]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const code = e.currentTarget.dataset.code;
      closeModal('modalManageQRs');
      openFormForQR(code);
    });
  });

  container.querySelectorAll('[data-action="view-occupied-ficha"]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const code = e.currentTarget.dataset.code;
      closeModal('modalManageQRs');
      handleScannedCode(code);
    });
  });

  container.querySelectorAll('[data-action="delete-qr"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const code = e.currentTarget.dataset.code;
      const isOccupied = e.currentTarget.dataset.occupied === 'true';

      const msg = isOccupied
        ? `La etiqueta QR "${code}" está asignada a una prenda. ¿Deseas eliminarla? La prenda quedará desvinculada.`
        : `¿Estás seguro de que deseas eliminar permanentemente la etiqueta QR "${code}"?`;

      if (confirm(msg)) {
        const res = await StorageManager.deleteQR(code);
        if (res && res.success) {
          showToast(res.message || `Etiqueta QR "${code}" eliminada con éxito`, 'success');
          await loadAndRenderQRManager();
        } else {
          showToast(res?.error || 'No se pudo eliminar la etiqueta QR', 'error');
        }
      }
    });
  });
}

function printQRStickerSheet(qrs) {
  const sheet = document.getElementById('printableQrSheet');
  if (!sheet) return;

  sheet.innerHTML = '';
  showToast('Generando plancha de stickers QR...', 'info');

  const originPath = window.location.href.split('?')[0];

  qrs.forEach((qr, index) => {
    const itemDiv = document.createElement('div');
    itemDiv.className = 'qr-sticker-item';
    itemDiv.innerHTML = `
      <div id="printQrItem_${index}" style="display:flex; justify-content:center; margin-bottom: 4px;"></div>
      <div style="font-family: monospace; font-weight: 900; font-size: 13px; color: #000;">${escapeHTML(qr.codigo)}</div>
      <div style="font-size: 9px; color: #334155; font-weight: 700;">Técnica 5 - Ropero Escolar</div>
    `;
    sheet.appendChild(itemDiv);

    if (typeof QRCode !== 'undefined') {
      try {
        const qrContainer = itemDiv.querySelector(`#printQrItem_${index}`);
        new QRCode(qrContainer, {
          text: `${originPath}?qr=${encodeURIComponent(qr.codigo)}`,
          width: 125,
          height: 125,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.M
        });
      } catch (e) {}
    }
  });

  setTimeout(() => {
    window.print();
  }, 400);
}

/* ========================================================
   SECCIÓN DE RESERVAS & TURNOS (USUARIO & ADMIN)
   ======================================================== */
function openReservationModal(id) {
  const item = StorageManager.getClothing().find(i => String(i.id) === String(id) || String(i.raw_id) === String(id));
  if (!item) {
    showToast('No se encontró la prenda seleccionada', 'error');
    return;
  }

  const previewImg = document.getElementById('reservePreviewImg');
  const previewTitle = document.getElementById('reservePreviewTitle');
  const previewSize = document.getElementById('reservePreviewSize');
  const previewCat = document.getElementById('reservePreviewCat');
  const previewQr = document.getElementById('reservePreviewQr');
  const hiddenId = document.getElementById('reservePrendaId');
  const hiddenQr = document.getElementById('reserveCodigoQr');
  const hiddenTitle = document.getElementById('reservePrendaTitulo');
  const dateInput = document.getElementById('reserveFechaSolicitada');

  if (previewImg) previewImg.src = item.image || 'imagenes/logo.jfif';
  if (previewTitle) previewTitle.textContent = item.name || item.titulo || 'Prenda Escolar';
  if (previewSize) previewSize.textContent = `Talle: ${item.size || item.talle || 'Único'}`;
  if (previewCat) previewCat.textContent = item.category || item.categoria || 'General';
  if (previewQr) previewQr.textContent = `Código: ${item.qrCode || item.codigo_qr || 'PRD'}`;

  if (hiddenId) hiddenId.value = item.raw_id || item.id;
  if (hiddenQr) hiddenQr.value = item.qrCode || item.codigo_qr || '';
  if (hiddenTitle) hiddenTitle.value = item.name || item.titulo || '';

  // Establecer fecha mínima como hoy
  const today = new Date().toISOString().split('T')[0];
  if (dateInput) {
    dateInput.min = today;
    if (!dateInput.value) {
      dateInput.value = today;
    }
  }

  openModal('modalReserveProduct');
}

async function handleReserveSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSubmitReserve');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-xs"></i><span>Guardando reserva...</span>`;
  }

  const prenda_id = document.getElementById('reservePrendaId')?.value || '';
  const codigo_qr = document.getElementById('reserveCodigoQr')?.value || '';
  const prenda_titulo = document.getElementById('reservePrendaTitulo')?.value || '';
  const nombre = document.getElementById('reserveNombre')?.value.trim() || '';
  const dni = document.getElementById('reserveDni')?.value.trim() || '';
  const telefono = document.getElementById('reserveTelefono')?.value.trim() || '';
  const fecha_solicitada = document.getElementById('reserveFechaSolicitada')?.value || '';

  if (!nombre) {
    showToast('El nombre y apellido son obligatorios', 'error');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<i class="fa-solid fa-check text-xs"></i><span>Confirmar Reserva</span>`;
    }
    return;
  }

  const res = await StorageManager.createReservation({
    prenda_id,
    codigo_qr,
    prenda_titulo,
    nombre,
    dni,
    curso: '',
    telefono,
    fecha_solicitada,
    notas: ''
  });

  if (btn) {
    btn.disabled = false;
    btn.innerHTML = `<i class="fa-solid fa-check text-xs"></i><span>Confirmar Reserva</span>`;
  }

  if (res && res.success) {
    closeModal('modalReserveProduct');
    document.getElementById('formReserveProduct')?.reset();
    showToast('¡Reserva registrada con éxito! La prenda quedó apartada.', 'success');
    renderCatalog();
    updateNotificationsBadge();
    updateAdminReservationsBadge();
  } else {
    showToast('No se pudo completar la reserva', 'error');
  }
}

let currentResFilter = 'Pendientes';

function setupReservationListeners() {
  document.getElementById('formReserveProduct')?.addEventListener('submit', handleReserveSubmit);

  document.getElementById('btnOpenReservations')?.addEventListener('click', () => {
    currentResFilter = 'Pendientes';
    openModal('modalManageReservations');
    renderAdminReservations();
  });

  document.getElementById('tabResPendientes')?.addEventListener('click', () => {
    currentResFilter = 'Pendientes';
    renderAdminReservations();
  });

  document.getElementById('tabResAprobadas')?.addEventListener('click', () => {
    currentResFilter = 'Aprobadas';
    renderAdminReservations();
  });

  document.getElementById('tabResCompletadas')?.addEventListener('click', () => {
    currentResFilter = 'Completadas';
    renderAdminReservations();
  });

  document.getElementById('tabResTodas')?.addEventListener('click', () => {
    currentResFilter = 'Todas';
    renderAdminReservations();
  });
}

function updateAdminReservationsBadge() {
  const badge = document.getElementById('badgeAdminPendingReservations');
  if (!badge) return;
  const count = StorageManager.getPendingReservationsCount();
  if (count > 0) {
    badge.textContent = count;
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
}

function renderAdminReservations() {
  const container = document.getElementById('reservationsListContainer');
  const countPend = document.getElementById('countResPendientes');
  const countAprob = document.getElementById('countResAprobadas');
  const countComp = document.getElementById('countResCompletadas');
  const countTodas = document.getElementById('countResTodas');

  const tabPend = document.getElementById('tabResPendientes');
  const tabAprob = document.getElementById('tabResAprobadas');
  const tabComp = document.getElementById('tabResCompletadas');
  const tabTodas = document.getElementById('tabResTodas');

  const allRes = StorageManager.getReservations();

  const pendientes = allRes.filter(r => r.estado === 'Pendiente');
  const aprobadas = allRes.filter(r => r.estado === 'Aprobada');
  const completadas = allRes.filter(r => r.estado === 'Completada' || r.estado === 'Entregado');

  if (countPend) countPend.textContent = pendientes.length;
  if (countAprob) countAprob.textContent = aprobadas.length;
  if (countComp) countComp.textContent = completadas.length;
  if (countTodas) countTodas.textContent = allRes.length;

  const updateTabStyle = (btn, isActive, activeColor) => {
    if (!btn) return;
    if (isActive) {
      btn.className = `px-3.5 py-2 rounded-xl ${activeColor} text-white shadow-sm shrink-0 min-h-[38px] transition`;
    } else {
      btn.className = `px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 shrink-0 min-h-[38px] transition`;
    }
  };

  updateTabStyle(tabPend, currentResFilter === 'Pendientes', 'bg-amber-500');
  updateTabStyle(tabAprob, currentResFilter === 'Aprobadas', 'bg-blue-600');
  updateTabStyle(tabComp, currentResFilter === 'Completadas', 'bg-emerald-600');
  updateTabStyle(tabTodas, currentResFilter === 'Todas', 'bg-slate-800 dark:bg-slate-700');

  if (!container) return;

  let filtered = allRes;
  if (currentResFilter === 'Pendientes') filtered = pendientes;
  else if (currentResFilter === 'Aprobadas') filtered = aprobadas;
  else if (currentResFilter === 'Completadas') filtered = completadas;

  if (filtered.length === 0) {
    container.innerHTML = `
      <div class="p-8 text-center text-slate-400 space-y-2 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
        <i class="fa-solid fa-calendar-xmark text-3xl sm:text-4xl text-slate-300 dark:text-slate-600"></i>
        <p class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200">No hay reservas en esta sección</p>
      </div>
    `;
    return;
  }

  container.innerHTML = filtered.map(r => {
    const p = r.prenda || {};
    const imgUrl = p.image || 'imagenes/logo.jfif';
    const status = r.estado || 'Pendiente';
    const statusBadges = {
      'Pendiente': 'bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/30',
      'Aprobada': 'bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-500/30',
      'Completada': 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30',
      'Cancelada': 'bg-rose-500/20 text-rose-700 dark:text-rose-400 border border-rose-500/30'
    };

    const cleanPhone = (r.telefono || '').replace(/\D/g, '');
    const waUrl = cleanPhone ? `https://wa.me/549${cleanPhone}?text=${encodeURIComponent(`Hola ${r.nombre}, te contactamos del Ropero Escolar de la Técnica 5 sobre tu reserva de "${r.prendaTitulo}".`)}` : '';

    return `
      <div class="p-3.5 sm:p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/80 shadow-sm space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-700/60 pb-2.5">
          <div class="flex items-center space-x-2.5 min-w-0">
            <span class="w-8 h-8 rounded-xl bg-[rgb(232,125,53)]/10 text-[rgb(232,125,53)] flex items-center justify-center font-bold shrink-0 text-xs">
              <i class="fa-solid fa-user"></i>
            </span>
            <div class="min-w-0">
              <h4 class="font-black text-sm text-slate-900 dark:text-white truncate">${escapeHTML(r.nombre)}</h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold truncate">
                ${r.dni ? `DNI: ${escapeHTML(r.dni)} • ` : ''}${r.curso ? `Curso: ${escapeHTML(r.curso)}` : 'Alumno'}
              </p>
            </div>
          </div>
          <div class="flex items-center space-x-2 shrink-0">
            <span class="px-2.5 py-1 rounded-lg text-xs font-black ${statusBadges[status] || statusBadges['Pendiente']}">
              ${status}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
          <!-- INFO DE LA PRENDA -->
          <div class="flex space-x-3 p-2.5 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-100 dark:border-slate-700/40">
            <img src="${imgUrl}" class="w-14 h-14 rounded-lg object-cover shrink-0 border border-slate-200 dark:border-slate-700" />
            <div class="min-w-0 flex-1">
              <h5 class="font-black text-slate-900 dark:text-white text-xs truncate">${escapeHTML(r.prendaTitulo)}</h5>
              <p class="text-[10px] font-mono text-slate-400 mt-0.5 truncate">${escapeHTML(r.codigoQr || 'Prenda Escolar')}</p>
              ${p.size ? `<span class="tag-badge text-[9px] mt-1 inline-block">Talle: ${escapeHTML(p.size)}</span>` : ''}
            </div>
          </div>

          <!-- DETALLES Y CONTACTO -->
          <div class="space-y-1.5 p-2.5 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-100 dark:border-slate-700/40">
            <div class="flex items-center justify-between">
              <span class="text-slate-400 font-bold text-[11px]">Fecha pedida:</span>
              <span class="font-black text-slate-700 dark:text-slate-200">${escapeHTML(r.fechaSolicitada || 'A coordinar')}</span>
            </div>
            ${r.telefono ? `
              <div class="flex items-center justify-between">
                <span class="text-slate-400 font-bold text-[11px]">Contacto:</span>
                <div class="flex items-center space-x-1.5">
                  <span class="font-bold text-slate-700 dark:text-slate-200">${escapeHTML(r.telefono)}</span>
                  ${waUrl ? `
                    <a href="${waUrl}" target="_blank" title="Abrir chat en WhatsApp" class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black inline-flex items-center space-x-1">
                      <i class="fa-brands fa-whatsapp"></i>
                      <span>Chat</span>
                    </a>
                  ` : ''}
                </div>
              </div>
            ` : ''}
            ${r.notasUsuario ? `
              <p class="text-[11px] text-slate-500 italic mt-1 bg-white dark:bg-slate-800 p-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
                "${escapeHTML(r.notasUsuario)}"
              </p>
            ` : ''}
          </div>
        </div>

        <!-- ASIGNACIÓN DE FECHA DE RETIRO Y NOTAS DEL ADMIN -->
        <div class="space-y-2 pt-1 border-t border-slate-100 dark:border-slate-700/60">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div>
              <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">
                <i class="fa-solid fa-clock text-slate-400 mr-1"></i>Anotar fecha de retiro / turno:
              </label>
              <input type="date" id="admin_date_${r.id}" value="${r.fechaRetiro || r.fechaSolicitada || ''}"
                class="w-full px-3 py-1.5 text-xs font-bold bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none min-h-[40px]" />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">
                <i class="fa-solid fa-comment-dots text-slate-400 mr-1"></i>Indicaciones para el alumno:
              </label>
              <input type="text" id="admin_notes_${r.id}" value="${escapeHTML(r.notasAdmin || '')}" placeholder="Ej: Retirar por preceptoría 1er piso"
                class="w-full px-3 py-1.5 text-xs font-semibold bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none min-h-[40px]" />
            </div>
          </div>

          <!-- BOTONES DE ACCIÓN DEL ADMIN -->
          <div class="flex flex-wrap gap-2 pt-1 justify-end">
            ${status !== 'Aprobada' && status !== 'Completada' ? `
              <button data-action="admin-res-approve" data-id="${r.id}" class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black transition flex items-center space-x-1.5 min-h-[38px] active:scale-95 shadow-sm">
                <i class="fa-solid fa-calendar-check text-xs"></i>
                <span>Aprobar Turno</span>
              </button>
            ` : ''}

            ${status !== 'Completada' ? `
              <button data-action="admin-res-complete" data-id="${r.id}" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition flex items-center space-x-1.5 min-h-[38px] active:scale-95 shadow-sm">
                <i class="fa-solid fa-circle-check text-xs"></i>
                <span>Entregar Prenda</span>
              </button>
            ` : ''}

            ${status !== 'Cancelada' ? `
              <button data-action="admin-res-cancel" data-id="${r.id}" class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 dark:border-rose-900/50 dark:bg-rose-950/30 text-xs font-bold transition flex items-center space-x-1 min-h-[38px] active:scale-95">
                <i class="fa-solid fa-ban text-xs"></i>
                <span>Cancelar (Liberar)</span>
              </button>
            ` : ''}
          </div>
        </div>
      </div>
    `;
  }).join('');

  // Eventos de botones admin
  container.querySelectorAll('[data-action="admin-res-approve"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = e.currentTarget.dataset.id;
      const dateVal = document.getElementById(`admin_date_${id}`)?.value || '';
      const notesVal = document.getElementById(`admin_notes_${id}`)?.value || '';

      await StorageManager.updateReservation(id, 'Aprobada', dateVal, notesVal);
      showToast('Reserva aprobada con turno registrado', 'success');
      renderAdminReservations();
      renderCatalog();
      updateAdminReservationsBadge();
    });
  });

  container.querySelectorAll('[data-action="admin-res-complete"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = e.currentTarget.dataset.id;
      const dateVal = document.getElementById(`admin_date_${id}`)?.value || '';
      const notesVal = document.getElementById(`admin_notes_${id}`)?.value || '';

      if (confirm('¿Confirmas que la prenda fue entregada al solicitante?')) {
        await StorageManager.updateReservation(id, 'Completada', dateVal, notesVal);
        showToast('Prenda marcada como entregada', 'success');
        renderAdminReservations();
        renderCatalog();
        updateAdminReservationsBadge();
      }
    });
  });

  container.querySelectorAll('[data-action="admin-res-cancel"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = e.currentTarget.dataset.id;
      const dateVal = document.getElementById(`admin_date_${id}`)?.value || '';
      const notesVal = document.getElementById(`admin_notes_${id}`)?.value || '';

      if (confirm('¿Deseas cancelar esta reserva? La prenda volverá a estar disponible en el catálogo.')) {
        await StorageManager.updateReservation(id, 'Cancelada', dateVal, notesVal);
        showToast('Reserva cancelada y prenda liberada', 'info');
        renderAdminReservations();
        renderCatalog();
        updateAdminReservationsBadge();
      }
    });
  });
}

/* ========================================================
   SECCIÓN DE NOTIFICACIONES (AVISOS FORO Y RESERVAS)
   ======================================================== */
function setupNotificationListeners() {
  document.getElementById('btnOpenNotifications')?.addEventListener('click', () => {
    openModal('modalNotifications');
    renderNotifications();
  });

  document.getElementById('btnMarkAllNotifsRead')?.addEventListener('click', async () => {
    await StorageManager.markNotificationsRead();
    renderNotifications();
    updateNotificationsBadge();
    showToast('Notificaciones marcadas como leídas', 'info');
  });
}

function updateNotificationsBadge() {
  const badge = document.getElementById('badgeNotifCount');
  if (!badge) return;
  const count = StorageManager.getUnreadNotificationsCount();
  if (count > 0) {
    badge.textContent = count > 99 ? '99+' : count;
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
}

function renderNotifications() {
  const container = document.getElementById('notificationsListContainer');
  if (!container) return;

  const notifs = StorageManager.getNotifications();
  if (!notifs || notifs.length === 0) {
    container.innerHTML = `
      <div class="p-8 text-center text-slate-400 space-y-2 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
        <i class="fa-solid fa-bell-slash text-3xl sm:text-4xl text-slate-300 dark:text-slate-600"></i>
        <p class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200">No hay notificaciones</p>
        <p class="text-[11px] text-slate-500">Aquí te avisaremos cuando te respondan en el foro o sobre el estado de tus reservas.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = notifs.map(n => {
    let iconClass = 'fa-solid fa-bullhorn text-indigo-500';
    let bgIcon = 'bg-indigo-500/10';
    if (n.type === 'foro_reply') {
      iconClass = 'fa-solid fa-comments text-[rgb(232,125,53)]';
      bgIcon = 'bg-[rgb(232,125,53)]/10';
    } else if (n.type === 'reserva_nueva') {
      iconClass = 'fa-solid fa-calendar-plus text-emerald-600';
      bgIcon = 'bg-emerald-500/10';
    } else if (n.type === 'reserva_update') {
      iconClass = 'fa-solid fa-calendar-check text-blue-600';
      bgIcon = 'bg-blue-500/10';
    }

    const timeAgo = formatTimeAgo(n.createdAt);

    return `
      <div class="p-3 rounded-2xl border ${!n.read ? 'border-[rgb(232,125,53)]/40 bg-[rgb(232,125,53)]/5' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/60'} text-xs space-y-1 transition">
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center space-x-2 min-w-0">
            <span class="w-7 h-7 rounded-lg ${bgIcon} flex items-center justify-center shrink-0 text-xs">
              <i class="${iconClass}"></i>
            </span>
            <span class="font-black text-slate-800 dark:text-white text-xs truncate">${escapeHTML(n.title)}</span>
          </div>
          <span class="text-[10px] text-slate-400 font-semibold shrink-0">${timeAgo}</span>
        </div>
        <p class="text-slate-600 dark:text-slate-300 text-xs leading-relaxed pl-9">
          ${escapeHTML(n.message)}
        </p>
        ${n.link ? `
          <div class="pl-9 pt-1">
            <a href="${escapeHTML(n.link)}" class="text-[11px] font-bold text-[rgb(44,83,181)] dark:text-blue-400 hover:underline inline-flex items-center space-x-1">
              <span>Ver detalles</span>
              <i class="fa-solid fa-arrow-right text-[9px]"></i>
            </a>
          </div>
        ` : ''}
      </div>
    `;
  }).join('');
}

function formatTimeAgo(dateString) {
  if (!dateString) return '';
  const now = new Date();
  const date = new Date(dateString);
  const diffSec = Math.floor((now - date) / 1000);
  if (isNaN(diffSec)) return '';
  if (diffSec < 60) return 'Hace un momento';
  const diffMin = Math.floor(diffSec / 60);
  if (diffMin < 60) return `Hace ${diffMin} min`;
  const diffHours = Math.floor(diffMin / 60);
  if (diffHours < 24) return `Hace ${diffHours} h`;
  const diffDays = Math.floor(diffHours / 24);
  return `Hace ${diffDays} d`;
}