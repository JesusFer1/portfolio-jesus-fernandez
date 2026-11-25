document.addEventListener('DOMContentLoaded', () => {
    inicializarAcordeones();
    inicializarBuscadorHistorial();
    cargarArchivosYHistorial();
});

function inicializarAcordeones() {
    const cabeceras = document.querySelectorAll('.acordeon-cabecera');
    
    cabeceras.forEach(cabecera => {
        cabecera.addEventListener('click', () => {
            const acordeon = cabecera.parentElement;
            const contenido = cabecera.nextElementSibling;
            
            const estaActivo = acordeon.classList.contains('activo');
            
            document.querySelectorAll('.acordeon').forEach(item => {
                item.classList.remove('activo');
                item.querySelector('.acordeon-contenido').style.maxHeight = null;
            });
            
            if (!estaActivo) {
                acordeon.classList.add('activo');
                contenido.style.maxHeight = contenido.scrollHeight + 'px';
                
                const anio = cabecera.getAttribute('data-anio');
                cargarArchivosPorAnio(anio);
            }
        });
    });
}

function inicializarBuscadorHistorial() {
    const buscador = document.getElementById('buscador-historial');
    if (buscador) {
        buscador.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            filtrarHistorialVisualmente(query);
        });
    }
}

let datosArchivosHistorial = [];

const DEMO_ARCHIVOS_BIBLIOTEC = [
    { id: 1, titulo: 'Guía Práctica HTML5, CSS3 y Flexbox', anio: 7, division: 2, nombre_profesor: 'Prof. Martín Gómez', tipo_archivo: 'pdf', fecha_formateada: '18/11/2026', ruta_archivo: '#demo-1', descripcion: 'Apunte teórico-práctico sobre diseño web responsivo y grillas CSS modernas para 7°2° Programación.' },
    { id: 2, titulo: 'Ejercicios Resueltos PHP + MySQL PDO', anio: 7, division: 2, nombre_profesor: 'Prof. Laura Méndez', tipo_archivo: 'zip', fecha_formateada: '15/11/2026', ruta_archivo: '#demo-2', descripcion: 'Proyecto base con conexión segura PDO, consultas preparadas y CRUD completo.' },
    { id: 3, titulo: 'Arquitectura de Redes y Subnetting IPv4', anio: 7, division: 1, nombre_profesor: 'Prof. Carlos Ruiz', tipo_archivo: 'pdf', fecha_formateada: '12/11/2026', ruta_archivo: '#demo-3', descripcion: 'Cálculo de subredes, máscaras CIDR y configuración básica de routers Cisco.' },
    { id: 4, titulo: 'Plano Estructural Vivienda Unifamiliar', anio: 7, division: 3, nombre_profesor: 'Arq. Diego Pereyra', tipo_archivo: 'dwg', fecha_formateada: '10/11/2026', ruta_archivo: '#demo-4', descripcion: 'Planta baja, cortes y planilla de carpintería en formato AutoCAD.' },
    { id: 5, titulo: 'Introducción a Python y CustomTkinter', anio: 6, division: 2, nombre_profesor: 'Prof. Laura Méndez', tipo_archivo: 'pdf', fecha_formateada: '08/11/2026', ruta_archivo: '#demo-5', descripcion: 'Manual de interfaces gráficas modernas en Python con manejo de eventos y SQLite.' },
    { id: 6, titulo: 'Simulador de Circuitos Digitales y Compuertas', anio: 6, division: 1, nombre_profesor: 'Prof. Héctor Sosa', tipo_archivo: 'exe', fecha_formateada: '04/11/2026', ruta_archivo: '#demo-6', descripcion: 'Herramienta de laboratorio para probar tablas de verdad y circuitos combinacionales.' },
    { id: 7, titulo: 'Cálculo de Hormigón Armado — Vigas y Columnas', anio: 6, division: 3, nombre_profesor: 'Ing. Sofía Benítez', tipo_archivo: 'pdf', fecha_formateada: '01/11/2026', ruta_archivo: '#demo-7', descripcion: 'Guía de dimensionamiento según reglamento CIRSOC 201.' },
    { id: 8, titulo: 'Fundamentos de JavaScript y DOM', anio: 5, division: 2, nombre_profesor: 'Prof. Martín Gómez', tipo_archivo: 'pdf', fecha_formateada: '28/10/2026', ruta_archivo: '#demo-8', descripcion: 'Variables, funciones, arreglos, eventos y manipulación dinámica del DOM.' },
    { id: 9, titulo: 'Mantenimiento Preventivo de Hardware PC', anio: 5, division: 1, nombre_profesor: 'Prof. Carlos Ruiz', tipo_archivo: 'mp4', fecha_formateada: '24/10/2026', ruta_archivo: '#demo-9', descripcion: 'Video tutorial paso a paso sobre armado, pasta térmica y diagnóstico POST.' },
    { id: 10, titulo: 'Instalaciones Sanitarias y Desagües', anio: 5, division: 3, nombre_profesor: 'Arq. Diego Pereyra', tipo_archivo: 'dwg', fecha_formateada: '20/10/2026', ruta_archivo: '#demo-10', descripcion: 'Esquema de cañerías de agua fría, caliente y desagües cloacales.' },
    { id: 11, titulo: 'Lógica de Programación y Pseudocódigo (PSeInt)', anio: 4, division: 2, nombre_profesor: 'Prof. Analía Torres', tipo_archivo: 'pdf', fecha_formateada: '15/10/2026', ruta_archivo: '#demo-11', descripcion: 'Estructuras condicionales, ciclos repetitivos y diagramas de flujo.' },
    { id: 12, titulo: 'Sistemas Operativos: Windows y Linux Básico', anio: 4, division: 1, nombre_profesor: 'Prof. Héctor Sosa', tipo_archivo: 'pdf', fecha_formateada: '11/10/2026', ruta_archivo: '#demo-12', descripcion: 'Introducción a la consola de comandos, permisos y sistema de archivos.' },
    { id: 13, titulo: 'Dibujo Técnico: Normas IRAM y Acotaciones', anio: 4, division: 3, nombre_profesor: 'Ing. Sofía Benítez', tipo_archivo: 'pdf', fecha_formateada: '05/10/2026', ruta_archivo: '#demo-13', descripcion: 'Vistas ortogonales, escalas normalizadas y caligrafía técnica.' }
];

