<?php
session_start();
require_once 'conexion.php';
require_once 'tareas_helper.php';

ensureTareasTable($pdo, $dbDriver ?? 'sqlite');

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'usuario';

if (!$userId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'entregar_tarea') {
    $cursoId    = intval($_POST['curso_id'] ?? 0);
    $leccionId  = intval($_POST['leccion_id'] ?? 0);
    $comentario = trim($_POST['comentario_alumno'] ?? '');

    if (!$cursoId || !$leccionId) {
        header('Location: cursos.php');
        exit;
    }

    // Verificar que el alumno esté inscripto
    $stmtIns = $pdo->prepare("SELECT id FROM inscripciones WHERE usuario_id = ? AND curso_id = ?");
    $stmtIns->execute([$userId, $cursoId]);
    if (!$stmtIns->fetch()) {
        header('Location: ver_curso.php?id=' . $cursoId);
        exit;
    }

    // Buscar entrega previa (para conservar archivo si solo actualiza comentario)
    $stmtPrev = $pdo->prepare("SELECT * FROM entregas_tareas WHERE usuario_id = ? AND curso_id = ? AND leccion_id = ?");
    $stmtPrev->execute([$userId, $cursoId, $leccionId]);
    $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

    $archivoRuta   = $prev['archivo_ruta'] ?? null;
    $archivoNombre = $prev['archivo_nombre'] ?? null;

    if (isset($_FILES['archivo_tarea']) && $_FILES['archivo_tarea']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/tareas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $origName = basename($_FILES['archivo_tarea']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'zip', 'rar', 'mp4', 'mov', 'avi', 'veg', 'png', 'jpg', 'jpeg', 'txt', 'ppt', 'pptx'];
        if (in_array($ext, $allowed, true) || $ext === '') {
            $safeExt = $ext ?: 'dat';
            $fileName = "tarea_u{$userId}_c{$cursoId}_l{$leccionId}_" . time() . "." . $safeExt;
            $destPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['archivo_tarea']['tmp_name'], $destPath)) {
                $archivoRuta   = 'uploads/tareas/' . $fileName;
                $archivoNombre = $origName;
            }
        }
    }

    if (!$archivoRuta && $comentario === '') {
        header("Location: ver_curso.php?id={$cursoId}&leccion={$leccionId}&tarea_error=1#aula-virtual");
        exit;
    }

    if ($prev) {
        $stmtUp = $pdo->prepare("
            UPDATE entregas_tareas
            SET archivo_ruta = ?, archivo_nombre = ?, comentario_alumno = ?, estado = 'pendiente', fecha_entrega = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmtUp->execute([$archivoRuta, $archivoNombre, $comentario, $prev['id']]);
    } else {
        $stmtIn = $pdo->prepare("
            INSERT INTO entregas_tareas (usuario_id, curso_id, leccion_id, archivo_ruta, archivo_nombre, comentario_alumno, estado)
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stmtIn->execute([$userId, $cursoId, $leccionId, $archivoRuta, $archivoNombre, $comentario]);
    }

    header("Location: ver_curso.php?id={$cursoId}&leccion={$leccionId}&tarea_enviada=1#aula-virtual");
    exit;
}

if ($accion === 'corregir_tarea') {
    if (!in_array($userRole, ['instructor', 'administrador'], true)) {
        header('Location: dashboard.php');
        exit;
    }

    $entregaId = intval($_POST['entrega_id'] ?? 0);
    $estado    = $_POST['estado'] ?? 'aprobada';
    $nota      = intval($_POST['nota'] ?? 10);
    $feedback  = trim($_POST['feedback_instructor'] ?? '');

    if (!in_array($estado, ['aprobada', 'revisar'], true)) {
        $estado = 'aprobada';
    }
    $nota = max(1, min(10, $nota));

    // Verificar propiedad del curso si no es admin
    $stmtCheck = $pdo->prepare("
        SELECT e.id, c.instructor_id
        FROM entregas_tareas e
        INNER JOIN cursos c ON e.curso_id = c.id
        WHERE e.id = ?
    ");
    $stmtCheck->execute([$entregaId]);
    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($row && ($userRole === 'administrador' || (int)$row['instructor_id'] === (int)$userId)) {
        $stmtUp = $pdo->prepare("
            UPDATE entregas_tareas
            SET estado = ?, nota = ?, feedback_instructor = ?, fecha_correccion = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmtUp->execute([$estado, $nota, $feedback, $entregaId]);
    }

    $redirect = ($userRole === 'administrador') ? 'admin_dashboard.php' : 'dashboard.php?tarea_corregida=1#seccion-tareas';
    header('Location: ' . $redirect);
    exit;
}

header('Location: dashboard.php');
exit;
