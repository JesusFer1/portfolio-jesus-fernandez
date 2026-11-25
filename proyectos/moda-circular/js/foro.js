import { StorageManager } from './storage.js';

let currentSort = 'top';
let currentCategory = 'ALL';
let isPollMode = false;

function startForo() {
  initTheme();
  setupEventListeners();
  setupPostImageHandlers();
  setupCategorySelectHandler();
  setupPollFormHandlers();
  setupNotificationListeners();
  updateForoUserSession();
  checkAdminStatus();
  renderizarComentarios();

  // Cargar datos desde la base de datos MySQL / Local de forma asíncrona
  StorageManager.init().then(() => {
    updateForoUserSession();
    checkAdminStatus();
    updateDbBanner();
    updateNotificationsBadge();
    renderizarComentarios();
  }).catch((err) => {
    console.warn("StorageManager.init en foro fallo:", err);
    updateForoUserSession();
    checkAdminStatus();
    updateNotificationsBadge();
    renderizarComentarios();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', startForo);
} else {
  startForo();
}

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

async function updateDbBanner() {
  const banner = document.getElementById('dbStatusBannerForo');
  if (!banner) return;
  const info = await StorageManager.checkDbConnection();
  if (info && info.success) {
    banner.innerHTML = `
      <div class="flex items-center space-x-2 min-w-0">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
        <div class="truncate">
          <span class="font-extrabold text-slate-800 dark:text-slate-200">Base de Datos MySQL:</span>
          <span class="text-emerald-600 dark:text-emerald-400 font-black ml-1">Conectado a '${info.database}'</span>
        </div>
      </div>
      <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">Host: ${info.host}</span>
    `;
  } else {
    banner.innerHTML = `
      <div class="flex items-center space-x-2 min-w-0">
        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
        <div class="truncate">
          <span class="font-extrabold text-slate-800 dark:text-slate-200">Base de Datos:</span>
          <span class="text-amber-600 dark:text-amber-400 font-bold ml-1">Modo local / Contingencia</span>
        </div>
      </div>
      <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">Listo para sincronizar</span>
    `;
  }
}

function updateForoUserSession() {
  const user = StorageManager.getCurrentUser();
  const sessionBox = document.getElementById('foroUserSession');
  if (!sessionBox) return;

  if (user) {
    const roleColor = user.isAdmin ? 'text-rose-500 font-black' : 'text-blue-500 font-bold';
    sessionBox.innerHTML = `
      <div class="flex items-center space-x-2 bg-slate-100 dark:bg-slate-800 py-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 min-h-[44px]">
        <i class="fa-solid fa-user-circle text-[rgb(44,83,181)] text-sm"></i>
        <span class="font-extrabold text-xs sm:text-sm text-slate-800 dark:text-white truncate max-w-[110px]">${escapeHTML(user.nombre)}</span>
        <span class="text-[10px] sm:text-xs ${roleColor}">(${user.isAdmin ? 'Admin' : 'Alumno'})</span>
        <button id="btnForoLogout" title="Cerrar sesión" class="text-slate-400 hover:text-rose-600 ml-1.5 p-1 text-sm transition">
          <i class="fa-solid fa-right-from-bracket"></i>
        </button>
      </div>
    `;

    document.getElementById('btnForoLogout')?.addEventListener('click', async () => {
      await StorageManager.logout();
      showToast('Sesión cerrada', 'info');
      updateForoUserSession();
      checkAdminStatus();
      renderizarComentarios();
    });
  } else {
    sessionBox.innerHTML = '';
  }
}

function checkAdminStatus() {
  const isAdmin = StorageManager.isAdminLoggedIn();
  const adminWrapper = document.getElementById('adminCheckboxWrapper');
  const adminBanner = document.getElementById('adminStatusBanner');
  
  if (isAdmin) {
    if (adminWrapper) adminWrapper.classList.remove('hidden');
    if (adminBanner) adminBanner.classList.remove('hidden');
  } else {
    if (adminWrapper) adminWrapper.classList.add('hidden');
    if (adminBanner) adminBanner.classList.add('hidden');
  }
}

function setupPollFormHandlers() {
  const btnTypePost = document.getElementById('btnTypePost');
  const btnTypePoll = document.getElementById('btnTypePoll');
  const pollWrapper = document.getElementById('pollFieldsWrapper');
  const addOptionBtn = document.getElementById('btnAddPollOption');
  const optionsContainer = document.getElementById('pollOptionsContainer');

  btnTypePost?.addEventListener('click', () => {
    isPollMode = false;
    btnTypePost.className = 'py-2 text-xs font-black rounded-xl bg-[rgb(232,125,53)] text-white shadow-sm transition';
    btnTypePoll.className = 'py-2 text-xs font-black rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 transition';
    pollWrapper?.classList.add('hidden');
  });

  btnTypePoll?.addEventListener('click', () => {
    isPollMode = true;
    btnTypePoll.className = 'py-2 text-xs font-black rounded-xl bg-[rgb(232,125,53)] text-white shadow-sm transition';
    btnTypePost.className = 'py-2 text-xs font-black rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 transition';
    pollWrapper?.classList.remove('hidden');
  });

  addOptionBtn?.addEventListener('click', () => {
    const inputs = optionsContainer?.querySelectorAll('.poll-option-input');
    if (inputs && inputs.length >= 6) {
      showToast('Máximo 6 opciones', 'info');
      return;
    }
    const count = (inputs?.length || 0) + 1;
    const newInput = document.createElement('input');
    newInput.type = 'text';
    newInput.className = 'poll-option-input w-full px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 dark:text-white rounded-xl focus:outline-none';
    newInput.placeholder = `Opción ${count}`;
    optionsContainer?.appendChild(newInput);
  });
}

function setupCategorySelectHandler() {
  const select = document.getElementById('categoriaPostSelect');
  const customWrapper = document.getElementById('customCategoryWrapper');

  select?.addEventListener('change', () => {
    if (select.value === 'CUSTOM') {
      customWrapper?.classList.remove('hidden');
    } else {
      customWrapper?.classList.add('hidden');
    }
  });
}

let selectedPostImages = [];

function setupPostImageHandlers() {
  const cameraInput = document.getElementById('postCameraInput');
  const fileInput = document.getElementById('postFileInput');
  const previewContainer = document.getElementById('postPreviewContainer');
  const thumbnailsGrid = document.getElementById('postThumbnailsGrid');
  const badgeCount = document.getElementById('postImageCountBadge');
  const imageValueInput = document.getElementById('postImage');

  function renderThumbnails() {
    if (!thumbnailsGrid) return;
    thumbnailsGrid.innerHTML = '';

    if (selectedPostImages.length === 0) {
      previewContainer?.classList.add('hidden');
      badgeCount?.classList.add('hidden');
      if (imageValueInput) imageValueInput.value = '';
      return;
    }

    previewContainer?.classList.remove('hidden');
    if (badgeCount) {
      badgeCount.textContent = `${selectedPostImages.length} foto${selectedPostImages.length > 1 ? 's' : ''}`;
      badgeCount.classList.remove('hidden');
    }
    if (imageValueInput) {
      imageValueInput.value = selectedPostImages[0] || '';
    }

    selectedPostImages.forEach((imgSrc, idx) => {
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
        selectedPostImages.splice(delIdx, 1);
        renderThumbnails();
      });
    });

    thumbnailsGrid.querySelectorAll('img[data-thumb-idx]').forEach(img => {
      img.addEventListener('click', (e) => {
        const thumbIdx = parseInt(e.currentTarget.dataset.thumbIdx, 10);
        openZoomViewer(selectedPostImages, thumbIdx);
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

  const handleFiles = async (files) => {
    if (!files || files.length === 0) return;
    for (let i = 0; i < files.length; i++) {
      const b64 = await processFile(files[i]);
      if (b64) {
        selectedPostImages.push(b64);
      }
    }
    renderThumbnails();
  };

  cameraInput?.addEventListener('change', async (e) => {
    if (e.target.files && e.target.files.length > 0) {
      await handleFiles(e.target.files);
      cameraInput.value = '';
    }
  });

  fileInput?.addEventListener('change', async (e) => {
    if (e.target.files && e.target.files.length > 0) {
      await handleFiles(e.target.files);
      fileInput.value = '';
    }
  });
}

function setupEventListeners() {
  document.getElementById('btnThemeToggle')?.addEventListener('click', toggleTheme);

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
      closeModal(e.target.id);
    }
  });

  const btnToggle = document.getElementById('btnTogglePostForm');
  const formContainer = document.getElementById('postFormContainer');
  const btnClose = document.getElementById('btnClosePostForm');
  const btnCancel = document.getElementById('btnCancelPostForm');

  const toggleForm = () => formContainer?.classList.toggle('hidden');
  btnToggle?.addEventListener('click', toggleForm);
  btnClose?.addEventListener('click', toggleForm);
  btnCancel?.addEventListener('click', toggleForm);

  document.getElementById('formComentario')?.addEventListener('submit', handlePostSubmit);

  document.getElementById('btnCloseZoomViewer')?.addEventListener('click', closeZoomViewer);
  document.getElementById('btnToggleZoomScale')?.addEventListener('click', toggleZoomScale);
  document.getElementById('zoomImageWrapper')?.addEventListener('click', (e) => {
    if (e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
      toggleZoomScale();
    }
  });

  document.getElementById('btnZoomPrev')?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (currentZoomImages.length > 1) {
      currentZoomIndex = (currentZoomIndex - 1 + currentZoomImages.length) % currentZoomImages.length;
      isZoomedIn = false;
      updateZoomViewerUI();
    }
  });

  document.getElementById('btnZoomNext')?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (currentZoomImages.length > 1) {
      currentZoomIndex = (currentZoomIndex + 1) % currentZoomImages.length;
      isZoomedIn = false;
      updateZoomViewerUI();
    }
  });

  document.getElementById('modalImageZoom')?.addEventListener('click', (e) => {
    if (e.target.id === 'modalImageZoom') {
      closeZoomViewer();
    }
  });

  document.getElementById('filterTop')?.addEventListener('click', () => {
    currentSort = 'top';
    updateSortUI();
    renderizarComentarios();
  });

  document.getElementById('filterNew')?.addEventListener('click', () => {
    currentSort = 'new';
    updateSortUI();
    renderizarComentarios();
  });
}

