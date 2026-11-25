<?php
/**
 * API REST DE ÚTILES ESCOLARES
 * Ropero & Catálogo Escolar Solidario - Técnica 5
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'OPTIONS') {
    jsonResponse(['success' => true]);
}

$db = getDBOrNull();

// 1. OBTENER LISTA DE ÚTILES (GET)
if ($method === 'GET') {
    if (!$db) {
        jsonResponse([
            'success' => true,
            'count' => 0,
            'data' => [],
            'db_connected' => false,
            'message' => 'Base de datos MySQL no accesible'
        ]);
    }

    $q = trim($_GET['q'] ?? '');
    $cat = trim($_GET['categoria'] ?? '');
    $estado = trim($_GET['estado'] ?? '');

    $sql = "SELECT id, codigo_qr, nombre, categoria, cantidad, condicion, descripcion, donante, imagen_url, estado, creado_en, actualizado_en 
            FROM utiles WHERE 1=1";
    $params = [];

    if (!empty($q)) {
        $sql .= " AND (nombre LIKE :q1 OR categoria LIKE :q2 OR descripcion LIKE :q3)";
        $params[':q1'] = "%$q%";
        $params[':q2'] = "%$q%";
        $params[':q3'] = "%$q%";
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

        // Devolvemos el array real (vacío [] si eliminaste todos los registros)
        $utiles = array_map(function($row) {
            $images = parseStoredImages($row['imagen_url']);
            return [
                'id' => (string)$row['id'],
                'raw_id' => (int)$row['id'],
                'type' => 'supplies',
                'qrCode' => $row['codigo_qr'],
                'codigo_qr' => $row['codigo_qr'],
                'name' => $row['nombre'],
                'nombre' => $row['nombre'],
                'category' => $row['categoria'],
                'categoria' => $row['categoria'],
                'quantity' => (int)$row['cantidad'],
                'cantidad' => (int)$row['cantidad'],
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
            'count' => count($utiles),
            'data' => $utiles,
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

// 2. ELIMINAR ÚTIL (DELETE o POST action=delete)
if ($action === 'delete' || $method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
    if (!$db) {
        jsonError("La base de datos MySQL no está accesible.");
    }

    $input = getJsonInput();
    $id = $_GET['id'] ?? $input['id'] ?? $_POST['id'] ?? null;
    $codigoQr = trim($_GET['code'] ?? $_GET['codigo_qr'] ?? $input['code'] ?? $input['codigo_qr'] ?? $_POST['code'] ?? '');

    $numericId = null;
    if ($id !== null && $id !== '') {
        if (is_numeric($id)) {
            $numericId = (int)$id;
        } elseif (preg_match('/(?:util|utl)-?(\d+)/i', (string)$id, $matches)) {
            $numericId = (int)$matches[1];
        }
    }

    if (!$numericId && empty($codigoQr) && empty($id)) {
        jsonError("ID o código QR de útil escolar requerido para eliminar.");
    }

    try {
        $deleted = false;
        if ($numericId) {
            $stmt = $db->prepare("DELETE FROM utiles WHERE id = :id");
            $stmt->execute([':id' => $numericId]);
            if ($stmt->rowCount() > 0) {
                $deleted = true;
            }
        }

        if (!$deleted && !empty($codigoQr)) {
            $stmt = $db->prepare("DELETE FROM utiles WHERE codigo_qr = :c");
            $stmt->execute([':c' => $codigoQr]);
            if ($stmt->rowCount() > 0) {
                $deleted = true;
            }
        }

        if (!$deleted && !empty($id) && !is_numeric($id)) {
            $stmt = $db->prepare("DELETE FROM utiles WHERE codigo_qr = :c");
            $stmt->execute([':c' => (string)$id]);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Útil escolar eliminado correctamente de MySQL'
        ]);
    } catch (\Exception $e) {
        jsonError("Error al eliminar el útil escolar: " . $e->getMessage(), 500);
    }
}

// 3. GUARDAR O EDITAR ÚTIL (POST)
if ($method === 'POST') {
    if (!$db) {
        jsonError("La base de datos MySQL no está accesible para guardar cambios.");
    }

    $input = getJsonInput();
    if (empty($input)) {
        $input = $_POST;
    }

    if ($action === 'status') {
        $id = $input['id'] ?? $_GET['id'] ?? null;
        $nuevoEstado = $input['status'] ?? $input['estado'] ?? 'Disponible';

        if (!$id) {
            jsonError("ID de útil requerido.");
        }

        $stmt = $db->prepare("UPDATE utiles SET estado = :est WHERE id = :id");
        $stmt->execute([':est' => $nuevoEstado, ':id' => $id]);

        jsonResponse([
            'success' => true,
            'message' => "Estado actualizado a '{$nuevoEstado}'"
        ]);
    }

    $id = !empty($input['id']) ? $input['id'] : null;
    $nombre = trim($input['name'] ?? $input['nombre'] ?? '');
    $categoria = trim($input['category'] ?? $input['categoria'] ?? 'Útiles');
    $cantidad = (int)($input['quantity'] ?? $input['cantidad'] ?? 1);
    $condicion = trim($input['condition'] ?? $input['condicion'] ?? 'Excelente');
    $estado = trim($input['status'] ?? $input['estado'] ?? 'Disponible');
    $descripcion = trim($input['description'] ?? $input['descripcion'] ?? '');
    $donante = trim($input['donor'] ?? $input['donante'] ?? 'Anónimo');

    $imagesInput = $input['images'] ?? [];
    $imagenRaw = $input['image'] ?? $input['imagen_url'] ?? '';

    $savedImages = [];
    if (!empty($imagesInput) && is_array($imagesInput)) {
        $savedImages = saveMultipleUploadedImages($imagesInput, 'util');
    } elseif (!empty($imagenRaw)) {
        $single = saveUploadedImage($imagenRaw, 'util');
        if ($single) $savedImages[] = $single;
    }

    $imagenUrl = null;
    if (count($savedImages) > 1) {
        $imagenUrl = json_encode($savedImages, JSON_UNESCAPED_SLASHES);
    } elseif (count($savedImages) === 1) {
        $imagenUrl = $savedImages[0];
    }

    if (empty($nombre)) {
        jsonError("El nombre del útil escolar es obligatorio.");
    }

    if ($id && is_numeric($id)) {
        $sql = "UPDATE utiles SET 
                    nombre = :nombre,
                    categoria = :cat,
                    cantidad = :cant,
                    condicion = :condicion,
                    descripcion = :desc,
                    donante = :donante,
                    estado = :estado" . 
                    ($imagenUrl ? ", imagen_url = :img" : "") . 
                " WHERE id = :id";
        
        $params = [
            ':nombre' => $nombre,
            ':cat' => $categoria,
            ':cant' => $cantidad,
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
            'message' => 'Útil escolar actualizado correctamente en MySQL',
            'id' => (string)$id
        ]);
    } else {
        $codigoQr = !empty($input['qrCode']) 
            ? trim($input['qrCode']) 
            : (!empty($input['codigo_qr']) 
                ? trim($input['codigo_qr']) 
                : ('UTL-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6))));

        $stmt = $db->prepare("
            INSERT INTO utiles 
            (codigo_qr, nombre, categoria, cantidad, condicion, descripcion, donante, imagen_url, estado) 
            VALUES 
            (:qr, :nombre, :cat, :cant, :condicion, :desc, :donante, :img, :estado)
        ");
        $stmt->execute([
            ':qr' => $codigoQr,
            ':nombre' => $nombre,
            ':cat' => $categoria,
            ':cant' => $cantidad,
            ':condicion' => $condicion,
            ':desc' => $descripcion,
            ':donante' => $donante,
            ':img' => $imagenUrl ?: 'imagenes/logo.jfif',
            ':estado' => $estado
        ]);

        $newId = (int)$db->lastInsertId();

        $savedItem = [
            'id' => (string)$newId,
            'raw_id' => $newId,
            'type' => 'supplies',
            'qrCode' => $codigoQr,
            'codigo_qr' => $codigoQr,
            'name' => $nombre,
            'category' => $categoria,
            'quantity' => $cantidad,
            'condition' => $condicion,
            'status' => $estado,
            'description' => $descripcion,
            'donor' => $donante,
            'image' => $imagenUrl ?: 'imagenes/logo.jfif',
            'createdAt' => date('Y-m-d H:i:s')
        ];

        jsonResponse([
            'success' => true,
            'message' => 'Útil escolar guardado correctamente en MySQL',
            'item' => $savedItem
        ], 201);
    }
}

jsonError("Método o acción no permitida.");