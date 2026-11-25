<?php
session_start();
require_once 'conexion.php';

$targetRole = $_POST['role'] ?? ($_GET['role'] ?? '');
$returnUrl  = $_POST['return'] ?? ($_GET['return'] ?? '');

// Sanitizar returnUrl para permitir solo rutas internas relativas de SkillHub
$safeReturn = '';
if ($returnUrl !== '' && preg_match('/^[a-zA-Z0-9_\-]+\.php(\?[a-zA-Z0-9_\-=&%]+)?$/', $returnUrl)) {
    $safeReturn = $returnUrl;
}

if ($targetRole === 'guest') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!in_array($targetRole, ['usuario', 'instructor', 'administrador'], true)) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rol = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$targetRole]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_name']    = $user['nombre'];
        $_SESSION['role']         = $user['rol'];
        $_SESSION['membresia_id'] = $user['membresia_id'] ?? 1;
    }
} catch (\PDOException $e) {
    header('Location: index.php');
    exit;
}

if ($targetRole === 'administrador') {
    header('Location: admin_dashboard.php');
    exit;
}

// Si venía viendo un curso específico, volver al mismo curso para comparar la vista al instante
if ($safeReturn !== '' && strpos($safeReturn, 'ver_curso.php') === 0) {
    header('Location: ' . $safeReturn);
    exit;
}

header('Location: dashboard.php');
exit;
