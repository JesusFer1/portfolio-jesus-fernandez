<?php
/**
 * CONFIGURACIÓN CENTRAL DE BASE DE DATOS Y UTILIDADES DE API
 * Ropero & Catálogo Escolar Solidario - Técnica 5
 * Compatible con InfinityFree y XAMPP local
 */

// Evitar que advertencias o notices de PHP corrompan las respuestas JSON
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
@ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    @session_start();
}

// Manejo temprano de solicitudes preflight OPTIONS (CORS)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    http_response_code(200);
    exit;
}

// Detección automática de entorno (Localhost / Servidor InfinityFree)
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = ($httpHost === 'localhost' || strpos($httpHost, 'localhost:') === 0 || 
            $httpHost === '127.0.0.1' || strpos($httpHost, '127.0.0.1:') === 0 ||
            strpos($httpHost, '192.168.') === 0 || strpos($httpHost, '10.') === 0 || strpos($httpHost, '172.') === 0 ||
            (php_sapi_name() === 'cli' && empty(getenv('INFINITY_SERVER'))));

if ($isLocal) {
    // Entorno local con XAMPP
    $DB_HOST = '127.0.0.1';
    $DB_PORT = '3306';
    $DB_NAME = 'ropero_escolar';
    $DB_USER = 'root';
    $DB_PASS = '';
} else {
    // Entorno servidor InfinityFree
    $DB_HOST = 'sql312.infinityfree.com';         
    $DB_PORT = '3306';              
    $DB_NAME = 'if0_43211220_tec5';     
    $DB_USER = 'if0_43211220';              
    $DB_PASS = 'estemen6666';
}

// Carga de archivo local personalizado si existe
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

/**
 * Obtener conexión PDO a la base de datos MySQL
 * Si falla y $exitOnError es true, emite jsonError y finaliza.
 * Si $exitOnError es false, retorna null.
 */
function getDB($exitOnError = true) {
    global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS, $httpHost;
    
    static $pdo = null;
    static $hasCheckedSchema = false;

    // En Render no existe acceso externo al MySQL de InfinityFree: responder al instante en modo demo
    if (getenv('RENDER') || stripos($httpHost ?? '', 'onrender.com') !== false) {
        if ($exitOnError) {
            jsonError('Modo demostración sin MySQL externo.', 503);
        }
        return null;
    }

    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 1,
    ];

    // Intento 1: Conexión con los parámetros configurados directamente
    try {
        $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    } catch (\PDOException $e1) {
        $pdo = null;
    }

    // Intento 2: Si falló la base de datos específica (por ej. typo o prefijo en InfinityFree),
    // conectar sin dbname y descubrir la base de datos real del usuario
    if (!$pdo) {
        try {
            $dsnNoDb = "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4";
            $tempPdo = new PDO($dsnNoDb, $DB_USER, $DB_PASS, $options);
            $stmt = $tempPdo->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $matchedDB = null;
            // 1. Buscar base de datos que contenga ropero o escolar
            foreach ($databases as $dbCandidate) {
                if (in_array(strtolower($dbCandidate), ['information_schema', 'performance_schema', 'mysql', 'sys'])) continue;
                if (stripos($dbCandidate, 'ropero') !== false || stripos($dbCandidate, 'escolar') !== false) {
                    $matchedDB = $dbCandidate;
                    break;
                }
            }
            // 2. Si no, buscar la asociada al usuario
            if (!$matchedDB && $DB_USER) {
                foreach ($databases as $dbCandidate) {
                    if (in_array(strtolower($dbCandidate), ['information_schema', 'performance_schema', 'mysql', 'sys'])) continue;
                    if (stripos($dbCandidate, $DB_USER) !== false) {
                        $matchedDB = $dbCandidate;
                        break;
                    }
                }
            }
            // 3. Si no, primer esquema de usuario disponible
            if (!$matchedDB) {
                foreach ($databases as $dbCandidate) {
                    if (!in_array(strtolower($dbCandidate), ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
                        $matchedDB = $dbCandidate;
                        break;
                    }
                }
            }

            if ($matchedDB) {
                $tempPdo->exec("USE `{$matchedDB}`");
                $pdo = $tempPdo;
                $DB_NAME = $matchedDB;
            }
        } catch (\PDOException $e2) {
            $pdo = null;
        }
    }

    // Intento 3: Conmutación por fallo de red / entorno (por ej. celular en red local accediendo por IP)
    if (!$pdo) {
        $altHost = ($DB_HOST === '127.0.0.1' || $DB_HOST === 'localhost') ? 'sql312.infinityfree.com' : '127.0.0.1';
        $altUser = ($altHost === '127.0.0.1') ? 'root' : 'if0_43211220';
        $altPass = ($altHost === '127.0.0.1') ? '' : 'estemen666';
        $altDb   = ($altHost === '127.0.0.1') ? 'ropero_escolar' : 'if0_43211220_ropero_escolar';

        try {
            $dsnAlt = "mysql:host={$altHost};port=3306;dbname={$altDb};charset=utf8mb4";
            $pdo = new PDO($dsnAlt, $altUser, $altPass, $options);
            $DB_HOST = $altHost;
            $DB_USER = $altUser;
            $DB_PASS = $altPass;
            $DB_NAME = $altDb;
        } catch (\PDOException $e3) {
            $pdo = null;
        }
    }

    if ($pdo) {
        if (!$hasCheckedSchema) {
            $hasCheckedSchema = true;
            initDBSchema($pdo);
        }
        return $pdo;
    }

    if ($exitOnError) {
        jsonError("No se pudo conectar a la base de datos MySQL en '{$DB_HOST}'. Verifica las credenciales en 'api/config.php'.", 500, [
            'host' => $DB_HOST,
            'database' => $DB_NAME
        ]);
        exit;
    }

    return null;
}

