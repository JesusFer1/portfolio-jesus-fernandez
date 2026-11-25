<?php
session_start();
require_once 'conexion.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
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
    <title>Crear Cuenta | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/registro.css">
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
                <a href="login.php" class="btn-primary">Iniciar Sesión</a>
            </div>
        </div>
    </header>

    <main class="auth-page-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <h1>Crear tu cuenta </h1>
                <p>Sumate hoy a la comunidad de aprendizaje de SkillHub.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="auth_process.php" method="POST" class="auth-form">
                <input type="hidden" name="action" value="register">

                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: María Gómez" class="form-control">
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required placeholder="tu@email.com" class="form-control">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="Al menos 6 caracteres" class="form-control">
                </div>

                <div class="form-group">
                    <label>Tipo de Cuenta</label>
                    <div class="role-selector-grid">
                        <label class="role-option">
                            <input type="radio" name="rol" value="usuario" checked>
                            <div class="role-card-label">
                                <span class="role-icon"></span>
                                <span class="role-title">Usuario</span>
                                <span class="role-desc">Aprender e inscribirme a cursos</span>
                            </div>
                        </label>

                        <label class="role-option">
                            <input type="radio" name="rol" value="instructor">
                            <div class="role-card-label">
                                <span class="role-icon"></span>
                                <span class="role-title">Instructor</span>
                                <span class="role-desc">Crear y vender mis cursos</span>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit">Crear Mi Cuenta &rarr;</button>
            </form>

            <div class="auth-card-footer">
                ¿Ya tenés una cuenta? <a href="login.php">Iniciá sesión acá</a>
            </div>
        </div>
    </main>

</body>
</html>
