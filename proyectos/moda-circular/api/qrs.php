<?php
/**
 * API REST DE GESTIÓN DE ETIQUETAS QR Y VINCULACIÓN CON PRENDAS
 * Ropero & Catálogo Escolar Solidario - Técnica 5
 * 
 * Enfoque: "PRENDAS PARA QR" (Etiquetas físicas pre-creadas y reutilizables)
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = getJsonInput();
$action = $_GET['action'] ?? $input['action'] ?? $_POST['action'] ?? '';

if ($method === 'OPTIONS') {
    jsonResponse(['success' => true]);
}

$db = getDBOrNull();

if ($db) {
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `etiquetas_qr` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `codigo` VARCHAR(100) NOT NULL UNIQUE,
                `prenda_id` INT NULL,
                `estado` ENUM('libre', 'ocupado') DEFAULT 'libre',
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 1. Vincular etiquetas con prendas reales existentes que tengan código QR y título
        $db->exec("
            UPDATE `etiquetas_qr` eq
            INNER JOIN `prendas` p ON eq.codigo = p.codigo_qr
            SET eq.prenda_id = p.id, eq.estado = 'ocupado'
            WHERE p.titulo IS NOT NULL AND p.titulo != '';
        ");

        // 2. Liberar automáticamente CUALQUIER etiqueta que NO coincida con una prenda real activa
        $db->exec("
            UPDATE `etiquetas_qr` eq
            LEFT JOIN `prendas` p ON eq.codigo = p.codigo_qr
            SET eq.prenda_id = NULL, eq.estado = 'libre'
            WHERE p.id IS NULL OR p.titulo IS NULL OR p.titulo = '';
        ");
    } catch (\Exception $e) {}
}

// 1. LISTAR TODAS LAS ETIQUETAS QR
if ($action === 'list') {
    if (!$db) {
        jsonResponse([
            'success' => true,
            'count' => 0,
            'totalLibres' => 0,
            'totalOcupados' => 0,
            'data' => [],
            'db_connected' => false
        ]);
    }

    try {
        $sql = "
            SELECT 
                eq.id, eq.codigo, eq.prenda_id, eq.estado, eq.creado_en, eq.actualizado_en,
                p.id AS prenda_id_real,
                p.titulo AS prenda_titulo,
                p.categoria AS prenda_categoria,
                p.talle AS prenda_talle,
                p.color AS prenda_color,
                p.color_hex AS prenda_color_hex,
                p.imagen_url AS prenda_imagen,
                p.estado AS prenda_estado
            FROM etiquetas_qr eq
            LEFT JOIN prendas p ON eq.codigo = p.codigo_qr
            ORDER BY eq.id DESC
        ";
        $stmt = $db->query($sql);
        $qrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedQrs = [];
        $totalLibres = 0;
        $totalOcupados = 0;

        foreach ($qrs as $item) {
            // Un código QR SOLO y ÚNICAMENTE está ocupado si existe una prenda real con título
            $hasPrenda = !empty($item['prenda_titulo']) && (!empty($item['prenda_id_real']) || !empty($item['prenda_id']));

            if ($hasPrenda) {
                $item['estado'] = 'ocupado';
                $item['prenda_id'] = $item['prenda_id_real'] ?: $item['prenda_id'];
                $totalOcupados++;
            } else {
                $item['estado'] = 'libre';
                $item['prenda_id'] = null;
                $item['prenda_titulo'] = null;
                $item['prenda_categoria'] = null;
                $item['prenda_talle'] = null;
                $item['prenda_color'] = null;
                $item['prenda_color_hex'] = null;
                $item['prenda_imagen'] = null;
                $item['prenda_estado'] = null;
                $totalLibres++;
            }
            $processedQrs[] = $item;
        }

        jsonResponse([
            'success' => true,
            'count' => count($processedQrs),
            'totalLibres' => $totalLibres,
            'totalOcupados' => $totalOcupados,
            'data' => $processedQrs,
            'db_connected' => true
        ]);
    } catch (\Exception $e) {
        jsonResponse([
            'success' => true,
            'count' => 0,
            'totalLibres' => 0,
            'totalOcupados' => 0,
            'data' => [],
            'db_connected' => true,
            'error_detail' => $e->getMessage()
        ]);
    }
}

// 2. ESCANEAR / CONSULTAR UN CÓDIGO QR
if ($action === 'scan') {
    $rawCode = trim($_GET['code'] ?? $_POST['code'] ?? $input['code'] ?? '');
    
    if (strpos($rawCode, '?qr=') !== false || strpos($rawCode, '&qr=') !== false) {
        $parts = parse_url($rawCode);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $rawCode = $query['qr'] ?? $rawCode;
        }
    } elseif (strpos($rawCode, '?codigo=') !== false || strpos($rawCode, '&codigo=') !== false) {
        $parts = parse_url($rawCode);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $rawCode = $query['codigo'] ?? $rawCode;
        }
    }

    $rawCode = trim($rawCode);

    if (empty($rawCode)) {
        jsonError("Debes especificar o escanear un código QR válido.");
    }

    if (!$db) {
        jsonResponse([
            'success' => true,
            'code' => $rawCode,
            'is_assigned' => false,
            'prenda' => null,
            'db_connected' => false
        ]);
    }

    $stmt = $db->prepare("SELECT * FROM etiquetas_qr WHERE codigo = :c LIMIT 1");
    $stmt->execute([':c' => $rawCode]);
    $qr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$qr) {
        $pStmt = $db->prepare("SELECT * FROM prendas WHERE codigo_qr = :c AND titulo IS NOT NULL AND titulo != '' LIMIT 1");
        $pStmt->execute([':c' => $rawCode]);
        $foundPrenda = $pStmt->fetch(PDO::FETCH_ASSOC);

        if ($foundPrenda) {
            $ins = $db->prepare("INSERT INTO etiquetas_qr (codigo, prenda_id, estado) VALUES (:c, :pid, 'ocupado')");
            $ins->execute([':c' => $rawCode, ':pid' => $foundPrenda['id']]);
            $qr = [
                'id' => (int)$db->lastInsertId(),
                'codigo' => $rawCode,
                'prenda_id' => (int)$foundPrenda['id'],
                'estado' => 'ocupado'
            ];
            $prenda = $foundPrenda;
        } else {
            $ins = $db->prepare("INSERT INTO etiquetas_qr (codigo, prenda_id, estado) VALUES (:c, NULL, 'libre')");
            $ins->execute([':c' => $rawCode]);
            $qr = [
                'id' => (int)$db->lastInsertId(),
                'codigo' => $rawCode,
                'prenda_id' => null,
                'estado' => 'libre'
            ];
            $prenda = null;
        }
    } else {
        $prenda = null;
        if (!empty($qr['prenda_id'])) {
            $pStmt = $db->prepare("SELECT * FROM prendas WHERE id = :id AND titulo IS NOT NULL AND titulo != '' LIMIT 1");
            $pStmt->execute([':id' => $qr['prenda_id']]);
            $prenda = $pStmt->fetch(PDO::FETCH_ASSOC);
        }
        if (!$prenda && !empty($qr['codigo'])) {
            $pStmt = $db->prepare("SELECT * FROM prendas WHERE codigo_qr = :c AND titulo IS NOT NULL AND titulo != '' LIMIT 1");
            $pStmt->execute([':c' => $qr['codigo']]);
            $prenda = $pStmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    $isAssigned = !empty($prenda) && !empty($prenda['titulo']);
    if ($isAssigned) {
        $qr['estado'] = 'ocupado';
        $qr['prenda_id'] = $prenda['id'];
        $db->prepare("UPDATE etiquetas_qr SET prenda_id = :pid, estado = 'ocupado' WHERE id = :id")->execute([':pid' => $prenda['id'], ':id' => $qr['id']]);
    } else {
        $qr['estado'] = 'libre';
        $qr['prenda_id'] = null;
        $db->prepare("UPDATE etiquetas_qr SET prenda_id = NULL, estado = 'libre' WHERE id = :id")->execute([':id' => $qr['id']]);
    }

    jsonResponse([
        'success' => true,
        'code' => $qr['codigo'],
        'qr' => $qr,
        'is_assigned' => $isAssigned,
        'prenda' => $prenda ? [
            'id' => (string)$prenda['id'],
            'raw_id' => (int)$prenda['id'],
            'name' => $prenda['titulo'],
            'titulo' => $prenda['titulo'],
            'category' => $prenda['categoria'],
            'categoria' => $prenda['categoria'],
            'size' => $prenda['talle'],
            'talle' => $prenda['talle'],
            'color' => $prenda['color'],
            'colorHex' => $prenda['color_hex'] ?: '#2563eb',
            'status' => $prenda['estado'] ?: 'Disponible',
            'estado' => $prenda['estado'] ?: 'Disponible',
            'image' => $prenda['imagen_url'] ?: 'imagenes/logo.jfif',
            'description' => $prenda['descripcion'] ?: ''
        ] : null
    ]);
}

// 3. GENERAR LOTE DE QRS (CANTIDADES PRE-CREADAS)
if ($action === 'generate_batch') {
    if (!$db) {
        jsonError("La base de datos MySQL no está disponible.");
    }

    try {
        $count = (int)($input['count'] ?? $_POST['count'] ?? $_GET['count'] ?? 10);
        $prefix = trim($input['prefix'] ?? $_POST['prefix'] ?? $_GET['prefix'] ?? 'ROPERO-');
        if ($count < 1) $count = 1;
        if ($count > 100) $count = 100;

        $db->exec("
            CREATE TABLE IF NOT EXISTS `etiquetas_qr` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `codigo` VARCHAR(100) NOT NULL UNIQUE,
                `prenda_id` INT NULL,
                `estado` ENUM('libre', 'ocupado') DEFAULT 'libre',
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $stmt = $db->prepare("SELECT codigo FROM etiquetas_qr WHERE codigo LIKE :p");
        $stmt->execute([':p' => "{$prefix}%"]);
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $highest = 0;
        foreach ($existing as $cod) {
            $numPart = substr($cod, strlen($prefix));
            if (is_numeric($numPart)) {
                $val = (int)$numPart;
                if ($val > $highest) $highest = $val;
            }
        }

        $created = [];
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM etiquetas_qr WHERE codigo = :c");
        $insStmt = $db->prepare("INSERT INTO etiquetas_qr (codigo, prenda_id, estado) VALUES (:c, NULL, 'libre')");

        for ($i = 1; $i <= $count; $i++) {
            do {
                $highest++;
                $code = sprintf("%s%03d", $prefix, $highest);
                $checkStmt->execute([':c' => $code]);
                $exists = (int)$checkStmt->fetchColumn();
            } while ($exists > 0);

            $insStmt->execute([':c' => $code]);
            $created[] = $code;
        }

        jsonResponse([
            'success' => true,
            'message' => "Se generaron {$count} etiquetas QR correctamente.",
            'count' => count($created),
            'codes' => $created
        ]);
    } catch (\Exception $e) {
        jsonError("Error al generar lote de QRs en la base de datos: " . $e->getMessage(), 500);
    }
}

// 4. CAMBIAR ESTADO DE LA PRENDA ASIGNADA AL QR
if ($action === 'change_status') {
    if (!$db) {
        jsonError("La base de datos MySQL no está disponible.");
    }

    $prendaId = $input['prenda_id'] ?? $input['prendaId'] ?? $_POST['prenda_id'] ?? $_GET['prenda_id'] ?? null;
    $nuevoEstado = trim($input['status'] ?? $input['estado'] ?? $_POST['status'] ?? $_GET['status'] ?? 'Disponible');

    if (!$prendaId) {
        jsonError("ID de prenda no especificado.");
    }

    $stmt = $db->prepare("UPDATE prendas SET estado = :est WHERE id = :id");
    $stmt->execute([':est' => $nuevoEstado, ':id' => $prendaId]);

    jsonResponse([
        'success' => true,
        'message' => "Estado de la prenda actualizado a '{$nuevoEstado}'."
    ]);
}

// 5. HEREDAR QR A OTRA PRENDA
if ($action === 'inherit') {
    if (!$db) {
        jsonError("La base de datos MySQL no está disponible.");
    }

    $qrCode = trim($input['code'] ?? $input['codigo'] ?? $_POST['code'] ?? $_GET['code'] ?? '');
    $prendaId = $input['prenda_id'] ?? $input['prendaId'] ?? $_POST['prenda_id'] ?? $_GET['prenda_id'] ?? null;

    if (empty($qrCode)) {
        jsonError("Código QR requerido para heredar.");
    }

    if ($prendaId) {
        $archivedQr = 'ARCH-' . time() . '-' . $qrCode;
        $db->prepare("UPDATE prendas SET codigo_qr = :arch, estado = 'Entregado' WHERE id = :pid")
           ->execute([':arch' => $archivedQr, ':pid' => $prendaId]);
    } else {
        $archivedQr = 'ARCH-' . time() . '-' . $qrCode;
        $db->prepare("UPDATE prendas SET codigo_qr = :arch, estado = 'Entregado' WHERE codigo_qr = :c")
           ->execute([':arch' => $archivedQr, ':c' => $qrCode]);
    }

    $db->prepare("UPDATE etiquetas_qr SET prenda_id = NULL, estado = 'libre' WHERE codigo = :c")
       ->execute([':c' => $qrCode]);

    jsonResponse([
        'success' => true,
        'code' => $qrCode,
        'message' => "La etiqueta QR '{$qrCode}' ha sido liberada y ya está lista para heredar a una nueva prenda."
    ]);
}

// 6. ELIMINAR PRENDA Y LIBERAR EL QR
if ($action === 'delete_prenda') {
    if (!$db) {
        jsonError("La base de datos MySQL no está disponible.");
    }

    $prendaId = $input['prenda_id'] ?? $input['prendaId'] ?? $_POST['prenda_id'] ?? $_GET['prenda_id'] ?? null;
    $qrCode = trim($input['code'] ?? $input['codigo'] ?? $_POST['code'] ?? $_GET['code'] ?? '');

    if (!empty($qrCode)) {
        $db->prepare("UPDATE etiquetas_qr SET prenda_id = NULL, estado = 'libre' WHERE codigo = :c")
           ->execute([':c' => $qrCode]);
    }

    if ($prendaId) {
        $db->prepare("UPDATE etiquetas_qr SET prenda_id = NULL, estado = 'libre' WHERE prenda_id = :id")
           ->execute([':id' => $prendaId]);
        $db->prepare("DELETE FROM prendas WHERE id = :id")->execute([':id' => $prendaId]);
    }

    jsonResponse([
        'success' => true,
        'message' => "Prenda eliminada y etiqueta QR liberada correctamente."
    ]);
}

// 7. ELIMINAR ETIQUETA QR DIRECTAMENTE
if ($action === 'delete_qr') {
    if (!$db) {
        jsonError("La base de datos MySQL no está disponible.");
    }

    $code = trim($input['code'] ?? $input['codigo'] ?? $_GET['code'] ?? $_POST['code'] ?? '');

    if (empty($code)) {
        jsonError("Código QR requerido para eliminar.");
    }

    try {
        // Si la etiqueta estaba asignada a una prenda, desvincularla para mantener integridad
        try {
            $db->prepare("UPDATE prendas SET codigo_qr = CONCAT('SIN-QR-', id, '-', UNIX_TIMESTAMP()) WHERE codigo_qr = :c")->execute([':c' => $code]);
        } catch (\Exception $e) {}

        $stmt = $db->prepare("DELETE FROM etiquetas_qr WHERE codigo = :c");
        $stmt->execute([':c' => $code]);

        jsonResponse([
            'success' => true,
            'message' => "Etiqueta QR '{$code}' eliminada correctamente de la base de datos."
        ]);
    } catch (\Exception $e) {
        jsonError("Error al eliminar la etiqueta QR: " . $e->getMessage(), 500);
    }
}

jsonError("Acción de QR no válida.");