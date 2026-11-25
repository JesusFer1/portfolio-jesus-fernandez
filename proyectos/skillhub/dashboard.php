<?php
session_start();
require_once 'conexion.php';
require_once 'tareas_helper.php';

ensureTareasTable($pdo, $dbDriver ?? 'sqlite');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'usuario';
$userTier = $_SESSION['membresia_id'] ?? 1;

// El administrador solo accede a su Panel de Control
if ($userRole === 'administrador') {
    header('Location: admin_dashboard.php');
    exit;
}

$misCursos     = [];
$totalAlumnos  = 0;
$totalVentas   = 0;
$ultimasVentas = [];
$misPagos      = [];

try {
    if (in_array($userRole, ['instructor', 'administrador'])) {
        // Cursos creados por el instructor
        $stmtCursos = $pdo->prepare("SELECT * FROM cursos WHERE instructor_id = ? ORDER BY id DESC");
        $stmtCursos->execute([$userId]);
        $misCursos = $stmtCursos->fetchAll();

        // Alumnos totales inscriptos a sus cursos
        $stmtStats = $pdo->prepare("
            SELECT COUNT(i.id) as total, SUM(c.precio) as ingresos
            FROM inscripciones i 
            INNER JOIN cursos c ON i.curso_id = c.id 
            WHERE c.instructor_id = ?
        ");
        $stmtStats->execute([$userId]);
        $resStats = $stmtStats->fetch();
        $totalAlumnos = $resStats['total'] ?? 0;
        $totalVentas  = $resStats['ingresos'] ?? 0;

        // Historial de ventas del instructor
        $stmtVentas = $pdo->prepare("
            SELECT p.*, c.titulo as curso_titulo, u.nombre as comprador_nombre 
            FROM pagos p 
            INNER JOIN cursos c ON p.curso_id = c.id 
            INNER JOIN usuarios u ON p.usuario_id = u.id 
            WHERE c.instructor_id = ? 
            ORDER BY p.id DESC LIMIT 5
        ");
        $stmtVentas->execute([$userId]);
        $ultimasVentas = $stmtVentas->fetchAll();

        // Evaluaciones de los alumnos para el instructor (excluyendo al propio instructor)
        $stmtEvals = $pdo->prepare("
            SELECT e.*, u.nombre as alumno_nombre, u.email as alumno_email, c.titulo as curso_titulo
            FROM evaluaciones_resultados e
            INNER JOIN usuarios u ON e.usuario_id = u.id
            INNER JOIN cursos c ON e.curso_id = c.id
            WHERE c.instructor_id = ? AND e.usuario_id != c.instructor_id
            ORDER BY e.id DESC
        ");
        $stmtEvals->execute([$userId]);
        $evaluacionesAlumnos = $stmtEvals->fetchAll();

        // Entregas de tareas de las clases para corregir
        $stmtTareas = $pdo->prepare("
            SELECT t.*, u.nombre as alumno_nombre, u.email as alumno_email,
                   c.titulo as curso_titulo, l.titulo as leccion_titulo
            FROM entregas_tareas t
            INNER JOIN usuarios u ON t.usuario_id = u.id
            INNER JOIN cursos c ON t.curso_id = c.id
            LEFT JOIN lecciones l ON t.leccion_id = l.id
            WHERE c.instructor_id = ?
            ORDER BY CASE WHEN t.estado = 'pendiente' THEN 0 WHEN t.estado = 'revisar' THEN 1 ELSE 2 END, t.fecha_entrega DESC
        ");
        $stmtTareas->execute([$userId]);
        $tareasAlumnos = $stmtTareas->fetchAll();
        $tareasPendientesCount = count(array_filter($tareasAlumnos, fn($x) => $x['estado'] === 'pendiente'));

    } else {
        // Cursos inscriptos del usuario alumno
        $stmtInscritos = $pdo->prepare("
            SELECT c.*, i.fecha_inscripcion 
            FROM cursos c 
            INNER JOIN inscripciones i ON c.id = i.curso_id 
            WHERE i.usuario_id = ? 
            ORDER BY i.fecha_inscripcion DESC
        ");
        $stmtInscritos->execute([$userId]);
        $misCursos = $stmtInscritos->fetchAll();

        // Comprobantes de pago del alumno (Cursos y Membresías)
        $stmtMisPagos = $pdo->prepare("
            SELECT p.*, 
                   COALESCE(c.titulo, CASE WHEN p.monto >= 30 THEN 'Membresía VIP Master' ELSE 'Membresía Pro' END) as curso_titulo,
                   CASE WHEN p.curso_id = 0 THEN 'Membresía' ELSE 'Curso' END as tipo_pago
            FROM pagos p 
            LEFT JOIN cursos c ON p.curso_id = c.id 
            WHERE p.usuario_id = ? 
            ORDER BY p.id DESC
        ");
        $stmtMisPagos->execute([$userId]);
        $misPagos = $stmtMisPagos->fetchAll();
    }

    // Nombre de la membresía del usuario
    $stmtM = $pdo->prepare("SELECT nombre FROM membresias WHERE id = ?");
    $stmtM->execute([$userTier]);
    $nombreMembresia = $stmtM->fetchColumn() ?: 'Básica';

} catch (\PDOException $e) {
    die("Error general en el dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>

    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <ul class="nav-links">
                <li><a href="cursos.php">Cursos</a></li>
                <?php if ($userRole === 'usuario'): ?>
                    <li><a href="membresias.php">Membresías</a></li>
                <?php endif; ?>
            </ul>
            <div class="auth-buttons">
                <div class="user-profile-menu">
                    <button type="button" class="user-avatar-btn" id="userMenuBtn">
                        <span class="avatar-circle" style="<?= $userRole === 'instructor' ? 'background:#7c3aed;' : '' ?>"><?= strtoupper(substr($userName, 0, 1)) ?></span>
                        <span class="user-name-display"><?= htmlspecialchars($userName) ?></span>
                        <span class="role-badge <?= htmlspecialchars($userRole) ?>" style="font-size:0.68rem; padding:2px 7px;"><?= $userRole === 'instructor' ? 'Instructor' : 'Estudiante' ?></span>
                        <span class="dropdown-arrow">▾</span>
                    </button>

                    <div class="profile-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <p class="dropdown-user-name"><?= htmlspecialchars($userName) ?></p>
                            <span class="role-badge <?= htmlspecialchars($userRole) ?>"><?= $userRole === 'instructor' ? 'Instructor' : 'Estudiante' ?></span>
                        </div>
                        <hr class="dropdown-divider">
                        <ul class="dropdown-links">
                            <li><a href="index.php">Inicio</a></li>
                            <?php if ($userRole === 'instructor'): ?>
                                <li><a href="crear_curso.php" class="highlight-link">+ Crear Curso</a></li>
                            <?php else: ?>
                                <li><a href="membresias.php">Mi Membresía</a></li>
                            <?php endif; ?>
                        </ul>
                        <hr class="dropdown-divider">
                        <a href="logout.php" class="dropdown-logout">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container dashboard-container">
        
        <div class="dashboard-header">
            <div>
                <span class="role-badge <?= htmlspecialchars($userRole) ?>"><?= $userRole === 'instructor' ? ' Panel de Instructor' : ' Panel de Estudiante' ?></span>
                <h1>¡Hola, <?= htmlspecialchars($userName) ?>! </h1>
                <p style="color: var(--text-muted); font-weight: 600; margin-top: 4px;">
                    <?= $userRole === 'instructor'
                        ? 'Gestioná tus cursos publicados, clases, evaluaciones de alumnos e ingresos.'
                        : 'Accedé a tus cursos inscriptos, certificados y comprobantes de pago.' ?>
                </p>
            </div>

            <div class="header-action-buttons">
                <?php if ($userRole === 'instructor'): ?>
                    <a href="crear_curso.php" class="btn-primary">+ Crear Nuevo Curso</a>
                <?php else: ?>
                    <a href="cursos.php" class="btn-primary">Explorar Catálogo &rarr;</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (in_array($userRole, ['instructor', 'administrador'])): ?>
            <!-- ESTADÍSTICAS PARA INSTRUCTORES -->
            <div class="dashboard-stats-grid">
                <div class="dash-stat-card">
                    <h3><?= count($misCursos) ?></h3>
                    <p>Cursos Publicados</p>
                </div>
                <div class="dash-stat-card">
                    <h3><?= $totalAlumnos ?></h3>
                    <p>Alumnos Inscriptos</p>
                </div>
                <div class="dash-stat-card">
                    <h3>$<?= number_format($totalVentas, 2) ?>USD</h3>
                    <p>Ingresos Generados</p>
                </div>
            </div>

            <!-- CURSOS CREADOS -->
            <section class="dash-section">
                <div class="dash-section-header">
                    <h2>Mis Cursos Creados</h2>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">Total: <?= count($misCursos) ?></span>
                </div>

                <?php if (empty($misCursos)): ?>
                    <div class="empty-state">
                        <p style="font-weight: 700; color: var(--text-muted); font-size: 1.05rem;">Aún no has creado ningún curso en la plataforma.</p>
                        <a href="crear_curso.php" class="btn-primary" style="margin-top: 16px;">+ Crear mi primer curso</a>
                    </div>
                <?php else: ?>
                    <div class="dash-courses-grid">
                        <?php foreach ($misCursos as $curso): ?>
                            <div class="dash-course-card">
                                <div class="dash-card-header">
                                    <span class="badge-tag" style="background-color: #f7a8b8; color: #7d192c; font-size: 0.72rem; padding: 3px 8px; border-radius: 8px;">
                                        <?= htmlspecialchars($curso['categoria'] ?? 'General') ?>
                                    </span>
                                </div>
                                
                                <h3><?= htmlspecialchars($curso['titulo']) ?></h3>
                                <p><?= htmlspecialchars(substr(strip_tags($curso['descripcion']), 0, 90)) ?>...</p>
                                
                                <div class="course-card-footer">
                                    <span style="font-weight: 900; color: var(--primary); font-size: 1.15rem;">$<?= number_format($curso['precio'], 2) ?>USD</span>
                                    <div class="card-actions-group">
                                        <a href="editar_curso.php?id=<?= $curso['id'] ?>" class="btn-card-edit" title="Editar Contenido y Videos">Editar</a>
                                        <a href="ver_curso.php?id=<?= $curso['id'] ?>" class="btn-card-view" title="Ver Curso y Aula">Ver Curso &rarr;</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- SECCIÓN DE TAREAS Y TRABAJOS PRÁCTICOS DE ALUMNOS PARA CORREGIR -->
            <section id="seccion-tareas" class="dash-section" style="margin-top: 24px;">
                <div class="dash-section-header">
                    <h2>Tareas y Trabajos Prácticos de Alumnos </h2>
                    <span style="font-size: 0.9rem; font-weight: 800; color: <?= $tareasPendientesCount >0 ? '#b45309' : 'var(--text-muted)' ?>;">
                        Pendientes de corrección: <?= $tareasPendientesCount ?> / Total: <?= count($tareasAlumnos) ?>
                    </span>
                </div>

                <?php if (isset($_GET['tarea_corregida'])): ?>
                    <div style="background:#dcfce7; border:2px solid #16a34a; color:#166534; padding:12px 16px; border-radius:10px; font-weight:700; margin-bottom:14px;">
                         Corrección guardada y notificada al estudiante.
                    </div>
                <?php endif; ?>

                <?php if (empty($tareasAlumnos)): ?>
                    <div class="empty-state" style="padding: 24px;">
                        <p style="font-weight: 600; color: var(--text-muted);">Aún ningún estudiante ha enviado trabajos prácticos en tus cursos.</p>
                    </div>
                <?php else: ?>
                    <div style="background: #ffffff; border: 2px solid var(--text-main); border-radius: var(--radius); overflow-x: auto; box-shadow: 4px 4px 0px var(--text-main);">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                            <thead>
                                <tr style="background: #faf9f6; border-bottom: 2px solid var(--border-color);">
                                    <th style="padding: 12px 14px;">Estudiante</th>
                                    <th style="padding: 12px 14px;">Curso / Clase</th>
                                    <th style="padding: 12px 14px;">Entrega / Archivo</th>
                                    <th style="padding: 12px 14px;">Estado</th>
                                    <th style="padding: 12px 14px;">Corregir Trabajo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tareasAlumnos as $t): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 12px 14px;">
                                            <strong><?= htmlspecialchars($t['alumno_nombre']) ?></strong><br>
                                            <small style="color: var(--text-muted);"><?= htmlspecialchars($t['alumno_email']) ?></small><br>
                                            <small style="color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($t['fecha_entrega'])) ?></small>
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <strong><?= htmlspecialchars($t['curso_titulo']) ?></strong><br>
                                            <span style="color: var(--text-muted); font-size: 0.82rem;"><?= htmlspecialchars($t['leccion_titulo'] ?: 'Clase #' . $t['leccion_id']) ?></span>
                                        </td>
                                        <td style="padding: 12px 14px; max-width: 240px;">
                                            <?php if (!empty($t['archivo_ruta'])): ?>
                                                <a href="<?= htmlspecialchars($t['archivo_ruta']) ?>" download target="_blank" style="display:inline-block; background:#eff6ff; color:#1d4ed8; border:1px solid #93c5fd; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.78rem; text-decoration:none; margin-bottom:6px;">
                                                     <?= htmlspecialchars($t['archivo_nombre'] ?: 'Descargar Archivo') ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($t['comentario_alumno'])): ?>
                                                <div style="font-size:0.82rem; color:var(--text-main); background:#f8fafc; padding:6px 8px; border-radius:6px; border:1px solid #e2e8f0;">
                                                    <?= nl2br(htmlspecialchars($t['comentario_alumno'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <?php if ($t['estado'] === 'aprobada'): ?>
                                                <span style="background:#dcfce7; color:#166534; padding:4px 9px; border-radius:6px; font-weight:800; font-size:0.76rem; display:inline-block;">Aprobada (<?= $t['nota'] ?: 10 ?>/10)</span>
                                            <?php elseif ($t['estado'] === 'revisar'): ?>
                                                <span style="background:#fee2e2; color:#991b1b; padding:4px 9px; border-radius:6px; font-weight:800; font-size:0.76rem; display:inline-block;">Corregir</span>
                                            <?php else: ?>
                                                <span style="background:#fef3c7; color:#92400e; padding:4px 9px; border-radius:6px; font-weight:800; font-size:0.76rem; display:inline-block;">⏳ Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px 14px; min-width: 250px;">
                                            <form action="procesar_tarea.php" method="POST" style="display:flex; flex-direction:column; gap:6px;">
                                                <input type="hidden" name="accion" value="corregir_tarea">
                                                <input type="hidden" name="entrega_id" value="<?= $t['id'] ?>">
                                                <div style="display:flex; gap:6px;">
                                                    <select name="estado" style="flex:1; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem; font-weight:700;">
                                                        <option value="aprobada" <?= $t['estado'] === 'aprobada' ? 'selected' : '' ?>>Aprobar Tarea</option>
                                                        <option value="revisar" <?= $t['estado'] === 'revisar' ? 'selected' : '' ?>>Pedir Corrección</option>
                                                    </select>
                                                    <input type="number" name="nota" min="1" max="10" value="<?= htmlspecialchars($t['nota'] ?? 9) ?>" title="Nota (1-10)" style="width:62px; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem; font-weight:800;">
                                                </div>
                                                <input type="text" name="feedback_instructor" value="<?= htmlspecialchars($t['feedback_instructor'] ?? '') ?>" placeholder="Devolución / comentario para el alumno..." style="padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
                                                <button type="submit" class="btn-card-edit" style="cursor:pointer; text-align:center; padding:6px 10px; font-size:0.78rem;">Guardar Corrección</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- SECCIÓN DE EVALUACIONES Y EXÁMENES DE ALUMNOS (PARA PEPE Y ADMIN) -->
            <section class="dash-section" style="margin-top: 24px;">
                <div class="dash-section-header">
                    <h2>Exámenes y Evaluaciones de Alumnos </h2>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">Total Rendidos: <?= count($evaluacionesAlumnos) ?></span>
                </div>

                <?php if (empty($evaluacionesAlumnos)): ?>
                    <div class="empty-state" style="padding: 24px;">
                        <p style="font-weight: 600; color: var(--text-muted);">Aún ningún estudiante ha rendido la prueba final de tus cursos.</p>
                    </div>
                <?php else: ?>
                    <div style="background: #ffffff; border: 2px solid var(--text-main); border-radius: var(--radius); overflow-x: auto; box-shadow: 4px 4px 0px var(--text-main);">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #faf9f6; border-bottom: 2px solid var(--border-color);">
                                    <th style="padding: 14px 18px;">Estudiante</th>
                                    <th style="padding: 14px 18px;">Curso</th>
                                    <th style="padding: 14px 18px;">Puntaje</th>
                                    <th style="padding: 14px 18px;">Estado Oficial</th>
                                    <th style="padding: 14px 18px;">Retroalimentación</th>
                                    <th style="padding: 14px 18px;">Fecha</th>
                                    <th style="padding: 14px 18px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evaluacionesAlumnos as $ev): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 12px 18px;">
                                            <strong><?= htmlspecialchars($ev['alumno_nombre']) ?></strong><br>
                                            <small style="color: var(--text-muted);"><?= htmlspecialchars($ev['alumno_email']) ?></small>
                                        </td>
                                        <td style="padding: 12px 18px; font-weight: 800;"><?= htmlspecialchars($ev['curso_titulo']) ?></td>
                                        <td style="padding: 12px 18px; font-weight: 900; color: #166534;">
                                            <?= $ev['puntaje'] ?>/<?= $ev['total_preguntas'] ?>
                                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">(<?= round(($ev['puntaje']/$ev['total_preguntas'])*100) ?>%)</span>
                                        </td>
                                        <td style="padding: 12px 18px;">
                                            <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 0.78rem; background: <?= $ev['aprobado'] ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
                                                <?= htmlspecialchars($ev['estado_calificacion'] ?: ($ev['aprobado'] ? 'Aprobado' : 'Requiere Revisión')) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($ev['feedback'] ?: 'Sin observaciones aún') ?>
                                        </td>
                                        <td style="padding: 12px 18px; color: var(--text-muted); font-size: 0.82rem;">
                                            <?= date('d/m/Y H:i', strtotime($ev['fecha'])) ?>
                                        </td>
                                        <td style="padding: 12px 18px;">
                                            <a href="evaluacion.php?curso_id=<?= $ev['curso_id'] ?>" class="btn-card-edit" style="font-size: 0.8rem; padding: 6px 12px; text-decoration: none;">
                                                 Evaluar / Feedback &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- VENTAS RECIENTES PARA EL INSTRUCTOR -->
            <?php if (!empty($ultimasVentas)): ?>
                <section class="dash-section" style="margin-top: 20px;">
                    <h2>Últimas Ventas y Transacciones </h2>
                    <div style="background: #ffffff; border: 2px solid var(--text-main); border-radius: var(--radius); overflow-x: auto; box-shadow: 4px 4px 0px var(--text-main);">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #faf9f6; border-bottom: 2px solid var(--border-color);">
                                    <th style="padding: 14px 18px;">Transacción</th>
                                    <th style="padding: 14px 18px;">Curso</th>
                                    <th style="padding: 14px 18px;">Estudiante</th>
                                    <th style="padding: 14px 18px;">Método</th>
                                    <th style="padding: 14px 18px;">Monto</th>
                                    <th style="padding: 14px 18px;">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasVentas as $v): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 12px 18px; font-family: monospace; font-weight: 700; color: var(--primary);"><?= htmlspecialchars($v['transaccion_id']) ?></td>
                                        <td style="padding: 12px 18px; font-weight: 800;"><?= htmlspecialchars($v['curso_titulo']) ?></td>
                                        <td style="padding: 12px 18px;"><?= htmlspecialchars($v['comprador_nombre']) ?></td>
                                        <td style="padding: 12px 18px; text-transform: capitalize; font-weight: 600;"><?= htmlspecialchars($v['metodo_pago']) ?></td>
                                        <td style="padding: 12px 18px; font-weight: 900; color: #166534;">+$<?= number_format($v['monto'], 2) ?>USD</td>
                                        <td style="padding: 12px 18px; color: var(--text-muted); font-size: 0.82rem;"><?= date('d/m/Y H:i', strtotime($v['fecha_pago'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

        <?php else: ?>
            <!-- SECCIÓN PARA USUARIO ALUMNO -->
            <div class="dashboard-stats-grid">
                <div class="dash-stat-card">
                    <h3><?= count($misCursos) ?></h3>
                    <p>Cursos Inscriptos</p>
                </div>
                <div class="dash-stat-card">
                    <h3><?= htmlspecialchars($nombreMembresia) ?></h3>
                    <p>Plan de Membresía Actual</p>
                </div>
                <div class="dash-stat-card">
                    <h3 style="color: #10b981; font-size: 1.8rem;">Activa</h3>
                    <p>Estado de Cuenta</p>
                </div>
            </div>

            <section class="dash-section">
                <div class="dash-section-header">
                    <h2>Mis Cursos Inscriptos</h2>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">Total: <?= count($misCursos) ?></span>
                </div>

                <?php if (empty($misCursos)): ?>
                    <div class="empty-state">
                        <p style="font-weight: 700; color: var(--text-muted); font-size: 1.05rem;">Aún no te has inscripto en ningún curso.</p>
                        <a href="cursos.php" class="btn-primary" style="margin-top: 16px;">Explorar Catálogo de Cursos &rarr;</a>
                    </div>
                <?php else: ?>
                    <div class="dash-courses-grid">
                        <?php foreach ($misCursos as $curso): ?>
                            <div class="dash-course-card">
                                <div class="dash-card-header">
                                    <span class="badge-tag" style="background-color: #f7a8b8; color: #7d192c; font-size: 0.72rem; padding: 3px 8px; border-radius: 8px;">
                                        <?= htmlspecialchars($curso['categoria'] ?? 'General') ?>
                                    </span>
                                    <span style="color: #166534; font-weight: 800; font-size: 0.75rem;">Inscripto</span>
                                </div>

                                <h3><?= htmlspecialchars($curso['titulo']) ?></h3>
                                <p><?= htmlspecialchars(substr(strip_tags($curso['descripcion']), 0, 90)) ?>...</p>
                                
                                <div class="course-card-footer">
                                    <a href="ver_curso.php?id=<?= $curso['id'] ?>" class="btn-primary" style="width: 100%; text-align: center; padding: 10px;">Continuar Curso &rarr;</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- COMPROBANTES DE COMPRA DEL ALUMNO -->
            <?php if (!empty($misPagos)): ?>
                <section class="dash-section" style="margin-top: 20px;">
                    <h2>Mis Comprobantes de Pago </h2>
                    <div style="background: #ffffff; border: 2px solid var(--text-main); border-radius: var(--radius); overflow-x: auto; box-shadow: 4px 4px 0px var(--text-main);">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #faf9f6; border-bottom: 2px solid var(--border-color);">
                                    <th style="padding: 14px 18px;">Transacción</th>
                                    <th style="padding: 14px 18px;">Concepto / Detalle</th>
                                    <th style="padding: 14px 18px;">Método de Pago</th>
                                    <th style="padding: 14px 18px;">Monto</th>
                                    <th style="padding: 14px 18px;">Fecha</th>
                                    <th style="padding: 14px 18px;">Recibo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($misPagos as $p): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 12px 18px; font-family: monospace; font-weight: 700; color: var(--primary);"><?= htmlspecialchars($p['transaccion_id']) ?></td>
                                        <td style="padding: 12px 18px; font-weight: 800;">
                                            <span style="display: inline-block; font-size: 0.72rem; padding: 2px 8px; border-radius: 6px; font-weight: 800; text-transform: uppercase; margin-right: 6px; background-color: <?= $p['curso_id'] == 0 ? '#fef08a; color: #854d0e;' : '#e0e7ff; color: #3730a3;' ?>">
                                                <?= $p['curso_id'] == 0 ? 'Suscripción' : 'Curso' ?>
                                            </span>
                                            <?= htmlspecialchars($p['curso_titulo']) ?>
                                        </td>
                                        <td style="padding: 12px 18px; text-transform: capitalize;"><?= htmlspecialchars($p['metodo_pago']) ?></td>
                                        <td style="padding: 12px 18px; font-weight: 900; color: var(--primary);">$<?= number_format($p['monto'], 2) ?>USD</td>
                                        <td style="padding: 12px 18px; color: var(--text-muted); font-size: 0.82rem;"><?= date('d/m/Y H:i', strtotime($p['fecha_pago'])) ?></td>
                                        <td style="padding: 12px 18px;">
                                            <a href="pago_exitoso.php?pago_id=<?= $p['id'] ?>" class="btn-card-edit" style="font-size: 0.78rem; padding: 4px 10px;">Ver Recibo</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

        <?php endif; ?>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userDropdown = document.getElementById('userDropdown');

            if (userMenuBtn && userDropdown) {
                userMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                });

                document.addEventListener('click', (e) => {
                    if (!userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                        userDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
<?php include 'demo_switcher_widget.php'; ?>
</body>
</html>
