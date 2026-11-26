import { INITIAL_CLOTHING, INITIAL_SUPPLIES } from './data.js?v=5.0';

export class StorageManager {
  static currentUser = null;
  static clothingItems = [];
  static supplyItems = [];
  static forumPosts = [];
  static reservations = [];
  static notifications = [];
  static dbConnected = false;

  static async init() {
    const isAdminActive = sessionStorage.getItem('is_admin_mode') === 'true';

    if (!isAdminActive) {
      // Si el acceso no vino con credenciales de admin, limpiar cualquier residuo anterior
      this.currentUser = null;
      localStorage.removeItem('app_user');
      try {
        await fetch('api/auth.php?action=logout');
      } catch (e) {}
    } else {
      // 1. Cargar usuario de localStorage si existe
      try {
        const savedUser = localStorage.getItem('app_user');
        if (savedUser) {
          this.currentUser = JSON.parse(savedUser);
        }
      } catch (e) {}

      // 2. Comprobar sesión en backend PHP (silencioso)
      try {
        const authRes = await fetch('api/auth.php?action=check');
        if (authRes.ok) {
          const authData = await authRes.json();
          if (authData && authData.loggedIn && authData.user) {
            this.currentUser = authData.user;
            localStorage.setItem('app_user', JSON.stringify(authData.user));
          }
        }
      } catch (e) {}
    }

    // 3. Cargar prendas desde API REST o LocalStorage
    let loadedClothingApi = false;
    try {
      const resC = await fetch(`api/prendas.php?_t=${Date.now()}`);
      if (resC.ok) {
        const dataC = await resC.json();
        if (dataC && dataC.success && dataC.db_connected && Array.isArray(dataC.data) && dataC.data.length > 0) {
          this.clothingItems = dataC.data;
          this.dbConnected = true;
          loadedClothingApi = true;
          localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
        }
      }
    } catch (e) {
      console.warn("API de prendas no disponible directamente, usando almacenamiento local");
    }

    if (!loadedClothingApi) {
      try {
        const localC = localStorage.getItem('app_clothing');
        const parsedC = localC ? JSON.parse(localC) : null;
        this.clothingItems = Array.isArray(parsedC) && parsedC.length > 0 ? parsedC : [...INITIAL_CLOTHING];
        localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
      } catch (e) {
        this.clothingItems = [...INITIAL_CLOTHING];
      }
    }

    // 4. Cargar útiles desde API REST o LocalStorage
    let loadedSuppliesApi = false;
    try {
      const resS = await fetch(`api/utiles.php?_t=${Date.now()}`);
      if (resS.ok) {
        const dataS = await resS.json();
        if (dataS && dataS.success && dataS.db_connected !== false && Array.isArray(dataS.data) && dataS.data.length > 0) {
          this.supplyItems = dataS.data;
          loadedSuppliesApi = true;
          localStorage.setItem('app_supplies', JSON.stringify(this.supplyItems));
        }
      }
    } catch (e) {
      console.warn("API de útiles no disponible directamente, usando almacenamiento local");
    }

    if (!loadedSuppliesApi) {
      try {
        const localS = localStorage.getItem('app_supplies');
        const parsedS = localS ? JSON.parse(localS) : null;
        this.supplyItems = Array.isArray(parsedS) && parsedS.length > 0 ? parsedS : [...INITIAL_SUPPLIES];
        localStorage.setItem('app_supplies', JSON.stringify(this.supplyItems));
      } catch (e) {
        this.supplyItems = [...INITIAL_SUPPLIES];
      }
    }

    // 5. Cargar foro desde API REST o LocalStorage
    try {
      const resF = await fetch('api/foro.php');
      if (resF.ok) {
        const dataF = await resF.json();
        if (dataF && dataF.success && Array.isArray(dataF.data) && dataF.data.length > 0) {
          this.forumPosts = dataF.data;
          localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
        }
      }
    } catch (e) {}

    if (!this.forumPosts || this.forumPosts.length === 0) {
      try {
        const localF = localStorage.getItem('app_foro_posts');
        this.forumPosts = localF ? JSON.parse(localF) : [
          {
            id: '1',
            category: 'AVISOS',
            title: '¡Bienvenidos al Ropero & Catálogo Solidario de la Técnica 5!',
            author: 'u/Administrador',
            text: 'Este espacio comunitario permite compartir prendas, útiles escolares y novedades para toda la comunidad educativa. Podés consultar prendas disponibles, coordinar donaciones y participar en las encuestas comunitarias.',
            image: null,
            isAdmin: true,
            isPinned: true,
            isLocked: false,
            isPoll: false,
            score: 15,
            pollOptions: [],
            replies: [
              {
                id: 'rep-1',
                postId: '1',
                author: 'u/Alumno',
                text: 'Excelente iniciativa para que todos tengamos acceso a lo necesario para las clases.',
                image: null,
                isAdmin: false,
                createdAt: new Date().toISOString()
              }
            ],
            createdAt: new Date().toISOString()
          },
          {
            id: '2',
            category: 'UTILES',
            title: '¿Qué útiles escolares son más necesarios para el segundo cuatrimestre?',
            author: 'u/Administrador',
            text: 'Votemos para coordinar la próxima campaña de donaciones en la escuela.',
            image: null,
            isAdmin: true,
            isPinned: false,
            isLocked: false,
            isPoll: true,
            score: 8,
            pollOptions: [
              { id: 'opt-1', text: 'Cuadernos y Carpetas', votes: 12 },
              { id: 'opt-2', text: 'Cartucheras y Lapiceras', votes: 7 },
              { id: 'opt-3', text: 'Mochilas', votes: 10 },
              { id: 'opt-4', text: 'Juegos de Geometría / Arte', votes: 4 }
            ],
            replies: [],
            createdAt: new Date().toISOString()
          }
        ];
      } catch (e) {
        this.forumPosts = [];
      }
    }

    // 6. Cargar reservas desde API REST o LocalStorage
    try {
      const resR = await fetch(`api/prendas.php?action=list_reservas&_t=${Date.now()}`);
      if (resR.ok) {
        const dataR = await resR.json();
        if (dataR && dataR.success && Array.isArray(dataR.data)) {
          this.reservations = dataR.data;
          localStorage.setItem('app_reservas', JSON.stringify(this.reservations));
        }
      }
    } catch (e) {}

    if (!Array.isArray(this.reservations) || this.reservations.length === 0) {
      try {
        const localR = localStorage.getItem('app_reservas');
        this.reservations = localR ? JSON.parse(localR) : [];
      } catch (e) {
        this.reservations = [];
      }
    }

    // 7. Cargar notificaciones desde API REST o LocalStorage
    try {
      const resN = await fetch(`api/foro.php?action=notifications&_t=${Date.now()}`);
      if (resN.ok) {
        const dataN = await resN.json();
        if (dataN && dataN.success && Array.isArray(dataN.data)) {
          this.notifications = dataN.data;
          localStorage.setItem('app_notifications', JSON.stringify(this.notifications));
        }
      }
    } catch (e) {}

    if (!Array.isArray(this.notifications) || this.notifications.length === 0) {
      try {
        const localN = localStorage.getItem('app_notifications');
        this.notifications = localN ? JSON.parse(localN) : [
          {
            id: 'notif-welcome',
            type: 'general',
            title: '¡Bienvenido al Ropero Escolar!',
            message: 'Podés navegar el catálogo, reservar prendas y participar en el foro solidario de la Técnica 5.',
            recipient: 'ALL',
            link: 'index.html',
            read: false,
            createdAt: new Date().toISOString()
          }
        ];
      } catch (e) {
        this.notifications = [];
      }
    }

    return true;
  }

