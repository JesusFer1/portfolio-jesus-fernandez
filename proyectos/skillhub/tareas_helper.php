<?php
/**
 * SkillHub - Helper de Tareas, Correcciones y Progreso de Certificación
 */

function ensureTareasTable(PDO $pdo, string $dbDriver = 'sqlite'): void {
    if ($dbDriver === 'mysql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS entregas_tareas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                curso_id INT NOT NULL,
                leccion_id INT NOT NULL,
                archivo_ruta VARCHAR(255) DEFAULT NULL,
                archivo_nombre VARCHAR(255) DEFAULT NULL,
                comentario_alumno TEXT,
                estado VARCHAR(20) DEFAULT 'pendiente',
                nota INT DEFAULT NULL,
                feedback_instructor TEXT,
                fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_correccion DATETIME DEFAULT NULL,
                UNIQUE KEY uniq_entrega (usuario_id, curso_id, leccion_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS entregas_tareas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                curso_id INTEGER NOT NULL,
                leccion_id INTEGER NOT NULL,
                archivo_ruta TEXT DEFAULT NULL,
                archivo_nombre TEXT DEFAULT NULL,
                comentario_alumno TEXT,
                estado TEXT DEFAULT 'pendiente',
                nota INTEGER DEFAULT NULL,
                feedback_instructor TEXT,
                fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_correccion DATETIME DEFAULT NULL,
                UNIQUE(usuario_id, curso_id, leccion_id)
            )
        ");
    }
}

function getProgresoTareasAlumno(PDO $pdo, int $userId, int $cursoId): array {
    $stmtL = $pdo->prepare("SELECT id FROM lecciones WHERE curso_id = ? ORDER BY orden ASC, id ASC");
    $stmtL->execute([$cursoId]);
    $leccionIds = $stmtL->fetchAll(PDO::FETCH_COLUMN);
    $totalClases = count($leccionIds);

    $stmtE = $pdo->prepare("SELECT * FROM entregas_tareas WHERE usuario_id = ? AND curso_id = ?");
    $stmtE->execute([$userId, $cursoId]);
    $entregas = $stmtE->fetchAll(PDO::FETCH_ASSOC);

    $porLeccion = [];
    $aprobadas = 0;
    $pendientes = 0;
    $revisar = 0;

    foreach ($entregas as $e) {
        $porLeccion[(int)$e['leccion_id']] = $e;
        if ($e['estado'] === 'aprobada') {
            $aprobadas++;
        } elseif ($e['estado'] === 'revisar') {
            $revisar++;
        } else {
            $pendientes++;
        }
    }

    $entregadas = count($porLeccion);
    $todasAprobadas = ($totalClases >0 && $aprobadas >= $totalClases);

    return [
        'total_clases'    => $totalClases,
        'entregadas'      => $entregadas,
        'aprobadas'       => $aprobadas,
        'pendientes'      => $pendientes,
        'revisar'         => $revisar,
        'todas_aprobadas' => $todasAprobadas,
        'por_leccion'     => $porLeccion
    ];
}

function getExamenAprobadoAlumno(PDO $pdo, int $userId, int $cursoId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM evaluaciones_resultados WHERE usuario_id = ? AND curso_id = ? AND aprobado = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId, $cursoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\PDOException $e) {
        return null;
    }
}
