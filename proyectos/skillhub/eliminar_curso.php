<?php
session_start();
require_once 'conexion.php';

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'usuario';
$cursoId  = intval($_GET['id'] ?? 0);

if (!$userId || !in_array($userRole, ['instructor', 'administrador']) || !$cursoId) {
    header('Location: index.php');
    exit;
}

try {
    $stmtCheck = $pdo->prepare("SELECT instructor_id FROM cursos WHERE id = ?");
    $stmtCheck->execute([$cursoId]);
    $c = $stmtCheck->fetch();

    if ($c && ($userRole === 'administrador' || $c['instructor_id'] == $userId)) {
        // Eliminar registros asociados de forma segura
        $pdo->prepare("DELETE FROM lecciones WHERE curso_id = ?")->execute([$cursoId]);
        $pdo->prepare("DELETE FROM evaluaciones_resultados WHERE curso_id = ?")->execute([$cursoId]);
        $pdo->prepare("DELETE FROM inscripciones WHERE curso_id = ?")->execute([$cursoId]);
        $pdo->prepare("DELETE FROM cursos WHERE id = ?")->execute([$cursoId]);
    }
} catch (\PDOException $e) {
    die("Error al eliminar curso: " . $e->getMessage());
}

if ($userRole === 'administrador' && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin_dashboard') !== false) {
    header('Location: admin_dashboard.php');
} else {
    header('Location: dashboard.php');
}
exit;
?>