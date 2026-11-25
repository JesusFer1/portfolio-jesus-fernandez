<?php
session_start();
require_once 'conexion.php';
require_once 'tareas_helper.php';

ensureTareasTable($pdo, $dbDriver ?? 'sqlite');

$cursoId   = intval($_GET['id'] ?? 0);
$leccionGet = intval($_GET['leccion'] ?? 0);
$userId    = $_SESSION['user_id'] ?? null;
$userName  = $_SESSION['user_name'] ?? 'Usuario';
$userRole  = $_SESSION['role'] ?? 'usuario';
$userTier  = $_SESSION['membresia_id'] ?? 1;

if (!$cursoId) {
    header('Location: cursos.php');
    exit;
}

function getYoutubeId($url) {
    $url = trim($url);
    if (empty($url)) return 'dQw4w9WgXcQ';
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
        return $match[1];
    }
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    return 'dQw4w9WgXcQ';
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre as instructor_nombre, u.email as instructor_email 
        FROM cursos c 
        LEFT JOIN usuarios u ON c.instructor_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$cursoId]);
    $curso = $stmt->fetch();

    if (!$curso) {
        header('Location: cursos.php');
        exit;
    }

    $stmtLec = $pdo->prepare("SELECT * FROM lecciones WHERE curso_id = ? ORDER BY orden ASC, id ASC");
    $stmtLec->execute([$cursoId]);
    $lecciones = $stmtLec->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lecciones)) {
        $lecciones = [
            [
                'id'              =>1,
                'titulo'          => 'Lección 1: Bienvenida e Introducción al Curso',
                'duracion'        => '12 min',
                'video_url'       => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'resumen'         => 'Introducción al contenido del curso y preparación del entorno.',
                'contenido_texto' => 'Bienvenido al curso. En esta primera sesión revisaremos los objetivos y conceptos fundamentales.',
                'material_pdf'    => '',
                'material_nombre' => '',
                'orden'           =>1
            ]
        ];
    }

    foreach ($lecciones as &$lec) {
        $lec['youtube_id'] = getYoutubeId($lec['video_url']);
    }
    unset($lec);

    $estaInscripto      = false;
    $esAdmin            = ($userId && $userRole === 'administrador');
    $esInstructorDuenio = ($userId && $curso['instructor_id'] == $userId);
    $esDuenio           = ($esInstructorDuenio || $esAdmin);

    if ($userId) {
        $stmtIns = $pdo->prepare("SELECT id, fecha_inscripcion FROM inscripciones WHERE usuario_id = ? AND curso_id = ?");
        $stmtIns->execute([$userId, $cursoId]);
        $ins = $stmtIns->fetch();
        if ($ins || $esDuenio) {
            $estaInscripto = true;
        }
    }

    // Progreso real de tareas y certificación (solo para estudiantes inscriptos)
    $progresoTareas = [
        'total_clases'    =>count($lecciones),
        'entregadas'      =>0,
        'aprobadas'       =>0,
        'pendientes'      =>0,
        'revisar'         =>0,
        'todas_aprobadas' =>false,
        'por_leccion'     => []
    ];
    $evaluacionAprobada = null;

    if ($userId && $estaInscripto && !$esDuenio) {
        $progresoTareas = getProgresoTareasAlumno($pdo, (int)$userId, $cursoId);
        $evaluacionAprobada = getExamenAprobadoAlumno($pdo, (int)$userId, $cursoId);
    }

    $puedeRendirExamen = ($progresoTareas['todas_aprobadas']);
    $puedeCertificado  = ($progresoTareas['todas_aprobadas'] && !empty($evaluacionAprobada));

} catch (\PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// Seleccionar lección activa (si viene ?leccion=ID o la primera)
$leccionActivaIndex = 0;
if ($leccionGet >0) {
    foreach ($lecciones as $idx => $l) {
        if ((int)$l['id'] === $leccionGet) {
            $leccionActivaIndex = $idx;
            break;
        }
    }
}
$primeraLeccion = $lecciones[$leccionActivaIndex];

// Objetivos de aprendizaje, documentación y PDF dinámico según categoría
$cat = $curso['categoria'] ?? 'General';
$docUrl = 'documentacion_curso.php?curso_id=' . $curso['id'];
$docLabel = ' Documentación Oficial del Curso &rarr;';
$pdfGuiaUrl = 'uploads/materiales/plan_estudio_atajos_sony_vegas.pdf';
$pdfGuiaLabel = ' Descargar Guía & Atajos (.pdf)';

if ($cat === 'Edición de Video' || stripos($curso['titulo'], 'vegas') !== false) {
    $docUrl = 'documentacion_vegas.php?curso_id=' . $curso['id'];
    $docLabel = ' Documentación Oficial de Sony Vegas Pro &rarr;';
    $pdfGuiaUrl = 'uploads/materiales/plan_estudio_atajos_sony_vegas.pdf';
    $pdfGuiaLabel = ' Descargar Cheatsheet de Atajos Sony Vegas (.pdf)';
    $aprendizajes = [
        'Dominar la interfaz, línea de tiempo y restauración del espacio de trabajo en Sony Vegas Pro.',
        'Separar y editar pistas de audio, aplicar cortes precisos y trabajar con superposición multicapa (PiP).',
        'Crear efectos profesionales: cámara lenta/rápida, barras cinemáticas 21:9, estilo retro VHS y Chroma Key.',
        'Exportar y renderizar proyectos en Full HD 1080p 60fps listos para YouTube y redes sociales.'
    ];
} elseif ($cat === 'Programación') {
    $docLabel = ' Documentación Oficial HTML5 & CSS3 &rarr;';
    $pdfGuiaUrl = 'uploads/materiales/guia_atajos_desarrollo_web.pdf';
    $pdfGuiaLabel = ' Descargar Guía & Atajos HTML5/CSS3 (.pdf)';
    $aprendizajes = [
        'Estructurar páginas web modernas utilizando etiquetas semánticas HTML5 y formularios validados.',
        'Configurar metadatos SEO, Open Graph, favicons e integración multimedia nativa.',
        'Estilizar sitios profesionales con selectores CSS3, paletas de color y diseño adaptable.',
        'Acelerar tu flujo de trabajo con atajos de Visual Studio Code y Emmet.'
    ];
} elseif ($cat === 'Diseño UI/UX') {
    $docLabel = ' Documentación Oficial de Figma UI/UX &rarr;';
    $pdfGuiaUrl = 'uploads/materiales/guia_atajos_figma_uiux.pdf';
    $pdfGuiaLabel = ' Descargar Guía & Atajos de Figma (.pdf)';
    $aprendizajes = [
        'Organizar proyectos, equipos y librerías de recursos profesionales en Figma.',
        'Dominar Frames, alineación, distribución, Constraints y escalado proporcional.',
        'Diseñar interfaces limpias para Desktop y Mobile aplicando principios de accesibilidad UI/UX.',
        'Acelerar tu diseño diario con los atajos esenciales de productividad en Figma.'
    ];
} elseif ($cat === 'Marketing Digital') {
    $docLabel = ' Documentación Oficial de Marketing Digital &rarr;';
    $pdfGuiaUrl = 'uploads/materiales/guia_estrategia_marketing_digital.pdf';
    $pdfGuiaLabel = ' Descargar Guía Estratégica de Marketing (.pdf)';
    $aprendizajes = [
        'Definir tu Buyer Persona, propuesta de valor y embudo de conversión (TOFU, MOFU, BOFU).',
        'Configurar y vincular perfiles comerciales de Facebook e Instagram para negocios.',
        'Planificar calendarios editoriales efectivos con días y horarios óptimos de publicación.',
        'Convertir seguidores en clientes reales midiendo CTR, engagement y resultados.'
    ];
} else {
    $aprendizajes = [
        'Comprender los fundamentos prácticos y herramientas esenciales del área.',
        'Aplicar los conocimientos en trabajos prácticos entregables clase por clase.',
        'Recibir corrección y devolución personalizada del instructor en cada entrega.',
        'Validar tu aprendizaje mediante una evaluación final para obtener el certificado oficial.'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($curso['titulo']) ?> | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/ver_curso.css?v=<?= time() ?>">
    <style>
        /* Estilo Alison para Acerca de este Curso */
        .alison-overview-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            padding: 34px;
            margin-bottom: 32px;
        }
        .alison-grid {
            display: grid;
            grid-template-columns: 1.55fr 1fr;
            gap: 32px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .alison-grid { grid-template-columns: 1fr; }
        }
        .alison-meta-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 14px 0 22px;
        }
        .alison-pill {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 999px;
        }
        .alison-learn-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 22px;
            margin-top: 20px;
        }
        .alison-learn-list {
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 18px;
            margin-top: 12px;
        }
        @media (max-width: 680px) {
            .alison-learn-list { grid-template-columns: 1fr; }
        }
        .alison-learn-list li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 0.9rem;
            color: #1e293b;
            line-height: 1.45;
            font-weight: 600;
        }
        .alison-check {
            color: #16a34a;
            font-weight: 900;
            flex-shrink: 0;
        }
        .alison-side-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
        }
        .alison-side-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
        }
        .cert-roadmap {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 20px;
        }
        @media (max-width: 720px) {
            .cert-roadmap { grid-template-columns: 1fr; }
        }
        .roadmap-step {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.82rem;
        }
        .roadmap-step.done {
            background: #f0fdf4;
            border-color: #86efac;
        }
        .roadmap-step strong {
            display: block;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .tarea-status-banner {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
        }
        .tarea-status-banner.aprobada { background: #dcfce7; border-color: #86efac; color: #166534; }
        .tarea-status-banner.pendiente { background: #fef3c7; border-color: #fcd34d; color: #92400e; }
        .tarea-status-banner.revisar { background: #fee2e2; border-color: #fca5a5; color: #991b1b; }
        .tarea-form label {
            display: block;
            font-size: 0.82rem;
            font-weight: 800;
            color: #334155;
            margin-bottom: 6px;
        }
        .tarea-form input[type="file"], .tarea-form textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.9rem;
            margin-bottom: 12px;
            background: #fff;
        }
        .locked-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 9px 14px;
            border-radius: 10px;
            font-size: 0.84rem;
            font-weight: 800;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="independent-page-body">

    <!-- BARRA DE NAVEGACIÓN SUPERIOR -->
    <header class="independent-course-header">
        <div class="independent-nav-container">
            <div class="independent-nav-left">
                <a href="<?= $esAdmin ? 'admin_dashboard.php' : 'index.php' ?>" class="logo">
                    SkillHub<span class="logo-dot">.</span>
                </a>
                <span class="nav-separator">/</span>
                <?php if ($esAdmin): ?>
                    <a href="admin_dashboard.php" class="btn-catalog-back">&larr; Panel Admin</a>
                <?php else: ?>
                    <a href="cursos.php" class="btn-catalog-back">&larr; Catálogo</a>
                <?php endif; ?>
                <span class="nav-separator">/</span>
                <span class="nav-cat-pill"><?= htmlspecialchars($curso['categoria'] ?? 'General') ?></span>
                <span class="nav-course-title" title="<?= htmlspecialchars($curso['titulo']) ?>"><?= htmlspecialchars($curso['titulo']) ?></span>
            </div>

            <div class="independent-nav-right">
                <?php if ($esAdmin): ?>
                    <span class="badge-nav-enrolled" style="background:#fef3c7; color:#92400e; border-color:#f59e0b;">Vista Admin</span>
                <?php elseif ($esInstructorDuenio): ?>
                    <span class="badge-nav-enrolled" style="background:#ede9fe; color:#5b21b6; border-color:#7c3aed;">Tu Curso</span>
                <?php elseif ($estaInscripto): ?>
                    <span class="badge-nav-enrolled">Inscripto</span>
                <?php elseif ($userRole === 'instructor'): ?>
                    <span class="nav-price-tag"><?= $curso['precio'] > 0 ? '$' . number_format($curso['precio'], 2) . ' USD' : 'Gratis' ?></span>
                <?php elseif ($userId && $userTier == 3): ?>
                    <form action="procesar_inscripcion.php" method="POST" style="margin: 0; display: inline;">
                        <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                        <button type="submit" class="btn-nav-buy" style="cursor: pointer;">Inscribirme VIP &rarr;</button>
                    </form>
                <?php elseif ($curso['precio'] <= 0): ?>
                    <form action="procesar_inscripcion.php" method="POST" style="margin: 0; display: inline;">
                        <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                        <button type="submit" class="btn-nav-buy" style="cursor: pointer;">Inscribirme Gratis &rarr;</button>
                    </form>
                <?php else: ?>
                    <span class="nav-price-tag">$<?= number_format($curso['precio'] ?? 0, 2) ?> USD</span>
                    <a href="checkout.php?curso_id=<?= $curso['id'] ?>" class="btn-nav-buy">Inscribirme &rarr;</a>
                <?php endif; ?>

                <?php if (!$esAdmin): ?>
                    <?php if ($userId): ?>
                        <a href="dashboard.php" class="btn-catalog-back"><?= $userRole === 'instructor' ? 'Panel Instructor' : 'Mi Panel' ?></a>
                    <?php else: ?>
                        <a href="login.php" class="btn-catalog-back">Iniciar Sesión</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="independent-canvas-wrapper">

        <?php if (isset($_GET['bienvenido'])): ?>
            <div style="background-color: var(--success-light); border: 1px solid var(--success-border); border-radius: var(--radius); padding: 16px 20px; margin-bottom: 22px; display: flex; align-items: center; gap: 14px;">
                <span style="font-size: 1.5rem;"></span>
                <div>
                    <strong style="color: var(--success-text); display: block;">¡Inscripción Confirmada!</strong>
                    <span style="color: var(--success-text); font-size: 0.88rem; font-weight: 600;">Ya podés acceder a las clases debajo, enviar tus trabajos prácticos para corrección del instructor y avanzar hacia la prueba final.</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['tarea_enviada'])): ?>
            <div style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #166534; font-weight: 700;">
                 ¡Trabajo práctico enviado con éxito! Quedó en revisión para que el instructor lo corrija.
            </div>
        <?php elseif (isset($_GET['tarea_error'])): ?>
            <div style="background-color: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #991b1b; font-weight: 700;">
                 Debés adjuntar un archivo o escribir un comentario/enlace para entregar tu tarea.
            </div>
        <?php endif; ?>

        <!-- ================================================================ -->
        <!-- 1. PRIMERO: ACERCA DE ESTE CURSO (ESTILO ALISON)                 -->
        <!-- ================================================================ -->
        <section class="alison-overview-card">
            <div class="alison-grid">
                <div>
                    <span class="nav-cat-pill"><?= htmlspecialchars($curso['categoria'] ?? 'General') ?></span>
                    <h1 style="font-size: 2.15rem; font-weight: 900; margin: 12px 0 8px; color: var(--text-main); line-height: 1.2;">
                        <?= htmlspecialchars($curso['titulo']) ?>
                    </h1>
                    <p style="font-weight: 600; color: var(--text-muted); font-size: 0.95rem;">
                        Dictado por <strong><?= htmlspecialchars($curso['instructor_nombre'] ?? 'Instructor') ?></strong>
                    </p>

                    <div class="alison-meta-pills">
                        <span class="alison-pill"> <?= count($lecciones) ?>Clases en Video</span>
                        <span class="alison-pill">Idioma: Español</span>
                        <span class="alison-pill">Trabajos Prácticos por Clase</span>
                        <span class="alison-pill">Certificado al Aprobar</span>
                    </div>

                    <h2 style="font-size: 1.25rem; font-weight: 900; margin-bottom: 10px; color: var(--text-main);">Acerca de este Curso</h2>
                    <div style="font-size: 0.98rem; line-height: 1.7; color: #334155;">
                        <?= nl2br(htmlspecialchars(strip_tags($curso['descripcion']))) ?>
                    </div>

                    <div class="alison-learn-box">
                        <strong style="font-size: 1rem; color: #0f172a;">¿Qué aprenderás en este programa?</strong>
                        <ul class="alison-learn-list">
                            <?php foreach ($aprendizajes as $item): ?>
                                <li><span class="alison-check"></span><span><?= htmlspecialchars($item) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- RUTA DE CERTIFICACIÓN -->
                    <div class="cert-roadmap">
                        <div class="roadmap-step <?= $progresoTareas['todas_aprobadas'] ? 'done' : '' ?>">
                            <strong>1. Tareas por Clase</strong>
                            <?php if ($estaInscripto && !$esDuenio): ?>
                                <span><?= $progresoTareas['aprobadas'] ?>/<?= $progresoTareas['total_clases'] ?>aprobadas por el instructor</span>
                            <?php else: ?>
                                <span>Entregá el trabajo práctico de cada clase</span>
                            <?php endif; ?>
                        </div>
                        <div class="roadmap-step <?= !empty($evaluacionAprobada) ? 'done' : '' ?>">
                            <strong>2. Prueba Final</strong>
                            <span><?= !empty($evaluacionAprobada) ? 'Aprobada ' : ($puedeRendirExamen ? 'Disponible para rendir' : 'Se desbloquea al aprobar todas las tareas') ?></span>
                        </div>
                        <div class="roadmap-step <?= $puedeCertificado ? 'done' : '' ?>">
                            <strong>3. Certificado Oficial</strong>
                            <span><?= $puedeCertificado ? '¡Desbloqueado y listo!' : 'Se otorga al aprobar tareas + prueba final' ?></span>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: PORTADA, DOCUMENTACIÓN Y ACCIÓN -->
                <div class="alison-side-card">
                    <?php if (!empty($curso['imagen_url'])): ?>
                        <img src="<?= htmlspecialchars($curso['imagen_url']) ?>" alt="<?= htmlspecialchars($curso['titulo']) ?>">
                    <?php endif; ?>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <a href="<?= htmlspecialchars($docUrl) ?>" class="btn-primary" style="text-align:center; padding:12px 16px; background:#0f172a; text-decoration:none; font-size:0.9rem;">
                            <?= $docLabel ?>
                        </a>

                        <?php if ($estaInscripto): ?>
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; font-size:0.84rem; color:#334155;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                    <span>Total de clases:</span>
                                    <strong><?= count($lecciones) ?></strong>
                                </div>
                                <?php if (!$esDuenio): ?>
                                    <div style="display:flex; justify-content:space-between;">
                                        <span>Tareas aprobadas:</span>
                                        <strong style="color:#16a34a;"><?= $progresoTareas['aprobadas'] ?> / <?= $progresoTareas['total_clases'] ?></strong>
                                    </div>
                                <?php else: ?>
                                    <div style="display:flex; justify-content:space-between;">
                                        <span>Estado del curso:</span>
                                        <strong style="color:#6d28d9;">Publicado</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($userRole === 'instructor'): ?>
                            <a href="cursos.php" class="btn-primary" style="text-align:center; padding:12px 16px; text-decoration:none;">&larr; Volver al Catálogo</a>
                        <?php elseif ($userId && $userTier == 3): ?>
                            <form action="procesar_inscripcion.php" method="POST" style="margin:0;">
                                <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                <button type="submit" class="btn-primary" style="width:100%; padding:12px 16px; background:var(--accent); cursor:pointer;">Inscribirme Gratis con VIP &rarr;</button>
                            </form>
                        <?php elseif ($curso['precio'] <= 0): ?>
                            <form action="procesar_inscripcion.php" method="POST" style="margin:0;">
                                <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                <button type="submit" class="btn-primary" style="width:100%; padding:12px 16px; background:var(--success); cursor:pointer;">Inscribirme Gratis &rarr;</button>
                            </form>
                        <?php else: ?>
                            <div style="text-align:center; font-size:1.5rem; font-weight:900; color:var(--primary); margin:4px 0;">
                                $<?= number_format($curso['precio'], 2) ?> USD
                            </div>
                            <a href="checkout.php?curso_id=<?= $curso['id'] ?>" class="btn-primary" style="text-align:center; padding:12px 16px; text-decoration:none;">
                                Inscribirme al Curso &rarr;
                            </a>
                        <?php endif; ?>

                        <a href="<?= htmlspecialchars($pdfGuiaUrl) ?>" download style="text-align:center; font-size:0.82rem; font-weight:800; color:#475569; text-decoration:none; padding:8px; border:1px dashed #cbd5e1; border-radius:10px; background:#fff;">
                            <?= $pdfGuiaLabel ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================ -->
        <!-- 2. DEBAJO: LAS CLASES (AULA VIRTUAL + ENTREGA DE TAREAS)         -->
        <!-- ================================================================ -->
        <?php if ($estaInscripto): ?>
            <section id="aula-virtual" class="aula-card-main">
                <div class="aula-header-top">
                    <div>
                        <?php if ($esAdmin): ?>
                            <span class="badge-aula-status" style="background:#fef3c7; color:#92400e;">Supervisión de Administrador</span>
                        <?php elseif ($esInstructorDuenio): ?>
                            <span class="badge-aula-status" style="background:#ede9fe; color:#5b21b6;">Vista de Instructor — Tu Curso</span>
                        <?php else: ?>
                            <span class="badge-aula-status">Clases y Entregas del Curso</span>
                        <?php endif; ?>
                        <h2 class="aula-main-title" style="font-size:1.6rem;">Aula Virtual — Clases y Trabajos Prácticos</h2>
                        <p class="aula-instructor-info">Mirate cada clase, enviá tu trabajo práctico para que lo corrija el instructor y desbloqueá la evaluación final.</p>
                    </div>

                    <div class="aula-actions-toolbar">
                        <?php if ($esDuenio): ?>
                            <a href="editar_curso.php?id=<?= $curso['id'] ?>" class="btn-toolbar btn-toolbar-edit">Gestionar Clases</a>
                            <a href="dashboard.php#seccion-tareas" class="btn-toolbar btn-toolbar-primary">Corregir Tareas</a>
                            <a href="evaluacion.php?curso_id=<?= $curso['id'] ?>" class="btn-toolbar btn-toolbar-primary">Evaluaciones</a>
                        <?php else: ?>
                            <?php if ($puedeRendirExamen): ?>
                                <a href="evaluacion.php?curso_id=<?= $curso['id'] ?>" class="btn-toolbar btn-toolbar-primary">
                                     <?= $evaluacionAprobada ? 'Ver Evaluación Aprobada' : 'Rendir Prueba Final' ?>
                                </a>
                            <?php else: ?>
                                <span class="locked-pill" title="Aprobá todas las tareas con el instructor para desbloquear la prueba final">
                                     Prueba Final (<?= $progresoTareas['aprobadas'] ?>/<?= $progresoTareas['total_clases'] ?>tareas aprobadas)
                                </span>
                            <?php endif; ?>

                            <?php if ($puedeCertificado): ?>
                                <a href="certificado.php?curso_id=<?= $curso['id'] ?>" class="btn-toolbar btn-toolbar-gold">
                                     Descargar Certificado Oficial
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; align-items: flex-start;">
                    <!-- COLUMNA IZQUIERDA: VIDEO + PESTAÑAS (APUNTES / TAREA / PDF) -->
                    <div>
                        <div class="video-player-container">
                            <iframe id="mainLessonPlayer" 
                                    src="https://www.youtube.com/embed/<?= htmlspecialchars($primeraLeccion['youtube_id']) ?>?rel=0&autoplay=0" 
                                    style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border: 0;" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                    allowfullscreen>
                            </iframe>
                        </div>

                        <div class="active-lesson-meta-card">
                            <div class="active-lesson-meta-header">
                                <span id="activeLessonDuration" class="active-lesson-duration-badge">
                                    ⏱ Duración: <?= htmlspecialchars($primeraLeccion['duracion'] ?? '15 min') ?>
                                </span>
                            </div>
                            <h2 id="activeLessonTitle" class="active-lesson-title">
                                <?= htmlspecialchars($primeraLeccion['titulo']) ?>
                            </h2>
                            <p id="activeLessonDesc" class="active-lesson-desc">
                                <?= htmlspecialchars($primeraLeccion['resumen'] ?? '') ?>
                            </p>
                        </div>

                        <div class="lesson-tabs-wrapper-card">
                            <div class="lesson-tabs-nav">
                                <?php if (!$esDuenio): ?>
                                    <button type="button" class="lesson-tab-btn active" onclick="switchLessonTab('tab-tarea', this)">
                                         Enviar Tarea de esta Clase
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="lesson-tab-btn <?= $esDuenio ? 'active' : '' ?>" onclick="switchLessonTab('tab-texto', this)">
                                     Explicación & Apuntes
                                </button>
                                <button type="button" class="lesson-tab-btn" onclick="switchLessonTab('tab-pdf', this)">
                                     PDF de Apoyo
                                </button>
                            </div>

                            <?php if (!$esDuenio): ?>
                                <!-- PESTAÑA DE ENTREGA DE TAREA POR CLASE -->
                                <div id="tab-tarea" class="lesson-tab-content active">
                                    <div id="tareaStatusBox" class="tarea-status-banner"></div>

                                    <form action="procesar_tarea.php" method="POST" enctype="multipart/form-data" class="tarea-form">
                                        <input type="hidden" name="accion" value="entregar_tarea">
                                        <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                        <input type="hidden" name="leccion_id" id="tareaLeccionId" value="<?= (int)$primeraLeccion['id'] ?>">

                                        <label for="archivo_tarea">Adjuntar archivo del trabajo práctico (video .mp4, proyecto .veg, .pdf, .zip, captura .png/.jpg)</label>
                                        <input type="file" id="archivo_tarea" name="archivo_tarea" accept=".pdf,.doc,.docx,.zip,.rar,.mp4,.mov,.avi,.veg,.png,.jpg,.jpeg,.txt,.ppt,.pptx">

                                        <label for="comentario_alumno">Notas, enlace al video/proyecto o comentarios para el instructor</label>
                                        <textarea id="comentario_alumno" name="comentario_alumno" rows="3" placeholder="Ej: Adjunto el ejercicio práctico de esta clase siguiendo los pasos del video..."></textarea>

                                        <button type="submit" id="btnEnviarTarea" class="btn-primary" style="padding: 11px 22px; cursor: pointer;">
                                             Enviar Trabajo Práctico al Instructor
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- PESTAÑA EXPLICACIÓN -->
                            <div id="tab-texto" class="lesson-tab-content <?= $esDuenio ? 'active' : '' ?>">
                                <div id="activeLessonFullText" class="lesson-fulltext-content">
                                    <?= !empty($primeraLeccion['contenido_texto']) ? nl2br(htmlspecialchars($primeraLeccion['contenido_texto'])) : '<em>El instructor no ha agregado notas escritas adicionales para esta clase.</em>' ?>
                                </div>
                            </div>

                            <!-- PESTAÑA PDF -->
                            <div id="tab-pdf" class="lesson-tab-content">
                                <div id="pdfDownloadContainer">
                                    <?php if (!empty($primeraLeccion['material_pdf'])): ?>
                                        <div class="lesson-pdf-box">
                                            <div class="lesson-pdf-info">
                                                <div class="lesson-pdf-icon"></div>
                                                <div>
                                                    <strong style="color: var(--text-main); font-size: 1.05rem; display: block;">
                                                        <?= htmlspecialchars($primeraLeccion['material_nombre'] ?: 'Documento de Estudio.pdf') ?>
                                                    </strong>
                                                    <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Archivo de apoyo de esta clase</span>
                                                </div>
                                            </div>
                                            <a href="<?= htmlspecialchars($primeraLeccion['material_pdf']) ?>" download target="_blank" class="btn-download-pdf">
                                                 Descargar PDF
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="lesson-pdf-empty">
                                            <span>ℹ Esta clase no cuenta con un archivo PDF adjunto.</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: PLAYLIST DE CLASES -->
                    <div class="playlist-sidebar-card">
                        <div class="playlist-sidebar-header">
                            <strong style="font-size: 1.05rem; font-weight: 900; color: var(--text-main);">Clases del Curso</strong>
                            <span style="font-size: 0.82rem; font-weight: 800; color: var(--text-muted);"><?= count($lecciones) ?>Clases</span>
                        </div>

                        <?php if (!$esDuenio): ?>
                            <div class="playlist-progress-box">
                                <span style="font-size: 0.82rem; font-weight: 800; color: var(--text-muted);">Tareas Aprobadas:</span>
                                <span style="font-weight: 900; font-size: 0.85rem; color: var(--success);">
                                    <?= $progresoTareas['aprobadas'] ?>/<?= $progresoTareas['total_clases'] ?>
                                    (<?= $progresoTareas['total_clases'] >0 ? round(($progresoTareas['aprobadas'] / $progresoTareas['total_clases']) * 100) : 0 ?>%)
                                </span>
                            </div>
                        <?php endif; ?>

                        <div id="lessonsPlaylistContainer" style="display: flex; flex-direction: column; gap: 10px; max-height: 560px; overflow-y: auto; padding-right: 4px;">
                            <?php foreach ($lecciones as $index => $lec):
                                $lid = (int)$lec['id'];
                                $ent = $progresoTareas['por_leccion'][$lid] ?? null;
                                $estadoTxt = '○ Sin entregar';
                                $estadoColor = '#64748b';
                                if ($esDuenio) {
                                    $estadoTxt = '▶ Clase ' . ($index + 1);
                                } elseif ($ent) {
                                    if ($ent['estado'] === 'aprobada') {
                                        $estadoTxt = ' Aprobada (' . ($ent['nota'] ?: 10) . '/10)';
                                        $estadoColor = '#166534';
                                    } elseif ($ent['estado'] === 'revisar') {
                                        $estadoTxt = ' Corregir';
                                        $estadoColor = '#dc2626';
                                    } else {
                                        $estadoTxt = '⏳ En revisión';
                                        $estadoColor = '#b45309';
                                    }
                                }
                            ?>
                                <div class="lesson-nav-card <?= $index === $leccionActivaIndex ? 'active' : '' ?>" 
                                     data-id="<?= $lid ?>"
                                     data-video="<?= htmlspecialchars($lec['youtube_id']) ?>"
                                     data-title="<?= htmlspecialchars($lec['titulo']) ?>"
                                     data-dur="<?= htmlspecialchars($lec['duracion'] ?? '15 min') ?>"
                                     data-desc="<?= htmlspecialchars($lec['resumen'] ?? '') ?>"
                                     data-texto="<?= htmlspecialchars($lec['contenido_texto'] ?? '') ?>"
                                     data-pdf="<?= htmlspecialchars($lec['material_pdf'] ?? '') ?>"
                                     data-pdf-name="<?= htmlspecialchars($lec['material_nombre'] ?? 'Guía_Clase.pdf') ?>">
                                    <strong style="font-size: 0.9rem; color: var(--text-main); display: block; margin-bottom: 4px;"><?= htmlspecialchars($lec['titulo']) ?></strong>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                                        <span>⏱ <?= htmlspecialchars($lec['duracion'] ?? '15 min') ?></span>
                                        <span style="font-weight: 800; color: <?= $estadoColor ?>;"><?= $estadoTxt ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="alison-overview-card" style="text-align:center;">
                <h2 style="font-size:1.4rem; font-weight:900; margin-bottom:8px;">Clases Disponibles al Inscribirte</h2>
                <p style="color:var(--text-muted); font-weight:600; margin-bottom:18px;">
                    Este curso cuenta con <?= count($lecciones) ?>clases en video, entregas prácticas con corrección del instructor, evaluación final y certificado oficial.
                </p>
                <?php if ($userRole !== 'instructor'): ?>
                    <a href="checkout.php?curso_id=<?= $curso['id'] ?>" class="btn-primary" style="padding:12px 28px; text-decoration:none; display:inline-block;">
                         Inscribirme ahora &rarr;
                    </a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </main>

    <script>
        const ENTREGAS_POR_LECCION = <?= json_encode($progresoTareas['por_leccion'], JSON_UNESCAPED_UNICODE) ?>;

        function switchLessonTab(tabId, btn) {
            document.querySelectorAll('.lesson-tab-btn').forEach(b =>b.classList.remove('active'));
            document.querySelectorAll('.lesson-tab-content').forEach(c =>c.classList.remove('active'));
            if (btn) btn.classList.add('active');
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
        }

        function renderEstadoTarea(leccionId) {
            const box = document.getElementById('tareaStatusBox');
            const inpId = document.getElementById('tareaLeccionId');
            const txtCom = document.getElementById('comentario_alumno');
            const btnEnv = document.getElementById('btnEnviarTarea');
            if (!box || !inpId) return;

            inpId.value = leccionId;
            const ent = ENTREGAS_POR_LECCION[String(leccionId)] || ENTREGAS_POR_LECCION[Number(leccionId)] || null;

            if (!ent) {
                box.className = 'tarea-status-banner';
                box.innerHTML = ' <strong>Sin entregar:</strong>Realizá el ejercicio de esta clase y subí tu archivo o comentario para que el instructor lo corrija.';
                if (txtCom) txtCom.value = '';
                if (btnEnv) btnEnv.textContent = ' Enviar Trabajo Práctico al Instructor';
                return;
            }

            if (txtCom) txtCom.value = ent.comentario_alumno || '';
            const fileHtml = ent.archivo_ruta
                ? `<div style="margin-top:6px;">Archivo enviado: <a href="${ent.archivo_ruta}" download target="_blank" style="color:inherit;text-decoration:underline;">${ent.archivo_nombre || 'Ver archivo'}</a></div>`
                : '';

            if (ent.estado === 'aprobada') {
                box.className = 'tarea-status-banner aprobada';
                box.innerHTML = ` <strong>¡Tarea Aprobada! (Nota: ${ent.nota || 10}/10)</strong><br>
                    ${ent.feedback_instructor ? `<span>Devolución del instructor: "${ent.feedback_instructor}"</span>` : '<span>Excelente trabajo, esta clase ya cuenta para desbloquear la prueba final.</span>'}
                    ${fileHtml}`;
                if (btnEnv) btnEnv.textContent = '↻ Actualizar entrega';
            } else if (ent.estado === 'revisar') {
                box.className = 'tarea-status-banner revisar';
                box.innerHTML = ` <strong>Requiere Corrección (Nota: ${ent.nota || '-'}/10)</strong><br>
                    <span>Devolución del instructor: "${ent.feedback_instructor || 'Revisá el trabajo y volvé a enviarlo.'}"</span>
                    ${fileHtml}`;
                if (btnEnv) btnEnv.textContent = ' Reenviar Trabajo Corregido';
            } else {
                box.className = 'tarea-status-banner pendiente';
                box.innerHTML = `⏳ <strong>Entregada — Pendiente de corrección del instructor</strong><br>
                    <span>Tu trabajo fue recibido y está esperando revisión.</span>
                    ${fileHtml}`;
                if (btnEnv) btnEnv.textContent = '↻ Reemplazar / Actualizar Entrega';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderEstadoTarea(<?= (int)$primeraLeccion['id'] ?>);

            const lessonCards = document.querySelectorAll('.lesson-nav-card');
            const player = document.getElementById('mainLessonPlayer');
            const titleEl = document.getElementById('activeLessonTitle');
            const descEl = document.getElementById('activeLessonDesc');
            const durEl = document.getElementById('activeLessonDuration');
            const fullTextEl = document.getElementById('activeLessonFullText');
            const pdfContainer = document.getElementById('pdfDownloadContainer');

            lessonCards.forEach(card => {
                card.addEventListener('click', () => {
                    lessonCards.forEach(c =>c.classList.remove('active'));
                    card.classList.add('active');

                    const id = card.getAttribute('data-id');
                    const videoId = card.getAttribute('data-video');
                    const title = card.getAttribute('data-title');
                    const desc = card.getAttribute('data-desc');
                    const dur = card.getAttribute('data-dur');
                    const texto = card.getAttribute('data-texto') || '';
                    const pdfPath = card.getAttribute('data-pdf') || '';
                    const pdfName = card.getAttribute('data-pdf-name') || 'Material_Clase.pdf';

                    if (player && videoId) player.src = `https://www.youtube.com/embed/${videoId}?rel=0&autoplay=1`;
                    if (titleEl) titleEl.textContent = title;
                    if (descEl) descEl.textContent = desc;
                    if (durEl) durEl.textContent = `⏱ Duración: ${dur}`;
                    if (fullTextEl) {
                        fullTextEl.innerHTML = texto.trim()
                            ? texto.replace(/\n/g, '<br>')
                            : '<em>El instructor no ha agregado notas escritas adicionales para esta clase.</em>';
                    }
                    if (pdfContainer) {
                        pdfContainer.innerHTML = pdfPath.trim()
                            ? `<div class="lesson-pdf-box"><div class="lesson-pdf-info"><div class="lesson-pdf-icon"></div><div><strong>${pdfName}</strong><span style="display:block;font-size:0.82rem;color:var(--text-muted);">Archivo de apoyo de esta clase</span></div></div><a href="${pdfPath}" download target="_blank" class="btn-download-pdf">Descargar PDF</a></div>`
                            : `<div class="lesson-pdf-empty"><span>ℹ Esta clase no cuenta con un archivo PDF adjunto.</span></div>`;
                    }

                    renderEstadoTarea(id);
                });
            });
        });
    </script>
<?php include 'demo_switcher_widget.php'; ?>
</body>
</html>
