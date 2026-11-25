<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['auth_error'] = "Debes iniciar sesión para inscribirte a un curso.";
    header('Location: login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$userTier = $_SESSION['membresia_id'] ?? 1;
$cursoId  = intval($_POST['curso_id'] ?? 0);

if (!$cursoId) {
    header('Location: cursos.php');
    exit;
}

try {
    // 1. Obtener precio y datos del curso
    $stmtC = $pdo->prepare("SELECT id, precio, titulo FROM cursos WHERE id = ?");
    $stmtC->execute([$cursoId]);
    $curso = $stmtC->fetch();

    if (!$curso) {
        header('Location: cursos.php');
        exit;
    }

    // 2. Verificar si ya está inscripto
    $stmtCheck = $pdo->prepare("SELECT id FROM inscripciones WHERE usuario_id = ? AND curso_id = ?");
    $stmtCheck->execute([$userId, $cursoId]);
    if ($stmtCheck->fetch()) {
        header('Location: ver_curso.php?id=' . $cursoId);
        exit;
    }

    // 3. Verificar si tiene acceso legítimo:
    // - Es curso gratuito (precio == 0)
    // - O tiene membresía VIP (Nivel 3)
    // - O ya existe un pago confirmado en la tabla pagos
    $tienePago = false;
    $stmtPago = $pdo->prepare("SELECT id FROM pagos WHERE usuario_id = ? AND curso_id = ? AND estado = 'completado'");
    $stmtPago->execute([$userId, $cursoId]);
    if ($stmtPago->fetch()) {
        $tienePago = true;
    }

    $puedeInscribirse = ($curso['precio'] <= 0) || ($userTier == 3) || $tienePago;

    if (!$puedeInscribirse) {
        // Redirigir al Checkout real para pagar el curso
        header('Location: checkout.php?curso_id=' . $cursoId);
        exit;
    }

    // 4. Registrar inscripción válida en la base de datos
    $stmtIns = $pdo->prepare("INSERT INTO inscripciones (usuario_id, curso_id) VALUES (?, ?)");
    $stmtIns->execute([$userId, $cursoId]);

    // Si se inscribe por Membresía VIP y no tenía pago, registrar comprobante bonificado
    if ($userTier == 3 && !$tienePago && $curso['precio'] >0) {
        $txnId = 'TXN-VIP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $stmtVIP = $pdo->prepare("INSERT INTO pagos (usuario_id, curso_id, monto, metodo_pago, transaccion_id, estado) VALUES (?, ?, 0.00, 'membresia_vip', ?, 'completado')");
        $stmtVIP->execute([$userId, $cursoId, $txnId]);
    }

    header('Location: ver_curso.php?id=' . $cursoId . '&bienvenido=1');
    exit;

} catch (\PDOException $e) {
    die("Error al procesar inscripción: " . $e->getMessage());
}
?>
