<?php
session_start();
require_once 'conexion.php';

$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? 'usuario';
$cursoId  = intval($_POST['curso_id'] ?? 0);

if (!$userId || !in_array($userRole, ['instructor', 'administrador']) || !$cursoId) {
    header('Location: dashboard.php');
    exit;
}

$titulo      = trim($_POST['titulo'] ?? '');
$categoria   = trim($_POST['categoria'] ?? 'General');
$precio      = floatval($_POST['precio'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');
$imagenUrl   = trim($_POST['imagen_url'] ?? '');

if (empty($imagenUrl)) {
    $imagenUrl = 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800';
}

$uploadDir = 'c:/xampp/htdocs/skillhub/uploads/materiales/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($titulo) && !empty($descripcion)) {
    try {
        // Verificar propiedad si no es admin
        $stmtCheck = $pdo->prepare("SELECT instructor_id FROM cursos WHERE id = ?");
        $stmtCheck->execute([$cursoId]);
        $c = $stmtCheck->fetch();

        if (!$c || ($userRole !== 'administrador' && $c['instructor_id'] != $userId)) {
            header('Location: dashboard.php');
            exit;
        }

        $pdo->beginTransaction();

        // 1. Actualizar Curso
        $stmt = $pdo->prepare("
            UPDATE cursos 
            SET titulo = ?, categoria = ?, precio = ?, descripcion = ?, contenido_css = '', es_drag_drop = 0, imagen_url = ? 
            WHERE id = ?
        ");
        $stmt->execute([$titulo, $categoria, $precio, $descripcion, $imagenUrl, $cursoId]);

        // 2. Actualizar Lecciones
        $titulos       = $_POST['leccion_titulo'] ?? [];
        $duraciones    = $_POST['leccion_duracion'] ?? [];
        $videos        = $_POST['leccion_video'] ?? [];
        $resumenes     = $_POST['leccion_resumen'] ?? [];
        $textos        = $_POST['leccion_texto'] ?? [];
        $pdfUrls       = $_POST['leccion_pdf_url'] ?? [];
        $pdfActuales   = $_POST['leccion_pdf_actual'] ?? [];
        $nomActuales   = $_POST['leccion_pdf_nombre_actual'] ?? [];

        // Eliminar lecciones anteriores para reordenar limpiamente
        $pdo->prepare("DELETE FROM lecciones WHERE curso_id = ?")->execute([$cursoId]);

        $stmtLec = $pdo->prepare("
            INSERT INTO lecciones (curso_id, titulo, duracion, video_url, resumen, contenido_texto, material_pdf, material_nombre, orden) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $orden = 1;
        for ($i = 0; $i < count($titulos); $i++) {
            $lecTitulo   = trim($titulos[$i] ?? '');
            $lecDuracion = trim($duraciones[$i] ?? '15 min');
            $lecVideo    = trim($videos[$i] ?? '');
            $lecResumen  = trim($resumenes[$i] ?? '');
            $lecTexto    = trim($textos[$i] ?? '');
            $pdfUrlInput = trim($pdfUrls[$i] ?? '');
            $pdfExistente= trim($pdfActuales[$i] ?? '');
            $nomExistente= trim($nomActuales[$i] ?? '');

            if (!empty($lecTitulo)) {
                if (empty($lecVideo)) {
                    $lecVideo = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                }

                $matPdf = '';
                $matNom = '';

                // A) Si se subió un archivo nuevo
                if (isset($_FILES['leccion_pdf_file']['name'][$i]) && $_FILES['leccion_pdf_file']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName  = $_FILES['leccion_pdf_file']['tmp_name'][$i];
                    $origName = $_FILES['leccion_pdf_file']['name'][$i];
                    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $allowed  = ['pdf', 'doc', 'docx', 'zip', 'png', 'jpg'];

                    if (in_array($ext, $allowed)) {
                        $cleanName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $origName);
                        $fileName  = time() . '_' . $i . '_' . $cleanName;
                        if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                            $matPdf = 'uploads/materiales/' . $fileName;
                            $matNom = $origName;
                        }
                    }
                }

                // B) Si no subió archivo pero colocó URL nueva
                if (empty($matPdf) && !empty($pdfUrlInput)) {
                    $matPdf = $pdfUrlInput;
                    $matNom = basename($pdfUrlInput) ?: 'Documento_Clase_' . $orden . '.pdf';
                }

                // C) Si no cambió nada, conservar archivo o URL anterior
                if (empty($matPdf) && !empty($pdfExistente)) {
                    $matPdf = $pdfExistente;
                    $matNom = !empty($nomExistente) ? $nomExistente : 'Material_Clase_' . $orden . '.pdf';
                }

                $stmtLec->execute([$cursoId, $lecTitulo, $lecDuracion, $lecVideo, $lecResumen, $lecTexto, $matPdf, $matNom, $orden]);
                $orden++;
            }
        }

        if ($orden === 1) {
            $stmtLec->execute([
                $cursoId,
                'Lección 1: Bienvenida al Curso',
                '10 min',
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'Introducción al temario del curso.',
                'Bienvenido a la primera clase del curso.',
                '',
                '',
                1
            ]);
        }

        $pdo->commit();

        header('Location: ver_curso.php?id=' . $cursoId);
        exit;

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error al actualizar el curso y sus lecciones: " . $e->getMessage());
    }
}

header('Location: dashboard.php');
exit;
?>
