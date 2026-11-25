<?php
/**
 * Test de Conexión a Base de Datos MySQL
 * Permite comprobar si la conexión a localhost o al servidor de InfinityFree está activa.
 */

require_once __DIR__ . '/config.php';

$db = getDBOrNull();

if (!$db) {
    jsonResponse([
        'success' => false,
        'message' => "No se pudo conectar a MySQL en '{$DB_HOST}'. La aplicación funcionará en modo local / contingencia.",
        'host' => $DB_HOST,
        'database' => $DB_NAME,
        'tables' => [],
        'counts' => [
            'prendas' => 0,
            'utiles' => 0,
            'usuarios' => 0,
            'foro_posts' => 0
        ]
    ]);
}

try {
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $prendasCount = 0;
    $utilesCount = 0;
    $usuariosCount = 0;
    $postsCount = 0;

    if (in_array('prendas', $tables)) {
        $prendasCount = (int)$db->query("SELECT COUNT(*) FROM prendas")->fetchColumn();
    }
    if (in_array('utiles', $tables)) {
        $utilesCount = (int)$db->query("SELECT COUNT(*) FROM utiles")->fetchColumn();
    }
    if (in_array('usuarios', $tables)) {
        $usuariosCount = (int)$db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    }
    if (in_array('foro_posts', $tables)) {
        $postsCount = (int)$db->query("SELECT COUNT(*) FROM foro_posts")->fetchColumn();
    }

    $serverVersion = $db->getAttribute(PDO::ATTR_SERVER_VERSION);

    jsonResponse([
        'success' => true,
        'message' => '¡Conexión a MySQL exitosa!',
        'host' => $DB_HOST,
        'database' => $DB_NAME,
        'server_version' => $serverVersion,
        'tables' => $tables,
        'counts' => [
            'prendas' => $prendasCount,
            'utiles' => $utilesCount,
            'usuarios' => $usuariosCount,
            'foro_posts' => $postsCount
        ]
    ]);
} catch (\Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => "Error al consultar tablas en MySQL: " . $e->getMessage(),
        'host' => $DB_HOST,
        'database' => $DB_NAME
    ]);
}
