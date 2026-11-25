<?php
header('Content-Type: application/json; charset=utf-8');
$dbPath = __DIR__ . '/local_gym.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

function generateUniquePin(PDO $pdo): string {
    $used = $pdo->query("SELECT codigo_teclado FROM socios WHERE codigo_teclado IS NOT NULL AND codigo_teclado != ''")->fetchAll(PDO::FETCH_COLUMN);
    $usedSet = array_flip(array_map('strval', $used));
    for ($i = 0; $i < 200; $i++) {
        $pin = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        if (!isset($usedSet[$pin])) {
            return $pin;
        }
    }
    return str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
}

$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?: [];

if ($action === 'state') {
    $planes = $pdo->query("
        SELECT p.*, COUNT(s.id_Socios) AS total_socios
        FROM planes p
        LEFT JOIN socios s ON p.id_Planes = s.plan_actual
        GROUP BY p.id_Planes
        ORDER BY p.id_Planes ASC
    ")->fetchAll();

    $socios = $pdo->query("
        SELECT s.*, p.nombre_plan, p.precio
        FROM socios s
        LEFT JOIN planes p ON s.plan_actual = p.id_Planes
        ORDER BY s.id_Socios DESC
    ")->fetchAll();

    $accesos = $pdo->query("
        SELECT a.*,
               COALESCE(s.nombre_completo, 'Desconocido') AS socio_nombre,
               COALESCE(s.id_rfid, '---') AS socio_rfid,
               COALESCE(p.nombre_plan, 'Estándar') AS nombre_plan
        FROM accesos a
        LEFT JOIN socios s ON a.id_Socios = s.id_Socios
        LEFT JOIN planes p ON a.id_planes = p.id_Planes
        ORDER BY a.id_accesos DESC
        LIMIT 80
    ")->fetchAll();

    echo json_encode([
        'ok' => true,
        'planes' => $planes,
        'socios' => $socios,
        'accesos' => $accesos,
        'next_pin' => generateUniquePin($pdo)
    ]);
    exit;
}

if ($action === 'next_pin') {
    echo json_encode(['ok' => true, 'pin' => generateUniquePin($pdo)]);
    exit;
}

if ($action === 'add_socio' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($body['nombre'] ?? '');
    $rfid = strtoupper(preg_replace('/[^0-9A-Z]/', '', trim($body['rfid'] ?? '')));
    $pin = trim($body['pin'] ?? '');
    $dni = trim($body['dni'] ?? '');
    $planId = intval($body['plan_id'] ?? 1);
    $estado = isset($body['estado_cuenta']) ? (intval($body['estado_cuenta']) ? 1 : 0) : 1;

    if ($nombre === '') {
        echo json_encode(['ok' => false, 'error' => 'El nombre del socio es obligatorio.']);
        exit;
    }
    if ($rfid === '') {
        echo json_encode(['ok' => false, 'error' => 'Detectá o ingresá un código de tarjeta RFID.']);
        exit;
    }
    if ($pin === '') {
        $pin = generateUniquePin($pdo);
    }
    if ($dni === '') {
        $dni = 'RFID-' . $rfid;
    }

    $chk = $pdo->prepare("SELECT nombre_completo FROM socios WHERE id_rfid = ?");
    $chk->execute([$rfid]);
    if ($exists = $chk->fetch()) {
        echo json_encode(['ok' => false, 'error' => "La tarjeta {$rfid} ya está asignada a {$exists['nombre_completo']}."]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO socios (nombre_completo, dni, codigo_teclado, id_rfid, id_huella, estado_cuenta, plan_actual, fecha_registro) VALUES (?, ?, ?, ?, 0, ?, ?, datetime('now','localtime'))");
    $stmt->execute([$nombre, $dni, $pin, $rfid, $estado, $planId]);
    $newId = $pdo->lastInsertId();

    $log = $pdo->prepare("INSERT INTO accesos (id_Socios, id_planes, fecha_hora_escaneo, tipo_acceso, resultado) VALUES (?, ?, datetime('now','localtime'), 'REGISTRO', 1)");
    $log->execute([$newId, $planId]);

    echo json_encode(['ok' => true, 'id' => $newId, 'rfid' => $rfid, 'pin' => $pin]);
    exit;
}

if ($action === 'edit_socio' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($body['id'] ?? 0);
    $nombre = trim($body['nombre'] ?? '');
    $dni = trim($body['dni'] ?? '');
    $rfid = strtoupper(preg_replace('/[^0-9A-Z]/', '', trim($body['rfid'] ?? '')));
    $pin = trim($body['pin'] ?? '');
    $planId = intval($body['plan_id'] ?? 1);
    $estado = isset($body['estado_cuenta']) ? (intval($body['estado_cuenta']) ? 1 : 0) : 1;

    if (!$id || $nombre === '' || $rfid === '') {
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos para actualizar socio.']);
        exit;
    }

    $chk = $pdo->prepare("SELECT id_Socios, nombre_completo FROM socios WHERE id_rfid = ? AND id_Socios != ?");
    $chk->execute([$rfid, $id]);
    if ($exists = $chk->fetch()) {
        echo json_encode(['ok' => false, 'error' => "La tarjeta {$rfid} ya pertenece a {$exists['nombre_completo']}."]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE socios SET nombre_completo = ?, dni = ?, id_rfid = ?, codigo_teclado = ?, estado_cuenta = ?, plan_actual = ? WHERE id_Socios = ?");
    $stmt->execute([$nombre, $dni, $rfid, $pin, $estado, $planId, $id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete_socio' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($body['id'] ?? 0);
    if (!$id) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido']);
        exit;
    }
    $pdo->prepare("DELETE FROM accesos WHERE id_Socios = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM socios WHERE id_Socios = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'toggle_cuota' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($body['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE socios SET estado_cuenta = CASE WHEN estado_cuenta = 1 THEN 0 ELSE 1 END WHERE id_Socios = ?");
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'update_plan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($body['id'] ?? 0);
    $precio = floatval($body['precio'] ?? 0);
    if (!$id || $precio < 0) {
        echo json_encode(['ok' => false, 'error' => 'Tarifa inválida']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE planes SET precio = ? WHERE id_Planes = ?");
    $stmt->execute([$precio, $id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'verify_access' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfid = strtoupper(preg_replace('/[^0-9A-Z]/', '', trim($body['rfid'] ?? '')));
    $pin = trim($body['pin'] ?? '');
    $metodo = $rfid !== '' ? 'RFID' : 'KEYPAD';

    $socio = null;
    if ($rfid !== '') {
        $stmt = $pdo->prepare("SELECT s.*, p.nombre_plan FROM socios s LEFT JOIN planes p ON s.plan_actual = p.id_Planes WHERE UPPER(s.id_rfid) = ?");
        $stmt->execute([$rfid]);
        $socio = $stmt->fetch();
    } elseif ($pin !== '') {
        $stmt = $pdo->prepare("SELECT s.*, p.nombre_plan FROM socios s LEFT JOIN planes p ON s.plan_actual = p.id_Planes WHERE s.codigo_teclado = ?");
        $stmt->execute([$pin]);
        $socio = $stmt->fetch();
    }

    if (!$socio) {
        $pdo->prepare("INSERT INTO accesos (id_Socios, id_planes, fecha_hora_escaneo, tipo_acceso, resultado) VALUES (NULL, 1, datetime('now','localtime'), ?, 0)")
            ->execute([$metodo]);
        echo json_encode([
            'ok' => true,
            'granted' => false,
            'cmd' => 'NO',
            'unknown' => true,
            'code' => $rfid ?: $pin,
            'metodo' => $metodo,
            'reason' => $metodo === 'RFID' ? 'Tarjeta no registrada' : 'PIN no registrado'
        ]);
        exit;
    }

    $granted = intval($socio['estado_cuenta']) === 1;
    $pdo->prepare("INSERT INTO accesos (id_Socios, id_planes, fecha_hora_escaneo, tipo_acceso, resultado) VALUES (?, ?, datetime('now','localtime'), ?, ?)")
        ->execute([$socio['id_Socios'], $socio['plan_actual'] ?: 1, $metodo, $granted ? 1 : 0]);

    echo json_encode([
        'ok' => true,
        'granted' => $granted,
        'cmd' => $granted ? 'OK' : 'NO',
        'unknown' => false,
        'metodo' => $metodo,
        'reason' => $granted ? 'Acceso permitido' : 'Cuota vencida / Suspendido',
        'socio' => $socio
    ]);
    exit;
}

if ($action === 'manual_open' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = $pdo->query("SELECT id_Socios, plan_actual FROM socios LIMIT 1")->fetch();
    $sId = $first ? $first['id_Socios'] : null;
    $pId = $first ? $first['plan_actual'] : 1;
    $pdo->prepare("INSERT INTO accesos (id_Socios, id_planes, fecha_hora_escaneo, tipo_acceso, resultado) VALUES (?, ?, datetime('now','localtime'), 'MANUAL', 1)")
        ->execute([$sId, $pId]);
    echo json_encode(['ok' => true, 'cmd' => 'OK']);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
