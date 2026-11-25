<?php
require_once '../config/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido."]);
    exit();
}

$dni = isset($_POST['dni']) ? trim($_POST['dni']) : '';
$contrasenia = isset($_POST['contrasenia']) ? $_POST['contrasenia'] : '';

if (empty($dni) || empty($contrasenia)) {
    echo json_encode(["exito" => false, "error" => "Por favor, complete todos los campos."]);
    exit();
}

try {
    $sql = "SELECT id, nombre, contrasenia, requiere_cambio_clave FROM profesores WHERE dni = :dni LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['dni' => $dni]);
    $profesor = $stmt->fetch();

    if ($profesor) {
        $login_valido = false;

        // Comprobación de seguridad: Primero probamos si la clave coincide con la encriptación hash (Bcrypt)
        if (password_verify($contrasenia, $profesor['contrasenia'])) {
            $login_valido = true;
        } 
        // Comprobación de respaldo: Si es el DNI predeterminado sin encriptar (para cuentas recién creadas)
        elseif ($contrasenia === $dni && $profesor['contrasenia'] === $dni) {
            $login_valido = true;
        }

        if ($login_valido) {
            session_start();
            $_SESSION['profesor_id'] = $profesor['id'];
            $_SESSION['profesor_nombre'] = $profesor['nombre'];
            $_SESSION['profesor_dni'] = $dni;

            echo json_encode(["exito" => true]);
        } else {
            echo json_encode(["exito" => false, "error" => "DNI o contraseña incorrectos."]);
        }
    } else {
        echo json_encode(["exito" => false, "error" => "DNI o contraseña incorrectos."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "error" => "Error interno del servidor al verificar credenciales."]);
}