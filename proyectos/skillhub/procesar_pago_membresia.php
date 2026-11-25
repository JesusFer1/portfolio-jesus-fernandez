<?php
session_start();
require_once 'conexion.php';

$membresiaId = intval($_POST['membresia_id'] ?? 0);
$monto       = floatval($_POST['monto'] ?? 0);
$metodoPago  = trim($_POST['metodo_pago'] ?? 'tarjeta');

if (!in_array($membresiaId, [2, 3])) {
    header('Location: membresias.php');
    exit;
}

try {
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        $nombre   = trim($_POST['nombre'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($nombre) || empty($email)) {
            die("Por favor completa los datos del titular.");
        }

        $stmtUser = $pdo->prepare("SELECT id, nombre, rol FROM usuarios WHERE email = ?");
        $stmtUser->execute([$email]);
        $existing = $stmtUser->fetch();

        if ($existing) {
            $userId = $existing['id'];
            $_SESSION['user_id']      = $existing['id'];
            $_SESSION['user_name']    = $existing['nombre'];
            $_SESSION['role']         = $existing['rol'];
            $_SESSION['membresia_id'] = $membresiaId;
        } else {
            $hash = password_hash(!empty($password) ? $password : 'Alumno2026!', PASSWORD_BCRYPT);
            $stmtNew = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, membresia_id) VALUES (?, ?, ?, 'usuario', ?)");
            $stmtNew->execute([$nombre, $email, $hash, $membresiaId]);

            $userId = $pdo->lastInsertId();
            $_SESSION['user_id']      = $userId;
            $_SESSION['user_name']    = $nombre;
            $_SESSION['role']         = 'usuario';
            $_SESSION['membresia_id'] = $membresiaId;
        }
    }

    // 1. Generar ID único de transacción
    $txnId = 'TXN-MEM-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    // 2. Registrar el Pago en la base de datos (curso_id = 0 para membresías)
    $stmtPago = $pdo->prepare("
        INSERT INTO pagos (usuario_id, curso_id, monto, metodo_pago, transaccion_id, estado) 
        VALUES (?, 0, ?, ?, ?, 'completado')
    ");
    $stmtPago->execute([$userId, $monto, $metodoPago, $txnId]);
    $pagoId = $pdo->lastInsertId();

    // 3. Actualizar la membresía real del usuario en la base de datos
    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET membresia_id = ? WHERE id = ?");
    $stmtUpdate->execute([$membresiaId, $userId]);

    // 4. Actualizar sesión
    $_SESSION['membresia_id'] = $membresiaId;

    // Redirigir al comprobante oficial de pago
    header('Location: pago_exitoso.php?pago_id=' . $pagoId);
    exit;

} catch (\PDOException $e) {
    die("Error al procesar suscripción: " . $e->getMessage());
}
?>