function updateSortUI() {
  const btnTop = document.getElementById('filterTop');
  const btnNew = document.getElementById('filterNew');

  if (currentSort === 'top') {
    btnTop.className = 'px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[rgb(232,125,53)] font-extrabold flex items-center space-x-1 text-[11px] sm:text-xs';
    btnNew.className = 'px-2.5 py-1 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center space-x-1 text-[11px] sm:text-xs';
  } else {
    btnNew.className = 'px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[rgb(232,125,53)] font-extrabold flex items-center space-x-1 text-[11px] sm:text-xs';
    btnTop.className = 'px-2.5 py-1 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center space-x-1 text-[11px] sm:text-xs';
  }
}

async function handlePostSubmit(e) {
  e.preventDefault();

  const selectVal = document.getElementById('categoriaPostSelect').value;
  const customVal = document.getElementById('categoriaPostCustom')?.value.trim();
  
  let category = selectVal;
  if (selectVal === 'CUSTOM') {
    if (!customVal) {
      showToast('Ingresa el nombre de la categoría', 'error');
      return;
    }
    category = customVal.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
  }

  const titulo = document.getElementById('tituloPost').value.trim();
  const texto = document.getElementById('textoComentario').value.trim();
  const image = document.getElementById('postImage').value;
  
  const user = StorageManager.getCurrentUser();
  const isAdmin = StorageManager.isAdminLoggedIn();
  
  let author = 'u/Anónimo';
  if (isAdmin) {
    author = 'u/Administrador';
  } else if (user) {
    author = 'u/' + (user.username || user.nombre);
  }

  let pollOptions = [];
  if (isPollMode) {
    const inputs = document.querySelectorAll('.poll-option-input');
    inputs.forEach((input, index) => {
      const val = input.value.trim();
      if (val) {
        pollOptions.push({
          id: 'opt-' + index + '-' + Date.now(),
          text: val,
          votes: 0
        });
      }
    });

    if (pollOptions.length < 2) {
      showToast('Añade al menos 2 opciones', 'error');
      return;
    }
  }

  if (!titulo) {
    showToast('Ingresa un título o pregunta', 'error');
    return;
  }

  const postImgsToSend = selectedPostImages.length > 0 ? selectedPostImages : (image ? [image] : []);

  await StorageManager.addComment({
    category: category,
    title: titulo,
    author: author,
    text: texto,
    image: postImgsToSend[0] || null,
    images: postImgsToSend,
    isAdmin: isAdmin,
    isPoll: isPollMode,
    pollOptions: pollOptions
  });

  selectedPostImages = [];
  document.getElementById('formComentario').reset();
  document.getElementById('customCategoryWrapper')?.classList.add('hidden');
  document.getElementById('postImage').value = '';
  const postThumbs = document.getElementById('postThumbnailsGrid');
  if (postThumbs) postThumbs.innerHTML = '';
  document.getElementById('postPreviewContainer')?.classList.add('hidden');
  document.getElementById('postImageCountBadge')?.classList.add('hidden');
  document.getElementById('postFormContainer')?.classList.add('hidden');
  
  document.getElementById('btnTypePost')?.click();

  renderizarComentarios();
  showToast(isPollMode ? '¡Encuesta guardada en MySQL!' : '¡Publicación guardada en MySQL!', 'success');
}

