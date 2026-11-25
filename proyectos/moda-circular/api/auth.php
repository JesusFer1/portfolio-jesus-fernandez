<?php
/**
 * API DE AUTENTICACIÓN Y SESIONES CON MYSQL
 * Ropero & Catálogo Escolar Solidario - Técnica 5
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'OPTIONS') {
    jsonResponse(['success' => true]);
}

// 1. VERIFICAR SESIÓN ACTUAL (No requiere conexión a DB)
if ($action === 'check') {
    if (!empty($_SESSION['user'])) {
        jsonResponse([
            'loggedIn' => true,
            'user' => $_SESSION['user']
        ]);
    } else {
        jsonResponse([
            'loggedIn' => false,
            'user' => null
        ]);
    }
}

// 2. CERRAR SESIÓN (LOGOUT - No requiere conexión a DB)
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    jsonResponse([
        'success' => true,
        'message' => 'Sesión cerrada correctamente'
    ]);
}

$db = getDBOrNull();

// 3. INICIAR SESIÓN (LOGIN)
if ($action === 'login' && $method === 'POST') {
    $input = getJsonInput();
    if (empty($input)) {
        $input = $_POST;
    }

    $identificador = trim($input['user'] ?? $input['username'] ?? $input['email'] ?? '');
    $password = trim($input['password'] ?? $input['pass'] ?? '');

    if (empty($identificador) || empty($password)) {
        jsonError("Debes ingresar usuario o correo y contraseña.");
    }

    $adminDefault = [
        'id' => 1,
        'rol_id' => 1,
        'username' => 'admin',
        'nombre' => 'Administrador',
        'apellido' => 'Escolar',
        'email' => 'admin@escuela.edu.ar',
        'telefono' => '',
        'rol_nombre' => 'Administrador',
        'isAdmin' => true
    ];

    // Si la base de datos no está disponible temporalmente, permitir acceso admin de contingencia
    if (!$db) {
        if ($identificador === 'admin' && ($password === 'admin123' || $password === 'admin')) {
            $_SESSION['user'] = $adminDefault;
            jsonResponse([
                'success' => true,
                'message' => 'Sesión de Administrador iniciada (Modo de contingencia)',
                'user' => $adminDefault,
                'db_connected' => false
            ]);
        }
        jsonError("Base de datos no disponible y credenciales no corresponden a administrador local.");
    }

    $stmt = $db->prepare("
        SELECT u.id, u.rol_id, u.username, u.nombre, u.apellido, u.email, u.password, u.telefono, r.nombre AS rol_nombre
        FROM usuarios u
        LEFT JOIN roles r ON u.rol_id = r.id
        WHERE u.username = :id1 OR u.email = :id2
        LIMIT 1
    ");
    $stmt->execute([':id1' => $identificador, ':id2' => $identificador]);
    $user = $stmt->fetch();

    if (!$user) {
        // Fallback para admin por defecto si no fue encontrado en la base de datos
        if ($identificador === 'admin' && ($password === 'admin123' || $password === 'admin')) {
            $_SESSION['user'] = $adminDefault;
            jsonResponse([
                'success' => true,
                'message' => 'Sesión iniciada correctamente',
                'user' => $adminDefault
            ]);
        }
        jsonError("Usuario o correo no encontrado.");
    }

    // Verificar contraseña (hash bcrypt, texto plano o admin123)
    $passwordValid = false;
    if (password_verify($password, $user['password'])) {
        $passwordValid = true;
    } elseif ($password === $user['password']) {
        $passwordValid = true;
    } elseif ($user['rol_id'] == 1 && ($password === 'admin123' || $password === 'admin')) {
        $passwordValid = true;
    }

    if (!$passwordValid) {
        jsonError("Contraseña incorrecta.");
    }

    $userData = [
        'id' => (int)$user['id'],
        'rol_id' => (int)$user['rol_id'],
        'username' => $user['username'],
        'nombre' => $user['nombre'],
        'apellido' => $user['apellido'],
        'email' => $user['email'],
        'telefono' => $user['telefono'],
        'rol_nombre' => $user['rol_nombre'] ?? ($user['rol_id'] == 1 ? 'Administrador' : 'Alumno'),
        'isAdmin' => ((int)$user['rol_id'] === 1)
    ];

    $_SESSION['user'] = $userData;

    jsonResponse([
        'success' => true,
        'message' => 'Sesión iniciada con éxito',
        'user' => $userData
    ]);
}

// 4. REGISTRARSE (ALUMNO O USUARIO)
if ($action === 'register' && $method === 'POST') {
    if (!$db) {
        jsonError("La base de datos no está disponible en este momento. Por favor, intenta más tarde.");
    }

    $input = getJsonInput();
    if (empty($input)) {
        $input = $_POST;
    }

    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $telefono = trim($input['telefono'] ?? '');

    if (empty($nombre) || empty($username) || empty($email) || empty($password)) {
        jsonError("Por favor completa todos los campos requeridos (nombre, usuario, email, contraseña).");
    }

    $checkStmt = $db->prepare("SELECT id FROM usuarios WHERE username = :u OR email = :e LIMIT 1");
    $checkStmt->execute([':u' => $username, ':e' => $email]);
    if ($checkStmt->fetch()) {
        jsonError("El nombre de usuario o correo electrónico ya está registrado.");
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $rolId = 2; // Alumno

    $insertStmt = $db->prepare("
        INSERT INTO usuarios (rol_id, username, nombre, apellido, email, password, telefono)
        VALUES (:rol, :u, :nom, :ape, :email, :pass, :tel)
    ");
    $insertStmt->execute([
        ':rol' => $rolId,
        ':u' => $username,
        ':nom' => $nombre,
        ':ape' => $apellido,
        ':email' => $email,
        ':pass' => $passwordHash,
        ':tel' => $telefono
    ]);

    $newId = (int)$db->lastInsertId();

    $userData = [
        'id' => $newId,
        'rol_id' => $rolId,
        'username' => $username,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'telefono' => $telefono,
        'rol_nombre' => 'Alumno',
        'isAdmin' => false
    ];

    $_SESSION['user'] = $userData;

    jsonResponse([
        'success' => true,
        'message' => 'Cuenta registrada correctamente',
        'user' => $userData
    ], 201);
}

jsonError("Acción no válida o no especificada.");