function obtenerArchivosLocales() {
    if (window.BiblioTecStore && typeof window.BiblioTecStore.getArchivos === 'function') {
        return window.BiblioTecStore.getArchivos();
    }
    try {
        const guardados = localStorage.getItem('bibliotec_archivos_demo');
        if (guardados) {
            const parsed = JSON.parse(guardados);
            if (Array.isArray(parsed) && parsed.length > 0) return parsed;
        }
    } catch (e) {}
    return DEMO_ARCHIVOS_BIBLIOTEC;
}

function cargarArchivosYHistorial() {
    fetch('api/obtener_archivos.php?accion=historial')
        .then(response => {
            if (!response.ok) throw new Error('Sin backend PHP');
            return response.json();
        })
        .then(datos => {
            datosArchivosHistorial = Array.isArray(datos) && datos.length > 0 ? datos : obtenerArchivosLocales();
            renderizarHistorial(datosArchivosHistorial);
        })
        .catch(() => {
            datosArchivosHistorial = obtenerArchivosLocales();
            renderizarHistorial(datosArchivosHistorial);
        });
}

function cargarArchivosPorAnio(anio) {
    const divisionesContenedores = document.querySelectorAll(`.division-bloque[data-anio="${anio}"]`);

    const pintarPorAnio = (archivos) => {
        divisionesContenedores.forEach(contenedor => {
            const division = contenedor.getAttribute('data-division');
            const listaContainer = contenedor.querySelector('.archivos-lista-container');
            const archivosDivision = archivos.filter(a => parseInt(a.division) === parseInt(division));
            renderizarListaArchivosEnAcordeon(listaContainer, archivosDivision);
        });
        const acordeonActivo = document.querySelector(`.acordeon-cabecera[data-anio="${anio}"]`);
        if (acordeonActivo && acordeonActivo.parentElement.classList.contains('activo')) {
            const contenido = acordeonActivo.nextElementSibling;
            contenido.style.maxHeight = contenido.scrollHeight + 'px';
        }
    };

    fetch(`api/obtener_archivos.php?accion=por_anio&anio=${anio}`)
        .then(response => {
            if (!response.ok) throw new Error('Sin backend PHP');
            return response.json();
        })
        .then(archivos => {
            if (Array.isArray(archivos) && archivos.length > 0) {
                pintarPorAnio(archivos);
            } else {
                pintarPorAnio(obtenerArchivosLocales().filter(a => parseInt(a.anio) === parseInt(anio)));
            }
        })
        .catch(() => {
            pintarPorAnio(obtenerArchivosLocales().filter(a => parseInt(a.anio) === parseInt(anio)));
        });
}

function renderizarListaArchivosEnAcordeon(contenedor, archivos) {
    contenedor.innerHTML = '';
    
    if (archivos.length === 0) {
        contenedor.innerHTML = '<p class="archivo-vacio-aviso">No hay apuntes publicados para esta división.</p>';
        return;
    }
    
    archivos.forEach(archivo => {
        const infoIcono = obtenerIconoFormato(archivo.tipo_archivo);
        const itemHtml = `
            <div class="archivo-item">
                <div class="archivo-info-izquierda">
                    <div class="archivo-icono-tipo">
                        <i data-lucide="${infoIcono.icono}" class="${infoIcono.claseCSS}"></i>
                    </div>
                    <div class="archivo-detalles-texto">
                        <p class="archivo-titulo-nombre" title="${archivo.titulo}">${archivo.titulo}</p>
                        <div class="archivo-meta-informacion">
                            <span>Prof: ${archivo.nombre_profesor}</span>
                            <span>•</span>
                            <span>Formato: ${archivo.tipo_archivo.toUpperCase()}</span>
                        </div>
                    </div>
                </div>
                <button onclick="descargarArchivo('${archivo.ruta_archivo}', '${archivo.titulo}')" class="boton-descarga-archivo">
                    <i data-lucide="download"></i> Descargar
                </button>
            </div>
        `;
        contenedor.innerHTML += itemHtml;
    });
    
    lucide.createIcons();
}

