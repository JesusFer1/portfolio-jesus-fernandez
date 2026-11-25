<?php
// SkillHub - Database Connection with Dual MySQL & SQLite Fallback

$dbFile = __DIR__ . '/skillhub.db';
$dbDriver = 'mysql';

$host = 'localhost';
$db   = 'skillhub';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$pdo = null;

try {
    // Intentar conexión con MySQL primero
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            =>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE =>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   =>false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    $dbDriver = 'mysql';
} catch (\PDOException $e) {
    // Si falla MySQL (por ejemplo, XAMPP no corriendo), utilizar SQLite embebido
    try {
        $pdo = new PDO("sqlite:" . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $dbDriver = 'sqlite';
    } catch (\PDOException $e2) {
        die("Error de conexión a la base de datos: " . $e2->getMessage());
    }
}
?>