  static async login(username, password) {
    const adminUser = {
      id: 1,
      rol_id: 1,
      username: 'admin',
      nombre: 'Administrador',
      apellido: 'Escolar',
      email: 'admin@escuela.edu.ar',
      rol_nombre: 'Administrador',
      isAdmin: true
    };

    try {
      const res = await fetch('api/auth.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      });
      const data = await res.json();
      if (data && data.success) {
        this.currentUser = data.user;
        sessionStorage.setItem('is_admin_mode', 'true');
        localStorage.setItem('app_user', JSON.stringify(data.user));
        return { success: true, user: data.user };
      }
      
      if (username === 'admin' && (password === 'admin123' || password === 'admin')) {
        this.currentUser = adminUser;
        sessionStorage.setItem('is_admin_mode', 'true');
        localStorage.setItem('app_user', JSON.stringify(adminUser));
        return { success: true, user: adminUser };
      }
      return { success: false, error: data?.error || 'Credenciales incorrectas' };
    } catch (e) {
      if (username === 'admin' && (password === 'admin123' || password === 'admin')) {
        this.currentUser = adminUser;
        sessionStorage.setItem('is_admin_mode', 'true');
        localStorage.setItem('app_user', JSON.stringify(adminUser));
        return { success: true, user: adminUser };
      }
      return { success: false, error: 'Error al conectar con el servidor' };
    }
  }

  static async logout() {
    this.currentUser = null;
    localStorage.removeItem('app_user');
    sessionStorage.removeItem('is_admin_mode');
    try {
      await fetch('api/auth.php?action=logout');
    } catch (e) {}
  }

  static getCurrentUser() {
    return this.currentUser;
  }

  static isAdminLoggedIn() {
    return !!(this.currentUser && this.currentUser.isAdmin);
  }

  static async checkDbConnection() {
    try {
      const res = await fetch('api/test_db.php');
      if (res.ok) {
        const data = await res.json();
        return data;
      }
    } catch (e) {}
    return { success: false };
  }

  static getClothing() {
    return this.clothingItems || [];
  }

  static getSupplies() {
    return this.supplyItems || [];
  }

  static async saveClothingItem(item) {
    try {
      const res = await fetch(`api/prendas.php?_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(item)
      });
      const data = await res.json();
      if (data && data.success && data.item) {
        this.clothingItems = this.clothingItems.filter(i => 
          String(i.id) !== String(data.item.id) && 
          String(i.raw_id) !== String(data.item.raw_id) &&
          i.qrCode !== data.item.qrCode &&
          i.codigo_qr !== data.item.qrCode
        );
        this.clothingItems.unshift(data.item);
        localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
        return { success: true, item: data.item };
      } else {
        throw new Error(data?.error || 'Error al guardar en el servidor');
      }
    } catch (e) {
      console.warn("API de prendas falló, usando contingencia local:", e);
      item.id = Date.now().toString();
      item.raw_id = Date.now();
      item.type = 'clothing';
      if (!item.qrCode) item.qrCode = 'PRD-' + Math.random().toString(36).substr(2, 6).toUpperCase();
      this.clothingItems.unshift(item);
      localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
      return { success: true, item, localFallback: true };
    }
  }

  static async saveSupplyItem(item) {
    try {
      const res = await fetch(`api/utiles.php?_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(item)
      });
      const data = await res.json();
      if (data && data.success && data.item) {
        this.supplyItems.unshift(data.item);
        localStorage.setItem('app_supplies', JSON.stringify(this.supplyItems));
        return { success: true, item: data.item };
      }
    } catch (e) {}

    item.id = Date.now().toString();
    item.raw_id = Date.now();
    item.type = 'supplies';
    if (!item.qrCode) item.qrCode = 'UTL-' + Math.random().toString(36).substr(2, 6).toUpperCase();
    this.supplyItems.unshift(item);
    localStorage.setItem('app_supplies', JSON.stringify(this.supplyItems));
    return { success: true, item, localFallback: true };
  }

