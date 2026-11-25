<?php
session_start();
require_once 'conexion.php';

$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'usuario';
$userTier = $_SESSION['membresia_id'] ?? 1;

if ($userId && $userRole === 'administrador') {
    header('Location: admin_dashboard.php');
    exit;
}

try {
    $stmtM = $pdo->query("SELECT * FROM membresias ORDER BY nivel ASC");
    $membresias = $stmtM->fetchAll();
} catch (\PDOException $e) {
    $membresias = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membresías y Planes | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/membresias.css">
</head>
<body>

    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <ul class="nav-links">
                <li><a href="cursos.php">Cursos</a></li>
                <li><a href="membresias.php" style="color: var(--primary);">Membresías</a></li>
            </ul>
            <div class="auth-buttons">
                <?php if ($userId): ?>
                    <a href="dashboard.php" class="btn-primary">Mi Panel &rarr;</a>
                <?php else: ?>
                    <a href="login.php" class="btn-secondary-link">Iniciar Sesión</a>
                    <a href="registro.php" class="btn-primary">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 50px; padding-bottom: 80px;">
        
        <div class="memberships-hero">
            <h1>Elegí el plan perfecto para tu aprendizaje </h1>
            <p>Accede a contenidos exclusivos, soporte en el foro y herramientas según tu nivel.</p>

            <?php if ($userId): ?>
                <div class="membership-current-status">
                    <span>Tu plan actual:</span>
                    <strong style="color: var(--primary); font-size: 1.1rem;">
                        <?php 
                            if ($userTier == 3) echo "VIP Gold (Nivel 3)";
                            elseif ($userTier == 2) echo "Pro Silver (Nivel 2)";
                            else echo "Básica (Gratis)";
                        ?>
                    </strong>
                </div>
            <?php endif; ?>
        </div>

        <div class="pricing-grid-3">
            
            <!-- PLAN 1: BÁSICA -->
            <div class="membership-card">
                <h3 class="membership-title">Básica</h3>
                <p class="membership-speech">Ideal para explorar y conocer la plataforma sin costo.</p>
                <div class="membership-price">$0 <span>/ siempre</span></div>
                <ul class="membership-features-list">
                    <li><span class="check"></span>Acceso a cursos gratuitos</li>
                    <li><span class="check"></span>Lectura del foro de comunidad</li>
                    <li><span class="check"></span>Perfil de usuario estándar</li>
                </ul>
                <?php if (!$userId): ?>
                    <a href="registro.php" class="btn-select-plan" style="text-align: center; text-decoration: none; display: block;">Comenzar Gratis</a>
                <?php elseif ($userTier == 1): ?>
                    <button class="btn-select-plan" style="background-color: var(--border-color); color: var(--text-main); cursor: default;">Tu Plan Actual</button>
                <?php else: ?>
                    <form action="procesar_membresia.php" method="POST">
                        <input type="hidden" name="membresia_id" value="1">
                        <button type="submit" class="btn-select-plan" style="background-color: var(--text-main);">Cambiar a Básica</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- PLAN 2: PRO -->
            <div class="membership-card">
                <span class="membership-badge-top">Recomendado</span>
                <h3 class="membership-title">Pro</h3>
                <p class="membership-speech">Para estudiantes dedicados que quieren avanzar más rápido.</p>
                <div class="membership-price">$19.99 <span>/ mes</span></div>
                <ul class="membership-features-list">
                    <li><span class="check"></span>Acceso a cursos estándar</li>
                    <li><span class="check"></span>Publicar y responder en el Foro</li>
                    <li><span class="check"></span>Certificados de finalización</li>
                    <li><span class="check"></span>20% de descuento en cursos premium</li>
                </ul>
                <?php if ($userTier == 2): ?>
                    <button class="btn-select-plan" style="background-color: var(--border-color); color: var(--text-main); cursor: default;">Tu Plan Actual</button>
                <?php else: ?>
                    <a href="checkout_membresia.php?id=2" class="btn-select-plan" style="text-align: center; text-decoration: none; display: block;">
                        Obtener Pro &rarr;
                    </a>
                <?php endif; ?>
            </div>

            <!-- PLAN 3: VIP -->
            <div class="membership-card vip-featured">
                <span class="membership-badge-top" style="background-color: #f7a8b8; color: #7d192c;">Acceso Total</span>
                <h3 class="membership-title">VIP Master</h3>
                <p class="membership-speech">Acceso ilimitado a todos los cursos y beneficios exclusivos.</p>
                <div class="membership-price">$49.99 <span>/ mes</span></div>
                <ul class="membership-features-list">
                    <li><span class="check"></span> <strong>Acceso ILIMITADO a TODOS los cursos</strong></li>
                    <li><span class="check"></span>Acceso completo a todas las Aulas Virtuales y videos en HD</li>
                    <li><span class="check"></span>Certificado Master con verificación QR</li>
                    <li><span class="check"></span>Mentoría directa y soporte prioritario</li>
                </ul>
                <?php if ($userTier == 3): ?>
                    <button class="btn-select-plan" style="background-color: var(--border-color); color: var(--text-main); cursor: default;">Tu Plan Actual</button>
                <?php else: ?>
                    <a href="checkout_membresia.php?id=3" class="btn-select-plan" style="background-color: var(--primary); text-align: center; text-decoration: none; display: block;">
                        Ser VIP Master &rarr;
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </main>

<?php include 'demo_switcher_widget.php'; ?>
</body>
</html>
