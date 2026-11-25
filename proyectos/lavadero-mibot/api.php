<?php
header('Content-Type: application/json; charset=utf-8');
$dbPath = __DIR__ . '/lavanderia.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?: [];

if ($action === 'state') {
    $turnos = $pdo->query("SELECT * FROM turnos ORDER BY id DESC LIMIT 40")->fetchAll();
    $servicios = $pdo->query("SELECT * FROM servicios WHERE tenant_id = 1 ORDER BY id ASC")->fetchAll();
    $tokens = $pdo->query("SELECT COALESCE(SUM(total_tokens), 0) AS total FROM token_logs")->fetch();
    $tenant = $pdo->query("SELECT * FROM tenants WHERE id = 1")->fetch();
    echo json_encode([
        'ok' => true,
        'turnos' => $turnos,
        'servicios' => $servicios,
        'total_tokens' => intval($tokens['total'] ?? 0),
        'tenant' => $tenant
    ]);
    exit;
}

if ($action === 'add_turno' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO turnos (cliente_nombre, cliente_telefono, modelo_auto, medidas_auto, servicio, fecha, hora, estado, created_at, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'CONFIRMADO', datetime('now','localtime'), 1)");
    $stmt->execute([
        trim($body['cliente'] ?? 'Cliente Web'),
        trim($body['telefono'] ?? '+5491100000000'),
        trim($body['auto'] ?? 'Vehículo'),
        trim($body['medidas'] ?? 'Estándar'),
        trim($body['servicio'] ?? 'Lavado Completo'),
        trim($body['fecha'] ?? date('Y-m-d')),
        trim($body['hora'] ?? '15:00')
    ]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

if ($action === 'toggle_estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($body['id'] ?? 0);
    $estado = trim($body['estado'] ?? 'CONFIRMADO');
    $stmt = $pdo->prepare("UPDATE turnos SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'log_token' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $summary = substr(trim($body['summary'] ?? 'Chat MIbot'), 0, 140);
    $inTok = intval($body['in_tokens'] ?? 12);
    $outTok = intval($body['out_tokens'] ?? 42);
    $stmt = $pdo->prepare("INSERT INTO token_logs (session_id, prompt_type, input_tokens, output_tokens, total_tokens, prompt_summary, user_identifier, timestamp, tenant_id) VALUES ('web_portfolio', 'CHAT_BOT', ?, ?, ?, ?, 'localhost', datetime('now','localtime'), 1)");
    $stmt->execute([$inTok, $outTok, $inTok + $outTok, $summary]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
