<?php
session_start();
require_once 'conexion.php';
require_once 'tareas_helper.php';

ensureTareasTable($pdo, $dbDriver ?? 'sqlite');

$cursoId  = intval($_GET['curso_id'] ?? 0);
$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Estudiante';
$userRole = $_SESSION['role'] ?? 'usuario';
$userTier = $_SESSION['membresia_id'] ?? 1;

if (!$cursoId || !$userId) {
    header('Location: login.php');
    exit;
}

try {
    // 1. Obtener datos del curso e instructor
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre as instructor_nombre 
        FROM cursos c 
        LEFT JOIN usuarios u ON c.instructor_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$cursoId]);
    $curso = $stmt->fetch();

    if (!$curso) {
        header('Location: cursos.php');
        exit;
    }

    // 2. Verificar que el usuario esté inscripto
    $stmtIns = $pdo->prepare("SELECT fecha_inscripcion FROM inscripciones WHERE usuario_id = ? AND curso_id = ?");
    $stmtIns->execute([$userId, $cursoId]);
    $inscripcion = $stmtIns->fetch();

    if (!$inscripcion && $userTier != 3 && $userRole !== 'administrador') {
        header('Location: ver_curso.php?id=' . $cursoId);
        exit;
    }

    $fechaCert = !empty($inscripcion['fecha_inscripcion']) ? date('d/m/Y', strtotime($inscripcion['fecha_inscripcion'])) : date('d/m/Y');
    $certId = 'CERT-' . strtoupper(substr(md5($userId . '-' . $cursoId . '-skillhub'), 0, 10));

    // 3. Verificar tareas aprobadas + evaluación final aprobada
    $progresoTareas = getProgresoTareasAlumno($pdo, (int)$userId, $cursoId);
    $evaluacionAprobada = getExamenAprobadoAlumno($pdo, (int)$userId, $cursoId);

    if ($userRole !== 'administrador' && (!$progresoTareas['todas_aprobadas'] || !$evaluacionAprobada)) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Certificado Bloqueado | SkillHub</title>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
        </head>
        <body style="background:#f8fafc; font-family:'Plus Jakarta Sans',sans-serif; padding:40px 20px; margin:0;">
            <div style="max-width:620px; margin:40px auto; background:#fff; border:2px solid #0f172a; border-radius:18px; padding:40px; text-align:center; box-shadow:6px 6px 0 #0f172a;">
                <div style="font-size:3rem; margin-bottom:10px;"></div>
                <h1 style="font-size:1.65rem; font-weight:900; color:#0f172a; margin-bottom:10px;">Certificado aún no disponible</h1>
                <p style="color:#475569; font-weight:600; line-height:1.6; margin-bottom:22px;">
                    El certificado oficial de <strong><?= htmlspecialchars($curso['titulo']) ?></strong>se desbloquea únicamente cuando completás los trabajos prácticos de cada clase, el instructor los aprueba y aprobás la evaluación final.
                </p>
                <div style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:12px; padding:16px; margin-bottom:24px; text-align:left; font-size:0.92rem;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span>1. Trabajos prácticos aprobados por instructor:</span>
                        <strong style="color:<?= $progresoTareas['todas_aprobadas'] ? '#16a34a' : '#dc2626' ?>;">
                            <?= $progresoTareas['aprobadas'] ?> / <?= $progresoTareas['total_clases'] ?> <?= $progresoTareas['todas_aprobadas'] ? '' : '' ?>
                        </strong>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span>2. Prueba Final aprobada:</span>
                        <strong style="color:<?= $evaluacionAprobada ? '#16a34a' : '#dc2626' ?>;">
                            <?= $evaluacionAprobada ? 'Aprobada ' : 'Pendiente ' ?>
                        </strong>
                    </div>
                </div>
                <a href="ver_curso.php?id=<?= $cursoId ?>#aula-virtual" style="display:inline-block; background:#0f172a; color:#fff; padding:12px 24px; border-radius:10px; font-weight:800; text-decoration:none;">&larr; Volver al Curso</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

} catch (\PDOException $e) {
    die("Error al emitir certificado: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado Oficial | <?= htmlspecialchars($curso['titulo']) ?> | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,900;1,700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cert-container {
            width: 100%;
            max-width: 960px;
            background: #ffffff;
            border: 8px solid #0f172a;
            padding: 46px 54px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
            position: relative;
            text-align: center;
            border-radius: 12px;
        }

        .cert-inner-border {
            border: 2px solid #d97706;
            padding: 36px 30px;
            border-radius: 6px;
            position: relative;
        }

        .cert-badge-top {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        .cert-org-title {
            font-size: 1.05rem;
            font-weight: 900;
            letter-spacing: 3px;
            color: #d97706;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .cert-main-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.2rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }

        .cert-speech {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .cert-student-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 900;
            color: #4f46e5;
            border-bottom: 2px solid #c7d2fe;
            display: inline-block;
            padding: 0 40px 8px;
            margin-bottom: 24px;
        }

        .cert-course-info {
            font-size: 1.15rem;
            color: #0f172a;
            line-height: 1.6;
            max-width: 700px;
            margin: 0 auto 40px;
        }

        .cert-course-info strong {
            font-size: 1.3rem;
            color: #0f172a;
        }

        .cert-footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            align-items: flex-end;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            width: 160px;
            height: 2px;
            background-color: #0f172a;
            margin: 0 auto 8px;
        }

        .sig-name {
            font-weight: 800;
            font-size: 0.95rem;
            color: #0f172a;
        }

        .sig-title {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 600;
        }

        .cert-id-badge {
            font-family: monospace;
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 700;
        }

        .cert-actions {
            margin-top: 28px;
            display: flex;
            gap: 14px;
        }

        .btn-cert {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 10px;
            border: 1.5px solid #0f172a;
            box-shadow: 2px 2px 0px #0f172a;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.15s ease;
        }

        .btn-cert:hover {
            background-color: #4338ca;
            transform: translateY(-1px);
        }

        .btn-cert-alt {
            background-color: #ffffff;
            color: #0f172a;
            font-weight: 700;
            padding: 12px 20px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.15s ease;
        }

        .btn-cert-alt:hover {
            border-color: #0f172a;
        }

        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .cert-actions { display: none !important; }
            .cert-container { box-shadow: none !important; border-width: 6px !important; }
        }
    </style>
</head>
<body>

    <div class="cert-container">
        <div class="cert-inner-border">
            
            <div class="cert-badge-top"></div>
            <div class="cert-org-title">SkillHub Academy • Certificación Oficial</div>
            <h1 class="cert-main-title">Diploma de Finalización</h1>
            
            <p class="cert-speech">Por haber completado satisfactoriamente todos los módulos del curso:</p>
            
            <div class="cert-student-name">
                <?= htmlspecialchars($userName) ?>
            </div>

            <div class="cert-course-info">
                Por su dedicación en el curso profesional<br>
                <strong>«<?= htmlspecialchars($curso['titulo']) ?>»</strong><br>
                Categoría: <?= htmlspecialchars($curso['categoria'] ?? 'Tecnología') ?>
                <?php if ($evaluacionAprobada): ?>
                    <div style="margin-top: 14px; display: inline-flex; align-items: center; gap: 8px; background: #fef08a; border: 2px solid #ca8a04; color: #854d0e; padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.9rem;">
                        <span> Evaluación de Aprobación: <?= $evaluacionAprobada['puntaje'] ?>/<?= $evaluacionAprobada['total_preguntas'] ?>Correctas (<?= round(($evaluacionAprobada['puntaje'] / $evaluacionAprobada['total_preguntas']) * 100) ?>%)</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cert-footer-grid">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-name"><?= htmlspecialchars($curso['instructor_nombre'] ?? 'Instructor') ?></div>
                    <div class="sig-title">Instructor Titular</div>
                </div>

                <div class="cert-id-badge">
                    <span>ID Verificación:</span><br>
                    <strong><?= $certId ?></strong><br>
                    <span>Emitido: <?= $fechaCert ?></span>
                </div>

                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-name">SkillHub Academy</div>
                    <div class="sig-title">Dirección Académica</div>
                </div>
            </div>

        </div>
    </div>

    <div class="cert-actions">
        <button type="button" onclick="window.print();" class="btn-cert">Imprimir / Guardar en PDF</button>
        <a href="evaluacion.php?curso_id=<?= $curso['id'] ?>" class="btn-cert-alt">Evaluación del Curso</a>
        <a href="ver_curso.php?id=<?= $curso['id'] ?>" class="btn-cert-alt">&larr; Volver al Aula</a>
    </div>

</body>
</html>
