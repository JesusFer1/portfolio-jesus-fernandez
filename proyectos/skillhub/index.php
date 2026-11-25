<?php
session_start();
require_once 'conexion.php';

$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'usuario';

// El administrador solo ve su Panel de Control
if ($userId && $userRole === 'administrador') {
    header('Location: admin_dashboard.php');
    exit;
}

// Obtener inscripciones del estudiante (solo para rol usuario)
$userEnrolledIds = [];
if ($userId && $userRole === 'usuario') {
    try {
        $stmtE = $pdo->prepare("SELECT curso_id FROM inscripciones WHERE usuario_id = ?");
        $stmtE->execute([$userId]);
        $userEnrolledIds = $stmtE->fetchAll(PDO::FETCH_COLUMN);
    } catch (\PDOException $e) {
        $userEnrolledIds = [];
    }
}

// Obtener cursos destacados
try {
    $stmt = $pdo->query("SELECT c.*, u.nombre as instructor_nombre FROM cursos c LEFT JOIN usuarios u ON c.instructor_id = u.id ORDER BY c.id DESC LIMIT 4");
    $cursosDestacados = $stmt->fetchAll();
} catch (\PDOException $e) {
    $cursosDestacados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillHub - Plataforma de Venta y Aprendizaje de Cursos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/index.css">
</head>
<body>

    <!-- HEADER / NAVEGACIÓN -->
    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <ul class="nav-links">
                <li><a href="cursos.php">Cursos</a></li>
                <?php if (!$userId || $userRole === 'usuario'): ?>
                    <li><a href="membresias.php">Membresías</a></li>
                <?php endif; ?>
                <?php if ($userId && $userRole === 'instructor'): ?>
                    <li><a href="dashboard.php">Panel Instructor</a></li>
                <?php endif; ?>
            </ul>
            <div class="auth-buttons">
                <?php if ($userId): ?>
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
                                <li><a href="dashboard.php"><?= $userRole === 'instructor' ? 'Panel Instructor' : 'Mi Aprendizaje' ?></a></li>
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
                <?php else: ?>
                    <a href="login.php" class="btn-secondary-link">Iniciar Sesión</a>
                    <a href="registro.php" class="btn-primary">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        
        <!-- HERO SECTION -->
        <section class="container hero-main">
            <div class="hero-grid-home">
                <div>
                    <?php if ($userId && $userRole === 'instructor'): ?>
                        <span class="badge-tag" style="background:#ede9fe; color:#5b21b6; border-color:#7c3aed;">Modo Instructor · <?= htmlspecialchars($userName) ?></span>
                        <h1 class="hero-title-lg">Creá y gestioná tus cursos desde un solo lugar.</h1>
                        <p class="hero-subtext" style="margin-bottom: 28px;">Publicá lecciones en video, subí material PDF, corregí evaluaciones de tus alumnos y seguí tus ventas en tiempo real.</p>
                        <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                            <a href="crear_curso.php" class="btn-primary" style="padding: 14px 28px; font-size: 1.05rem;">+ Crear Nuevo Curso</a>
                            <a href="dashboard.php" class="btn-secondary-link" style="font-size: 1rem;">Ir a Panel Instructor &rarr;</a>
                        </div>
                    <?php else: ?>
                        <span class="badge-tag"><?= $userId ? ' Modo Estudiante · ' . htmlspecialchars($userName) : 'Aprende y Enseña sin límites ' ?></span>
                        <h1 class="hero-title-lg">La plataforma donde las habilidades cobran vida.</h1>
                        <p class="hero-subtext" style="margin-bottom: 28px;">Diseña cursos con un creador visual arrastrar y soltar estilo WordPress, accede a lecciones prácticas de alta demanda e interactúa con la comunidad en el foro.</p>
                        
                        <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                            <a href="cursos.php" class="btn-primary" style="padding: 14px 28px; font-size: 1.05rem;">Explorar Cursos &rarr;</a>
                            <?php if ($userId): ?>
                                <a href="dashboard.php" class="btn-secondary-link" style="font-size: 1rem;">Ir a Mi Aprendizaje</a>
                            <?php else: ?>
                                <a href="registro.php" class="btn-secondary-link" style="font-size: 1rem;">Crear Cuenta</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="hero-visual-card">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800" alt="Estudiantes en SkillHub">
                    <span class="floating-pill">Visual Page Builder Integrado</span>
                </div>
            </div>
        </section>

        <!-- NAVEGACIÓN DE ESTADÍSTICAS -->
        <section class="container stats-bar">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>2</h3>
                    <p>Roles Principales (Usuario e Instructor)</p>
                </div>
                <div class="stat-item">
                    <h3>100%</h3>
                    <p>Visual Builder Drag & Drop en Localhost</p>
                </div>
                <div class="stat-item">
                    <h3>3</h3>
                    <p>Membresías con Beneficios (Básica, Pro, VIP)</p>
                </div>
                <div class="stat-item">
                    <h3>4</h3>
                    <p>Áreas de Formación con Guías PDF</p>
                </div>
            </div>
        </section>

        <!-- SECCIÓN DE CURSOS DESTACADOS -->
        <section class="container" style="margin-bottom: 90px;">
            <div class="section-header">
                <span class="badge-tag-alt">Catálogo</span>
                <h2>Cursos Más Populares </h2>
                <p class="section-speech">Comenzá a aprender hoy con las mejores lecciones prácticas.</p>
            </div>

            <div class="courses-catalog-grid">
                <?php foreach ($cursosDestacados as $c):
                    $esMiCurso = ($userId && $userRole === 'instructor' && $c['instructor_id'] == $userId);
                    $estaInscripto = ($userId && $userRole === 'usuario' && in_array($c['id'], $userEnrolledIds));
                ?>
                    <div class="course-card-public">
                        <div class="course-thumb-container">
                            <img src="<?= htmlspecialchars($c['imagen_url'] ?: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800') ?>" alt="<?= htmlspecialchars($c['titulo']) ?>">
                            <?php if (!empty($c['es_drag_drop'])): ?>
                                <span class="badge-builder">Visual Page</span>
                            <?php endif; ?>
                        </div>
                        <div class="course-card-body">
                            <span class="badge-cat"><?= htmlspecialchars($c['categoria'] ?? 'General') ?></span>
                            <h3><?= htmlspecialchars($c['titulo']) ?></h3>
                            <p class="instructor-tag">Por <?= htmlspecialchars($c['instructor_nombre'] ?? 'Instructor') ?></p>
                            <div class="card-public-footer">
                                <?php if ($esMiCurso): ?>
                                    <span style="color: #6d28d9; font-weight: 800; font-size: 0.84rem;">Tu Curso</span>
                                    <div style="display:flex; gap:6px;">
                                        <a href="editar_curso.php?id=<?= $c['id'] ?>" class="btn-secondary-link" style="padding: 7px 12px; font-size: 0.8rem;">Editar</a>
                                        <a href="ver_curso.php?id=<?= $c['id'] ?>" class="btn-primary" style="padding: 8px 14px; font-size: 0.82rem;">Ver Aula &rarr;</a>
                                    </div>
                                <?php elseif ($estaInscripto): ?>
                                    <span style="color: #166534; font-weight: 800; font-size: 0.84rem;">Inscripto</span>
                                    <a href="ver_curso.php?id=<?= $c['id'] ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem; background-color: var(--text-main);">Ir al Aula &rarr;</a>
                                <?php else: ?>
                                    <span class="price-display"><?= $c['precio'] >0 ? '$' . number_format($c['precio'], 2) . ' USD' : 'Gratis' ?></span>
                                    <a href="ver_curso.php?id=<?= $c['id'] ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem;">Ver Curso &rarr;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- SECCIÓN DE PROPUESTA DE VALOR Y EXPERIENCIA VISUAL -->
        <section class="container how-it-works">
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 24px; padding: 44px 40px; box-shadow: var(--shadow-md); display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 38px; align-items: center;">
                <div>
                    <span class="badge-tag-alt" style="margin-bottom: 12px; display: inline-block;">Metodología SkillHub </span>
                    <h2 style="font-size: 2.1rem; font-weight: 900; line-height: 1.18; color: var(--text-main); margin-bottom: 14px;">
                        Aprendé haciendo, con acompañamiento real en cada clase.
                    </h2>
                    <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.65; font-weight: 500; margin-bottom: 22px;">
                        En SkillHub no mirás videos pasivamente: cada curso combina lecciones prácticas guiadas, documentación oficial descargable con atajos, trabajos prácticos corregidos por el instructor y una evaluación final que valida lo que realmente sabés hacer.
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 26px;">
                        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 14px;">
                            <strong style="display: block; font-size: 0.92rem; color: var(--text-main); margin-bottom: 4px;">Manuales &amp; PDFs</strong>
                            <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Guías técnicas y atajos de teclado listos para descargar en cada curso.</span>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 14px;">
                            <strong style="display: block; font-size: 0.92rem; color: var(--text-main); margin-bottom: 4px;">Corrección Docente</strong>
                            <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Enviá tus prácticas clase por clase y recibí devolución personalizada.</span>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 14px;">
                            <strong style="display: block; font-size: 0.92rem; color: var(--text-main); margin-bottom: 4px;">Evaluación Final</strong>
                            <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Validá tus conocimientos con exámenes prácticos específicos por especialidad.</span>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 14px;">
                            <strong style="display: block; font-size: 0.92rem; color: var(--text-main); margin-bottom: 4px;">Certificado Verificable</strong>
                            <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Desbloqueá tu diploma oficial al aprobar las prácticas y el examen final.</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
                        <a href="cursos.php" class="btn-primary" style="padding: 12px 24px;">Explorar Cursos por Sección &rarr;</a>
                    </div>
                </div>

                <div style="position: relative;">
                    <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=900" alt="Aprendizaje práctico en SkillHub" style="width: 100%; height: 370px; object-fit: cover; border-radius: 18px; display: block; border: 1px solid var(--border-color); box-shadow: var(--shadow-lg);">
                    <div style="position: absolute; bottom: 16px; left: 16px; right: 16px; background: rgba(15, 23, 42, 0.88); backdrop-filter: blur(8px); color: #ffffff; padding: 14px 18px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                        <div>
                            <strong style="display: block; font-size: 0.92rem;">Formación 100% Práctica</strong>
                            <span style="font-size: 0.78rem; color: #cbd5e1;">Edición de Video · Programación · UI/UX · Marketing</span>
                        </div>
                        <span style="background: #10b981; color: #042f2e; font-weight: 900; font-size: 0.76rem; padding: 5px 10px; border-radius: 999px; white-space: nowrap;">Certificado</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- MANIFIESTO FOOTER -->
        <section class="manifesto-section">
            <div class="container manifesto-content">
                <span class="manifesto-quote">“</span>
                <h2>Transformando la enseñanza y venta de cursos online.</h2>
                <p style="font-weight: 600; opacity: 0.9; max-width: 600px; margin: 0 auto 28px;">Plataforma desarrollada con arquitectura limpia en PHP, PDO SQLite/MySQL, Creador Visual y CSS independiente para funcionamiento 100% en localhost.</p>
                <?php if ($userId): ?>
                    <a href="dashboard.php" class="btn-primary" style="background-color: var(--text-main); border: 2px solid #ffffff; padding: 14px 28px;">Ir a Mi Panel &rarr;</a>
                <?php else: ?>
                    <a href="registro.php" class="btn-primary" style="background-color: var(--text-main); border: 2px solid #ffffff; padding: 14px 28px;">Crear Mi Cuenta Gratis &rarr;</a>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <!-- FOOTER GLOBAL -->
    <footer style="background-color: var(--bg-alt); border-top: 3px solid var(--text-main); padding: 40px 20px 30px; margin-top: 60px;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <a href="index.php" class="logo" style="font-size: 1.5rem;">SkillHub<span class="logo-dot">.</span></a>
                <p style="color: var(--text-muted); font-size: 0.88rem; font-weight: 600; margin-top: 6px;">Plataforma de Cursos, Aulas Virtuales, Evaluaciones y Certificados.</p>
            </div>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.92rem; font-weight: 700;">
                <a href="cursos.php" style="color: var(--text-main); text-decoration: none;">Cursos</a>
                <a href="membresias.php" style="color: var(--text-main); text-decoration: none;">Membresías</a>
                <?php if ($userId): ?>
                    <a href="dashboard.php" style="color: var(--text-main); text-decoration: none;">Mi Panel</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="container" style="border-top: 1px solid var(--border-color); margin-top: 24px; padding-top: 16px; text-align: center; font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
            © <?= date('Y') ?>SkillHub. Funcionamiento 100% nativo en PHP & XAMPP Localhost.
        </div>
    </footer>

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
