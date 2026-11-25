<?php
session_start();
if (!isset($_SESSION['profesor_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado."]);
    exit();
}

require_once '../config/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido."]);
    exit();
}

$nueva_pass = isset($_POST['nueva_pass']) ? $_POST['nueva_pass'] : '';

if (strlen($nueva_pass) < 6) {
    echo json_encode(["exito" => false, "error" => "La contraseña debe tener al menos 6 caracteres."]);
    exit();
}

try {
    // Encriptamos la contraseña utilizando Bcrypt para máxima seguridad
    $hash_pass = password_hash($nueva_pass, PASSWORD_BCRYPT);
    $profesor_id = $_SESSION['profesor_id'];

    $sql = "UPDATE profesores SET contrasenia = :hash, requiere_cambio_clave = 0 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'hash' => $hash_pass,
        'id' => $profesor_id
    ]);

    echo json_encode(["exito" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "error" => "Error al actualizar la contraseña en el servidor."]);
}