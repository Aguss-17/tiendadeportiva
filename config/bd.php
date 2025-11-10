<?php
/* HEADERS DE SEGURIDAD HTTP */
// Evita que la web sea cargada dentro de iframes (clickjacking)
header("X-Frame-Options: DENY");

// Evita que el navegador intente adivinar tipos MIME
header("X-Content-Type-Options: nosniff");

// Política de referer más segura
header("Referrer-Policy: strict-origin-when-cross-origin");

// Política de Contenido (CSP) segura y compatible con tu proyecto
header("Content-Security-Policy: default-src 'self';img-src 'self' data: https:;script-src 'self' https: 'unsafe-inline';style-src 'self' https: 'unsafe-inline';font-src 'self' https: data:;connect-src 'self' https:;frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com;child-src 'self' https://www.youtube.com https://www.youtube-nocookie.com;");

// HSTS: fuerza navegación HTTPS (solo si AlwaysData sirve la web por HTTPS)
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

/* SESIÓN + TOKEN CSRF */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* CONFIGURACIÓN DE ENTORNO */
define('ENV', getenv('ENV') ?: 'production'); // 'development' o 'production'

/* DATOS DE CONEXIÓN */
define('DB_HOST', getenv('DB_HOST') ?: 'mysql-agustina.alwaysdata.net');
define('DB_NAME', getenv('DB_NAME') ?: 'agustina_aurasport');
define('DB_USER', getenv('DB_USER') ?: 'agustina');
define('DB_PASS', getenv('DB_PASS') ?: '47422Agus');

/* CONEXIÓN PDO SEGURA */
function getConexion() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        $conexion = new PDO($dsn, DB_USER, DB_PASS);

        // Modo estricto y seguro
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Advertencias SOLO en desarrollo
        if (ENV === 'development') {
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        }

        return $conexion;

    } catch (PDOException $ex) {
        error_log("Error de conexión: " . $ex->getMessage());
        die("Error al conectar con la base de datos. Por favor, intente más tarde.");
    }
}

/* CREAR CONEXIÓN */
$conexion = getConexion();

/* SISTEMA DE CACHÉ */
require_once __DIR__ . '/../config/cache.php';

/* FUNCIÓN GLOBAL PARA OBTENER TOKEN CSRF */
if (!function_exists('csrf_token')) {
    function csrf_token() {
        return $_SESSION['csrf_token'] ?? '';
    }
}

/* MONITOREO DE CONSULTAS LENTAS */
/* REGISTRAR CONSULTAS LENTAS */
function logConsultaLenta($query, $tiempoEjecucion, $umbral = 0.1) {
    if ($tiempoEjecucion > $umbral) {

        // cortar query para el error_log principal
        $queryCorto = substr($query, 0, 200);
        error_log("🚨 CONSULTA LENTA: {$tiempoEjecucion}s - {$queryCorto}...");

        // archivo específico
        $logFile = __DIR__ . '/../logs/slow_queries.log';
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        $logEntry = date('Y-m-d H:i:s') . " | {$tiempoEjecucion}s | {$query}\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/* EJECUTAR CONSULTA MIDIENTE Y REGISTRAR SI ES LENTA */
function medirConsulta($conexion, $sql, $params = []) {
    $inicio = microtime(true);

    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tiempo = microtime(true) - $inicio;
        logConsultaLenta($sql, $tiempo);

        return $resultado;

    } catch (Exception $e) {
        error_log("Error en consulta medida: " . $e->getMessage());
        return [];
    }
}

?>