function renderCategoryFilterBar(posts) {
  const bar = document.getElementById('categoryFilterBar');
  if (!bar) return;

  const defaultCats = ['MODA', 'TECNOLOGIA', 'UTILES', 'AVISOS', 'GENERAL'];
  const postCats = posts.map(p => p.category || 'GENERAL');
  const allCategories = Array.from(new Set([...defaultCats, ...postCats]));

  let html = `
    <button data-cat="ALL" class="cat-filter-btn px-3.5 py-2 rounded-xl ${currentCategory === 'ALL' ? 'bg-[rgb(232,125,53)] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-[rgb(232,125,53)]'} shrink-0 text-xs sm:text-sm font-black min-h-[38px] transition">
      Todos
    </button>
  `;

  allCategories.forEach(cat => {
    const active = currentCategory === cat;
    html += `
      <button data-cat="${cat}" class="cat-filter-btn px-3.5 py-2 rounded-xl ${active ? 'bg-[rgb(232,125,53)] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-[rgb(232,125,53)]'} shrink-0 text-xs sm:text-sm font-black min-h-[38px] transition">
        r/${cat}
      </button>
    `;
  });

  bar.innerHTML = html;

  bar.querySelectorAll('.cat-filter-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      currentCategory = e.currentTarget.dataset.cat;
      renderizarComentarios();
    });
  });
}

