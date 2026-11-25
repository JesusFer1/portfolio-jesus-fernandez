<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['instructor', 'administrador'])) {
    header('Location: login.php');
    exit;
}

$instructorId = $_SESSION['user_id'];
$titulo       = trim($_POST['titulo'] ?? '');
$categoria    = trim($_POST['categoria'] ?? 'General');
$precio       = floatval($_POST['precio'] ?? 0);
$descripcion  = trim($_POST['descripcion'] ?? '');
$imagenUrl    = trim($_POST['imagen_url'] ?? '');

if (empty($imagenUrl)) {
    $imagenUrl = 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800';
}

$uploadDir = 'c:/xampp/htdocs/skillhub/uploads/materiales/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($titulo) && !empty($descripcion)) {
    try {
        $pdo->beginTransaction();

        // 1. Guardar Curso
        $stmt = $pdo->prepare("
            INSERT INTO cursos (titulo, descripcion, contenido_css, precio, categoria, instructor_id, es_drag_drop, imagen_url) 
            VALUES (?, ?, '', ?, ?, ?, 0, ?)
        ");
        $stmt->execute([$titulo, $descripcion, $precio, $categoria, $instructorId, $imagenUrl]);
        $newCourseId = $pdo->lastInsertId();

        // 2. Guardar Lecciones, Videos, Textos y PDFs
        $titulos    = $_POST['leccion_titulo'] ?? [];
        $duraciones = $_POST['leccion_duracion'] ?? [];
        $videos     = $_POST['leccion_video'] ?? [];
        $resumenes  = $_POST['leccion_resumen'] ?? [];
        $textos     = $_POST['leccion_texto'] ?? [];
        $pdfUrls    = $_POST['leccion_pdf_url'] ?? [];

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

            if (!empty($lecTitulo)) {
                if (empty($lecVideo)) {
                    $lecVideo = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                }

                $matPdf = '';
                $matNom = '';

                // Si se subió un archivo físico
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

                // Si no subió archivo pero ingresó URL de PDF / Drive
                if (empty($matPdf) && !empty($pdfUrlInput)) {
                    $matPdf = $pdfUrlInput;
                    $matNom = basename($pdfUrlInput) ?: 'Documento_Clase_' . $orden . '.pdf';
                }

                $stmtLec->execute([$newCourseId, $lecTitulo, $lecDuracion, $lecVideo, $lecResumen, $lecTexto, $matPdf, $matNom, $orden]);
                $orden++;
            }
        }

        if ($orden === 1) {
            $stmtLec->execute([
                $newCourseId,
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

        header('Location: ver_curso.php?id=' . $newCourseId);
        exit;

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error al guardar el curso y sus lecciones: " . $e->getMessage());
    }
}

header('Location: dashboard.php');
exit;
?>
