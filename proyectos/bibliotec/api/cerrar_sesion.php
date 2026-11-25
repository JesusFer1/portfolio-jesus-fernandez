<?php
session_start();
// Destruimos todas las variables guardadas en la sesión del servidor
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
// Redirigimos al inicio de la biblioteca
header("Location: ../index.php");
exit();