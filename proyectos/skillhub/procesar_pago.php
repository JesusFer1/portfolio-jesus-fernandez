<?php
session_start();
require_once 'conexion.php';

$cursoId    = intval($_POST['curso_id'] ?? 0);
$monto      = floatval($_POST['monto'] ?? 0);
$metodoPago = trim($_POST['metodo_pago'] ?? 'tarjeta');

if (!$cursoId) {
    header('Location: cursos.php');
    exit;
}

try {
    // 1. Manejo del Usuario (Logueado o Registro Instantáneo en Checkout)
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        $nombre   = trim($_POST['nombre'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($nombre) || empty($email)) {
            die("Por favor completa los datos del comprador.");
        }

        // Verificar si el correo ya existe
        $stmtUser = $pdo->prepare("SELECT id, password, nombre, rol, membresia_id FROM usuarios WHERE email = ?");
        $stmtUser->execute([$email]);
        $existing = $stmtUser->fetch();

        if ($existing) {
            // Si existe la cuenta, asociar y loguear
            $userId = $existing['id'];
            $_SESSION['user_id']      = $existing['id'];
            $_SESSION['user_name']    = $existing['nombre'];
            $_SESSION['role']         = $existing['rol'];
            $_SESSION['membresia_id'] = $existing['membresia_id'] ?? 1;
        } else {
            // Crear nueva cuenta de estudiante
            $hash = password_hash(!empty($password) ? $password : 'Alumno2026!', PASSWORD_BCRYPT);
            $stmtNew = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, membresia_id) VALUES (?, ?, ?, 'usuario', 1)");
            $stmtNew->execute([$nombre, $email, $hash]);

            $userId = $pdo->lastInsertId();
            $_SESSION['user_id']      = $userId;
            $_SESSION['user_name']    = $nombre;
            $_SESSION['role']         = 'usuario';
            $_SESSION['membresia_id'] = 1;
        }
    }

    // 2. Generar ID único de transacción
    $txnId = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    // 3. Registrar el Pago en la base de datos
    $stmtPago = $pdo->prepare("
        INSERT INTO pagos (usuario_id, curso_id, monto, metodo_pago, transaccion_id, estado) 
        VALUES (?, ?, ?, ?, ?, 'completado')
    ");
    $stmtPago->execute([$userId, $cursoId, $monto, $metodoPago, $txnId]);
    $pagoId = $pdo->lastInsertId();

    // 4. Inscribir al estudiante al curso (si no lo está ya)
    $stmtCheck = $pdo->prepare("SELECT id FROM inscripciones WHERE usuario_id = ? AND curso_id = ?");
    $stmtCheck->execute([$userId, $cursoId]);
    if (!$stmtCheck->fetch()) {
        $stmtIns = $pdo->prepare("INSERT INTO inscripciones (usuario_id, curso_id) VALUES (?, ?)");
        $stmtIns->execute([$userId, $cursoId]);
    }

    // 5. Redireccionar a la pantalla de confirmación / recibo
    header('Location: pago_exitoso.php?pago_id=' . $pagoId);
    exit;

} catch (\PDOException $e) {
    die("Error al procesar el pago: " . $e->getMessage());
}
?>
