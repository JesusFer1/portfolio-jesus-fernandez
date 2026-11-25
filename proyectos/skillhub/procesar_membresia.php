<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['auth_error'] = "Iniciá sesión para gestionar tu membresía.";
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$membresiaId = intval($_POST['membresia_id'] ?? 1);

// Si quiere cambiar a Pro o VIP, debe pasar obligatoriamente por la pasarela de pago
if ($membresiaId == 2 || $membresiaId == 3) {
    header('Location: checkout_membresia.php?id=' . $membresiaId);
    exit;
}

// Si quiere volver a la membresía Básica (gratuita)
if ($membresiaId === 1) {
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET membresia_id = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        
        $_SESSION['membresia_id'] = 1;
        header('Location: dashboard.php?msg=membresia_basica');
        exit;
    } catch (\PDOException $e) {
        die("Error al actualizar la membresía: " . $e->getMessage());
    }
}

header('Location: membresias.php');
exit;
?>
