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

$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$anio = isset($_POST['anio']) ? intval($_POST['anio']) : 0;
$division = isset($_POST['division']) ? intval($_POST['division']) : 0;
$profesor_id = $_SESSION['profesor_id'];

if (empty($titulo) || $anio < 4 || $anio > 7 || $division < 1 || $division > 3 || !isset($_FILES['archivo'])) {
    echo json_encode(["exito" => false, "error" => "Parámetros incompletos o incorrectos."]);
    exit();
}

$archivo = $_FILES['archivo'];

if ($archivo['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["exito" => false, "error" => "Error al subir el archivo físico."]);
    exit();
}

// 1. Obtener el ID del curso de la base de datos basándose en el Año y División seleccionados
try {
    $sql_curso = "SELECT id FROM cursos WHERE anio = :anio AND division = :division LIMIT 1";
    $stmt_curso = $pdo->prepare($sql_curso);
    $stmt_curso->execute(['anio' => $anio, 'division' => $division]);
    $curso = $stmt_curso->fetch();

    if (!$curso) {
        echo json_encode(["exito" => false, "error" => "El curso seleccionado es inválido."]);
        exit();
    }
    
    $curso_id = $curso['id'];
} catch (PDOException $e) {
    echo json_encode(["exito" => false, "error" => "Error al validar la sección escolar."]);
    exit();
}

// 2. Procesar el archivo físico de forma segura
$nombre_original = basename($archivo['name']);
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

// Sanitizar el nombre original para evitar caracteres raros o vulnerabilidades de inyección en archivos
$nombre_sanitizado = preg_replace('/[^a-zA-Z0-9]/', "_", pathinfo($nombre_original, PATHINFO_FILENAME));
$nombre_unico = uniqid() . "_" . $nombre_sanitizado . "." . $extension;

// Definir la ruta física de destino (Subiendo desde la carpeta api hacia uploads/)
$directorio_destino = "../uploads/archivos/";
$ruta_destino_completa = $directorio_destino . $nombre_unico;

// Comprobar si la carpeta de destino existe, si no, crearla con permisos seguros
if (!is_dir($directorio_destino)) {
    mkdir($directorio_destino, 0755, true);
}

// 3. Mover el archivo temporal de PHP a su carpeta permanente
if (move_uploaded_file($archivo['tmp_name'], $ruta_destino_completa)) {
    // Definimos la ruta pública que usará el index.php para las descargas de los alumnos
    $ruta_db = "uploads/archivos/" . $nombre_unico;

    try {
        $sql_insert = "INSERT INTO archivos (titulo, ruta_archivo, tipo_archivo, profesor_id, curso_id) 
                       VALUES (:titulo, :ruta, :tipo, :profesor, :curso)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            'titulo' => $titulo,
            'ruta' => $ruta_db,
            'tipo' => $extension,
            'profesor' => $profesor_id,
            'curso' => $curso_id
        ]);

        echo json_encode(["exito" => true]);
    } catch (PDOException $e) {
        // En caso de que falle la BD, eliminamos el archivo físico para no dejar basura en el disco
        if (file_exists($ruta_destino_completa)) {
            unlink($ruta_destino_completa);
        }
        echo json_encode(["exito" => false, "error" => "Error al registrar los datos en el índice de la biblioteca."]);
    }
} else {
    echo json_encode(["exito" => false, "error" => "No se pudo mover el archivo al servidor."]);
}