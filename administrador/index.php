<?php
require_once __DIR__ . '/../config/bd.php';

// Inicializar contador de intentos si no existe
if (!isset($_SESSION['intentos_login'])) {
    $_SESSION['intentos_login'] = 0;
}

// Configurar límite de intentos
$limite_intentos = 5;
$bloqueo_tiempo = 300;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar si el usuario está bloqueado
    if (isset($_SESSION['bloqueo_hasta']) && time() < $_SESSION['bloqueo_hasta']) {
        $tiempo_restante = $_SESSION['bloqueo_hasta'] - time();
        $mensaje = "Demasiados intentos fallidos. Espere " . ceil($tiempo_restante / 60) . " minutos antes de intentar nuevamente.";
    } else {
        // Remover bloqueo si ya expiró
        unset($_SESSION['bloqueo_hasta']);
        $_SESSION['intentos_login'] = 0;
        
        // Sanitizar entradas
        $usuario = htmlspecialchars(strip_tags(trim($_POST['usuario'] ?? '')), ENT_QUOTES, 'UTF-8');
        $contrasenia = trim($_POST['contrasenia'] ?? '');
        
        // Validar campos vacíos
        if (empty($usuario) || empty($contrasenia)) {
            $mensaje = "Por favor, complete todos los campos.";
        } else {
            // Buscar usuario en base de datos
            $sentenciaSQL = $conexion->prepare("SELECT id, usuario, contrasenia FROM administradores WHERE usuario = :usuario");
            $sentenciaSQL->execute([':usuario' => $usuario]);
            $admin = $sentenciaSQL->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($contrasenia, $admin['contrasenia'])) {
                // Login exitoso: resetear intentos
                $_SESSION['intentos_login'] = 0;
                $_SESSION['usuario'] = "ok";
                $_SESSION['nombreUsuario'] = $admin['usuario'];
                $_SESSION['admin_id'] = $admin['id'];
                header('Location: inicio.php');
                exit();
            } else {
                // Login fallido: incrementar contador
                $_SESSION['intentos_login']++;
                
                if ($_SESSION['intentos_login'] >= $limite_intentos) {
                    $_SESSION['bloqueo_hasta'] = time() + $bloqueo_tiempo;
                    $mensaje = "Demasiados intentos fallidos. Su cuenta ha sido bloqueada por " . ($bloqueo_tiempo / 60) . " minutos.";
                } else {
                    $intentos_restantes = $limite_intentos - $_SESSION['intentos_login'];
                    $mensaje = "Usuario o contraseña incorrectos. Intentos restantes: " . $intentos_restantes;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Administrador</title>
    <link rel="stylesheet" href="/css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-lg rounded-4 border-0">
                <div class="card-header bg-dark text-white text-center rounded-top-4">
                    <h4 class="mb-0">Iniciar Sesión</h4>
                </div>
                <div class="card-body p-4">

                    <!-- Mensaje de error o aviso -->
                    <?php if (isset($mensaje)): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Atención:</strong> <?= htmlspecialchars($mensaje) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Mostrar tiempo de bloqueo si está activo -->
                    <?php if (isset($_SESSION['bloqueo_hasta']) && time() < $_SESSION['bloqueo_hasta']): ?>
                        <?php $tiempo_restante = $_SESSION['bloqueo_hasta'] - time(); ?>
                        <div class="alert alert-warning" role="alert">
                            <strong>Cuenta bloqueada:</strong> Tiempo restante: <?= ceil($tiempo_restante / 60) ?> minutos.
                        </div>
                    <?php endif; ?>

                    <form method="POST" 
                        <?= (isset($_SESSION['bloqueo_hasta']) && time() < $_SESSION['bloqueo_hasta']) ? 'onsubmit="return false;"' : '' ?>>
                        
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="usuario" name="usuario"
                                placeholder="Ingrese su usuario"
                                value="<?= isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : '' ?>"
                                <?= (isset($_SESSION['bloqueo_hasta']) && time() < $_SESSION['bloqueo_hasta']) ? 'disabled' : '' ?>>
                        </div>

                        <div class="mb-3">
                            <label for="contrasenia" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="contrasenia" name="contrasenia"
                                placeholder="Ingrese su contraseña"
                                <?= (isset($_SESSION['bloqueo_hasta']) && time() < $_SESSION['bloqueo_hasta']) ? 'disabled' : '' ?>>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark"
                                <?= (isset($_SESSION['bloqueo_hasta']) && time() < $_SESSION['bloqueo_hasta']) ? 'disabled' : '' ?>>
                                Ingresar Administrador
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</body>
</html>