/**
 * Obtener conexión a base de datos de manera segura (sin forzar salida 500)
 */
function getDBOrNull() {
    return getDB(false);
}

/**
 * Auto-inicializador del esquema de base de datos
 * Garantiza que si la base de datos está vacía en InfinityFree, las tablas se creen
 * automáticamente y contengan los datos esenciales para funcionar.
 */
function initDBSchema($pdo) {
    try {
        // 1. Roles
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `roles` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `nombre` VARCHAR(50) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("INSERT IGNORE INTO `roles` (`id`, `nombre`) VALUES (1, 'Administrador'), (2, 'Alumno'), (3, 'Docente');");

        // 2. Usuarios
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `usuarios` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `rol_id` INT NOT NULL DEFAULT 2,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `nombre` VARCHAR(100) NOT NULL,
                `apellido` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `telefono` VARCHAR(30) NULL,
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Comprobar si existe el admin, sino crearlo con admin123
        $stmtAdmin = $pdo->prepare("SELECT id FROM usuarios WHERE username = 'admin' LIMIT 1");
        $stmtAdmin->execute();
        if (!$stmtAdmin->fetch()) {
            $passHash = password_hash('admin123', PASSWORD_BCRYPT);
            $pdo->prepare("
                INSERT INTO `usuarios` (`id`, `rol_id`, `username`, `nombre`, `apellido`, `email`, `password`, `telefono`)
                VALUES (1, 1, 'admin', 'Administrador', 'Escolar', 'admin@escuela.edu.ar', :p, '1100000000')
            ")->execute([':p' => $passHash]);
        }

        // 3. Etiquetas QR
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `etiquetas_qr` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `codigo` VARCHAR(100) NOT NULL UNIQUE,
                `prenda_id` INT NULL,
                `estado` ENUM('libre', 'ocupado') DEFAULT 'libre',
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Prendas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `prendas` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `codigo_qr` VARCHAR(100) NOT NULL UNIQUE,
                `titulo` VARCHAR(150) NOT NULL,
                `categoria` VARCHAR(50) NOT NULL,
                `talle` VARCHAR(20) NOT NULL,
                `color` VARCHAR(50) NOT NULL,
                `color_hex` VARCHAR(20) DEFAULT '#2563eb',
                `condicion` VARCHAR(50) DEFAULT 'Excelente',
                `descripcion` TEXT NULL,
                `donante` VARCHAR(100) DEFAULT 'Anónimo',
                `imagen_url` LONGTEXT NULL,
                `estado` VARCHAR(50) NOT NULL DEFAULT 'Disponible',
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Útiles
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `utiles` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `codigo_qr` VARCHAR(100) NOT NULL UNIQUE,
                `nombre` VARCHAR(150) NOT NULL,
                `categoria` VARCHAR(50) NOT NULL,
                `cantidad` INT NOT NULL DEFAULT 1,
                `condicion` VARCHAR(50) DEFAULT 'Excelente',
                `descripcion` TEXT NULL,
                `donante` VARCHAR(100) DEFAULT 'Anónimo',
                `imagen_url` LONGTEXT NULL,
                `estado` VARCHAR(50) NOT NULL DEFAULT 'Disponible',
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. Foro
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `foro_posts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `categoria` VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
                `titulo` VARCHAR(255) NOT NULL,
                `texto` TEXT NULL,
                `autor` VARCHAR(100) NOT NULL DEFAULT 'u/Anónimo',
                `imagen_url` LONGTEXT NULL,
                `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
                `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
                `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
                `is_poll` TINYINT(1) NOT NULL DEFAULT 0,
                `score` INT NOT NULL DEFAULT 0,
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `foro_poll_opciones` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `post_id` INT NOT NULL,
                `opcion_texto` VARCHAR(255) NOT NULL,
                `votos` INT NOT NULL DEFAULT 0,
                FOREIGN KEY (`post_id`) REFERENCES `foro_posts`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `foro_respuestas` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `post_id` INT NOT NULL,
                `autor` VARCHAR(100) NOT NULL DEFAULT 'u/Anónimo',
                `texto` TEXT NOT NULL,
                `imagen_url` LONGTEXT NULL,
                `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`post_id`) REFERENCES `foro_posts`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 7. Reservas / Turnos de Prendas y Útiles
        $pdo->exec("
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

        // 8. Notificaciones de la Comunidad (Foro, Reservas y Avisos)
        $pdo->exec("
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

        // Sembrar post inicial si no hay posts
        $cntPosts = (int)$pdo->query("SELECT COUNT(*) FROM `foro_posts`")->fetchColumn();
        if ($cntPosts === 0) {
            $pdo->exec("
                INSERT INTO `foro_posts` (`id`, `categoria`, `titulo`, `texto`, `autor`, `imagen_url`, `is_admin`, `is_pinned`, `is_locked`, `is_poll`, `score`) VALUES
                (1, 'AVISOS', '¡Bienvenidos al Ropero & Catálogo Solidario de la Técnica 5!', 'Este espacio comunitario permite compartir prendas, útiles escolares y novedades para toda la comunidad educativa. Podés solicitar prendas, coordinar donaciones y participar en las encuestas comunitarias.', 'u/Administrador', NULL, 1, 1, 0, 0, 15);
            ");
        }

    } catch (\Exception $e) {
        // Silencioso para no romper flujo si el usuario de la DB tiene permisos limitados
    }
}

/**
 * Enviar respuesta en formato JSON con cabeceras CORS
 */
function jsonResponse($data, $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Enviar error en formato JSON
 */
function jsonError($message, $statusCode = 400, $extra = []) {
    $payload = array_merge([
        'success' => false,
        'error' => $message
    ], $extra);
    jsonResponse($payload, $statusCode);
}

/**
 * Obtener cuerpo de solicitud JSON (POST / PUT)
 */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Procesar y guardar imagen (Base64 o URL)
 */
function saveUploadedImage($imageData, $prefix = 'prenda') {
    if (empty($imageData)) {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $imageData) || strpos($imageData, 'uploads/') === 0) {
        return $imageData;
    }

    if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $imageData, $matches)) {
        $ext = strtolower($matches[1]);
        if ($ext === 'jpeg') $ext = 'jpg';
        $base64 = $matches[2];
        $binary = base64_decode($base64);

        if ($binary === false) {
            return $imageData;
        }

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $filepath = $uploadDir . $filename;
        
        if (@file_put_contents($filepath, $binary) !== false) {
            return 'uploads/' . $filename;
        }
    }

    return $imageData;
}

/**
 * Procesar y guardar múltiples imágenes (array o string)
 * Retorna array de rutas relativas guardadas
 */
function saveMultipleUploadedImages($imagesData, $prefix = 'item') {
    if (empty($imagesData)) {
        return [];
    }

    $list = [];
    if (is_array($imagesData)) {
        $list = $imagesData;
    } elseif (is_string($imagesData)) {
        $decoded = json_decode($imagesData, true);
        if (is_array($decoded)) {
            $list = $decoded;
        } else {
            $list = [$imagesData];
        }
    }

    $saved = [];
    foreach ($list as $item) {
        if (!empty($item)) {
            $res = saveUploadedImage($item, $prefix);
            if ($res) {
                $saved[] = $res;
            }
        }
    }

    return $saved;
}

/**
 * Normalizar imagen guardada a lista de URLs
 */
function parseStoredImages($rawImageUrl) {
    if (empty($rawImageUrl)) return [];
    if (is_array($rawImageUrl)) return $rawImageUrl;
    $rawImageUrl = trim((string)$rawImageUrl);
    if (strpos($rawImageUrl, '[') === 0) {
        $decoded = json_decode($rawImageUrl, true);
        if (is_array($decoded)) return $decoded;
    }
    return [$rawImageUrl];
}
