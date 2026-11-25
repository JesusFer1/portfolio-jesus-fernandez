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

$archivo_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$profesor_id = $_SESSION['profesor_id'];

try {
    // Comprobar que el archivo existe y le pertenece al profesor que intenta eliminarlo
    $sql_verify = "SELECT ruta_archivo FROM archivos WHERE id = :id AND profesor_id = :profesor LIMIT 1";
    $stmt_verify = $pdo->prepare($sql_verify);
    $stmt_verify->execute(['id' => $archivo_id, 'profesor' => $profesor_id]);
    $archivo = $stmt_verify->fetch();

    if ($archivo) {
        $ruta_fisica = "../" . $archivo['ruta_archivo'];

        // 1. Eliminar el registro de la base de datos
        $sql_delete = "DELETE FROM archivos WHERE id = :id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute(['id' => $archivo_id]);

        // 2. Eliminar el archivo físico del almacenamiento local si existe
        if (file_exists($ruta_fisica)) {
            unlink($ruta_fisica);
        }

        echo json_encode(["exito" => true]);
    } else {
        echo json_encode(["exito" => false, "error" => "Archivo no encontrado o no posee los permisos necesarios."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "error" => "Error interno al intentar eliminar el archivo."]);
}