/**
 * BiblioTec — Store Compartido (localStorage) + Selector de Rol (Administrador / Docente)
 * Permite registrar docentes desde el panel Administrador, iniciar sesión con su DNI
 * y publicar/eliminar materiales sincronizados entre panel.html, login.html e index.html.
 */
(function () {
    const STORAGE_KEYS = {
        ARCHIVOS: 'bibliotec_archivos_demo',
        PROFESORES: 'bibliotec_profesores_demo',
        SESION: 'bibliotec_sesion_actual'
    };

    const DEMO_ARCHIVOS_INICIALES = [
        { id: 'demo-1', titulo: 'Guía Práctica HTML5, CSS3 y Flexbox', anio: 7, division: 2, nombre_profesor: 'Prof. Martín Gómez', autor_dni: '35123456', tipo_archivo: 'pdf', fecha_formateada: '18/11/2026', ruta_archivo: '#demo-1', descripcion: 'Apunte teórico-práctico sobre diseño web responsivo y grillas CSS modernas para 7°2° Programación.', timestamp: 1794960000000 },
        { id: 'demo-2', titulo: 'Ejercicios Resueltos PHP + MySQL PDO', anio: 7, division: 2, nombre_profesor: 'Prof. Laura Méndez', autor_dni: '36789012', tipo_archivo: 'zip', fecha_formateada: '15/11/2026', ruta_archivo: '#demo-2', descripcion: 'Proyecto base con conexión segura PDO, consultas preparadas y CRUD completo.', timestamp: 1794700800000 },
        { id: 'demo-3', titulo: 'Arquitectura de Redes y Subnetting IPv4', anio: 7, division: 1, nombre_profesor: 'Prof. Carlos Ruiz', autor_dni: '32456789', tipo_archivo: 'pdf', fecha_formateada: '12/11/2026', ruta_archivo: '#demo-3', descripcion: 'Cálculo de subredes, máscaras CIDR y configuración básica de routers Cisco.', timestamp: 1794441600000 },
        { id: 'demo-4', titulo: 'Plano Estructural Vivienda Unifamiliar', anio: 7, division: 3, nombre_profesor: 'Arq. Diego Pereyra', autor_dni: '30111222', tipo_archivo: 'dwg', fecha_formateada: '10/11/2026', ruta_archivo: '#demo-4', descripcion: 'Planta baja, cortes y planilla de carpintería en formato AutoCAD.', timestamp: 1794268800000 },
        { id: 'demo-5', titulo: 'Introducción a Python y CustomTkinter', anio: 6, division: 2, nombre_profesor: 'Prof. Laura Méndez', autor_dni: '36789012', tipo_archivo: 'pdf', fecha_formateada: '08/11/2026', ruta_archivo: '#demo-5', descripcion: 'Manual de interfaces gráficas modernas en Python con manejo de eventos y SQLite.', timestamp: 1794096000000 },
        { id: 'demo-6', titulo: 'Simulador de Circuitos Digitales y Compuertas', anio: 6, division: 1, nombre_profesor: 'Prof. Héctor Sosa', autor_dni: '29876543', tipo_archivo: 'exe', fecha_formateada: '04/11/2026', ruta_archivo: '#demo-6', descripcion: 'Herramienta de laboratorio para probar tablas de verdad y circuitos combinacionales.', timestamp: 1793750400000 },
        { id: 'demo-7', titulo: 'Cálculo de Hormigón Armado — Vigas y Columnas', anio: 6, division: 3, nombre_profesor: 'Ing. Sofía Benítez', autor_dni: '34555666', tipo_archivo: 'pdf', fecha_formateada: '01/11/2026', ruta_archivo: '#demo-7', descripcion: 'Guía de dimensionamiento según reglamento CIRSOC 201.', timestamp: 1793491200000 },
        { id: 'demo-8', titulo: 'Fundamentos de JavaScript y DOM', anio: 5, division: 2, nombre_profesor: 'Prof. Martín Gómez', autor_dni: '35123456', tipo_archivo: 'pdf', fecha_formateada: '28/10/2026', ruta_archivo: '#demo-8', descripcion: 'Variables, funciones, arreglos, eventos y manipulación dinámica del DOM.', timestamp: 1793145600000 },
        { id: 'demo-9', titulo: 'Mantenimiento Preventivo de Hardware PC', anio: 5, division: 1, nombre_profesor: 'Prof. Carlos Ruiz', autor_dni: '32456789', tipo_archivo: 'mp4', fecha_formateada: '24/10/2026', ruta_archivo: '#demo-9', descripcion: 'Video tutorial paso a paso sobre armado, pasta térmica y diagnóstico POST.', timestamp: 1792800000000 },
        { id: 'demo-10', titulo: 'Instalaciones Sanitarias y Desagües', anio: 5, division: 3, nombre_profesor: 'Arq. Diego Pereyra', autor_dni: '30111222', tipo_archivo: 'dwg', fecha_formateada: '20/10/2026', ruta_archivo: '#demo-10', descripcion: 'Esquema de cañerías de agua fría, caliente y desagües cloacales.', timestamp: 1792454400000 },
        { id: 'demo-11', titulo: 'Lógica de Programación y Pseudocódigo (PSeInt)', anio: 4, division: 2, nombre_profesor: 'Prof. Analía Torres', autor_dni: '37222333', tipo_archivo: 'pdf', fecha_formateada: '15/10/2026', ruta_archivo: '#demo-11', descripcion: 'Estructuras condicionales, ciclos repetitivos y diagramas de flujo.', timestamp: 1792022400000 },
        { id: 'demo-12', titulo: 'Sistemas Operativos: Windows y Linux Básico', anio: 4, division: 1, nombre_profesor: 'Prof. Héctor Sosa', autor_dni: '29876543', tipo_archivo: 'pdf', fecha_formateada: '11/10/2026', ruta_archivo: '#demo-12', descripcion: 'Introducción a la consola de comandos, permisos y sistema de archivos.', timestamp: 1791676800000 },
        { id: 'demo-13', titulo: 'Dibujo Técnico: Normas IRAM y Acotaciones', anio: 4, division: 3, nombre_profesor: 'Ing. Sofía Benítez', autor_dni: '34555666', tipo_archivo: 'pdf', fecha_formateada: '05/10/2026', ruta_archivo: '#demo-13', descripcion: 'Vistas ortogonales, escalas normalizadas y caligrafía técnica.', timestamp: 1791158400000 }
    ];

    const DEMO_PROFESORES_INICIALES = [
        {
            dni: '35123456',
            nombre: 'Prof. Martín Gómez',
            especialidad: 'Programación',
            password: '35123456',
            requiere_cambio_clave: false,
            fecha_alta: '10/10/2026'
        },
        {
            dni: '36789012',
            nombre: 'Prof. Laura Méndez',
            especialidad: 'Programación',
            password: '36789012',
            requiere_cambio_clave: false,
            fecha_alta: '12/10/2026'
        },
        {
            dni: '32456789',
            nombre: 'Prof. Carlos Ruiz',
            especialidad: 'Informática',
            password: '32456789',
            requiere_cambio_clave: false,
            fecha_alta: '14/10/2026'
        }
    ];

    function formatearFechaHoy() {
        const hoy = new Date();
        const dd = String(hoy.getDate()).padStart(2, '0');
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const yyyy = hoy.getFullYear();
        return `${dd}/${mm}/${yyyy}`;
    }

    function leerJSON(key, fallback) {
        try {
            const raw = localStorage.getItem(key);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed !== null && parsed !== undefined) return parsed;
            }
        } catch (e) {}
        return null;
    }

    function guardarJSON(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('No se pudo guardar en localStorage:', e);
        }
    }

    function asegurarDatosIniciales() {
        if (!leerJSON(STORAGE_KEYS.ARCHIVOS)) {
            guardarJSON(STORAGE_KEYS.ARCHIVOS, DEMO_ARCHIVOS_INICIALES);
        }
        if (!leerJSON(STORAGE_KEYS.PROFESORES)) {
            guardarJSON(STORAGE_KEYS.PROFESORES, DEMO_PROFESORES_INICIALES);
        }
    }

    function getArchivos() {
        asegurarDatosIniciales();
        const list = leerJSON(STORAGE_KEYS.ARCHIVOS) || DEMO_ARCHIVOS_INICIALES;
        return Array.isArray(list) ? list : [];
    }

    function addArchivo({ titulo, anio, division, ruta_archivo, tipo_archivo, nombre_profesor, autor_dni, descripcion }) {
        const archivos = getArchivos();
        const nuevo = {
            id: 'pub-' + Date.now(),
            titulo: String(titulo || '').trim(),
            anio: parseInt(anio, 10) || 4,
            division: parseInt(division, 10) || 1,
            nombre_profesor: nombre_profesor || 'Equipo Docente',
            autor_dni: autor_dni || 'admin',
            tipo_archivo: (tipo_archivo || 'pdf').toLowerCase(),
            fecha_formateada: formatearFechaHoy(),
            ruta_archivo: String(ruta_archivo || '').trim() || ('#demo-' + Date.now()),
            descripcion: descripcion || 'Material publicado desde el panel de BiblioTec.',
            timestamp: Date.now()
        };
        archivos.unshift(nuevo);
        guardarJSON(STORAGE_KEYS.ARCHIVOS, archivos);
        return nuevo;
    }

    function deleteArchivo(id) {
        const archivos = getArchivos().filter(a => String(a.id) !== String(id));
        guardarJSON(STORAGE_KEYS.ARCHIVOS, archivos);
        return archivos;
    }

    function getProfesores() {
        asegurarDatosIniciales();
        const list = leerJSON(STORAGE_KEYS.PROFESORES) || DEMO_PROFESORES_INICIALES;
        return Array.isArray(list) ? list : [];
    }

    function addProfesor({ nombre, dni, especialidad }) {
        const dniLimpio = String(dni || '').replace(/\D/g, '').trim();
        const nombreLimpio = String(nombre || '').trim();
        if (!nombreLimpio) return { ok: false, error: 'Ingresá el nombre completo del docente.' };
        if (dniLimpio.length < 7 || dniLimpio.length > 9) {
            return { ok: false, error: 'El DNI debe tener entre 7 y 9 dígitos.' };
        }
        const profesores = getProfesores();
        if (profesores.some(p => String(p.dni) === dniLimpio)) {
            return { ok: false, error: 'Ya existe un docente registrado con ese DNI.' };
        }
        const nuevo = {
            dni: dniLimpio,
            nombre: nombreLimpio,
            especialidad: especialidad || 'Programación',
            password: dniLimpio,
            requiere_cambio_clave: true,
            fecha_alta: formatearFechaHoy()
        };
        profesores.unshift(nuevo);
        guardarJSON(STORAGE_KEYS.PROFESORES, profesores);
        return { ok: true, profesor: nuevo };
    }

    function deleteProfesor(dni) {
        const profesores = getProfesores().filter(p => String(p.dni) !== String(dni));
        guardarJSON(STORAGE_KEYS.PROFESORES, profesores);
        const sesion = getSesion();
        if (sesion && sesion.rol === 'docente' && String(sesion.dni) === String(dni)) {
            cerrarSesion();
        }
        return profesores;
    }

    function updatePassword(dni, nuevaClave) {
        const profesores = getProfesores();
        const idx = profesores.findIndex(p => String(p.dni) === String(dni));
        if (idx === -1) return { ok: false, error: 'Docente no encontrado.' };
        profesores[idx].password = String(nuevaClave);
        profesores[idx].requiere_cambio_clave = false;
        guardarJSON(STORAGE_KEYS.PROFESORES, profesores);
        return { ok: true, profesor: profesores[idx] };
    }

    function getSesion() {
        return leerJSON(STORAGE_KEYS.SESION);
    }

    function setSesion(sesion) {
        guardarJSON(STORAGE_KEYS.SESION, sesion);
        return sesion;
    }

    function cerrarSesion() {
        try {
            localStorage.removeItem(STORAGE_KEYS.SESION);
        } catch (e) {}
    }

    function loginConDni(dni, clave) {
        const dniLimpio = String(dni || '').replace(/\D/g, '').trim();
        const profesores = getProfesores();
        const prof = profesores.find(p => String(p.dni) === dniLimpio);
        if (!prof) {
            return { ok: false, error: 'DNI no registrado. Un Administrador debe dar de alta al docente primero.' };
        }
        if (String(prof.password) !== String(clave)) {
            return { ok: false, error: 'Contraseña incorrecta.' };
        }
        setSesion({
            rol: 'docente',
            dni: prof.dni,
            nombre: prof.nombre,
            especialidad: prof.especialidad
        });
        return { ok: true, profesor: prof, requiere_cambio_clave: !!prof.requiere_cambio_clave };
    }

    function entrarComoAdmin() {
        setSesion({
            rol: 'admin',
            dni: 'admin',
            nombre: 'Administrador E.E.S.T. N° 5',
            especialidad: 'Dirección / Administración'
        });
        window.location.href = 'panel.html';
    }

    function entrarComoDocente(dniOpcional) {
        const profesores = getProfesores();
        if (profesores.length === 0) {
            alert('Todavía no hay docentes registrados. Entrá como Administrador para registrar un docente primero.');
            return;
        }
        const elegido = dniOpcional
            ? profesores.find(p => String(p.dni) === String(dniOpcional)) || profesores[0]
            : profesores[0];

        setSesion({
            rol: 'docente',
            dni: elegido.dni,
            nombre: elegido.nombre,
            especialidad: elegido.especialidad
        });
        window.location.href = 'panel.html';
    }

    function escaparHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // --- Widget Flotante para cambiar entre Administrador y Usuario (Docente) ---
    function inyectarSelectorDeRol() {
        if (document.getElementById('bibliotec-role-switcher')) return;

        const style = document.createElement('style');
        style.textContent = `
            #bibliotec-role-switcher {
                position: fixed;
                right: 20px;
                bottom: 20px;
                z-index: 9999;
                font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            }
            .bt-rs-fab {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #0f172a;
                color: #f8fafc;
                border: 1px solid rgba(148, 163, 184, 0.35);
                border-radius: 999px;
                padding: 10px 16px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.28);
                transition: transform 0.15s ease, background 0.15s ease;
            }
            .bt-rs-fab:hover {
                background: #1e293b;
                transform: translateY(-1px);
            }
            .bt-rs-dot {
                width: 9px;
                height: 9px;
                border-radius: 50%;
                display: inline-block;
            }
            .bt-rs-dot.admin { background: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25); }
            .bt-rs-dot.docente { background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25); }
            .bt-rs-dot.publico { background: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25); }
            .bt-rs-menu {
                position: absolute;
                right: 0;
                bottom: 52px;
                width: 295px;
                background: #ffffff;
                color: #0f172a;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.22);
                padding: 14px;
                display: none;
            }
            .bt-rs-menu.abierto {
                display: block;
            }
            .bt-rs-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 10px;
                padding-bottom: 8px;
                border-bottom: 1px solid #f1f5f9;
            }
            .bt-rs-title {
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #475569;
            }
            .bt-rs-close {
                background: transparent;
                border: none;
                color: #64748b;
                cursor: pointer;
                font-size: 16px;
                line-height: 1;
                padding: 2px 6px;
            }
            .bt-rs-btn {
                width: 100%;
                text-align: left;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 10px 12px;
                margin-bottom: 8px;
                cursor: pointer;
                transition: background 0.15s ease, border-color 0.15s ease;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .bt-rs-btn:hover {
                background: #f1f5f9;
                border-color: #cbd5e1;
            }
            .bt-rs-btn.activo {
                border-color: #2563eb;
                background: #eff6ff;
            }
            .bt-rs-btn strong {
                font-size: 13px;
                color: #0f172a;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .bt-rs-btn span {
                font-size: 11px;
                color: #64748b;
            }
            .bt-rs-badge {
                font-size: 10px;
                font-weight: 700;
                padding: 2px 7px;
                border-radius: 999px;
                text-transform: uppercase;
            }
            .bt-rs-badge.admin { background: #e0e7ff; color: #3730a3; }
            .bt-rs-badge.docente { background: #d1fae5; color: #065f46; }
            .bt-rs-badge.publico { background: #e0f2fe; color: #075985; }
            .bt-rs-select-wrap {
                margin: 4px 0 10px 0;
                padding: 8px 10px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
            }
            .bt-rs-select-wrap label {
                display: block;
                font-size: 11px;
                font-weight: 600;
                color: #475569;
                margin-bottom: 5px;
            }
            .bt-rs-select {
                width: 100%;
                padding: 6px 8px;
                font-size: 12px;
                border-radius: 7px;
                border: 1px solid #cbd5e1;
                background: #fff;
                color: #0f172a;
                margin-bottom: 6px;
            }
            .bt-rs-subactions {
                display: flex;
                gap: 6px;
            }
            .bt-rs-mini-btn {
                flex: 1;
                padding: 6px 8px;
                font-size: 11px;
                font-weight: 600;
                border-radius: 7px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #0f172a;
                cursor: pointer;
                text-align: center;
                text-decoration: none;
            }
            .bt-rs-mini-btn.primario {
                background: #059669;
                border-color: #059669;
                color: #ffffff;
            }
            .bt-rs-mini-btn.primario:hover { background: #047857; }
            .bt-rs-mini-btn:hover { background: #f1f5f9; }
            .boton-subir {
                bottom: 78px !important;
            }
        `;
        document.head.appendChild(style);

        const sesion = getSesion();
        const esPanel = window.location.pathname.toLowerCase().includes('panel.html');
        const rolActual = esPanel && sesion ? sesion.rol : (sesion ? sesion.rol : 'publico');
        const profesores = getProfesores();

        let etiquetaFab = 'Cambiar Rol: Biblioteca';
        let claseDot = 'publico';
        if (rolActual === 'admin') {
            etiquetaFab = 'Rol: Administrador';
            claseDot = 'admin';
        } else if (rolActual === 'docente') {
            etiquetaFab = 'Rol: Docente (Usuario)';
            claseDot = 'docente';
        }

        const opcionesDocentes = profesores.map(p => {
            const selected = sesion && sesion.rol === 'docente' && String(sesion.dni) === String(p.dni) ? 'selected' : '';
            return `<option value="${escaparHtml(p.dni)}" ${selected}>${escaparHtml(p.nombre)} (DNI ${escaparHtml(p.dni)})</option>`;
        }).join('');

        const wrapper = document.createElement('div');
        wrapper.id = 'bibliotec-role-switcher';
        wrapper.innerHTML = `
            <div class="bt-rs-menu" id="bt-rs-menu">
                <div class="bt-rs-header">
                    <span class="bt-rs-title">Cambiar de Vista / Rol</span>
                    <button type="button" class="bt-rs-close" id="bt-rs-close" aria-label="Cerrar">&times;</button>
                </div>

                <button type="button" class="bt-rs-btn ${rolActual === 'admin' && esPanel ? 'activo' : ''}" id="bt-rs-go-admin">
                    <strong>
                        Administrador
                        <span class="bt-rs-badge admin">Admin</span>
                    </strong>
                    <span>Registrar nuevos docentes y gestionar publicaciones</span>
                </button>

                <div class="bt-rs-select-wrap">
                    <label for="bt-rs-docente-select">Usuario (Docente registrado):</label>
                    ${profesores.length > 0
                        ? `<select id="bt-rs-docente-select" class="bt-rs-select">${opcionesDocentes}</select>`
                        : `<div style="font-size:11px;color:#b91c1c;margin-bottom:6px;">No hay docentes registrados aún.</div>`
                    }
                    <div class="bt-rs-subactions">
                        <button type="button" class="bt-rs-mini-btn primario" id="bt-rs-go-docente">Entrar como Docente</button>
                        <a href="login.html" class="bt-rs-mini-btn">Login con DNI</a>
                    </div>
                </div>

                <button type="button" class="bt-rs-btn ${!esPanel && !window.location.pathname.toLowerCase().includes('login.html') ? 'activo' : ''}" id="bt-rs-go-publico">
                    <strong>
                        Biblioteca Pública
                        <span class="bt-rs-badge publico">Alumno</span>
                    </strong>
                    <span>Explorar y descargar apuntes por año y división</span>
                </button>
            </div>

            <button type="button" class="bt-rs-fab" id="bt-rs-fab">
                <span class="bt-rs-dot ${claseDot}"></span>
                <span>${escaparHtml(etiquetaFab)}</span>
            </button>
        `;

        document.body.appendChild(wrapper);

        const menu = document.getElementById('bt-rs-menu');
        const fab = document.getElementById('bt-rs-fab');
        const closeBtn = document.getElementById('bt-rs-close');

        fab.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('abierto');
        });
        closeBtn.addEventListener('click', () => menu.classList.remove('abierto'));
        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) menu.classList.remove('abierto');
        });

        document.getElementById('bt-rs-go-admin').addEventListener('click', () => {
            entrarComoAdmin();
        });

        document.getElementById('bt-rs-go-docente').addEventListener('click', () => {
            const select = document.getElementById('bt-rs-docente-select');
            const dni = select ? select.value : null;
            entrarComoDocente(dni);
        });

        document.getElementById('bt-rs-go-publico').addEventListener('click', () => {
            window.location.href = 'index.html';
        });
    }

    window.BiblioTecStore = {
        getArchivos,
        addArchivo,
        deleteArchivo,
        getProfesores,
        addProfesor,
        deleteProfesor,
        updatePassword,
        getSesion,
        setSesion,
        cerrarSesion,
        loginConDni,
        entrarComoAdmin,
        entrarComoDocente,
        escaparHtml,
        inyectarSelectorDeRol
    };

    asegurarDatosIniciales();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inyectarSelectorDeRol);
    } else {
        inyectarSelectorDeRol();
    }
})();
