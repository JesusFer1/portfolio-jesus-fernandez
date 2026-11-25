<?php
session_start();
require_once 'conexion.php';

$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'usuario';
$cursoId  = intval($_GET['curso_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentación Oficial — Curso de Sony Vegas Pro | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/estilos.css">
    <style>
        body { background: #f8fafc; color: #0f172a; }
        .vdoc-wrap { max-width: 1120px; margin: 36px auto 80px; padding: 0 20px; }
        .vdoc-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #0284c7 100%);
            color: #fff;
            border-radius: 20px;
            padding: 38px 36px;
            margin-bottom: 28px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .vdoc-hero h1 { font-size: 2rem; font-weight: 900; margin: 8px 0; }
        .vdoc-hero p { color: #cbd5e1; max-width: 650px; line-height: 1.6; font-weight: 500; }
        .vdoc-badge {
            display: inline-block;
            background: rgba(56, 189, 248, 0.2);
            border: 1px solid rgba(125, 211, 252, 0.5);
            color: #bae6fd;
            font-size: 0.76rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 999px;
            text-transform: uppercase;
        }
        .vdoc-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 860px) { .vdoc-layout { grid-template-columns: 1fr; } }
        .vdoc-toc {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            position: sticky;
            top: 20px;
        }
        .vdoc-toc h3 { font-size: 0.82rem; text-transform: uppercase; color: #64748b; margin-bottom: 12px; letter-spacing: 0.6px; }
        .vdoc-toc a {
            display: block;
            padding: 8px 10px;
            border-radius: 8px;
            color: #1e293b;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .vdoc-toc a:hover { background: #eff6ff; color: #1d4ed8; }
        .vdoc-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 22px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .vdoc-section h2 {
            font-size: 1.4rem;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
        }
        .vdoc-section h3 { font-size: 1.05rem; font-weight: 800; color: #1e3a8a; margin: 18px 0 8px; }
        .vdoc-section p, .vdoc-section li { color: #334155; line-height: 1.65; font-size: 0.94rem; }
        .vdoc-section ul, .vdoc-section ol { padding-left: 20px; margin-bottom: 12px; }
        .vdoc-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px; }
        @media (max-width: 680px) { .vdoc-grid-2 { grid-template-columns: 1fr; } }
        .vdoc-tip {
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 14px 16px;
            border-radius: 0 10px 10px 0;
            margin: 14px 0;
            font-size: 0.9rem;
            color: #0c4a6e;
            font-weight: 600;
        }
        .vdoc-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 0.9rem; }
        .vdoc-table th { background: #f1f5f9; text-align: left; padding: 10px 12px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #cbd5e1; }
        .vdoc-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        kbd {
            background: #0f172a;
            color: #fff;
            padding: 3px 8px;
            border-radius: 6px;
            font-family: Consolas, monospace;
            font-size: 0.82rem;
            font-weight: 700;
        }
        .vdoc-card-mini {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        @media print {
            header, .vdoc-toc, .no-print { display: none !important; }
            .vdoc-layout { grid-template-columns: 1fr; }
            .vdoc-section { box-shadow: none; break-inside: avoid; }
        }
    </style>
</head>
<body>

    <header class="no-print">
        <div class="container nav-container">
            <a href="<?= $userRole === 'administrador' ? 'admin_dashboard.php' : 'index.php' ?>" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <ul class="nav-links">
                <li><a href="cursos.php">Cursos</a></li>
            </ul>
            <div class="auth-buttons" style="display:flex; gap:10px; align-items:center;">
                <?php if ($cursoId): ?>
                    <a href="ver_curso.php?id=<?= $cursoId ?>" class="btn-secondary-link">&larr; Volver al Curso</a>
                <?php endif; ?>
                <?php if ($userId): ?>
                    <a href="<?= $userRole === 'administrador' ? 'admin_dashboard.php' : 'dashboard.php' ?>" class="btn-primary">Mi Panel &rarr;</a>
                <?php else: ?>
                    <a href="login.php" class="btn-primary">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="vdoc-wrap">
        <div class="vdoc-hero">
            <div>
                <span class="vdoc-badge">Manual Técnico Oficial · Edición de Video</span>
                <h1>Documentación del Curso de Sony Vegas Pro</h1>
                <p>Guía de referencia completa con configuración de proyectos, flujo de edición no lineal, composición multicapa, efectos visuales, atajos de teclado, exportación Full HD y requisitos de aprobación.</p>
            </div>
            <div class="no-print" style="display:flex; flex-direction:column; gap:10px;">
                <button type="button" onclick="window.print()" class="btn-primary" style="background:#fff; color:#0f172a; padding:12px 20px; cursor:pointer; border:none;">
                     Imprimir / Guardar en PDF
                </button>
                <a href="uploads/materiales/plan_estudio_atajos_sony_vegas.pdf" download style="text-align:center; color:#bae6fd; font-size:0.84rem; font-weight:700; text-decoration:underline;">
                     Descargar Cheatsheet (.pdf)
                </a>
            </div>
        </div>

        <div class="vdoc-layout">
            <!-- ÍNDICE LATERAL -->
            <aside class="vdoc-toc">
                <h3>Contenido del Manual</h3>
                <a href="#interfaz">1. Interfaz y Espacio de Trabajo</a>
                <a href="#proyecto">2. Configuración del Proyecto</a>
                <a href="#edicion">3. Edición y Manejo de Pistas</a>
                <a href="#efectos">4. Efectos, Velocidad y Composición</a>
                <a href="#atajos">5. Guía de Atajos de Teclado</a>
                <a href="#render">6. Renderizado y Exportación MP4</a>
                <a href="#evaluacion">7. Entregas y Certificación</a>
            </aside>

            <!-- CONTENIDO PRINCIPAL -->
            <div>
                <section id="interfaz" class="vdoc-section">
                    <h2>1. Interfaz y Espacio de Trabajo en Sony Vegas Pro</h2>
                    <p>Sony Vegas Pro (VEGAS Pro) es un editor de video no lineal (NLE) basado en pistas ilimitadas de video y audio. Antes de editar, es fundamental reconocer las 4 áreas principales:</p>
                    <div class="vdoc-grid-2">
                        <div class="vdoc-card-mini">
                            <strong>Medios de Proyecto y Explorador</strong>
                            <p>Almacena los clips importados, imágenes PNG, música y generadores de texto antes de llevarlos a la línea de tiempo.</p>
                        </div>
                        <div class="vdoc-card-mini">
                            <strong>Ventana de Vista Previa (Preview)</strong>
                            <p>Muestra el resultado en tiempo real. Si tu PC se traba, podés bajar la calidad a <em>Preview (Half)</em>sin afectar el render final.</p>
                        </div>
                        <div class="vdoc-card-mini">
                            <strong>Línea de Tiempo (Timeline)</strong>
                            <p>Zona central donde se organizan los clips en capas. Las pistas de video superiores tapan a las inferiores (lógica de capas).</p>
                        </div>
                        <div class="vdoc-card-mini">
                            <strong>Cabeceras de Pista y Mezclador</strong>
                            <p>Controlan opacidad, modo de composición, volumen, paneo, silencio (Mute) y solo (Solo) por cada pista.</p>
                        </div>
                    </div>
                    <div class="vdoc-tip">
                         <strong>¿Cerraste una ventana por error?</strong>Presioná <kbd>Alt</kbd>+ <kbd>D</kbd>y luego <kbd>D</kbd> (o ir a <em>Ver → Diseños de ventana → Diseño predeterminado</em>) para restaurar toda la interfaz de fábrica en un segundo.
                    </div>
                </section>

                <section id="proyecto" class="vdoc-section">
                    <h2>2. Configuración Correcta del Proyecto</h2>
                    <p>Configurar bien las propiedades del proyecto al inicio (<kbd>Alt</kbd>+ <kbd>Enter</kbd>) evita bordes negros indeseados, desfasajes de audio y pérdida de calidad.</p>
                    <table class="vdoc-table">
                        <thead>
                            <tr><th>Parámetro</th><th>Valor Recomendado (YouTube / Web)</th><th>Observación</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>Plantilla (Template)</strong></td><td>HD 1080-60p (1920x1080, 59.940 fps)</td><td>O 1080-30p (29.970 fps) si tus clips son a 30fps.</td></tr>
                            <tr><td><strong>Relación de píxeles</strong></td><td>1.0000 (Square)</td><td>Obligatorio para pantallas digitales.</td></tr>
                            <tr><td><strong>Modo de remuestreo</strong></td><td>Deshabilitar remuestreo (Disable resample)</td><td>Evita el efecto "fantasma" o borroso en movimiento.</td></tr>
                            <tr><td><strong>Calidad de render</strong></td><td>Óptima (Best)</td><td>Asegura nitidez en escalado y efectos Pan &amp; Crop.</td></tr>
                            <tr><td><strong>Audio</strong></td><td>Estéreo · 48.000 Hz · 16/24 bit</td><td>Estándar profesional para video digital.</td></tr>
                        </tbody>
                    </table>
                </section>

                <section id="edicion" class="vdoc-section">
                    <h2>3. Flujo de Edición y Manejo de Pistas</h2>
                    <h3>Cortar y organizar clips</h3>
                    <ul>
                        <li>Colocá el cursor en el punto exacto y presioná <kbd>S</kbd>para dividir (Split) el clip.</li>
                        <li>Activá el ajuste automático (<kbd>F8</kbd> — Enable Snapping) para que los clips encajen sin huecos negros.</li>
                        <li>Para crear transiciones suaves (Fade In / Fade Out), arrastrá la esquina superior izquierda o derecha de cualquier clip.</li>
                    </ul>
                    <h3>Desvincular y editar audio</h3>
                    <ul>
                        <li>Seleccioná un clip con audio integrado y presioná <kbd>U</kbd> (<strong>Ungroup</strong>) para separar la pista de audio del video.</li>
                        <li>Una vez desvinculada, podés eliminar el audio original (<kbd>Supr</kbd>), silenciarlo o reemplazarlo por música/voz en off.</li>
                        <li>Para volver a unir dos eventos, seleccionalos con <kbd>Ctrl</kbd>+ clic y presioná <kbd>G</kbd> (<strong>Group</strong>).</li>
                    </ul>
                </section>

                <section id="efectos" class="vdoc-section">
                    <h2>4. Efectos Visuales, Velocidad y Composición</h2>
                    <h3>Superposición Multicapa (Picture-in-Picture)</h3>
                    <p>Creá una pista de video superior (<kbd>Ctrl</kbd>+ <kbd>Shift</kbd>+ <kbd>Q</kbd>), colocá el segundo clip arriba y abrí el ícono de <strong>Panorámica / Recorte de evento (Event Pan &amp; Crop)</strong>para escalar y mover el recuadro con precisión o animarlo con fotogramas clave (Keyframes).</p>

                    <h3>Cámara Lenta y Cámara Rápida</h3>
                    <ul>
                        <li><strong>Método rápido (Time Stretch):</strong>Mantené presionada la tecla <kbd>Ctrl</kbd>mientras arrastrás el borde derecho del clip. Estirar = cámara lenta; contraer = cámara rápida.</li>
                        <li><strong>Método avanzado (Envolvente de velocidad):</strong>Clic derecho en el clip → <em>Insertar/Eliminar envolvente → Velocidad</em>para crear aceleraciones progresivas (Speed Ramps).</li>
                    </ul>

                    <h3>Barras Cinemáticas 21:9, VHS y Modos de Composición</h3>
                    <ul>
                        <li><strong>Overlays con fondo negro (VHS, partículas, REC):</strong>Cambiá el <em>Modo de Composición</em>de la pista superior a <strong>Pantalla (Screen)</strong>o <strong>Añadir (Add)</strong>para volver transparente el negro automáticamente.</li>
                        <li><strong>Pantalla verde (Chroma Key):</strong>Aplicá el efecto <em>Vegas Chroma Keyer</em>al clip superior, tomá el color verde con el cuentagotas y ajustá los umbrales Low/High Threshold + Blur Amount para bordes limpios.</li>
                        <li><strong>Barras negras cinemáticas:</strong>Colocá una plantilla PNG 21:9 con canal alfa en la pista superior o recortá en Pan &amp; Crop usando relación 2.35:1.</li>
                    </ul>
                </section>

                <section id="atajos" class="vdoc-section">
                    <h2>5. Guía Rápida de Atajos de Teclado</h2>
                    <table class="vdoc-table">
                        <thead>
                            <tr><th>Atajo</th><th>Acción</th><th>Uso habitual</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><kbd>Espacio</kbd></td><td>Reproducir / Detener</td><td>Vuelve el cursor al punto de inicio.</td></tr>
                            <tr><td><kbd>Enter</kbd></td><td>Reproducir / Pausar</td><td>Deja el cursor donde pausaste.</td></tr>
                            <tr><td><kbd>S</kbd></td><td>Dividir evento (Split)</td><td>Cortar clips en el cursor.</td></tr>
                            <tr><td><kbd>U</kbd> / <kbd>G</kbd></td><td>Desagrupar / Agrupar</td><td>Separar o volver a unir audio y video.</td></tr>
                            <tr><td><kbd>Ctrl</kbd>+ arrastrar borde</td><td>Estirar tiempo (Time Stretch)</td><td>Cámara rápida o cámara lenta directa.</td></tr>
                            <tr><td><kbd>Ctrl</kbd>+ <kbd>Shift</kbd>+ <kbd>Q</kbd></td><td>Nueva pista de video</td><td>Agregar capas para PiP, textos y overlays.</td></tr>
                            <tr><td><kbd>Ctrl</kbd>+ <kbd>Q</kbd></td><td>Nueva pista de audio</td><td>Agregar música o efectos de sonido.</td></tr>
                            <tr><td><kbd>M</kbd></td><td>Insertar marcador</td><td>Marcar golpes de música o puntos de corte.</td></tr>
                            <tr><td><kbd>Alt</kbd>+ <kbd>D</kbd>, <kbd>D</kbd></td><td>Restaurar diseño</td><td>Recuperar ventanas cerradas por accidente.</td></tr>
                        </tbody>
                    </table>
                </section>

                <section id="render" class="vdoc-section">
                    <h2>6. Renderizado y Exportación Óptima (MP4)</h2>
                    <ol>
                        <li>Seleccioná la región a exportar arrastrando la barra de bucle superior (o doble clic sobre los clips).</li>
                        <li>Ir a <strong>Archivo → Renderizar como... (Render As)</strong>.</li>
                        <li>Elegí el formato <strong>MAGIX AVC/AAC MP4</strong>y la plantilla <strong>Internet HD 1080p 59.94 fps</strong> (o 29.97 fps según tu proyecto).</li>
                        <li>En <em>Personalizar plantilla</em>: activá codificación por GPU (NVENC / AMD VCE / Intel QSV si está disponible), Bitrate variable (VBR) de 16 a 24 Mbps y audio AAC 192-320 kbps a 48.000 Hz.</li>
                        <li>Marcá <em>Renderizar solo región de bucle</em>y hacé clic en <strong>Renderizar</strong>.</li>
                    </ol>
                </section>

                <section id="evaluacion" class="vdoc-section">
                    <h2>7. Entregas Prácticas y Ruta de Certificación</h2>
                    <p>Para garantizar el dominio práctico del programa, la certificación en SkillHub sigue un proceso supervisado por el instructor:</p>
                    <ol>
                        <li><strong>Trabajo Práctico por Clase:</strong>En cada clase del Aula Virtual debés aplicar lo visto en el video y subir tu resultado (video corto `.mp4`, archivo de proyecto `.veg`, captura o enlace) desde la pestaña <em>Enviar Tarea de esta Clase</em>.</li>
                        <li><strong>Corrección Docente:</strong>El instructor revisa cada entrega, te asigna una calificación (1 a 10) con devolución y marca la tarea como <em>Aprobada</em> (o te indica qué ajustar).</li>
                        <li><strong>Evaluación Final:</strong>Una vez aprobadas todas las tareas de las clases, se desbloquea automáticamente la <em>Prueba Final</em>teórica-práctica.</li>
                        <li><strong>Certificado Oficial:</strong>Al aprobar la Evaluación Final, se habilita la descarga de tu Certificado Oficial verificable con código único.</li>
                    </ol>
                </section>
            </div>
        </div>
    </main>

</body>
</html>
