<?php
session_start();
require_once 'conexion.php';

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_name']    = $user['nombre'];
            $_SESSION['role']         = $user['rol'];
            $_SESSION['membresia_id'] = $user['membresia_id'] ?? 1;
            
            if ($user['rol'] === 'administrador') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $_SESSION['auth_error'] = "Correo electrónico o contraseña incorrectos.";
            header('Location: login.php');
            exit;
        }
    } else {
        $_SESSION['auth_error'] = "Por favor completa todos los campos.";
        header('Location: login.php');
        exit;
    }
} elseif ($action === 'register') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol      = $_POST['rol'] ?? 'usuario';

    // Normalizar rol
    if (!in_array($rol, ['usuario', 'instructor', 'administrador'])) {
        $rol = 'usuario';
    }

    if (!empty($nombre) && !empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $_SESSION['auth_error'] = "El correo electrónico ya está registrado.";
            header('Location: registro.php');
            exit;
        } else {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, membresia_id) VALUES (?, ?, ?, ?, 1)");
            
            if ($stmt->execute([$nombre, $email, $passwordHash, $rol])) {
                $_SESSION['user_id']      = $pdo->lastInsertId();
                $_SESSION['user_name']    = $nombre;
                $_SESSION['role']         = $rol;
                $_SESSION['membresia_id'] = 1;
                
                if ($rol === 'administrador') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                $_SESSION['auth_error'] = "Ocurrió un error al registrar la cuenta.";
                header('Location: registro.php');
                exit;
            }
        }
    } else {
        $_SESSION['auth_error'] = "Por favor completa todos los campos requeridos.";
        header('Location: registro.php');
        exit;
    }
}

header('Location: index.php');
exit;
?>