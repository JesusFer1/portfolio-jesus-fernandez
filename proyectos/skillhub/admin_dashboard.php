<?php
session_start();
require_once 'conexion.php';

// Seguridad: Solo Administradores
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrador') {
    header('Location: dashboard.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Admin';

try {
    // 1. Estadísticas Globales
    $totalUsuarios     = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $totalCursos       = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
    $totalInscripciones= $pdo->query("SELECT COUNT(*) FROM inscripciones")->fetchColumn();
    $totalTemasForo    = $pdo->query("SELECT COUNT(*) FROM foro_temas")->fetchColumn();
    $totalRecaudado    = $pdo->query("SELECT SUM(monto) FROM pagos WHERE estado = 'completado'")->fetchColumn() ?: 0;
    $totalPagos        = $pdo->query("SELECT COUNT(*) FROM pagos")->fetchColumn();

    // 2. Lista de Usuarios
    $stmtU = $pdo->query("SELECT u.*, m.nombre as nombre_membresia FROM usuarios u LEFT JOIN membresias m ON u.membresia_id = m.id ORDER BY u.id DESC");
    $usuarios = $stmtU->fetchAll();

    // 3. Lista de Cursos
    $stmtC = $pdo->query("SELECT c.*, u.nombre as instructor_nombre FROM cursos c LEFT JOIN usuarios u ON c.instructor_id = u.id ORDER BY c.id DESC");
    $cursos = $stmtC->fetchAll();

    // 4. Membresías para dropdowns
    $membresiasList = $pdo->query("SELECT * FROM membresias ORDER BY nivel ASC")->fetchAll();

    // 5. Últimas Transacciones Globales
    $stmtP = $pdo->query("
        SELECT p.*, u.nombre as usuario_nombre, u.email as usuario_email,
               COALESCE(c.titulo, 'Membresía ' || CASE WHEN p.monto >= 30 THEN 'VIP Master' ELSE 'Pro' END) as concepto
        FROM pagos p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN cursos c ON p.curso_id = c.id
        ORDER BY p.id DESC LIMIT 8
    ");
    $ultimosPagos = $stmtP->fetchAll();

} catch (\PDOException $e) {
    die("Error en el Panel Admin: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración Master | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/admin_dashboard.css">
</head>
<body>

    <header>
        <div class="container nav-container">
            <a href="admin_dashboard.php" class="logo">
                SkillHub<span class="logo-dot">.</span> <span style="font-size: 0.72rem; background: var(--mustard); color: #000; padding: 3px 8px; border-radius: 6px; font-weight: 900; vertical-align: middle; margin-left: 6px; text-transform: uppercase;">Admin</span>
            </a>
            <div style="display: flex; align-items: center; gap: 14px; font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">
                <span>Panel Exclusivo de Administración</span>
            </div>
            <div class="auth-buttons" style="display: flex; align-items: center; gap: 12px;">
                <span style="font-weight: 800; font-size: 0.88rem; color: var(--text-main);"> <?= htmlspecialchars($userName) ?></span>
                <a href="logout.php" class="btn-action-sm" style="background-color: #fee2e2; color: #dc2626; border-color: #dc2626; text-decoration: none; padding: 8px 14px;">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <main class="container admin-container">
        
        <div class="admin-header">
            <div>
                <span style="background-color: var(--mustard); color: #000; font-weight: 900; padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; text-transform: uppercase;">Administrador Master</span>
                <h1>Panel de Control Global</h1>
                <p style="opacity: 0.85; font-size: 0.95rem; margin-top: 4px;">Supervisión y control de usuarios, roles, membresías, cursos y contenidos de la plataforma.</p>
            </div>
            <div>
                <a href="crear_curso.php" class="btn-primary">+ Crear Nuevo Curso</a>
            </div>
        </div>

        <!-- TARJETAS DE MÉTRICAS GLOBALES -->
        <div class="dashboard-stats-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
            <div class="dash-stat-card">
                <h3><?= $totalUsuarios ?></h3>
                <p>Usuarios Registrados</p>
            </div>
            <div class="dash-stat-card">
                <h3><?= $totalCursos ?></h3>
                <p>Cursos Publicados</p>
            </div>
            <div class="dash-stat-card">
                <h3><?= $totalInscripciones ?></h3>
                <p>Inscripciones Totales</p>
            </div>
        </div>

        <!-- TABLA DE GESTIÓN DE USUARIOS ROLES Y MEMBRESÍAS -->
        <div class="admin-table-card">
            <h2>Gestión de Usuarios (<?= count($usuarios) ?>)</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol en la Plataforma</th>
                        <th>Nivel de Membresía</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <form action="procesar_admin.php" method="POST" style="display: flex; gap: 6px;">
                                    <input type="hidden" name="accion" value="cambiar_rol">
                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                    <select name="rol" class="select-sm" onchange="this.form.submit()">
                                        <option value="usuario" <?= $u['rol']==='usuario'?'selected':'' ?>>Usuario (Estudiante)</option>
                                        <option value="instructor" <?= $u['rol']==='instructor'?'selected':'' ?>>Instructor</option>
                                        <option value="administrador" <?= $u['rol']==='administrador'?'selected':'' ?>>Administrador</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <form action="procesar_admin.php" method="POST">
                                    <input type="hidden" name="accion" value="cambiar_membresia">
                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                    <select name="membresia_id" class="select-sm" onchange="this.form.submit()">
                                        <?php foreach ($membresiasList as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= $u['membresia_id']==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <form action="procesar_admin.php" method="POST" onsubmit="return confirm('¿Eliminar a este usuario permanentemente?');">
                                        <input type="hidden" name="accion" value="eliminar_usuario">
                                        <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-action-sm" style="background-color: #fee2e2; color: #dc2626; border-color: #dc2626; cursor: pointer;">Eliminar</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; font-weight: 800; color: var(--primary);">(Tu cuenta)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- TABLA DE GESTIÓN DE CURSOS -->
        <div class="admin-table-card">
            <h2>Gestión de Cursos (<?= count($cursos) ?>)</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Instructor</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos as $c): ?>
                        <tr>
                            <td>#<?= $c['id'] ?></td>
                            <td><strong><?= htmlspecialchars($c['titulo']) ?></strong></td>
                            <td><?= htmlspecialchars($c['categoria'] ?? 'General') ?></td>
                            <td><?= htmlspecialchars($c['instructor_nombre'] ?? 'Sistema') ?></td>
                            <td>$<?= number_format($c['precio'], 2) ?>USD</td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="ver_curso.php?id=<?= $c['id'] ?>" class="btn-action-sm" style="background-color: var(--bg-main); text-decoration: none;">Ver</a>
                                    <a href="editar_curso.php?id=<?= $c['id'] ?>" class="btn-action-sm" style="background-color: var(--mustard); color: #000; text-decoration: none;">Editar</a>
                                    <a href="eliminar_curso.php?id=<?= $c['id'] ?>" onclick="return confirm('¿Eliminar este curso permanentemente?');" class="btn-action-sm" style="background-color: #fee2e2; color: #dc2626; border-color: #dc2626; text-decoration: none;">Eliminar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

<?php include 'demo_switcher_widget.php'; ?>
</body>
</html>
