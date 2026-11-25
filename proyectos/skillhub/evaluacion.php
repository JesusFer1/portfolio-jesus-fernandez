<?php
session_start();
require_once 'conexion.php';
require_once 'tareas_helper.php';

ensureTareasTable($pdo, $dbDriver ?? 'sqlite');

$cursoId  = intval($_GET['curso_id'] ?? ($_POST['curso_id'] ?? 0));
$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Estudiante';
$userRole = $_SESSION['role'] ?? 'usuario';
$userTier = $_SESSION['membresia_id'] ?? 1;

if (!$cursoId || !$userId) {
    header('Location: login.php');
    exit;
}

try {
    // 1. Obtener curso
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
    $stmt->execute([$cursoId]);
    $curso = $stmt->fetch();

    if (!$curso) {
        header('Location: cursos.php');
        exit;
    }

    // 2. Verificar que esté inscripto o sea dueño/admin
    $esDuenio = ($curso['instructor_id'] == $userId || $userRole === 'administrador');
    $stmtIns = $pdo->prepare("SELECT id FROM inscripciones WHERE usuario_id = ? AND curso_id = ?");
    $stmtIns->execute([$userId, $cursoId]);
    $inscripto = $stmtIns->fetch();

    if (!$inscripto && !$esDuenio) {
        header('Location: ver_curso.php?id=' . $cursoId);
        exit;
    }

    // 2.5 Verificar progreso de tareas aprobadas por el instructor (para estudiantes)
    $progresoTareas = getProgresoTareasAlumno($pdo, (int)$userId, $cursoId);
    $examenBloqueado = (!$esDuenio && !$progresoTareas['todas_aprobadas']);

    // Banco de preguntas contextuales según categoría del curso
    $bancoPreguntas = [
        'Programación' => [
            [
                'q' => '¿Cuál es el beneficio primordial de usar sentencias preparadas con PDO en PHP?',
                'options' => [
                    'Aumentar la velocidad de carga de las imágenes.',
                    'Prevenir ataques de inyección SQL (SQL Injection).',
                    'Compilar el código a lenguaje ensamblador.',
                    'Evitar la necesidad de tener una base de datos.'
                ],
                'correct' =>1
            ],
            [
                'q' => '¿Qué función cumple una clave foránea (Foreign Key) en un modelo de base de datos relacional?',
                'options' => [
                    'Garantizar la integridad referencial vinculando dos tablas.',
                    'Encriptar las contraseñas de los usuarios con BCrypt.',
                    'Acelerar la conexión a internet del servidor.',
                    'Crear copias de seguridad automáticas en la nube.'
                ],
                'correct' =>0
            ],
            [
                'q' => 'En JavaScript moderno, ¿cuál es la ventaja de utilizar "async/await"?',
                'options' => [
                    'Hacer que el código asíncrono se escriba y lea de manera secuencial y clara.',
                    'Bloquear la ejecución del navegador hasta que el servidor responda.',
                    'Eliminar la necesidad de usar funciones.',
                    'Permitir que el navegador corra sin usar memoria RAM.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Por qué se recomienda almacenar las contraseñas usando algoritmos de hash como BCrypt en lugar de texto plano?',
                'options' => [
                    'Para que las contraseñas ocupen menos espacio en disco.',
                    'Porque es irreversible y protege las credenciales de accesos no autorizados.',
                    'Para que el usuario pueda recuperar su clave original con 1 clic.',
                    'Para que la base de datos las envíe por correo automáticamente.'
                ],
                'correct' =>1
            ],
            [
                'q' => '¿Qué principio de arquitectura de software promueve la separación de responsabilidades?',
                'options' => [
                    'Poner todo el código en un único archivo index.php.',
                    'Modelo-Vista-Controlador (MVC) y diseño modular.',
                    'Desactivar los manejadores de errores en producción.',
                    'Evitar el uso de clases y funciones reutilizables.'
                ],
                'correct' =>1
            ]
        ],
        'Diseño UI/UX' => [
            [
                'q' => '¿Cuál es el objetivo primordial del contraste de color en diseño de interfaces accesibles?',
                'options' => [
                    'Usar únicamente colores llamativos y saturados.',
                    'Garantizar la legibilidad y cumplir con estándares de accesibilidad (WCAG).',
                    'Reducir el peso de las hojas de estilo CSS.',
                    'Hacer que todos los botones sean invisibles hasta que se pase el cursor.'
                ],
                'correct' =>1
            ],
            [
                'q' => 'En la jerarquía visual de una página web, ¿qué elemento debe captar primero la atención?',
                'options' => [
                    'El aviso legal del pie de página.',
                    'El encabezado principal (H1) y el llamado a la acción prioritario (CTA).',
                    'El menú secundario de configuración.',
                    'La barra de desplazamiento lateral.'
                ],
                'correct' =>1
            ],
            [
                'q' => '¿Qué caracteriza al estilo de diseño "Neobrutalismo" en el desarrollo web moderno?',
                'options' => [
                    'Bordes gruesos y oscuros, sombras marcadas sin difuminar y colores de alto contraste.',
                    'Gradientes borrosos y fuentes tipográficas ultra delgadas e ilegibles.',
                    'Ausencia total de botones o interactividad.',
                    'Uso exclusivo de fondos oscuros con texto gris.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Cuál es el propósito de crear un Sistema de Diseño (Design System)?',
                'options' => [
                    'Escribir CSS desde cero en cada página diferente.',
                    'Mantener consistencia, escalabilidad y componentes reutilizables en todo el producto.',
                    'Limitar la creatividad del diseñador para que no pueda innovar.',
                    'Reemplazar a los desarrolladores por plantillas estáticas.'
                ],
                'correct' =>1
            ],
            [
                'q' => 'En pruebas de usabilidad (UX), ¿cuándo se considera exitoso un flujo de usuario?',
                'options' => [
                    'Cuando el usuario tarda más de 30 minutos en encontrar lo que busca.',
                    'Cuando el usuario completa su objetivo de forma intuitiva, rápida y sin fricción.',
                    'Cuando el usuario tiene que consultar el soporte técnico obligatoriamente.',
                    'Cuando la página contiene más de 100 enlaces en la misma pantalla.'
                ],
                'correct' =>1
            ]
        ],
        'Edición de Video' => [
            [
                'q' => '¿Cuál es el atajo de teclado para desagrupar (separar) una pista de audio de su video en la línea de tiempo de Sony Vegas?',
                'options' => [
                    'Tecla U (Ungroup).',
                    'Tecla S (Split / Dividir).',
                    'Tecla G (Group / Agrupar).',
                    'Control + Z.'
                ],
                'correct' =>0
            ],
            [
                'q' => 'Si por error cerraste la ventana de Vista Previa o el Explorador de medios, ¿cómo restauras el diseño estándar de fábrica?',
                'options' => [
                    'Reinstalar el software desde cero.',
                    'Ir a Ver ->Diseños de ventana ->Diseño predeterminado (o atajo Alt + D, D).',
                    'Presionar la barra espaciadora repetidamente.',
                    'Borrar el archivo de proyecto .veg.'
                ],
                'correct' =>1
            ],
            [
                'q' => '¿Qué herramienta permite redimensionar, reposicionar y crear efectos de Picture-in-Picture (video sobre video)?',
                'options' => [
                    'Panorámica / Recorte de evento (Event Pan & Crop).',
                    'Generador de medios de texto.',
                    'Envolvente de silencio de pista.',
                    'Exportar a Premiere.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Cómo puedes hacer cámara lenta o cámara rápida estirando el clip directamente en la línea de tiempo?',
                'options' => [
                    'Manteniendo presionada la tecla Ctrl mientras arrastras el borde del evento.',
                    'Manteniendo Shift + clic derecho.',
                    'Doble clic sobre el video.',
                    'Presionando la tecla Esc.'
                ],
                'correct' =>0
            ],
            [
                'q' => 'Al usar un overlay con fondo negro (como el efecto REC o VHS), ¿qué Modo de Composición de pista hace transparente el color negro?',
                'options' => [
                    'Modo Pantalla (Screen) o Añadir (Add).',
                    'Modo Multiplicar (Multiply).',
                    'Modo Quemar (Color Burn).',
                    'Modo Invertir.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Qué plugin nativo de Sony Vegas Pro se utiliza para eliminar fondos verdes o azules en videos?',
                'options' => [
                    'Incrustación por croma de Vegas (Vegas Chroma Keyer).',
                    'Corrector de color secundario.',
                    'Desenfoque gaussiano.',
                    'Destello de lente.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Por qué se recomienda "Desactivar Remuestreo" (Disable Resample) en las propiedades de los clips antes de exportar?',
                'options' => [
                    'Para evitar el molesto efecto fantasma (blur o sombras dobles) en movimientos rápidos.',
                    'Para que el video se guarde en blanco y negro.',
                    'Para convertir el audio estéreo a mono.',
                    'Para aumentar automáticamente el volumen del micrófono.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Cuál es el formato y códec estándar recomendado para renderizar videos en 1080p para YouTube con máxima compatibilidad?',
                'options' => [
                    'MAGIX AVC/AAC MP4 (Internet HD 1080p).',
                    'Windows Media Video v11 (.wmv).',
                    'Secuencia de imágenes BMP sin comprimir.',
                    'Audio Waveform (.wav).'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Cuál es la duración máxima estándar permitida por YouTube para las Pantallas Finales (Outros) interactivas?',
                'options' => [
                    '20 segundos.',
                    '60 segundos.',
                    '5 minutos.',
                    'Sin límite de tiempo.'
                ],
                'correct' =>0
            ],
            [
                'q' => 'Para colocar una marca de agua o logotipo con transparencia durante todo el video, ¿dónde debe ubicarse en el proyecto?',
                'options' => [
                    'En una pista de video superior dedicada, estirada a lo largo de toda la duración del proyecto.',
                    'Debajo de la pista de música de fondo.',
                    'Incrustada directamente en el monitor de vista previa.',
                    'En la carpeta de temporales de Windows.'
                ],
                'correct' =>0
            ]
        ],
        'Marketing Digital' => [
            [
                'q' => '¿Qué es un "Buyer Persona" en una estrategia de Marketing Digital?',
                'options' => [
                    'La representación semi-ficticia del cliente ideal basada en datos reales y comportamiento.',
                    'Un anuncio pagado en la red de búsqueda de Google.',
                    'El logotipo oficial de la empresa.',
                    'Un software para editar videos.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Cuál es la diferencia principal entre SEO y SEM?',
                'options' => [
                    'El SEO busca posicionamiento orgánico (no pago) y el SEM utiliza anuncios pagos en buscadores.',
                    'El SEO solo funciona en Instagram y el SEM en TikTok.',
                    'Son exactamente lo mismo.',
                    'El SEM no requiere presupuesto.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Qué significa la sigla CTR (Click-Through Rate) en campañas publicitarias?',
                'options' => [
                    'El porcentaje de personas que hacen clic en un anuncio respecto al total de impresiones.',
                    'El costo total mensual del hosting web.',
                    'La cantidad de seguidores nuevos por semana.',
                    'El tiempo que tarda en cargar una imagen.'
                ],
                'correct' =>0
            ],
            [
                'q' => 'En un embudo de conversión (Funnel), ¿cuál es el objetivo de la etapa TOFU (Top of Funnel)?',
                'options' => [
                    'Atraer tráfico y generar visibilidad/conciencia de marca ante nuevas audiencias.',
                    'Cerrar la venta inmediata con un descuento agresivo.',
                    'Emitir facturas de cobro.',
                    'Eliminar clientes inactivos.'
                ],
                'correct' =>0
            ],
            [
                'q' => '¿Para qué sirve un Llamado a la Acción (CTA) en una landing page o publicación?',
                'options' => [
                    'Guiar al usuario de forma clara hacia la siguiente acción deseada (comprar, registrarse, consultar).',
                    'Ocultar el precio del producto.',
                    'Evitar que el usuario interactúe con la página.',
                    'Cambiar el color de fondo del navegador.'
                ],
                'correct' =>0
            ]
        ]
    ];

    $categoriaCurso = $curso['categoria'] ?? 'General';
    $preguntas = $bancoPreguntas[$categoriaCurso] ?? ($bancoPreguntas['Edición de Video'] ?? $bancoPreguntas['Programación']);

    // Manejo de calificación por el instructor (Pepe o Admin)
    $mensajeInstructor = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'calificar_evaluacion' && $esDuenio) {
        $evalId = intval($_POST['eval_id'] ?? 0);
        $nuevoEstado = trim($_POST['estado_calificacion'] ?? 'Aprobado');
        $feedback = trim($_POST['feedback'] ?? '');
        $stmtUp = $pdo->prepare("UPDATE evaluaciones_resultados SET estado_calificacion = ?, feedback = ? WHERE id = ?");
        $stmtUp->execute([$nuevoEstado, $feedback, $evalId]);
        $mensajeInstructor = " Calificación y feedback guardados correctamente para el estudiante.";
    }

    // Lista de todas las evaluaciones de alumnos (excluyendo al propio instructor)
    $evaluacionesEstudiantes = [];
    if ($esDuenio) {
        $stmtAllEvals = $pdo->prepare("
            SELECT e.*, u.nombre as alumno_nombre, u.email as alumno_email 
            FROM evaluaciones_resultados e 
            INNER JOIN usuarios u ON e.usuario_id = u.id 
            WHERE e.curso_id = ? AND e.usuario_id != ?
            ORDER BY e.id DESC
        ");
        $stmtAllEvals->execute([$cursoId, $curso['instructor_id']]);
        $evaluacionesEstudiantes = $stmtAllEvals->fetchAll();
    }

    // Manejo del envío de la evaluación por el alumno (POST — bloqueado para instructores/admins)
    $evaluacionRendida = false;
    $puntajeObtenido = 0;
    $totalPreguntas = count($preguntas);
    $aprobado = false;
    $respuestasUsuario = [];

    if (!$esDuenio && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'rendir_evaluacion') {
        $evaluacionRendida = true;
        $respuestasUsuario = $_POST['respuestas'] ?? [];

        foreach ($preguntas as $idx => $p) {
            $resp = isset($respuestasUsuario[$idx]) ? intval($respuestasUsuario[$idx]) : -1;
            if ($resp === $p['correct']) {
                $puntajeObtenido++;
            }
        }

        $porcentaje = ($puntajeObtenido / $totalPreguntas) * 100;
        $aprobado = ($porcentaje >= 60);

        // Guardar resultado en la base de datos
        $stmtSave = $pdo->prepare("
            INSERT INTO evaluaciones_resultados (usuario_id, curso_id, puntaje, total_preguntas, aprobado, estado_calificacion) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $estadoInicial = $aprobado ? 'Aprobado' : 'Requiere Revisión';
        $stmtSave->execute([$userId, $cursoId, $puntajeObtenido, $totalPreguntas, $aprobado ? 1 : 0, $estadoInicial]);
    }

    // Consultar el último intento guardado
    $stmtUltimo = $pdo->prepare("SELECT * FROM evaluaciones_resultados WHERE usuario_id = ? AND curso_id = ? ORDER BY id DESC LIMIT 1");
    $stmtUltimo->execute([$userId, $cursoId]);
    $ultimoIntento = $stmtUltimo->fetch();

} catch (\PDOException $e) {
    die("Error en evaluación: " . $e->getMessage());
}

if ($examenBloqueado) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Evaluación Bloqueada | SkillHub</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="styles/crear_curso_form.css">
    </head>
    <body style="background:#f8fafc; font-family:'Plus Jakarta Sans',sans-serif; padding:40px 20px;">
        <div style="max-width:640px; margin:40px auto; background:#fff; border:2px solid #0f172a; border-radius:18px; padding:40px; text-align:center; box-shadow:6px 6px 0 #0f172a;">
            <div style="font-size:3rem; margin-bottom:12px;"></div>
            <h1 style="font-size:1.65rem; font-weight:900; color:#0f172a; margin-bottom:10px;">Evaluación Final Bloqueada</h1>
            <p style="color:#475569; font-weight:600; line-height:1.6; margin-bottom:22px;">
                Para rendir la prueba final de <strong><?= htmlspecialchars($curso['titulo']) ?></strong>, primero debés entregar los trabajos prácticos de todas las clases y recibir la aprobación del instructor.
            </p>
            <div style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:12px; padding:16px; margin-bottom:24px; text-align:left; font-size:0.92rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                    <span>Tareas entregadas:</span>
                    <strong><?= $progresoTareas['entregadas'] ?> / <?= $progresoTareas['total_clases'] ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                    <span>Tareas aprobadas por el instructor:</span>
                    <strong style="color:#16a34a;"><?= $progresoTareas['aprobadas'] ?> / <?= $progresoTareas['total_clases'] ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span>⏳ Pendientes de corrección:</span>
                    <strong style="color:#d97706;"><?= $progresoTareas['pendientes'] ?></strong>
                </div>
            </div>
            <a href="ver_curso.php?id=<?= $cursoId ?>#aula-virtual" class="btn-primary" style="display:inline-block; padding:12px 26px; text-decoration:none;">&larr; Volver a las Clases y Enviar Tareas</a>
        </div>
    <?php include 'demo_switcher_widget.php'; ?>
</body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación Oficial | <?= htmlspecialchars($curso['titulo']) ?> | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/crear_curso_form.css?v=<?= time() ?>">
    <link rel="stylesheet" href="styles/ver_curso.css?v=<?= time() ?>">
    <style>
        .exam-container {
            max-width: 820px;
            margin: 40px auto 80px;
            padding: 0 20px;
        }

        .exam-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 40px 36px;
        }

        .question-box {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .question-title {
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--text-main);
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .option-label {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-alt);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .option-label:hover {
            border-color: var(--primary-border);
            background-color: var(--primary-light);
            color: var(--primary);
        }

        .option-label input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .result-banner {
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .result-approved {
            background-color: var(--success-light);
            color: var(--success-text);
            border-color: var(--success-border);
        }

        .result-failed {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }
    </style>
</head>
<body class="independent-page-body">

    <!-- HEADER DE NAVEGACIÓN -->
    <header class="independent-course-header">
        <div class="independent-nav-container">
            <div class="independent-nav-left">
                <a href="ver_curso.php?id=<?= $curso['id'] ?>" class="btn-catalog-back">&larr; Volver al Aula</a>
                <a href="<?= $userRole === 'administrador' ? 'admin_dashboard.php' : 'index.php' ?>" class="logo" style="font-size: 1.4rem;">
                    SkillHub<span class="logo-dot">.</span>
                </a>
            </div>
            <div class="independent-nav-right">
                <?php if ($userRole === 'administrador'): ?>
                    <span class="badge-nav-enrolled" style="background:#fef3c7; color:#92400e; border-color:#f59e0b;">Vista Admin</span>
                    <a href="admin_dashboard.php" class="btn-catalog-back">Panel Admin</a>
                <?php elseif ($esDuenio): ?>
                    <span class="badge-nav-enrolled" style="background:#ede9fe; color:#5b21b6; border-color:#7c3aed;">Vista Instructor</span>
                    <a href="dashboard.php" class="btn-catalog-back">Panel Instructor</a>
                <?php else: ?>
                    <span class="badge-nav-enrolled">Alumno Activo</span>
                    <a href="dashboard.php" class="btn-catalog-back">Mi Panel</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="exam-container">
        
        <div class="exam-card">
            
            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 28px;">
                <span class="nav-cat-pill">
                    <?= $esDuenio ? 'Gestión Docente de Evaluaciones' : 'Prueba Final de Conocimientos & Certificación' ?>
                </span>
                <h1 style="font-size: 2.1rem; font-weight: 900; margin: 10px 0 6px; color: var(--text-main);">
                    Examen Final: <?= htmlspecialchars($curso['titulo']) ?>
                </h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">
                    <?= $esDuenio
                        ? 'Supervisá y calificá los exámenes finales enviados por tus estudiantes en este curso.'
                        : 'Responde las ' . count($preguntas) . ' preguntas del curso para certificar tus habilidades y desbloquear tu Certificado Oficial.' ?>
                </p>
            </div>

            <?php if ($esDuenio): ?>
                <!-- PANEL DE GESTIÓN Y EVALUACIÓN DEL INSTRUCTOR PEPE -->
                <div style="background-color: var(--primary-light); border: 1px solid var(--primary-border); border-radius: var(--radius); padding: 24px; margin-bottom: 32px; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="background-color: var(--primary); color: #fff; font-weight: 800; font-size: 0.75rem; padding: 3px 10px; border-radius: 6px; text-transform: uppercase;">
                                Vista de Instructor: <?= htmlspecialchars($userName) ?>
                            </span>
                            <h2 style="font-size: 1.5rem; font-weight: 900; color: var(--primary); margin: 6px 0 2px;">
                                 Panel de Evaluación y Calificación de Alumnos
                            </h2>
                            <p style="color: var(--text-muted); font-size: 0.88rem; font-weight: 600; margin: 0;">
                                Aquí puedes revisar los exámenes presentados por tus estudiantes, emitir calificaciones personalizadas y dejar retroalimentación formal.
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($mensajeInstructor)): ?>
                        <div style="background-color: var(--success-light); color: var(--success-text); border: 1px solid var(--success-border); border-radius: 10px; padding: 12px 16px; font-weight: 700; font-size: 0.9rem; margin-bottom: 16px;">
                            <?= $mensajeInstructor ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($evaluacionesEstudiantes)): ?>
                        <div style="background: #fff; border: 1px dashed var(--border-color); border-radius: 12px; padding: 20px; text-align: center; color: var(--text-muted); font-weight: 600;">
                            Aún ningún alumno ha enviado la prueba final para este curso.
                        </div>
                    <?php else: ?>
                        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; overflow-x: auto; box-shadow: var(--shadow-sm);">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
                                <thead>
                                    <tr style="background: var(--bg-alt); border-bottom: 1px solid var(--border-color);">
                                        <th style="padding: 12px 14px; font-weight: 800; color: var(--text-main);">Estudiante</th>
                                        <th style="padding: 12px 14px; font-weight: 800; color: var(--text-main);">Puntaje</th>
                                        <th style="padding: 12px 14px; font-weight: 800; color: var(--text-main);">Estado Actual</th>
                                        <th style="padding: 12px 14px; font-weight: 800; color: var(--text-main);">Fecha</th>
                                        <th style="padding: 12px 14px; font-weight: 800; color: var(--text-main);">Evaluar / Dejar Feedback</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($evaluacionesEstudiantes as $ev): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 12px 14px;">
                                                <strong><?= htmlspecialchars($ev['alumno_nombre']) ?></strong><br>
                                                <small style="color: var(--text-muted);"><?= htmlspecialchars($ev['alumno_email']) ?></small>
                                            </td>
                                            <td style="padding: 12px 14px; font-weight: 900; font-size: 1rem; color: var(--success-text);">
                                                <?= $ev['puntaje'] ?> / <?= $ev['total_preguntas'] ?> 
                                                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">(<?= round(($ev['puntaje'] / $ev['total_preguntas']) * 100) ?>%)</span>
                                            </td>
                                            <td style="padding: 12px 14px;">
                                                <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.78rem; background: <?= $ev['aprobado'] ? 'var(--success-light); color: var(--success-text); border: 1px solid var(--success-border);' : '#fee2e2; color: #991b1b; border: 1px solid #fca5a5;' ?>">
                                                    <?= htmlspecialchars($ev['estado_calificacion'] ?: ($ev['aprobado'] ? 'Aprobado' : 'Requiere Revisión')) ?>
                                                </span>
                                            </td>
                                            <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-muted);">
                                                <?= date('d/m/Y H:i', strtotime($ev['fecha'])) ?>
                                            </td>
                                            <td style="padding: 12px 14px;">
                                                <form action="evaluacion.php?curso_id=<?= $cursoId ?>" method="POST" style="display: flex; flex-direction: column; gap: 6px;">
                                                    <input type="hidden" name="accion" value="calificar_evaluacion">
                                                    <input type="hidden" name="eval_id" value="<?= $ev['id'] ?>">
                                                    
                                                    <div style="display: flex; gap: 6px;">
                                                        <select name="estado_calificacion" class="form-control" style="font-size: 0.82rem; padding: 4px 8px; height: auto;">
                                                            <option value="Aprobado con Distinción" <?= ($ev['estado_calificacion'] ?? '') === 'Aprobado con Distinción' ? 'selected' : '' ?>>Aprobado con Distinción </option>
                                                            <option value="Aprobado" <?= ($ev['estado_calificacion'] ?? '') === 'Aprobado' ? 'selected' : '' ?>>Aprobado </option>
                                                            <option value="Requiere Revisión" <?= ($ev['estado_calificacion'] ?? '') === 'Requiere Revisión' ? 'selected' : '' ?>>Requiere Revisión </option>
                                                        </select>
                                                        <button type="submit" class="btn-primary" style="padding: 4px 12px; font-size: 0.82rem; background: #854d0e; border-color: #713f12; cursor: pointer;">
                                                            Guardar
                                                        </button>
                                                    </div>
                                                    <input type="text" name="feedback" value="<?= htmlspecialchars($ev['feedback'] ?? '') ?>" placeholder="Comentario o retroalimentación del profesor..." class="form-control" style="font-size: 0.8rem; padding: 4px 8px;">
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- RESULTADO Y FORMULARIO (EXCLUSIVO PARA ESTUDIANTES — NUNCA PARA EL INSTRUCTOR) -->
            <?php if (!$esDuenio): ?>
            <?php if ($evaluacionRendida): ?>
                <?php if ($aprobado): ?>
                    <div class="result-banner result-approved">
                        <span style="font-size: 3rem; display: block; margin-bottom: 8px;"></span>
                        <h2 style="font-size: 1.8rem; font-weight: 900; margin-bottom: 8px;">¡Felicitaciones! Has Aprobado la Evaluación</h2>
                        <p style="font-size: 1.1rem; font-weight: 700; margin-bottom: 20px;">
                            Puntaje Obtenido: <strong><?= $puntajeObtenido ?>de <?= $totalPreguntas ?></strong> (<?= round(($puntajeObtenido/$totalPreguntas)*100) ?>%).
                        </p>
                        <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
                            <a href="certificado.php?curso_id=<?= $curso['id'] ?>" class="btn-primary" style="background-color: #dca82b; color: #000; font-size: 1.05rem; padding: 14px 28px; text-decoration: none; border: 2px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a; font-weight: 900;">
                                 Ver y Descargar Mi Certificado Oficial &rarr;
                            </a>
                            <a href="ver_curso.php?id=<?= $curso['id'] ?>" class="btn-secondary-link" style="padding: 14px 24px; font-size: 1rem;">
                                Ir al Aula Virtual
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="result-banner result-failed">
                        <span style="font-size: 3rem; display: block; margin-bottom: 8px;"></span>
                        <h2 style="font-size: 1.8rem; font-weight: 900; margin-bottom: 8px;">No alcanzaste el puntaje mínimo de aprobación</h2>
                        <p style="font-size: 1rem; font-weight: 700; margin-bottom: 16px;">
                            Obtuviste <strong><?= $puntajeObtenido ?>de <?= $totalPreguntas ?></strong>correctas. Se requiere al menos un 60% para desbloquear el diploma.
                        </p>
                        <a href="evaluacion.php?curso_id=<?= $curso['id'] ?>" class="btn-primary" style="padding: 12px 24px; text-decoration: none; display: inline-block;">
                             Reintentar Evaluación Ahora
                        </a>
                    </div>
                <?php endif; ?>
            <?php elseif ($ultimoIntento && $ultimoIntento['aprobado']): ?>
                <div class="result-banner result-approved" style="margin-bottom: 24px;">
                    <span style="font-size: 2.2rem; display: block; margin-bottom: 6px;"></span>
                    <h3 style="font-size: 1.3rem; font-weight: 900; margin-bottom: 6px;">Ya aprobaste esta evaluación con éxito</h3>
                    <p style="font-size: 0.95rem; font-weight: 700; margin-bottom: 12px;">
                        Puntaje registrado: <strong><?= $ultimoIntento['puntaje'] ?>de <?= $ultimoIntento['total_preguntas'] ?></strong> (<?= round(($ultimoIntento['puntaje'] / $ultimoIntento['total_preguntas']) * 100) ?>%).
                        <?php if (!empty($ultimoIntento['estado_calificacion'])): ?>
                            <br><span style="display: inline-block; margin-top: 6px; background: #166534; color: #fff; padding: 2px 10px; border-radius: 6px; font-size: 0.82rem;">Calificación del Instructor: <?= htmlspecialchars($ultimoIntento['estado_calificacion']) ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($ultimoIntento['feedback'])): ?>
                        <div style="background: #ffffff; border: 2px solid #16a34a; border-radius: 10px; padding: 12px 16px; margin: 0 auto 16px; max-width: 500px; text-align: left; font-size: 0.88rem;">
                            <strong style="color: #166534; display: block; margin-bottom: 2px;">Observaciones del Profesor:</strong>
                            <span><?= htmlspecialchars($ultimoIntento['feedback']) ?></span>
                        </div>
                    <?php endif; ?>
                    <a href="certificado.php?curso_id=<?= $curso['id'] ?>" class="btn-primary" style="background-color: #dca82b; color: #000; font-size: 0.95rem; padding: 10px 22px; text-decoration: none; border: 2px solid #1f1d1a; font-weight: 800;">
                         Ver Mi Certificado Oficial &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO DE PREGUNTAS -->
            <form action="evaluacion.php" method="POST">
                <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                <input type="hidden" name="accion" value="rendir_evaluacion">

                <?php foreach ($preguntas as $index => $item): ?>
                    <div class="question-box">
                        <div class="question-title">
                            <span style="background: var(--primary); color: #fff; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                                <?= $index + 1 ?>
                            </span>
                            <span><?= htmlspecialchars($item['q']) ?></span>
                        </div>

                        <div>
                            <?php foreach ($item['options'] as $optIdx => $optionText): ?>
                                <label class="option-label">
                                    <input type="radio" name="respuestas[<?= $index ?>]" value="<?= $optIdx ?>" required <?= (isset($respuestasUsuario[$index]) && $respuestasUsuario[$index] == $optIdx) ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars($optionText) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid var(--border-color); padding-top: 24px; margin-top: 28px; flex-wrap: wrap; gap: 14px;">
                    <a href="ver_curso.php?id=<?= $curso['id'] ?>" class="btn-secondary-link">&larr; Cancelar y Volver</a>
                    <button type="submit" class="btn-primary" style="font-size: 1.1rem; padding: 16px 36px; cursor: pointer;">
                         Enviar y Calificar Evaluación &rarr;
                    </button>
                </div>

            </form>
            <?php endif; ?>

        </div>

    </main>

<?php include 'demo_switcher_widget.php'; ?>
</body>
</html>
