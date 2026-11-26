<?php
/**
 * API REST DE PRENDAS (CATÁLOGO DE ROPA Y CARACTERÍSTICAS)
 * Ropero & Catálogo Escolar Solidario - Técnica 5
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'OPTIONS') {
    jsonResponse(['success' => true]);
}

$db = getDBOrNull();

// Prendas de contingencia por si la base de datos no está disponible temporalmente
function getDefaultPrendas() {
    return [
        [
            'id' => '101', 'raw_id' => 101, 'type' => 'clothing', 'qrCode' => 'PRD-101PULOVER', 'codigo_qr' => 'PRD-101PULOVER',
            'name' => 'Pulóver Rayado Blanco y Azul', 'titulo' => 'Pulóver Rayado Blanco y Azul',
            'category' => 'Suéter', 'categoria' => 'Suéter', 'size' => 'L', 'talle' => 'L',
            'color' => 'Multicolor', 'colorHex' => '#1e3a8a', 'condition' => 'Excelente', 'status' => 'Disponible',
            'description' => 'Pulóver tejido rayado azul marino y blanco con escote en V, muy abrigado.',
            'donor' => 'Ropero EEST5', 'image' => 'uploads/prenda_1792582219_3380.jpg', 'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '102', 'raw_id' => 102, 'type' => 'clothing', 'qrCode' => 'PRD-102REMERA', 'codigo_qr' => 'PRD-102REMERA',
            'name' => 'Remera Defensa y Justicia', 'titulo' => 'Remera Defensa y Justicia',
            'category' => 'Remera', 'categoria' => 'Remera', 'size' => 'M', 'talle' => 'M',
            'color' => 'Negro', 'colorHex' => '#18181b', 'condition' => 'Muy Bueno', 'status' => 'Disponible',
            'description' => 'Remera de algodón negra con escudo bordado, ideal para uso diario o educación física.',
            'donor' => 'Ropero EEST5', 'image' => 'uploads/prenda_1792582319_2044.jpg', 'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '103', 'raw_id' => 103, 'type' => 'clothing', 'qrCode' => 'PRD-103CAMPERA', 'codigo_qr' => 'PRD-103CAMPERA',
            'name' => 'Campera Inflable con Capucha', 'titulo' => 'Campera Inflable con Capucha',
            'category' => 'Campera', 'categoria' => 'Campera', 'size' => 'L', 'talle' => 'L',
            'color' => 'Negro', 'colorHex' => '#111827', 'condition' => 'Excelente', 'status' => 'Disponible',
            'description' => 'Campera inflable liviana con capucha y bolsillos con cierre. Ideal para invierno.',
            'donor' => 'Ropero EEST5', 'image' => 'uploads/prenda_1792363942_1294.jpg', 'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '104', 'raw_id' => 104, 'type' => 'clothing', 'qrCode' => 'PRD-104SHORT', 'codigo_qr' => 'PRD-104SHORT',
            'name' => 'Short Deportivo Nike', 'titulo' => 'Short Deportivo Nike',
            'category' => 'Short', 'categoria' => 'Short', 'size' => 'L', 'talle' => 'L',
            'color' => 'Negro', 'colorHex' => '#18181b', 'condition' => 'Muy Bueno', 'status' => 'Reservado',
            'description' => 'Short deportivo liviano con cintura elástica y bolsillos laterales.',
            'donor' => 'Ropero EEST5', 'image' => 'uploads/prenda_1792582393_8792.jpg', 'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '105', 'raw_id' => 105, 'type' => 'clothing', 'qrCode' => 'PRD-105ZAPAS', 'codigo_qr' => 'PRD-105ZAPAS',
            'name' => 'Zapatillas Urbanas Negras', 'titulo' => 'Zapatillas Urbanas Negras',
            'category' => 'Zapatillas', 'categoria' => 'Zapatillas', 'size' => '40', 'talle' => '40',
            'color' => 'Negro', 'colorHex' => '#18181b', 'condition' => 'Excelente', 'status' => 'Disponible',
            'description' => 'Zapatillas urbanas negras acolchadas, súper cómodas para escuela y uso diario.',
            'donor' => 'Ropero EEST5', 'image' => 'uploads/prenda_1792582272_8121.jpg', 'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '106', 'raw_id' => 106, 'type' => 'clothing', 'qrCode' => 'PRD-106BLANCA', 'codigo_qr' => 'PRD-106BLANCA',
            'name' => 'Remera de Algodón Blanca', 'titulo' => 'Remera de Algodón Blanca',
            'category' => 'Remera', 'categoria' => 'Remera', 'size' => 'M', 'talle' => 'M',
            'color' => 'Blanco', 'colorHex' => '#ffffff', 'condition' => 'Nuevo', 'status' => 'Disponible',
            'description' => 'Remera lisa 100% algodón en excelente estado.',
            'donor' => 'Ropero EEST5', 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&q=80', 'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '107', 'raw_id' => 107, 'type' => 'clothing', 'qrCode' => 'PRD-107JEAN', 'codigo_qr' => 'PRD-107JEAN',
            'name' => 'Pantalón Jean Clásico', 'titulo' => 'Pantalón Jean Clásico',
            'category' => 'Pantalón', 'categoria' => 'Pantalón', 'size' => '42', 'talle' => '42',
            'color' => 'Azul Denim', 'colorHex' => '#2563eb', 'condition' => 'Muy Bueno', 'status' => 'Disponible',
            'description' => 'Pantalón de jean tiro medio, tela resistente.',
            'donor' => 'Ropero EEST5', 'image' => 'https://images.unsplash.com/photo-1542272604-780c36856842?auto=format&fit=crop&w=600&q=80', 'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '108', 'raw_id' => 108, 'type' => 'clothing', 'qrCode' => 'PRD-108BUZO', 'codigo_qr' => 'PRD-108BUZO',
            'name' => 'Buzo Canguro con Capucha', 'titulo' => 'Buzo Canguro con Capucha',
            'category' => 'Buzo', 'categoria' => 'Buzo', 'size' => 'S', 'talle' => 'S',
            'color' => 'Rojo', 'colorHex' => '#dc2626', 'condition' => 'Muy Bueno', 'status' => 'Reservado',
            'description' => 'Buzo cómodo de friza con bolsillo frontal canguro.',
            'donor' => 'Ropero EEST5', 'image' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=600&q=80', 'createdAt' => date('Y-m-d H:i:s')
        ]
    ];
}

// Función para asegurar la existencia de tablas de reservas y notificaciones
function ensureReservasTableExists($db) {
    if (!$db) return;
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `reservas` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `prenda_id` INT NULL,
                `codigo_qr` VARCHAR(100) NULL,
                `prenda_titulo` VARCHAR(150) NULL,
                `nombre_solicitante` VARCHAR(120) NOT NULL,
                `dni` VARCHAR(30) NULL,
                `curso` VARCHAR(50) NULL,
                `telefono` VARCHAR(50) NULL,
                `fecha_solicitada` VARCHAR(50) NULL,
                `fecha_retiro` VARCHAR(50) NULL,
                `notas_usuario` TEXT NULL,
                `notas_admin` TEXT NULL,
                `estado` VARCHAR(50) DEFAULT 'Pendiente',
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS `notificaciones` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `tipo` VARCHAR(50) NOT NULL DEFAULT 'general',
                `titulo` VARCHAR(150) NOT NULL,
                `mensaje` TEXT NOT NULL,
                `destinatario` VARCHAR(100) NOT NULL DEFAULT 'ALL',
                `link` VARCHAR(255) NULL,
                `leido` TINYINT(1) NOT NULL DEFAULT 0,
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (\Exception $e) {}
}

// 0. LISTAR RESERVAS (GET action=list_reservas)
if ($method === 'GET' && ($action === 'list_reservas' || $action === 'reservas')) {
    if (!$db) {
        jsonResponse([
            'success' => true,
            'count' => 0,
            'pendingCount' => 0,
            'data' => [],
            'db_connected' => false,
            'message' => 'Base de datos MySQL no accesible'
        ]);
    }

    ensureReservasTableExists($db);

    try {
        $stmt = $db->query("
            SELECT r.*, p.imagen_url, p.talle, p.color, p.categoria, p.estado AS prenda_estado
            FROM reservas r
            LEFT JOIN prendas p ON (r.prenda_id = p.id OR (r.codigo_qr IS NOT NULL AND r.codigo_qr = p.codigo_qr))
            ORDER BY r.id DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reservas = array_map(function($row) {
            return [
                'id' => (string)$row['id'],
                'prendaId' => (string)($row['prenda_id'] ?? ''),
                'codigoQr' => $row['codigo_qr'] ?? '',
                'prendaTitulo' => $row['prenda_titulo'] ?? '',
                'nombre' => $row['nombre_solicitante'],
                'dni' => $row['dni'] ?? '',
                'curso' => $row['curso'] ?? '',
                'telefono' => $row['telefono'] ?? '',
                'fechaSolicitada' => $row['fecha_solicitada'] ?? '',
                'fechaRetiro' => $row['fecha_retiro'] ?? '',
                'notasUsuario' => $row['notas_usuario'] ?? '',
                'notasAdmin' => $row['notas_admin'] ?? '',
                'estado' => $row['estado'] ?: 'Pendiente',
                'createdAt' => $row['creado_en'],
                'updatedAt' => $row['actualizado_en'],
                'prenda' => [
                    'image' => $row['imagen_url'] ?: 'imagenes/logo.jfif',
                    'size' => $row['talle'] ?: '',
                    'color' => $row['color'] ?: '',
                    'category' => $row['categoria'] ?: ''
                ]
            ];
        }, $rows ?: []);

        $pendingCount = count(array_filter($reservas, function($r) {
            return $r['estado'] === 'Pendiente';
        }));

        jsonResponse([
            'success' => true,
            'count' => count($reservas),
            'pendingCount' => $pendingCount,
            'data' => $reservas,
            'db_connected' => true
        ]);
    } catch (\Exception $e) {
        jsonError("Error al consultar reservas: " . $e->getMessage(), 500);
    }
}

// 1. OBTENER LISTA DE PRENDAS (GET)
if ($method === 'GET') {
    if (!$db) {
        $defaults = getDefaultPrendas();
        jsonResponse([
            'success' => true,
            'count' => count($defaults),
            'data' => $defaults,
            'db_connected' => false,
            'message' => 'Modo demostración sin MySQL'
        ]);
    }

    $q = trim($_GET['q'] ?? '');
    $cat = trim($_GET['categoria'] ?? '');
    $estado = trim($_GET['estado'] ?? '');

    $sql = "SELECT id, codigo_qr, titulo, categoria, talle, color, color_hex, condicion, descripcion, donante, imagen_url, estado, creado_en, actualizado_en 
            FROM prendas WHERE 1=1";
    $params = [];

    if (!empty($q)) {
        $sql .= " AND (titulo LIKE :q1 OR categoria LIKE :q2 OR talle LIKE :q3 OR color LIKE :q4 OR descripcion LIKE :q5)";
        $params[':q1'] = "%$q%";
        $params[':q2'] = "%$q%";
        $params[':q3'] = "%$q%";
        $params[':q4'] = "%$q%";
        $params[':q5'] = "%$q%";
    }

    if (!empty($cat)) {
        $sql .= " AND categoria = :cat";
        $params[':cat'] = $cat;
    }

    if (!empty($estado)) {
        $sql .= " AND estado = :estado";
        $params[':estado'] = $estado;
    }

    $sql .= " ORDER BY id DESC";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Si no hay filas, devolvemos array vacío real para que no se inventen prendas
        $prendas = array_map(function($row) {
            $images = parseStoredImages($row['imagen_url']);
            return [
                'id' => (string)$row['id'],
                'raw_id' => (int)$row['id'],
                'type' => 'clothing',
                'qrCode' => $row['codigo_qr'],
                'codigo_qr' => $row['codigo_qr'],
                'name' => $row['titulo'],
                'titulo' => $row['titulo'],
                'category' => $row['categoria'],
                'categoria' => $row['categoria'],
                'size' => $row['talle'],
                'talle' => $row['talle'],
                'color' => $row['color'],
                'colorHex' => $row['color_hex'] ?: '#2563eb',
                'color_hex' => $row['color_hex'] ?: '#2563eb',
                'condition' => $row['condicion'] ?: 'Excelente',
                'condicion' => $row['condicion'] ?: 'Excelente',
                'status' => $row['estado'] ?: 'Disponible',
                'estado' => $row['estado'] ?: 'Disponible',
                'description' => $row['descripcion'] ?: '',
                'descripcion' => $row['descripcion'] ?: '',
                'donor' => $row['donante'] ?: 'Anónimo',
                'donante' => $row['donante'] ?: 'Anónimo',
                'image' => (!empty($images) ? $images[0] : ($row['imagen_url'] ?: 'imagenes/logo.jfif')),
                'images' => $images,
                'imagen_url' => (!empty($images) ? $images[0] : ($row['imagen_url'] ?: 'imagenes/logo.jfif')),
                'createdAt' => $row['creado_en'],
                'updatedAt' => $row['actualizado_en']
            ];
        }, $rows ?: []);

        jsonResponse([
            'success' => true,
            'count' => count($prendas),
            'data' => $prendas,
            'db_connected' => true
        ]);
    } catch (\Exception $e) {
        jsonResponse([
            'success' => true,
            'count' => 0,
            'data' => [],
            'db_connected' => false,
            'error_detail' => $e->getMessage()
        ]);
    }
}

// 2. ELIMINAR PRENDA (DELETE o POST action=delete)
if ($action === 'delete' || $method === 'DELETE') {
    if (!$db) {
        jsonError("La base de datos MySQL no está accesible.");
    }

    $input = getJsonInput();
    $id = $_GET['id'] ?? $input['id'] ?? $_POST['id'] ?? null;
    $codigoQr = $_GET['code'] ?? $_GET['codigo_qr'] ?? $input['code'] ?? $input['codigo_qr'] ?? $_POST['code'] ?? null;

    if (!$id && !$codigoQr) {
        jsonError("ID o código QR de la prenda es requerido para eliminar.");
    }

    try {
        $prendaOld = null;
        if ($id && is_numeric($id)) {
            $stmtP = $db->prepare("SELECT id, codigo_qr FROM prendas WHERE id = :id LIMIT 1");
            $stmtP->execute([':id' => (int)$id]);
            $prendaOld = $stmtP->fetch(PDO::FETCH_ASSOC);
        }

        if (!$prendaOld && !empty($codigoQr)) {
            $stmtP = $db->prepare("SELECT id, codigo_qr FROM prendas WHERE codigo_qr = :c LIMIT 1");
            $stmtP->execute([':c' => $codigoQr]);
            $prendaOld = $stmtP->fetch(PDO::FETCH_ASSOC);
        }

        $realId = $prendaOld ? $prendaOld['id'] : ($id && is_numeric($id) ? (int)$id : null);
        $realQr = $prendaOld ? $prendaOld['codigo_qr'] : $codigoQr;

        // 1. Liberar primero la etiqueta QR para no violar restricciones
        if (!empty($realQr)) {
            try {
                $db->prepare("UPDATE etiquetas_qr SET prenda_id = NULL, estado = 'libre' WHERE codigo = :c")
                   ->execute([':c' => $realQr]);
            } catch (\Exception $e) {}
        }
        if ($realId) {
            try {
                $db->prepare("UPDATE etiquetas_qr SET prenda_id = NULL, estado = 'libre' WHERE prenda_id = :pid")
                   ->execute([':pid' => $realId]);
            } catch (\Exception $e) {}
        }

        // 2. Eliminar la prenda de MySQL
        if ($realId) {
            $del = $db->prepare("DELETE FROM prendas WHERE id = :id");
            $del->execute([':id' => $realId]);
        } elseif ($realQr) {
            $del = $db->prepare("DELETE FROM prendas WHERE codigo_qr = :c");
            $del->execute([':c' => $realQr]);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Prenda eliminada correctamente de la base de datos MySQL',
            'id' => $realId,
            'codigo_qr' => $realQr
        ]);
    } catch (\Exception $e) {
        jsonError("Error al eliminar la prenda de la base de datos: " . $e->getMessage(), 500);
    }
}

// 3. GUARDAR O EDITAR PRENDA (POST)
if ($method === 'POST') {
    if (!$db) {
        jsonError("La base de datos MySQL no está accesible para guardar cambios.");
    }

    $input = getJsonInput();
    if (empty($input)) {
        $input = $_POST;
    }

    // ACCIÓN: CREAR RESERVA DE PRENDA
    if ($action === 'create_reserva' || $action === 'reservar') {
        ensureReservasTableExists($db);

        $prendaId = !empty($input['prenda_id']) ? (int)$input['prenda_id'] : (!empty($input['id']) ? (int)$input['id'] : null);
        $codigoQr = trim($input['codigo_qr'] ?? $input['qrCode'] ?? '');
        $prendaTitulo = trim($input['prenda_titulo'] ?? $input['name'] ?? $input['titulo'] ?? '');
        $nombre = trim($input['nombre'] ?? $input['nombre_solicitante'] ?? '');
        $dni = trim($input['dni'] ?? '');
        $curso = trim($input['curso'] ?? '');
        $telefono = trim($input['telefono'] ?? $input['contacto'] ?? '');
        $fechaSolicitada = trim($input['fecha_solicitada'] ?? $input['fecha'] ?? '');
        $notas = trim($input['notas'] ?? $input['notas_usuario'] ?? '');

        if (empty($nombre)) {
            jsonError("El nombre y apellido son obligatorios para reservar.");
        }

        // Si no tenemos prendaTitulo, buscarlo en prendas
        if ((!$prendaTitulo || !$codigoQr) && $prendaId) {
            $stmtP = $db->prepare("SELECT titulo, codigo_qr FROM prendas WHERE id = :id LIMIT 1");
            $stmtP->execute([':id' => $prendaId]);
            $pRow = $stmtP->fetch();
            if ($pRow) {
                if (!$prendaTitulo) $prendaTitulo = $pRow['titulo'];
                if (!$codigoQr) $codigoQr = $pRow['codigo_qr'];
            }
        }

        if (empty($prendaTitulo)) {
            $prendaTitulo = 'Prenda Escolar';
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO reservas 
                (prenda_id, codigo_qr, prenda_titulo, nombre_solicitante, dni, curso, telefono, fecha_solicitada, notas_usuario, estado)
                VALUES 
                (:pid, :qr, :tit, :nom, :dni, :cur, :tel, :fec, :not, 'Pendiente')
            ");
            $stmt->execute([
                ':pid' => $prendaId,
                ':qr' => $codigoQr,
                ':tit' => $prendaTitulo,
                ':nom' => $nombre,
                ':dni' => $dni,
                ':cur' => $curso,
                ':tel' => $telefono,
                ':fec' => $fechaSolicitada,
                ':not' => $notas
            ]);

            $reservaId = (int)$db->lastInsertId();

            // Actualizar estado de la prenda a 'Reservado'
            if ($prendaId) {
                $stmtUp = $db->prepare("UPDATE prendas SET estado = 'Reservado' WHERE id = :id");
                $stmtUp->execute([':id' => $prendaId]);
            } elseif ($codigoQr) {
                $stmtUp = $db->prepare("UPDATE prendas SET estado = 'Reservado' WHERE codigo_qr = :qr");
                $stmtUp->execute([':qr' => $codigoQr]);
            }

            // Crear notificación para el Administrador
            $dniTxt = !empty($dni) ? " (DNI: {$dni})" : "";
            $curTxt = !empty($curso) ? " - {$curso}" : "";
            $fecTxt = !empty($fechaSolicitada) ? " para el {$fechaSolicitada}" : "";
            $notifStmt = $db->prepare("
                INSERT INTO notificaciones (tipo, titulo, mensaje, destinatario, link, leido)
                VALUES ('reserva_nueva', :tit, :msg, 'admin', 'index.html', 0)
            ");
            $notifStmt->execute([
                ':tit' => 'Nueva Reserva de Prenda',
                ':msg' => "{$nombre}{$dniTxt}{$curTxt} reservó \"{$prendaTitulo}\"{$fecTxt}.",
            ]);

            jsonResponse([
                'success' => true,
                'message' => '¡Reserva confirmada con éxito! La prenda ha sido reservada.',
                'reserva' => [
                    'id' => (string)$reservaId,
                    'prendaId' => (string)$prendaId,
                    'codigoQr' => $codigoQr,
                    'prendaTitulo' => $prendaTitulo,
                    'nombre' => $nombre,
                    'dni' => $dni,
                    'curso' => $curso,
                    'telefono' => $telefono,
                    'fechaSolicitada' => $fechaSolicitada,
                    'notasUsuario' => $notas,
                    'estado' => 'Pendiente',
                    'createdAt' => date('Y-m-d H:i:s')
                ]
            ], 201);
        } catch (\Exception $e) {
            jsonError("Error al registrar la reserva en MySQL: " . $e->getMessage(), 500);
        }
    }

    // ACCIÓN: ACTUALIZAR ESTADO, FECHA DE RETIRO Y NOTAS DE RESERVA (ADMIN)
    if ($action === 'update_reserva') {
        ensureReservasTableExists($db);

        $id = (int)($input['id'] ?? 0);
        $nuevoEstado = trim($input['estado'] ?? 'Pendiente');
        $fechaRetiro = trim($input['fecha_retiro'] ?? $input['fechaRetiro'] ?? '');
        $notasAdmin = trim($input['notas_admin'] ?? $input['notasAdmin'] ?? '');

        if (!$id) {
            jsonError("ID de reserva requerido.");
        }

        try {
            $stmtPrev = $db->prepare("SELECT * FROM reservas WHERE id = :id LIMIT 1");
            $stmtPrev->execute([':id' => $id]);
            $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

            if (!$prev) {
                jsonError("Reserva no encontrada.");
            }

            $stmt = $db->prepare("
                UPDATE reservas 
                SET estado = :est,
                    fecha_retiro = :fec,
                    notas_admin = :not
                WHERE id = :id
            ");
            $stmt->execute([
                ':est' => $nuevoEstado,
                ':fec' => $fechaRetiro,
                ':not' => $notasAdmin,
                ':id' => $id
            ]);

            $prendaId = $prev['prenda_id'];
            $codigoQr = $prev['codigo_qr'];

            // Si se completa/entrega la reserva -> prenda a 'Entregado'
            if ($nuevoEstado === 'Completada' || $nuevoEstado === 'Entregado') {
                if ($prendaId) {
                    $db->prepare("UPDATE prendas SET estado = 'Entregado' WHERE id = :id")->execute([':id' => $prendaId]);
                } elseif ($codigoQr) {
                    $db->prepare("UPDATE prendas SET estado = 'Entregado' WHERE codigo_qr = :qr")->execute([':qr' => $codigoQr]);
                }
            }
            // Si se cancela la reserva -> prenda vuelve a 'Disponible'
            elseif ($nuevoEstado === 'Cancelada') {
                if ($prendaId) {
                    $db->prepare("UPDATE prendas SET estado = 'Disponible' WHERE id = :id")->execute([':id' => $prendaId]);
                } elseif ($codigoQr) {
                    $db->prepare("UPDATE prendas SET estado = 'Disponible' WHERE codigo_qr = :qr")->execute([':qr' => $codigoQr]);
                }
            }
            // Si se aprueba la reserva -> asegurar 'Reservado'
            elseif ($nuevoEstado === 'Aprobada') {
                if ($prendaId) {
                    $db->prepare("UPDATE prendas SET estado = 'Reservado' WHERE id = :id")->execute([':id' => $prendaId]);
                } elseif ($codigoQr) {
                    $db->prepare("UPDATE prendas SET estado = 'Reservado' WHERE codigo_qr = :qr")->execute([':qr' => $codigoQr]);
                }
            }

            // Crear notificación para todos / alumno
            $prendaTitulo = $prev['prenda_titulo'] ?: 'la prenda';
            $solicitante = $prev['nombre_solicitante'];
            $fechaTxt = !empty($fechaRetiro) ? " Turno de retiro: {$fechaRetiro}." : "";
            $notasTxt = !empty($notasAdmin) ? " Indicaciones: {$notasAdmin}" : "";

            $db->prepare("
                INSERT INTO notificaciones (tipo, titulo, mensaje, destinatario, link, leido)
                VALUES ('reserva_update', :tit, :msg, :dest, 'index.html', 0)
            ")->execute([
                ':tit' => "Reserva {$nuevoEstado}: {$prendaTitulo}",
                ':msg' => "La reserva de {$solicitante} para \"{$prendaTitulo}\" fue actualizada a {$nuevoEstado}.{$fechaTxt}{$notasTxt}",
                ':dest' => 'ALL'
            ]);

            jsonResponse([
                'success' => true,
                'message' => "Reserva actualizada a '{$nuevoEstado}' correctamente.",
                'id' => (string)$id,
                'estado' => $nuevoEstado,
                'fechaRetiro' => $fechaRetiro,
                'notasAdmin' => $notasAdmin
            ]);
        } catch (\Exception $e) {
            jsonError("Error al actualizar reserva: " . $e->getMessage(), 500);
        }
    }

    // Acción para cambiar únicamente el estado de una prenda
    if ($action === 'status') {
        $id = $input['id'] ?? $_GET['id'] ?? null;
        $nuevoEstado = $input['status'] ?? $input['estado'] ?? 'Disponible';

        if (!$id) {
            jsonError("ID de prenda requerido.");
        }

        $stmt = $db->prepare("UPDATE prendas SET estado = :est WHERE id = :id");
        $stmt->execute([':est' => $nuevoEstado, ':id' => $id]);

        jsonResponse([
            'success' => true,
            'message' => "Estado actualizado a '{$nuevoEstado}'"
        ]);
    }

    $id = !empty($input['id']) ? $input['id'] : null;
    $nombre = trim($input['name'] ?? $input['titulo'] ?? '');
    $categoria = trim($input['category'] ?? $input['categoria'] ?? 'General');
    $talle = trim($input['size'] ?? $input['talle'] ?? 'Único');
    $color = trim($input['color'] ?? 'Varios');
    $colorHex = trim($input['colorHex'] ?? $input['color_hex'] ?? '#2563eb');
    $condicion = trim($input['condition'] ?? $input['condicion'] ?? 'Excelente');
    $estado = trim($input['status'] ?? $input['estado'] ?? 'Disponible');
    $descripcion = trim($input['description'] ?? $input['descripcion'] ?? '');
    $donante = trim($input['donor'] ?? $input['donante'] ?? 'Anónimo');
    $imagesInput = $input['images'] ?? [];
    $imagenRaw = $input['image'] ?? $input['imagen_url'] ?? '';

    $savedImages = [];
    if (!empty($imagesInput) && is_array($imagesInput)) {
        $savedImages = saveMultipleUploadedImages($imagesInput, 'prenda');
    } elseif (!empty($imagenRaw)) {
        $single = saveUploadedImage($imagenRaw, 'prenda');
        if ($single) $savedImages[] = $single;
    }

    $imagenUrl = null;
    if (count($savedImages) > 1) {
        $imagenUrl = json_encode($savedImages, JSON_UNESCAPED_SLASHES);
    } elseif (count($savedImages) === 1) {
        $imagenUrl = $savedImages[0];
    }

    if (empty($nombre)) {
        jsonError("El nombre de la prenda es obligatorio.");
    }

    if ($id && is_numeric($id)) {
        try {
            $sql = "UPDATE prendas SET 
                        titulo = :titulo,
                        categoria = :cat,
                        talle = :talle,
                        color = :color,
                        color_hex = :color_hex,
                        condicion = :condicion,
                        descripcion = :desc,
                        donante = :donante,
                        estado = :estado" . 
                        ($imagenUrl ? ", imagen_url = :img" : "") . 
                    " WHERE id = :id";
            
            $params = [
                ':titulo' => $nombre,
                ':cat' => $categoria,
                ':talle' => $talle,
                ':color' => $color,
                ':color_hex' => $colorHex,
                ':condicion' => $condicion,
                ':desc' => $descripcion,
                ':donante' => $donante,
                ':estado' => $estado,
                ':id' => $id
            ];
            if ($imagenUrl) {
                $params[':img'] = $imagenUrl;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            jsonResponse([
                'success' => true,
                'message' => 'Prenda actualizada correctamente en la base de datos',
                'id' => (string)$id
            ]);
        } catch (\Exception $e) {
            jsonError("Error al actualizar la prenda en la base de datos: " . $e->getMessage(), 500);
        }
    } else {
        $codigoQr = !empty($input['qrCode']) 
            ? trim($input['qrCode']) 
            : (!empty($input['codigo_qr']) 
                ? trim($input['codigo_qr']) 
                : ('PRD-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6))));

        try {
            $stmt = $db->prepare("
                INSERT INTO prendas 
                (codigo_qr, titulo, categoria, talle, color, color_hex, condicion, descripcion, donante, imagen_url, estado) 
                VALUES 
                (:qr, :titulo, :cat, :talle, :color, :color_hex, :condicion, :desc, :donante, :img, :estado)
                ON DUPLICATE KEY UPDATE
                titulo = VALUES(titulo),
                categoria = VALUES(categoria),
                talle = VALUES(talle),
                color = VALUES(color),
                color_hex = VALUES(color_hex),
                condicion = VALUES(condicion),
                descripcion = VALUES(descripcion),
                donante = VALUES(donante),
                imagen_url = VALUES(imagen_url),
                estado = VALUES(estado)
            ");
            $stmt->execute([
                ':qr' => $codigoQr,
                ':titulo' => $nombre,
                ':cat' => $categoria,
                ':talle' => $talle,
                ':color' => $color,
                ':color_hex' => $colorHex,
                ':condicion' => $condicion,
                ':desc' => $descripcion,
                ':donante' => $donante,
                ':img' => $imagenUrl ?: 'imagenes/logo.jfif',
                ':estado' => $estado
            ]);

            $newId = (int)$db->lastInsertId();
            if (!$newId) {
                $checkExisting = $db->prepare("SELECT id FROM prendas WHERE codigo_qr = :c LIMIT 1");
                $checkExisting->execute([':c' => $codigoQr]);
                $newId = (int)$checkExisting->fetchColumn();
            }

            try {
                $checkQr = $db->prepare("SELECT id FROM etiquetas_qr WHERE codigo = :c LIMIT 1");
                $checkQr->execute([':c' => $codigoQr]);
                if ($checkQr->fetch()) {
                    $db->prepare("UPDATE etiquetas_qr SET prenda_id = :pid, estado = 'ocupado' WHERE codigo = :c")
                       ->execute([':pid' => $newId, ':c' => $codigoQr]);
                } else {
                    $db->prepare("INSERT INTO etiquetas_qr (codigo, prenda_id, estado) VALUES (:c, :pid, 'ocupado')")
                       ->execute([':c' => $codigoQr, ':pid' => $newId]);
                }
            } catch (\Exception $e) {}

            $savedItem = [
                'id' => (string)$newId,
                'raw_id' => $newId,
                'type' => 'clothing',
                'qrCode' => $codigoQr,
                'codigo_qr' => $codigoQr,
                'name' => $nombre,
                'category' => $categoria,
                'size' => $talle,
                'color' => $color,
                'colorHex' => $colorHex,
                'condition' => $condicion,
                'status' => $estado,
                'description' => $descripcion,
                'image' => (!empty($savedImages) ? $savedImages[0] : ($imagenUrl ?: 'imagenes/logo.jfif')),
                'images' => $savedImages,
                'createdAt' => date('Y-m-d H:i:s')
            ];

            jsonResponse([
                'success' => true,
                'message' => 'Prenda registrada con éxito en la base de datos MySQL',
                'item' => $savedItem
            ], 201);
        } catch (\Exception $e) {
            jsonError("Error al guardar la prenda en MySQL: " . $e->getMessage(), 500);
        }
    }
}

jsonError("Método o acción no permitida.");