function renderizarHistorial(archivos) {
    const contenedor = document.getElementById('contenedor-historial');
    const avisoVacio = document.getElementById('historial-vacio');
    
    contenedor.innerHTML = '';
    
    if (archivos.length === 0) {
        avisoVacio.classList.remove('oculto');
        return;
    }
    
    avisoVacio.classList.add('oculto');
    
    archivos.forEach(archivo => {
        const infoIcono = obtenerIconoFormato(archivo.tipo_archivo);
        const tarjetaHtml = `
            <div class="tarjeta-archivo" data-filtro-texto="${archivo.titulo.toLowerCase()} ${archivo.nombre_profesor.toLowerCase()}">
                <div class="tarjeta-meta-superior">
                    <span class="tarjeta-curso-badge">${archivo.anio}° ${archivo.division}°</span>
                    <span class="tarjeta-fecha">${archivo.fecha_formateada}</span>
                </div>
                <div class="tarjeta-cuerpo">
                    <h4 class="tarjeta-titulo" title="${archivo.titulo}">${archivo.titulo}</h4>
                    <div class="tarjeta-icono-bloque">
                        <i data-lucide="${infoIcono.icono}" class="${infoIcono.claseCSS}"></i>
                        <span class="tarjeta-icono-nombre">${archivo.tipo_archivo.toUpperCase()}</span>
                    </div>
                </div>
                <div class="tarjeta-autor-bloque">
                    <div class="autor-info">
                        <p class="autor-titulo">Publicado por</p>
                        <p class="autor-nombre">${archivo.nombre_profesor}</p>
                    </div>
                    <button onclick="descargarArchivo('${archivo.ruta_archivo}', '${archivo.titulo}')" class="boton-descarga-archivo">
                        <i data-lucide="download"></i>
                    </button>
                </div>
            </div>
        `;
        contenedor.innerHTML += tarjetaHtml;
    });
    
    lucide.createIcons();
}

function filtrarHistorialVisualmente(query) {
    const tarjetas = document.querySelectorAll('.tarjeta-archivo');
    const avisoVacio = document.getElementById('historial-vacio');
    let encontradas = 0;
    
    tarjetas.forEach(tarjeta => {
        const textoFiltro = tarjeta.getAttribute('data-filtro-texto');
        if (textoFiltro.includes(query)) {
            tarjeta.classList.remove('oculto');
            encontradas++;
        } else {
            tarjeta.classList.add('oculto');
        }
    });
    
    if (encontradas === 0) {
        avisoVacio.classList.remove('oculto');
    } else {
        avisoVacio.classList.add('oculto');
    }
}

function descargarArchivo(url, titulo) {
    if (!url || url.startsWith('#demo-')) {
        const item = obtenerArchivosLocales().find(a => a.ruta_archivo === url || a.titulo === titulo);
        const contenido = `BIBLIOTEC — E.E.S.T. N° 5 DE BERAZATEGUI\n========================================\nTítulo: ${titulo}\nCurso: ${item ? item.anio + '° ' + item.division + '°' : 'Ciclo Superior'}\nDocente: ${item ? item.nombre_profesor : 'Equipo Docente'}\nFormato original: ${item ? item.tipo_archivo.toUpperCase() : 'PDF'}\nFecha: ${item ? item.fecha_formateada : '2026'}\n\nDescripción:\n${item && item.descripcion ? item.descripcion : 'Material educativo del repositorio digital BiblioTec.'}\n`;
        const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8' });
        const blobUrl = URL.createObjectURL(blob);
        const enlace = document.createElement('a');
        enlace.href = blobUrl;
        enlace.download = `${titulo.replace(/[^a-z0-9áéíóúñ\s-_]/gi, '')}.txt`;
        document.body.appendChild(enlace);
        enlace.click();
        document.body.removeChild(enlace);
        setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
        return;
    }
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = titulo;
    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);
}

function obtenerIconoFormato(extension) {
    const ext = extension.toLowerCase();
    switch(ext) {
        case 'pdf':
            return { icono: 'file-text', claseCSS: 'ext-pdf' };
        case 'zip':
        case 'rar':
            return { icono: 'archive', claseCSS: 'ext-zip' };
        case 'dwg':
        case 'dxf':
            return { icono: 'drafting-compass', claseCSS: 'ext-dwg' };
        case 'mp4':
        case 'avi':
            return { icono: 'video', claseCSS: 'ext-mp4' };
        case 'exe':
            return { icono: 'cpu', claseCSS: 'ext-exe' };
        default:
            return { icono: 'file', claseCSS: 'ext-gen' };
    }
}