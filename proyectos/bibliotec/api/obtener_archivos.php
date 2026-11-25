<?php
require_once '../config/conexion.php';

header('Content-Type: application/json');

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

if ($accion === 'por_anio') {
    $anio = isset($_GET['anio']) ? intval($_GET['anio']) : 0;
    
    if ($anio < 4 || $anio > 7) {
        echo json_encode([]);
        exit();
    }
    
    try {
        $sql = "SELECT a.id, a.titulo, a.ruta_archivo, a.tipo_archivo, p.nombre AS nombre_profesor, c.division
                FROM archivos a
                INNER JOIN profesores p ON a.profesor_id = p.id
                INNER JOIN cursos c ON a.curso_id = c.id
                WHERE c.anio = :anio
                ORDER BY a.fecha_subida DESC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['anio' => $anio]);
        $resultados = $stmt->fetchAll();
        
        echo json_encode($resultados);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al consultar los apuntes del año seleccionado."]);
    }
    exit();
}

if ($accion === 'historial') {
    try {
        $sql = "SELECT a.id, a.titulo, a.ruta_archivo, a.tipo_archivo, p.nombre AS nombre_profesor, 
                       c.anio, c.division, DATE_FORMAT(a.fecha_subida, '%d/%m/%Y') AS fecha_formateada
                FROM archivos a
                INNER JOIN profesores p ON a.profesor_id = p.id
                INNER JOIN cursos c ON a.curso_id = c.id
                ORDER BY a.fecha_subida DESC
                LIMIT 15";
                
        $stmt = $pdo->query($sql);
        $resultados = $stmt->fetchAll();
        
        echo json_encode($resultados);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al obtener el historial de publicaciones."]);
    }
    exit();
}

// Nueva acción: Permite al profesor logueado ver únicamente sus archivos publicados
if ($accion === 'mis_archivos') {
    session_start();
    if (!isset($_SESSION['profesor_id'])) {
        http_response_code(403);
        echo json_encode(["error" => "No autorizado."]);
        exit();
    }

    $profesor_id = $_SESSION['profesor_id'];

    try {
        $sql = "SELECT a.id, a.titulo, a.ruta_archivo, a.tipo_archivo, 
                       c.anio, c.division, DATE_FORMAT(a.fecha_subida, '%d/%m/%Y') AS fecha_formateada
                FROM archivos a
                INNER JOIN cursos c ON a.curso_id = c.id
                WHERE a.profesor_id = :profesor
                ORDER BY a.fecha_subida DESC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['profesor' => $profesor_id]);
        $resultados = $stmt->fetchAll();
        
        echo json_encode($resultados);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al consultar los archivos personales."]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["error" => "Acción no especificada o incorrecta."]);