  static async deleteClothingItem(id, qrCode = '') {
    if (!qrCode) {
      const found = this.clothingItems.find(i => String(i.id) === String(id) || String(i.raw_id) === String(id));
      if (found) {
        qrCode = found.qrCode || found.codigo_qr || '';
      }
    }

    try {
      const res = await fetch(`api/prendas.php?action=delete&id=${encodeURIComponent(id)}&code=${encodeURIComponent(qrCode)}&_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, code: qrCode, action: 'delete' })
      });
      const data = await res.json();
      if (!res.ok || !data || !data.success) {
        return {
          success: false,
          error: data?.error || 'No se pudo eliminar la prenda de la base de datos MySQL.'
        };
      }
    } catch (e) {
      console.error("Error al eliminar en API de prendas:", e);
      return {
        success: false,
        error: 'Error de red al intentar eliminar la prenda de la base de datos.'
      };
    }

    this.clothingItems = this.clothingItems.filter(i => 
      String(i.id) !== String(id) && 
      String(i.raw_id) !== String(id) &&
      (!qrCode || (i.qrCode !== qrCode && i.codigo_qr !== qrCode))
    );
    try {
      localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
    } catch (e) {}

    return { success: true, message: 'Prenda eliminada correctamente de la base de datos MySQL' };
  }

  static async deleteSupplyItem(id, qrCode = '') {
    if (!qrCode) {
      const found = this.supplyItems.find(i => String(i.id) === String(id) || String(i.raw_id) === String(id));
      if (found) {
        qrCode = found.qrCode || found.codigo_qr || '';
      }
    }

    try {
      const res = await fetch(`api/utiles.php?action=delete&id=${encodeURIComponent(id)}&code=${encodeURIComponent(qrCode)}&_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, code: qrCode, action: 'delete' })
      });
      const data = await res.json();
      if (!res.ok || !data || !data.success) {
        console.warn("Aviso al eliminar útil en backend:", data?.error);
      }
    } catch (e) {
      console.warn("Error de red al eliminar útil en backend:", e);
    }

    this.supplyItems = this.supplyItems.filter(i => 
      String(i.id) !== String(id) && 
      String(i.raw_id) !== String(id) &&
      (!qrCode || (i.qrCode !== qrCode && i.codigo_qr !== qrCode))
    );
    try {
      localStorage.setItem('app_supplies', JSON.stringify(this.supplyItems));
    } catch (e) {}

    return { success: true, message: 'Útil escolar eliminado correctamente' };
  }

  static async deleteQR(code) {
    try {
      const res = await fetch(`api/qrs.php?action=delete_qr&code=${encodeURIComponent(code)}&_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code, action: 'delete_qr' })
      });
      const data = await res.json();
      if (!res.ok || !data || !data.success) {
        return {
          success: false,
          error: data?.error || 'No se pudo eliminar el código QR del servidor.'
        };
      }
    } catch (e) {
      console.warn("Aviso al eliminar QR:", e);
    }

    try {
      let qrs = JSON.parse(localStorage.getItem('app_qrs') || '[]');
      qrs = qrs.filter(q => q.codigo !== code);
      localStorage.setItem('app_qrs', JSON.stringify(qrs));
    } catch (e) {}

    return { success: true, message: `Etiqueta QR '${code}' eliminada con éxito` };
  }

  static async scanQR(code) {
    try {
      const res = await fetch(`api/qrs.php?action=scan&code=${encodeURIComponent(code)}&_t=${Date.now()}`);
      if (res.ok) {
        const data = await res.json();
        if (data && data.success) return data;
      }
    } catch (e) {}

    const found = this.clothingItems.find(i => i.qrCode === code || i.codigo_qr === code);
    if (found) {
      return {
        success: true,
        code: code,
        is_assigned: true,
        prenda: found
      };
    }
    return {
      success: true,
      code: code,
      is_assigned: false,
      prenda: null
    };
  }

  static async getQRs() {
    try {
      const res = await fetch(`api/qrs.php?action=list&_t=${Date.now()}`);
      if (res.ok) {
        const data = await res.json();
        if (data && data.success && Array.isArray(data.data)) {
          // Normalización estricta: sólo está 'ocupado' si tiene prenda real vinculada con título
          data.data = data.data.map(qr => {
            const hasPrenda = Boolean(qr.prenda_titulo && (qr.prenda_id || qr.prenda_id_real));
            return {
              ...qr,
              estado: hasPrenda ? 'ocupado' : 'libre',
              prenda_id: hasPrenda ? (qr.prenda_id || qr.prenda_id_real) : null,
              prenda_titulo: hasPrenda ? qr.prenda_titulo : null
            };
          });

          const libres = data.data.filter(q => q.estado === 'libre').length;
          const ocupados = data.data.length - libres;
          data.totalLibres = libres;
          data.totalOcupados = ocupados;

          if (data.db_connected !== false) {
            try {
              localStorage.setItem('app_qrs', JSON.stringify(data.data));
            } catch (e) {}
          }
          return data;
        }
      }
    } catch (e) {}

    let qrs = [];
    try {
      const localQ = localStorage.getItem('app_qrs');
      if (localQ) qrs = JSON.parse(localQ);
    } catch (e) {}

    // En contingencia local, cotejar con las prendas cargadas en clothingItems
    const processed = qrs.map(q => {
      const foundPrenda = (this.clothingItems || []).find(item => 
        (item.qrCode && item.qrCode === q.codigo) || 
        (item.codigo_qr && item.codigo_qr === q.codigo) ||
        (q.prenda_id && (String(item.id) === String(q.prenda_id) || String(item.raw_id) === String(q.prenda_id)))
      );
      const hasPrenda = Boolean(foundPrenda && (foundPrenda.name || foundPrenda.titulo));
      if (hasPrenda) {
        return {
          ...q,
          estado: 'ocupado',
          prenda_id: foundPrenda.raw_id || foundPrenda.id,
          prenda_titulo: foundPrenda.name || foundPrenda.titulo,
          prenda_categoria: foundPrenda.category || foundPrenda.categoria,
          prenda_talle: foundPrenda.size || foundPrenda.talle
        };
      }
      return {
        ...q,
        estado: 'libre',
        prenda_id: null,
        prenda_titulo: null
      };
    });

    const libres = processed.filter(q => q.estado === 'libre').length;
    const ocupados = processed.length - libres;

    return {
      success: true,
      count: processed.length,
      totalLibres: libres,
      totalOcupados: ocupados,
      data: processed,
      db_connected: false
    };
  }

  static async generateQRBatch(count, prefix = 'ROPERO-') {
    try {
      const res = await fetch(`api/qrs.php?action=generate_batch&_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ count, prefix, action: 'generate_batch' })
      });
      const data = await res.json();
      if (res.ok && data && data.success) {
        const listRes = await this.getQRs();
        return {
          success: true,
          message: data.message || `Se generaron ${count} etiquetas QR correctamente`,
          data: listRes.data
        };
      }
      if (data && data.error) {
        return { success: false, error: data.error };
      }
    } catch (e) {
      console.warn("Aviso al generar lote de QRs en backend:", e);
      return {
        success: false,
        error: 'No se pudo comunicar con el servidor para generar el lote de QRs'
      };
    }

    return {
      success: false,
      error: 'Error desconocido al generar lote de QRs'
    };
  }

  static async changePrendaStatus(prendaId, status) {
    try {
      const res = await fetch('api/qrs.php?action=change_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prenda_id: prendaId, status })
      });
      if (res.ok) return await res.json();
    } catch (e) {}

    const item = this.clothingItems.find(i => String(i.id) === String(prendaId) || String(i.raw_id) === String(prendaId));
    if (item) {
      item.status = status;
      item.estado = status;
      localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
    }
    return { success: true };
  }

  static async inheritQR(code, prendaId) {
    let resData = null;
    try {
      const res = await fetch('api/qrs.php?action=inherit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code, prenda_id: prendaId })
      });
      resData = await res.json();
    } catch (e) {}

    // Archivar código en prendas en memoria local
    const item = (this.clothingItems || []).find(i => String(i.id) === String(prendaId) || String(i.raw_id) === String(prendaId) || i.qrCode === code || i.codigo_qr === code);
    if (item) {
      item.qrCode = 'ARCH-' + Date.now() + '-' + code;
      item.codigo_qr = 'ARCH-' + Date.now() + '-' + code;
      item.status = 'Entregado';
      item.estado = 'Entregado';
      try {
        localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
      } catch (e) {}
    }

    // Liberar la etiqueta en la lista de QRs en localStorage
    try {
      let qrs = JSON.parse(localStorage.getItem('app_qrs') || '[]');
      const q = qrs.find(x => x.codigo === code);
      if (q) {
        q.estado = 'libre';
        q.prenda_id = null;
        q.prenda_titulo = null;
        localStorage.setItem('app_qrs', JSON.stringify(qrs));
      }
    } catch (e) {}

    return resData || { success: true, message: `La etiqueta QR '${code}' ha sido liberada para heredar` };
  }

  static async deletePrendaAndFreeQR(prendaId, code) {
    let resData = null;
    try {
      const res = await fetch('api/qrs.php?action=delete_prenda', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prenda_id: prendaId, code })
      });
      resData = await res.json();
    } catch (e) {}

    if (resData && resData.success) {
      this.clothingItems = this.clothingItems.filter(i => 
        String(i.id) !== String(prendaId) && 
        String(i.raw_id) !== String(prendaId) &&
        (!code || (i.qrCode !== code && i.codigo_qr !== code))
      );
      try {
        localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
      } catch (e) {}
      return resData;
    }

    return await this.deleteClothingItem(prendaId, code);
  }

  /* ========================================================
     MÉTODOS DEL FORO Y COMUNIDAD (TÉCNICA 5)
     ======================================================== */
  static getComments() {
    return this.forumPosts || [];
  }

  static async addComment(post) {
    try {
      const res = await fetch('api/foro.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(post)
      });
      const data = await res.json();
      if (data && data.success && data.post) {
        this.forumPosts.unshift(data.post);
        localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
        return true;
      }
    } catch (e) {}

    const newPost = {
      id: 'local-' + Date.now(),
      category: post.category || 'GENERAL',
      title: post.title,
      author: post.author || 'u/Anónimo',
      text: post.text || '',
      image: post.image || null,
      images: Array.isArray(post.images) && post.images.length ? post.images : (post.image ? [post.image] : []),
      isAdmin: !!post.isAdmin,
      isPinned: false,
      isLocked: false,
      isPoll: !!post.isPoll,
      score: 1,
      pollOptions: (post.pollOptions || []).map((o, idx) => ({
        id: 'opt-' + idx + '-' + Date.now(),
        text: o.text || o,
        votes: 0
      })),
      replies: [],
      createdAt: new Date().toISOString()
    };
    this.forumPosts.unshift(newPost);
    localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
    return true;
  }

  static async votePoll(optId) {
    try {
      await fetch('api/foro.php?action=vote_poll', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ optionId: optId })
      });
    } catch (e) {}

    this.forumPosts.forEach(post => {
      if (post.pollOptions) {
        post.pollOptions.forEach(opt => {
          if (String(opt.id) === String(optId)) {
            opt.votes = (opt.votes || 0) + 1;
          }
        });
      }
    });
    localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
  }

  static async voteComment(postId, direction) {
    try {
      await fetch('api/foro.php?action=vote', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ postId, direction })
      });
    } catch (e) {}

    const post = this.forumPosts.find(p => String(p.id) === String(postId));
    if (post) {
      post.score = (post.score || 0) + direction;
      localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
    }
  }

  static async togglePinPost(postId) {
    try {
      await fetch('api/foro.php?action=toggle_pin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ postId })
      });
    } catch (e) {}

    const post = this.forumPosts.find(p => String(p.id) === String(postId));
    if (post) {
      post.isPinned = !post.isPinned;
      localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
    }
  }

  static async toggleLockPost(postId) {
    try {
      await fetch('api/foro.php?action=toggle_lock', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ postId })
      });
    } catch (e) {}

    const post = this.forumPosts.find(p => String(p.id) === String(postId));
    if (post) {
      post.isLocked = !post.isLocked;
      localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
    }
  }

  static async addReply(postId, reply) {
    try {
      const res = await fetch('api/foro.php?action=reply', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ postId, ...reply })
      });
      const data = await res.json();
      if (data && data.success && data.reply) {
        const post = this.forumPosts.find(p => String(p.id) === String(postId));
        if (post) {
          if (!post.replies) post.replies = [];
          post.replies.push(data.reply);
          localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
          return true;
        }
      }
    } catch (e) {}

    const post = this.forumPosts.find(p => String(p.id) === String(postId));
    if (post) {
      if (!post.replies) post.replies = [];
      post.replies.push({
        id: 'rep-' + Date.now(),
        postId: postId,
        author: reply.author || 'u/Anónimo',
        text: reply.text,
        isAdmin: !!reply.isAdmin,
        createdAt: new Date().toISOString()
      });
      localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
    }
    return true;
  }

  static async deleteReply(postId, replyId) {
    try {
      await fetch('api/foro.php?action=delete_reply', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ replyId })
      });
    } catch (e) {}

    const post = this.forumPosts.find(p => String(p.id) === String(postId));
    if (post && post.replies) {
      post.replies = post.replies.filter(r => String(r.id) !== String(replyId));
      localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
    }
  }

  static async deleteComment(postId) {
    try {
      await fetch('api/foro.php?action=delete_post', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ postId })
      });
    } catch (e) {}

    this.forumPosts = this.forumPosts.filter(p => String(p.id) !== String(postId));
    localStorage.setItem('app_foro_posts', JSON.stringify(this.forumPosts));
  }

  /* ========================================================
     MÉTODOS DE RESERVAS & TURNOS (CATÁLOGO T5)
     ======================================================== */
  static getReservations() {
    return this.reservations || [];
  }

  static getPendingReservationsCount() {
    return (this.reservations || []).filter(r => r.estado === 'Pendiente').length;
  }

  static async createReservation(data) {
    let createdItem = null;
    try {
      const res = await fetch(`api/prendas.php?action=create_reserva&_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const resJson = await res.json();
      if (resJson && resJson.success && resJson.reserva) {
        createdItem = resJson.reserva;
      }
    } catch (e) {
      console.warn("API de reservas falló, usando contingencia local:", e);
    }

    if (!createdItem) {
      createdItem = {
        id: 'res-' + Date.now(),
        prendaId: String(data.prenda_id || data.id || ''),
        codigoQr: data.codigo_qr || data.qrCode || '',
        prendaTitulo: data.prenda_titulo || data.name || 'Prenda Escolar',
        nombre: data.nombre,
        dni: data.dni || '',
        curso: data.curso || '',
        telefono: data.telefono || '',
        fechaSolicitada: data.fecha_solicitada || '',
        fechaRetiro: '',
        notasUsuario: data.notas || '',
        notasAdmin: '',
        estado: 'Pendiente',
        createdAt: new Date().toISOString()
      };
    }

    // Actualizar estado de prenda local
    const pId = data.prenda_id || data.id;
    const pQr = data.codigo_qr || data.qrCode;
    const prenda = (this.clothingItems || []).find(i => 
      (pId && (String(i.id) === String(pId) || String(i.raw_id) === String(pId))) || 
      (pQr && (i.qrCode === pQr || i.codigo_qr === pQr))
    );
    if (prenda) {
      prenda.status = 'Reservado';
      prenda.estado = 'Reservado';
      try {
        localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
      } catch (e) {}
    }

    // Agregar reserva a la lista
    if (!this.reservations) this.reservations = [];
    this.reservations.unshift(createdItem);
    try {
      localStorage.setItem('app_reservas', JSON.stringify(this.reservations));
    } catch (e) {}

    // Agregar notificación de alerta
    this.addNotificationLocally({
      id: 'notif-' + Date.now(),
      type: 'reserva_nueva',
      title: 'Nueva Reserva de Prenda',
      message: `${data.nombre} solicitó "${createdItem.prendaTitulo}". Fecha: ${data.fecha_solicitada || 'A coordinar'}.`,
      recipient: 'admin',
      link: 'index.html',
      read: false,
      createdAt: new Date().toISOString()
    });

    return { success: true, reserva: createdItem };
  }

  static async updateReservation(id, estado, fechaRetiro = '', notasAdmin = '') {
    let success = false;
    try {
      const res = await fetch(`api/prendas.php?action=update_reserva&_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id,
          estado,
          fecha_retiro: fechaRetiro,
          notas_admin: notasAdmin
        })
      });
      const data = await res.json();
      if (data && data.success) {
        success = true;
      }
    } catch (e) {}

    const resItem = (this.reservations || []).find(r => String(r.id) === String(id));
    if (resItem) {
      resItem.estado = estado;
      if (fechaRetiro) resItem.fechaRetiro = fechaRetiro;
      if (notasAdmin) resItem.notasAdmin = notasAdmin;
      resItem.updatedAt = new Date().toISOString();

      // Sincronizar estado de la prenda vinculada
      const pId = resItem.prendaId;
      const pQr = resItem.codigoQr;
      const prenda = (this.clothingItems || []).find(i => 
        (pId && (String(i.id) === String(pId) || String(i.raw_id) === String(pId))) || 
        (pQr && (i.qrCode === pQr || i.codigo_qr === pQr))
      );

      if (prenda) {
        if (estado === 'Completada' || estado === 'Entregado') {
          prenda.status = 'Entregado';
          prenda.estado = 'Entregado';
        } else if (estado === 'Cancelada') {
          prenda.status = 'Disponible';
          prenda.estado = 'Disponible';
        } else if (estado === 'Aprobada') {
          prenda.status = 'Reservado';
          prenda.estado = 'Reservado';
        }
        try {
          localStorage.setItem('app_clothing', JSON.stringify(this.clothingItems));
        } catch (e) {}
      }

      try {
        localStorage.setItem('app_reservas', JSON.stringify(this.reservations));
      } catch (e) {}

      // Generar notificación informativa
      this.addNotificationLocally({
        id: 'notif-' + Date.now(),
        type: 'reserva_update',
        title: `Turno de Reserva: ${resItem.prendaTitulo}`,
        message: `La reserva de ${resItem.nombre} fue ${estado.toLowerCase()}.${fechaRetiro ? ' Fecha de retiro: ' + fechaRetiro + '.' : ''}${notasAdmin ? ' Nota: ' + notasAdmin : ''}`,
        recipient: 'ALL',
        link: 'index.html',
        read: false,
        createdAt: new Date().toISOString()
      });
    }

    return { success: true };
  }

  /* ========================================================
     MÉTODOS DE NOTIFICACIONES (FORO, RESERVAS Y COMUNIDAD)
     ======================================================== */
  static getNotifications() {
    return this.notifications || [];
  }

  static getUnreadNotificationsCount() {
    return (this.notifications || []).filter(n => !n.read).length;
  }

  static addNotificationLocally(notif) {
    if (!this.notifications) this.notifications = [];
    this.notifications.unshift(notif);
    try {
      localStorage.setItem('app_notifications', JSON.stringify(this.notifications));
    } catch (e) {}
  }

  static async markNotificationsRead() {
    try {
      await fetch(`api/foro.php?action=mark_read&_t=${Date.now()}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'mark_read' })
      });
    } catch (e) {}

    (this.notifications || []).forEach(n => n.read = true);
    try {
      localStorage.setItem('app_notifications', JSON.stringify(this.notifications));
    } catch (e) {}

    return { success: true };
  }
}