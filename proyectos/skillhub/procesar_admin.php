<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrador') {
    header('Location: dashboard.php');
    exit;
}

$accion    = $_POST['accion'] ?? '';
$usuarioId = intval($_POST['usuario_id'] ?? 0);

try {
    if ($accion === 'cambiar_rol') {
        $nuevoRol = $_POST['rol'] ?? 'usuario';
        if (in_array($nuevoRol, ['usuario', 'instructor', 'administrador']) && $usuarioId) {
            $stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
            $stmt->execute([$nuevoRol, $usuarioId]);
        }
    } elseif ($accion === 'cambiar_membresia') {
        $nuevaMembresia = intval($_POST['membresia_id'] ?? 1);
        if ($usuarioId && $nuevaMembresia >= 1 && $nuevaMembresia <= 3) {
            $stmt = $pdo->prepare("UPDATE usuarios SET membresia_id = ? WHERE id = ?");
            $stmt->execute([$nuevaMembresia, $usuarioId]);
        }
    } elseif ($accion === 'eliminar_usuario') {
        if ($usuarioId && $usuarioId != $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM inscripciones WHERE usuario_id = ?")->execute([$usuarioId]);
            $pdo->prepare("DELETE FROM foro_respuestas WHERE usuario_id = ?")->execute([$usuarioId]);
            $pdo->prepare("DELETE FROM foro_temas WHERE usuario_id = ?")->execute([$usuarioId]);
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$usuarioId]);
        }
    }
} catch (\PDOException $e) {
    die("Error al procesar acción administrativa: " . $e->getMessage());
}

header('Location: admin_dashboard.php');
exit;
?>
