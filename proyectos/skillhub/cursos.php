<?php
session_start();
require_once 'conexion.php';

$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'usuario';
$userTier = $_SESSION['membresia_id'] ?? 1;

// El administrador solo accede a su Panel de Control
if ($userId && $userRole === 'administrador') {
    header('Location: admin_dashboard.php');
    exit;
}

$busqueda  = trim($_GET['q'] ?? '');
$categoria = trim($_GET['cat'] ?? '');

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

try {
    $sql = "SELECT c.*, u.nombre as instructor_nombre 
            FROM cursos c 
            LEFT JOIN usuarios u ON c.instructor_id = u.id 
            WHERE 1=1";
    $params = [];

    if (!empty($busqueda)) {
        $sql .= " AND (c.titulo LIKE ? OR c.descripcion LIKE ? OR c.categoria LIKE ?)";
        $params[] = "%{$busqueda}%";
        $params[] = "%{$busqueda}%";
        $params[] = "%{$busqueda}%";
    }

    if (!empty($categoria)) {
        $sql .= " AND c.categoria = ?";
        $params[] = $categoria;
    }

    $sql .= " ORDER BY c.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cursos = $stmt->fetchAll();

} catch (\PDOException $e) {
    $cursos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Cursos | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/cursos.css">
</head>
<body>

    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <ul class="nav-links">
                <li><a href="cursos.php" style="color: var(--primary);">Cursos</a></li>
                <?php if (!$userId || $userRole === 'usuario'): ?>
                    <li><a href="membresias.php">Membresías</a></li>
                <?php endif; ?>
                <?php if ($userId && $userRole === 'instructor'): ?>
                    <li><a href="dashboard.php">Panel Instructor</a></li>
                    <li><a href="crear_curso.php" style="color: var(--primary); font-weight: 800;">+ Crear Curso</a></li>
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

    <main class="container" style="padding-top: 40px; padding-bottom: 80px;">
        
        <div class="catalog-hero">
            <h1>Explorar Cursos</h1>
            <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">Descubrí cursos prácticos de tecnología, diseño y negocios con lecciones estructuradas.</p>
        </div>

        <div class="search-filter-bar">
            <form action="cursos.php" method="GET" class="search-box-flex">
                <input type="text" name="q" class="search-input-lg" placeholder="Buscar por título, tecnología o palabras clave..." value="<?= htmlspecialchars($busqueda) ?>">
                <button type="submit" class="btn-primary" style="padding: 12px 28px;">Buscar</button>
            </form>

            <div class="category-chips">
                <a href="cursos.php" class="chip-btn <?= empty($categoria) ? 'active' : '' ?>">Todas</a>
                <a href="cursos.php?cat=Edición de Video" class="chip-btn <?= $categoria==='Edición de Video' ? 'active' : '' ?>">Edición de Video</a>
                <a href="cursos.php?cat=Programación" class="chip-btn <?= $categoria==='Programación' ? 'active' : '' ?>">Programación</a>
                <a href="cursos.php?cat=Diseño UI/UX" class="chip-btn <?= $categoria==='Diseño UI/UX' ? 'active' : '' ?>">Diseño UI/UX</a>
                <a href="cursos.php?cat=Marketing Digital" class="chip-btn <?= $categoria==='Marketing Digital' ? 'active' : '' ?>">Marketing Digital</a>
            </div>
        </div>

        <?php if (empty($cursos)): ?>
            <div class="empty-catalog">
                <h2>No encontramos cursos que coincidan con tu búsqueda</h2>
                <p style="color: var(--text-muted); margin-top: 8px;">Probá con otros términos o explorá las distintas categorías disponibles.</p>
                <a href="cursos.php" class="btn-primary" style="margin-top: 20px;">Ver todos los cursos</a>
            </div>
        <?php else: ?>
            <?php
                // Agrupar cursos por sección / categoría
                $cursosPorSeccion = [];
                foreach ($cursos as $c) {
                    $sec = trim($c['categoria'] ?? 'General') ?: 'General';
                    $cursosPorSeccion[$sec][] = $c;
                }
                $iconosSeccion = [
                    'Edición de Video'  => '',
                    'Programación'      => '',
                    'Diseño UI/UX'      => '',
                    'Marketing Digital' => '',
                    'Negocios'          => '',
                    'General'           => ''
                ];
            ?>
            <?php foreach ($cursosPorSeccion as $nombreSeccion => $listaCursos): ?>
                <section style="margin-bottom: 44px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid var(--border-color); padding-bottom:10px; margin-bottom:22px;">
                        <h2 style="font-size:1.45rem; font-weight:900; color:var(--text-main); margin:0;">
                            <?= $iconosSeccion[$nombreSeccion] ?? '' ?> <?= htmlspecialchars($nombreSeccion) ?>
                        </h2>
                        <span style="font-size:0.82rem; font-weight:800; color:var(--text-muted); background:#f1f5f9; padding:4px 12px; border-radius:999px;">
                            <?= count($listaCursos) ?> <?= count($listaCursos) === 1 ? 'curso' : 'cursos' ?>
                        </span>
                    </div>

                    <?php $esUnico = (count($listaCursos) === 1); ?>
                    <div class="<?= $esUnico ? '' : 'courses-catalog-grid' ?>">
                        <?php foreach ($listaCursos as $c): 
                            $esMiCurso = ($userId && $userRole === 'instructor' && $c['instructor_id'] == $userId);
                            $estaInscripto = ($userId && $userRole === 'usuario' && in_array($c['id'], $userEnrolledIds));
                        ?>
                            <div class="<?= $esUnico ? 'course-card-wide' : 'course-card-public' ?>">
                                <div class="course-thumb-container">
                                    <img src="<?= htmlspecialchars($c['imagen_url'] ?: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800') ?>" alt="<?= htmlspecialchars($c['titulo']) ?>">
                                </div>
                                <div class="course-card-body">
                                    <span class="badge-cat"><?= htmlspecialchars($c['categoria'] ?? 'General') ?></span>
                                    <h3><?= htmlspecialchars($c['titulo']) ?></h3>
                                    <p class="instructor-tag">Por <?= htmlspecialchars($c['instructor_nombre'] ?? 'Instructor') ?></p>
                                    <?php if ($esUnico && !empty($c['descripcion'])): ?>
                                        <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 18px;">
                                            <?= htmlspecialchars(mb_strimwidth(strip_tags($c['descripcion']), 0, 210, '...')) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="card-public-footer">
                                        <?php if ($esMiCurso): ?>
                                            <span style="color: #6d28d9; font-weight: 800; font-size: 0.85rem;">Tu Curso</span>
                                            <div style="display:flex; gap:6px;">
                                                <a href="editar_curso.php?id=<?= $c['id'] ?>" class="btn-secondary-link" style="padding: 7px 12px; font-size: 0.8rem;">Editar</a>
                                                <a href="ver_curso.php?id=<?= $c['id'] ?>" class="btn-primary" style="padding: 8px 14px; font-size: 0.82rem;">Ver Aula &rarr;</a>
                                            </div>
                                        <?php elseif ($userRole === 'instructor'): ?>
                                            <span class="price-display"><?= $c['precio'] >0 ? '$' . number_format($c['precio'], 2) . ' USD' : 'Gratis' ?></span>
                                            <a href="ver_curso.php?id=<?= $c['id'] ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem;">Ver Curso &rarr;</a>
                                        <?php elseif ($estaInscripto): ?>
                                            <span style="color: #166534; font-weight: 800; font-size: 0.85rem;">Inscripto</span>
                                            <a href="ver_curso.php?id=<?= $c['id'] ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem; background-color: var(--text-main);">Ir al Aula &rarr;</a>
                                        <?php elseif ($userId && $userTier == 3): ?>
                                            <span style="color: #854d0e; font-weight: 800; font-size: 0.82rem;">Incluido en VIP</span>
                                            <a href="ver_curso.php?id=<?= $c['id'] ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem; background-color: #854d0e; border-color: #713f12;">Inscribirme &rarr;</a>
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
            <?php endforeach; ?>
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
