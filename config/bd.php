<?php
// Headers de seguridad HTTP
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Iniciar sesión solo si no está activa - CORREGIDO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Configuración de entorno
define('ENV', getenv('ENV') ?: 'production'); // 'development' o 'production'

// Datos de conexión
define('DB_HOST', getenv('DB_HOST') ?: 'mysql-agustina.alwaysdata.net');
define('DB_NAME', getenv('DB_NAME') ?: 'agustina_aurasport');
define('DB_USER', getenv('DB_USER') ?: 'agustina');
define('DB_PASS', getenv('DB_PASS') ?: '47422Agus');

// Función para obtener conexión PDO
function getConexion() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        $conexion = new PDO($dsn, DB_USER, DB_PASS);

        // Configuración de PDO
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Opcional: mostrar advertencias en desarrollo
        if (ENV === 'development') {
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        }

        return $conexion;

    } catch (PDOException $ex) {
        // Loguear el error en el servidor
        error_log("Error de conexión: " . $ex->getMessage());

        // Mensaje genérico al usuario
        die("Error al conectar con la base de datos. Por favor, intente más tarde.");
    }
}

// Crear conexión
$conexion = getConexion();

// Incluir sistema de caché automáticamente
require_once __DIR__ . '/cache.php';

// Función helper global para obtener el token CSRF
if (!function_exists('csrf_token')) {
    function csrf_token() {
        return $_SESSION['csrf_token'] ?? '';
    }
}

?>