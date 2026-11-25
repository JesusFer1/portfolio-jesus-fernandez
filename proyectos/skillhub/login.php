<?php
session_start();
require_once 'conexion.php';

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'administrador') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/login.css">
</head>
<body>

    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <ul class="nav-links">
                <li><a href="cursos.php">Cursos</a></li>
                <li><a href="membresias.php">Membresías</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="registro.php" class="btn-primary">Registrarse</a>
            </div>
        </div>
    </header>

    <main class="auth-page-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <h1>¡Hola de nuevo! </h1>
                <p>Ingresá a tu cuenta para acceder a tus cursos y herramientas.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="auth_process.php" method="POST" class="auth-form">
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required placeholder="tu@email.com" class="form-control">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••" class="form-control">
                </div>

                <button type="submit" class="btn-auth-submit">Iniciar Sesión &rarr;</button>
            </form>
            <div class="auth-card-footer">
                ¿No tenés una cuenta? <a href="registro.php">Registrate gratis acá</a>
            </div>
        </div>
    </main>

<?php include 'demo_switcher_widget.php'; ?>
</body>
</html>
