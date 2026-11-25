<?php
// SkillHub - Automatic Database Setup & Seeder
require_once 'conexion.php';

$message = [];

try {
    if ($dbDriver === 'sqlite') {
        // SQLite Tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS membresias (
                id INTEGER PRIMARY KEY,
                nombre TEXT NOT NULL,
                precio REAL NOT NULL DEFAULT 0.00,
                nivel INTEGER NOT NULL DEFAULT 1,
                beneficios TEXT
            );

            CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                rol TEXT NOT NULL DEFAULT 'usuario',
                membresia_id INTEGER NOT NULL DEFAULT 1,
                fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cursos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo TEXT NOT NULL,
                descripcion TEXT,
                contenido_css TEXT,
                precio REAL NOT NULL DEFAULT 0.00,
                categoria TEXT DEFAULT 'General',
                instructor_id INTEGER NOT NULL,
                es_drag_drop INTEGER DEFAULT 0,
                imagen_url TEXT,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS inscripciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                curso_id INTEGER NOT NULL,
                fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS foro_temas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                titulo TEXT NOT NULL,
                contenido TEXT NOT NULL,
                categoria TEXT DEFAULT 'General',
                fijado INTEGER DEFAULT 0,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS foro_respuestas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tema_id INTEGER NOT NULL,
                usuario_id INTEGER NOT NULL,
                contenido TEXT NOT NULL,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pagos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                curso_id INTEGER NOT NULL,
                monto REAL NOT NULL,
                metodo_pago TEXT NOT NULL,
                transaccion_id TEXT NOT NULL,
                estado TEXT NOT NULL DEFAULT 'completado',
                fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS lecciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                curso_id INTEGER NOT NULL,
                titulo TEXT NOT NULL,
                duracion TEXT DEFAULT '15 min',
                video_url TEXT NOT NULL,
                resumen TEXT,
                contenido_texto TEXT DEFAULT '',
                material_pdf TEXT DEFAULT '',
                material_nombre TEXT DEFAULT '',
                orden INTEGER DEFAULT 1
            );

            CREATE TABLE IF NOT EXISTS evaluaciones_resultados (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                curso_id INTEGER NOT NULL,
                puntaje INTEGER NOT NULL,
                total_preguntas INTEGER NOT NULL,
                aprobado INTEGER NOT NULL DEFAULT 0,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        // MySQL Tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS membresias (
                id INT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                nivel INT NOT NULL DEFAULT 1,
                beneficios TEXT
            );

            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(250) NOT NULL,
                email VARCHAR(250) UNIQUE NOT NULL,
                password VARCHAR(250) NOT NULL,
                rol VARCHAR(50) NOT NULL DEFAULT 'usuario',
                membresia_id INT NOT NULL DEFAULT 1,
                fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS cursos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(250) NOT NULL,
                descripcion TEXT,
                contenido_css TEXT,
                precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                categoria VARCHAR(100) DEFAULT 'General',
                instructor_id INT NOT NULL,
                es_drag_drop INT DEFAULT 0,
                imagen_url TEXT,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS inscripciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                curso_id INT NOT NULL,
                fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS foro_temas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                titulo VARCHAR(250) NOT NULL,
                contenido TEXT NOT NULL,
                categoria VARCHAR(100) DEFAULT 'General',
                fijado INT DEFAULT 0,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS foro_respuestas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tema_id INT NOT NULL,
                usuario_id INT NOT NULL,
                contenido TEXT NOT NULL,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pagos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                curso_id INT NOT NULL,
                monto DECIMAL(10,2) NOT NULL,
                metodo_pago VARCHAR(50) NOT NULL,
                transaccion_id VARCHAR(100) NOT NULL,
                estado VARCHAR(50) NOT NULL DEFAULT 'completado',
                fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS lecciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                curso_id INT NOT NULL,
                titulo VARCHAR(255) NOT NULL,
                duracion VARCHAR(50) DEFAULT '15 min',
                video_url VARCHAR(500) NOT NULL,
                resumen TEXT,
                contenido_texto TEXT,
                material_pdf VARCHAR(500) DEFAULT '',
                material_nombre VARCHAR(255) DEFAULT '',
                orden INT DEFAULT 1
            );

            CREATE TABLE IF NOT EXISTS evaluaciones_resultados (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                curso_id INT NOT NULL,
                puntaje INT NOT NULL,
                total_preguntas INT NOT NULL,
                aprobado INT NOT NULL DEFAULT 0,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }
    $message[] = " Tablas de la base de datos verificadas y creadas correctamente ($dbDriver).";

    // 1. Seed Membresías
    $stmtMembresia = $pdo->query("SELECT COUNT(*) FROM membresias");
    if ($stmtMembresia->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO membresias (id, nombre, precio, nivel, beneficios) VALUES
            (1, 'Básica', 0.00, 1, 'Acceso a cursos gratuitos y lectura de comunidad.'),
            (2, 'Pro', 19.99, 2, 'Acceso a cursos estándar, certificados de estudio y publicación en foro.'),
            (3, 'VIP', 49.99, 3, 'Acceso ilimitado a TODOS los cursos (incluidos Drag&Drop), soporte prioritario y creador visual.');
        ");
        $message[] = " Membresías por defecto (Básica, Pro, VIP) insertadas.";
    }

    // 2. Seed Usuarios por defecto (Admin, Instructor Pepe, Alumno)
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmtUsers->fetchColumn() == 0) {
        $passAdmin = password_hash('admin123', PASSWORD_BCRYPT);
        $passPepe  = password_hash('pepe123', PASSWORD_BCRYPT);
        $passUser  = password_hash('alumno123', PASSWORD_BCRYPT);

        $stmtIns = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, membresia_id) VALUES (?, ?, ?, ?, ?)");
        $stmtIns->execute(['Administrador SkillHub', 'admin@skillhub.com', $passAdmin, 'administrador', 3]);
        $stmtIns->execute(['Pepe', 'pepe@skillhub.com', $passPepe, 'instructor', 3]);
        $stmtIns->execute(['Estudiante Ejemplo', 'alumno@skillhub.com', $passUser, 'usuario', 1]);

        $message[] = " Usuarios del sistema creados:";
        $message[] = " &nbsp;&nbsp;&bull; Admin: <b>admin@skillhub.com</b> / <b>admin123</b> (Rol: Administrador)";
        $message[] = " &nbsp;&nbsp;&bull; Instructor: <b>pepe@skillhub.com</b> / <b>pepe123</b> (Rol: Instructor Pepe)";
        $message[] = " &nbsp;&nbsp;&bull; Alumno: <b>alumno@skillhub.com</b> / <b>alumno123</b> (Rol: Usuario)";
    }

    // 3. Seed Cursos de ejemplo
    $stmtCursos = $pdo->query("SELECT COUNT(*) FROM cursos");
    if ($stmtCursos->fetchColumn() == 0) {
        $stmtInsC = $pdo->prepare("INSERT INTO cursos (titulo, descripcion, contenido_css, precio, categoria, instructor_id, es_drag_drop, imagen_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $htmlBuilderDemo = '<div style="padding: 40px; text-align: center; background-color: #ffffff; border-radius: 16px;">
            <h1 style="font-size: 2.5rem; color: #1f1d1a;">Curso Master de Diseño UI/UX</h1>
            <p style="font-size: 1.2rem; color: #6b665e;">Aprendé a diseñar interfaces profesionales usando componentes interactivos y sistemas de diseño neobrutalistas.</p>
            <div style="margin-top: 30px;"><a href="#" style="background-color: #e84a27; color: #fff; padding: 12px 28px; border-radius: 10px; font-weight: 800; text-decoration: none;">Comenzar Ahora</a></div>
        </div>';

        $stmtInsC->execute([
            'Desarrollo Web Full Stack con PHP y JS',
            'Aprende a construir aplicaciones dinámicas con MySQL, PHP PDO y JavaScript moderno desde cero.',
            '',
            29.99,
            'Programación',
            2,
            0,
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800'
        ]);

        $stmtInsC->execute([
            'Diseño UI/UX y Sistemas de Diseño Modernos',
            $htmlBuilderDemo,
            '.gjs-cell { padding: 10px; }',
            39.99,
            'Diseño UI/UX',
            2,
            1,
            'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=800'
        ]);

        $stmtInsC->execute([
            'Marketing Digital y Estrategias Growth 2026',
            'Domina la adquisición de clientes, embudos de venta y campañas publicitarias de alto retorno.',
            '',
            19.99,
            'Marketing Digital',
            2,
            0,
            'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800'
        ]);

        $message[] = " Cursos de demostración iniciales publicados.";
    }

    // 4. Seed Foro Temas
    $stmtForo = $pdo->query("SELECT COUNT(*) FROM foro_temas");
    if ($stmtForo->fetchColumn() == 0) {
        $stmtInsF = $pdo->prepare("INSERT INTO foro_temas (usuario_id, titulo, contenido, categoria, fijado) VALUES (?, ?, ?, ?, ?)");
        $stmtInsF->execute([1, '¡Bienvenidos a la Comunidad SkillHub! ', 'Hola a todos los estudiantes e instructores. Este foro está diseñado para resolver dudas, compartir ideas y conectarnos.', 'Anuncios', 1]);
        $stmtInsF->execute([3, '¿Cómo integrar el Visual Builder en mis cursos?', 'Hola, me gustaría saber si la opción Drag & Drop está disponible para los instructores con membresía VIP.', 'Programación', 0]);

        $message[] = " Temas de discusión iniciales creados en el foro.";
    }

} catch (\PDOException $e) {
    die("Error durante el setup de la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Instalación | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ece8e1; color: #1f1d1a; padding: 40px 20px; display: flex; justify-content: center; }
        .setup-card { background: #ffffff; border: 3px solid #1f1d1a; border-radius: 20px; box-shadow: 6px 6px 0px #1f1d1a; max-width: 650px; width: 100%; padding: 36px; }
        h1 { font-size: 2rem; font-weight: 900; color: #e84a27; margin-bottom: 20px; }
        .log-box { background: #f7f4ef; border: 2px solid #ded8ce; border-radius: 12px; padding: 18px; margin-bottom: 24px; font-size: 0.95rem; line-height: 1.6; }
        .btn-start { display: inline-block; background-color: #e84a27; color: #ffffff; font-weight: 800; padding: 14px 28px; border-radius: 12px; text-decoration: none; border: 2px solid #1f1d1a; box-shadow: 3px 3px 0px #1f1d1a; transition: all 0.15s ease; }
        .btn-start:hover { background-color: #cf3c1c; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="setup-card">
        <h1>¡Plataforma SkillHub Lista! </h1>
        <div class="log-box">
            <?php foreach ($message as $msg): ?>
                <p><?= $msg ?></p>
            <?php endforeach; ?>
        </div>
        <a href="index.php" class="btn-start">Ir a la Plataforma &rarr;</a>
    </div>
</body>
</html>