function renderizarComentarios() {
  const container = document.getElementById('listaComentarios');
  if (!container) return;

  let posts = StorageManager.getComments();
  const isAdmin = StorageManager.isAdminLoggedIn();

  renderCategoryFilterBar(posts);

  if (currentCategory !== 'ALL') {
    posts = posts.filter(p => (p.category || 'GENERAL') === currentCategory);
  }

  posts.sort((a, b) => {
    if (a.isPinned && !b.isPinned) return -1;
    if (!a.isPinned && b.isPinned) return 1;

    if (currentSort === 'top') {
      return (b.score || 0) - (a.score || 0);
    } else {
      return new Date(b.createdAt) - new Date(a.createdAt);
    }
  });

  if (posts.length === 0) {
    container.innerHTML = `
      <div class="text-center py-12 bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 p-6">
        <i class="fa-regular fa-comment-dots text-3xl text-slate-300 dark:text-slate-700 mb-2"></i>
        <h4 class="font-bold text-slate-700 dark:text-slate-300 text-sm">No hay publicaciones aún</h4>
        <p class="text-slate-400 text-xs">Sé la primera persona en publicar en esta categoría.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = posts.map(post => createPostCardHTML(post, isAdmin)).join('');
  attachPostEvents();
}

function createPostCardHTML(post, isAdmin) {
  const catName = post.category || 'GENERAL';
  const replies = post.replies || [];
  const userVote = post.userVote || 0;
  
  let dateStr = 'Reciente';
  if (post.createdAt) {
    try {
      const d = new Date(post.createdAt);
      dateStr = d.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
    } catch {
      dateStr = 'Reciente';
    }
  }

  let pollHTML = '';
  if (post.isPoll && post.pollOptions && post.pollOptions.length > 0) {
    const totalVotes = post.pollOptions.reduce((acc, opt) => acc + (opt.votes || 0), 0);

    pollHTML = `
      <div class="mt-3.5 p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700/60 space-y-2.5">
        <div class="flex items-center justify-between text-xs sm:text-sm font-black text-slate-500 uppercase tracking-wider pb-1.5 border-b border-slate-200/50 dark:border-slate-700/50">
          <span><i class="fa-solid fa-square-poll-vertical text-[rgb(232,125,53)] mr-1.5 text-sm"></i> Encuesta comunitaria</span>
          <span>${totalVotes} votos</span>
        </div>

        <div class="space-y-2 pt-1">
          ${post.pollOptions.map(opt => {
            const percent = totalVotes > 0 ? Math.round(((opt.votes || 0) / totalVotes) * 100) : 0;
            return `
              <div data-action="vote-poll" data-postid="${post.id}" data-optid="${opt.id}" class="poll-option-row relative overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-3 sm:p-3.5 flex items-center justify-between cursor-pointer hover:border-[rgb(232,125,53)] transition min-h-[48px]">
                <div class="absolute top-0 bottom-0 left-0 bg-[rgb(232,125,53)]/20 transition-all duration-300 pointer-events-none" style="width: ${percent}%;"></div>

                <span class="relative z-10 text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center space-x-2.5 truncate">
                  <i class="fa-regular fa-circle text-slate-400 shrink-0 text-sm"></i>
                  <span class="truncate">${escapeHTML(opt.text)}</span>
                </span>

                <span class="relative z-10 text-xs sm:text-sm font-black text-slate-600 dark:text-slate-300 shrink-0 ml-2">
                  ${percent}% (${opt.votes || 0})
                </span>
              </div>
            `;
          }).join('')}
        </div>
      </div>
    `;
  }

  return `
    <div class="bg-white dark:bg-slate-900 border ${post.isPinned ? 'border-[rgb(232,125,53)] ring-1 ring-[rgb(232,125,53)]/30' : 'border-slate-200 dark:border-slate-800'} rounded-2xl p-3.5 sm:p-5 shadow-sm flex space-x-3 sm:space-x-4 relative">
      
      ${post.isPinned ? `
        <div class="absolute -top-2.5 left-3.5 bg-[rgb(232,125,53)] text-white text-[9px] sm:text-[10px] font-black px-2.5 py-0.5 rounded-full shadow-sm flex items-center space-x-1">
          <i class="fa-solid fa-thumbtack text-[9px]"></i>
          <span>FIJADO</span>
        </div>
      ` : ''}

      <!-- BOTONES VOTOS -->
      <div class="flex flex-col items-center justify-start space-y-1 bg-slate-50 dark:bg-slate-800/60 p-1 sm:p-1.5 rounded-xl h-fit shrink-0 mt-0.5">
        <button data-action="upvote" data-id="${post.id}" class="w-9 h-9 min-w-[36px] min-h-[36px] flex items-center justify-center text-slate-400 hover:text-[rgb(232,125,53)] rounded-lg hover:bg-slate-200/60 dark:hover:bg-slate-700/60 transition">
          <i class="fa-solid fa-circle-chevron-up text-lg sm:text-xl"></i>
        </button>
        <span class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100 py-0.5">
          ${post.score || 0}
        </span>
        <button data-action="downvote" data-id="${post.id}" class="w-9 h-9 min-w-[36px] min-h-[36px] flex items-center justify-center text-slate-400 hover:text-blue-500 rounded-lg hover:bg-slate-200/60 dark:hover:bg-slate-700/60 transition">
          <i class="fa-solid fa-circle-chevron-down text-lg sm:text-xl"></i>
        </button>
      </div>

      <!-- PUBLICACIÓN -->
      <div class="flex-1 space-y-2 min-w-0">
        
        <div class="flex items-center justify-between text-xs sm:text-sm text-slate-400">
          <div class="flex items-center space-x-1.5 truncate">
            <span class="bg-slate-100 dark:bg-slate-800 text-[rgb(232,125,53)] font-black px-2 py-0.5 rounded-md text-[10px] sm:text-xs">r/${catName}</span>
            <span class="font-extrabold text-slate-700 dark:text-slate-300 truncate">${escapeHTML(post.author || 'u/Anónimo')}</span>
            ${post.isAdmin ? '<span class="bg-rose-600 text-white text-[9px] font-black px-1.5 py-0.5 rounded">OFICIAL</span>' : ''}
            <span>•</span>
            <span class="shrink-0 text-[11px] sm:text-xs">${dateStr}</span>
          </div>

          ${isAdmin ? `
            <div class="flex items-center space-x-1 text-sm shrink-0 ml-1">
              <button data-action="pin-post" data-id="${post.id}" title="Fijar post en base de datos" class="w-8 h-8 min-w-[32px] min-h-[32px] flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 ${post.isPinned ? 'text-[rgb(232,125,53)]' : 'text-slate-400 hover:text-slate-600'}">
                <i class="fa-solid fa-thumbtack"></i>
              </button>
              <button data-action="lock-post" data-id="${post.id}" title="Bloquear comentarios en base de datos" class="w-8 h-8 min-w-[32px] min-h-[32px] flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 ${post.isLocked ? 'text-amber-500' : 'text-slate-400 hover:text-slate-600'}">
                <i class="fa-solid ${post.isLocked ? 'fa-lock' : 'fa-lock-open'}"></i>
              </button>
              <button data-action="delete-post" data-id="${post.id}" title="Eliminar de la base de datos" class="w-8 h-8 min-w-[32px] min-h-[32px] flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-rose-600">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          ` : ''}
        </div>

        <h3 class="post-title-text font-black text-slate-900 dark:text-white text-base sm:text-lg leading-snug">${escapeHTML(post.title || post.text.slice(0, 40))}</h3>
        
        ${post.text ? `<p class="text-xs sm:text-base text-slate-700 dark:text-slate-300 font-medium leading-relaxed whitespace-pre-wrap">${escapeHTML(post.text)}</p>` : ''}

        <!-- ENCUESTA -->
        ${pollHTML}

        ${(() => {
          let imgs = [];
          if (Array.isArray(post.images) && post.images.length > 0) {
            imgs = post.images.filter(Boolean);
          } else if (post.image) {
            try {
              const parsed = JSON.parse(post.image);
              imgs = Array.isArray(parsed) ? parsed.filter(Boolean) : [post.image];
            } catch {
              imgs = [post.image];
            }
          }
          if (imgs.length === 0) return '';

          const encodedImgs = escapeHTML(JSON.stringify(imgs));
          const countBadge = imgs.length > 1 ? `
            <span class="absolute top-2 right-2 bg-slate-900/80 text-white text-[11px] font-black px-2 py-0.5 rounded-lg shadow pointer-events-none flex items-center space-x-1 backdrop-blur-sm z-10">
              <i class="fa-solid fa-images text-[10px] text-[rgb(232,125,53)]"></i>
              <span>${imgs.length} fotos</span>
            </span>
          ` : '';

          return `
            <div class="relative mt-2.5 rounded-xl overflow-hidden max-h-60 sm:max-h-80 w-full bg-slate-100 dark:bg-slate-800 cursor-pointer group" data-action="expand-img" data-images="${encodedImgs}" data-src="${imgs[0]}">
              <img src="${imgs[0]}" alt="Imagen del post" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" />
              ${countBadge}
              <div class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-colors pointer-events-none"></div>
            </div>
          `;
        })()}

        <!-- RESPUESTAS -->
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs sm:text-sm">
          <button data-action="toggle-replies" data-id="${post.id}" class="flex items-center space-x-1.5 text-slate-500 hover:text-slate-800 dark:hover:text-white font-bold text-xs sm:text-sm px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 min-h-[36px] transition">
            <i class="fa-regular fa-comment text-sm"></i>
            <span>${replies.length} Comentarios</span>
          </button>

          ${post.isLocked ? `
            <span class="text-xs text-amber-500 font-bold flex items-center space-x-1">
              <i class="fa-solid fa-lock"></i>
              <span>Bloqueado</span>
            </span>
          ` : ''}
        </div>

        <div id="replies-${post.id}" class="hidden pt-2.5 space-y-2.5 border-l-2 border-slate-200 dark:border-slate-800 pl-3 sm:pl-4 mt-2">
          
          ${!post.isLocked ? `
            <form data-action="reply-form" data-id="${post.id}" class="space-y-2">
              <div class="flex gap-2">
                <input type="text" placeholder="Escribe un comentario..." required class="flex-1 px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl dark:text-white focus:outline-none min-h-[44px]" />
                <button type="submit" class="bg-[rgb(232,125,53)] hover:bg-[rgb(212,105,33)] text-white px-4 py-2.5 rounded-xl text-xs sm:text-sm font-black shadow-sm min-h-[44px] transition">
                  Responder
                </button>
              </div>
            </form>
          ` : '<p class="text-xs sm:text-sm text-slate-400 italic">Los comentarios están bloqueados por el administrador.</p>'}

          <div class="space-y-2 pt-1">
            ${replies.map(r => `
              <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl space-y-1 relative">
                <div class="flex items-center justify-between text-xs">
                  <div class="flex items-center space-x-1.5">
                    <span class="font-bold text-slate-700 dark:text-slate-300 text-xs sm:text-sm">${escapeHTML(r.author || 'u/Anónimo')}</span>
                    ${r.isAdmin ? '<span class="bg-rose-600 text-white text-[9px] font-black px-1.5 rounded">ADMIN</span>' : ''}
                  </div>
                  ${isAdmin ? `
                    <button data-action="delete-reply" data-postid="${post.id}" data-replyid="${r.id}" class="text-slate-400 hover:text-rose-600 text-sm p-1">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  ` : ''}
                </div>
                <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 font-medium leading-relaxed">${escapeHTML(r.text)}</p>
              </div>
            `).join('')}
          </div>

        </div>

      </div>

    </div>
  `;
}

function attachPostEvents() {
  document.querySelectorAll('div[data-action="expand-img"]').forEach(div => {
    div.addEventListener('click', (e) => {
      let imgs = [];
      const imagesAttr = e.currentTarget.dataset.images;
      if (imagesAttr) {
        try {
          imgs = JSON.parse(imagesAttr);
        } catch {
          imgs = [imagesAttr];
        }
      }
      if (!imgs || imgs.length === 0) {
        imgs = [e.currentTarget.dataset.src];
      }
      openZoomViewer(imgs);
    });
  });

  // Votación de encuestas
  document.querySelectorAll('[data-action="vote-poll"]').forEach(elem => {
    elem.addEventListener('click', async (e) => {
      e.stopPropagation();
      const optId = e.currentTarget.dataset.optid;
      await StorageManager.votePoll(optId);
      renderizarComentarios();
      showToast('Voto registrado en la base de datos', 'success');
    });
  });

  document.querySelectorAll('button[data-action="upvote"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      await StorageManager.voteComment(e.currentTarget.dataset.id, 1);
      renderizarComentarios();
    });
  });

  document.querySelectorAll('button[data-action="downvote"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      await StorageManager.voteComment(e.currentTarget.dataset.id, -1);
      renderizarComentarios();
    });
  });

  document.querySelectorAll('button[data-action="toggle-replies"]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.dataset.id;
      document.getElementById(`replies-${id}`)?.classList.toggle('hidden');
    });
  });

  document.querySelectorAll('button[data-action="pin-post"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      await StorageManager.togglePinPost(e.currentTarget.dataset.id);
      renderizarComentarios();
      showToast('Publicación actualizada en base de datos', 'info');
    });
  });

  document.querySelectorAll('button[data-action="lock-post"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      await StorageManager.toggleLockPost(e.currentTarget.dataset.id);
      renderizarComentarios();
      showToast('Estado de comentarios actualizado', 'info');
    });
  });

  document.querySelectorAll('button[data-action="delete-reply"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const postId = e.currentTarget.dataset.postid;
      const replyId = e.currentTarget.dataset.replyid;
      if (confirm('¿Eliminar respuesta de la base de datos?')) {
        await StorageManager.deleteReply(postId, replyId);
        renderizarComentarios();
        showToast('Respuesta eliminada de la base de datos', 'info');
      }
    });
  });

  document.querySelectorAll('form[data-action="reply-form"]').forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const postId = e.currentTarget.dataset.id;
      const input = e.currentTarget.querySelector('input[type="text"]');
      const text = input ? input.value.trim() : '';

      if (!text) return;

      const user = StorageManager.getCurrentUser();
      const isAdmin = StorageManager.isAdminLoggedIn();
      let author = 'u/Anónimo';
      if (isAdmin) {
        author = 'u/Administrador';
      } else if (user) {
        author = 'u/' + (user.username || user.nombre);
      }

      await StorageManager.addReply(postId, {
        author: author,
        text: text,
        isAdmin: isAdmin
      });

      renderizarComentarios();
      showToast('Respuesta guardada en MySQL', 'success');
    });
  });

  document.querySelectorAll('button[data-action="delete-post"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      if (confirm('¿Eliminar publicación de la base de datos MySQL?')) {
        await StorageManager.deleteComment(e.currentTarget.dataset.id);
        renderizarComentarios();
        showToast('Publicación eliminada de MySQL', 'info');
      }
    });
  });
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    const firstInput = modal.querySelector('input:not([type="hidden"])');
    if (firstInput) setTimeout(() => firstInput.focus(), 50);
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
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

function openImageExpandModal(src) {
  openZoomViewer([src]);
}

function closeImageExpandModal() {
  closeZoomViewer();
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

  toast.className = `pointer-events-auto flex items-center space-x-2 px-3 py-1.5 rounded-xl shadow-lg ${bgColors[type] || bgColors.info} text-xs font-bold max-w-xs transition-all duration-200`;
  toast.innerHTML = `<span>${escapeHTML(message)}</span>`;

  container.appendChild(toast);
  setTimeout(() => toast.remove(), 2500);
}

function escapeHTML(str) {
  if (!str) return '';
  return String(str).replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag));
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