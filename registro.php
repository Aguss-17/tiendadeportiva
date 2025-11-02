<?php 
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/config/email.php';

// Verificar conexión
if (!isset($conexion) || $conexion === null) {
    die("Error: No se pudo establecer conexión con la base de datos");
}

/* FUNCIÓN PARA CREAR/GESTIONAR TOKEN CSRF */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/* VALIDAR TOKEN CSRF ANTES DE PROCESAR POST */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF inválido. Por seguridad, recarga la página e intenta nuevamente.");
    }
    // Si el token es válido, continúa el proceso de registro más abajo 👇
}

/* VERIFICACIÓN DE EMAIL DESDE TOKEN */
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    if (!empty($token)) {
        try {
            $sql = "SELECT id, email, expiracion_token FROM usuarios WHERE token_verificacion = :token AND verificado = 0";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                if (strtotime($usuario['expiracion_token']) > time()) {
                    $sql_update = "UPDATE usuarios SET verificado = 1, token_verificacion = NULL, expiracion_token = NULL WHERE id = :id";
                    $stmt_update = $conexion->prepare($sql_update);
                    $stmt_update->bindParam(':id', $usuario['id'], PDO::PARAM_INT);
                    
                    if ($stmt_update->execute()) {
                        $_SESSION['success'] = "¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.";
                    } else {
                        $_SESSION['error'] = "Error al actualizar la verificación.";
                    }
                } else {
                    $_SESSION['error'] = "El enlace de verificación ha expirado. Por favor, regístrate nuevamente.";
                }
            } else {
                $_SESSION['error'] = "Token de verificación inválido o cuenta ya verificada.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al verificar la cuenta: " . $e->getMessage();
        }
        
        header("Location: registro.php");
        exit();
    }
}

/* MENSAJE DE VERIFICACIÓN PENDIENTE */
if (isset($_GET['verificacion']) && $_GET['verificacion'] == 'pendiente') {
    include(__DIR__ . '/estructura/cabecera.php');
    ?>
    <main class="d-flex justify-content-center align-items-center py-5">
        <div class="card p-4 shadow text-center" style="width: 100%; max-width: 600px;">
            <h3>✅ Verificación Pendiente</h3>
            
            <?php if (isset($_SESSION['verification_email'])): ?>
                <p>Hemos enviado un enlace de verificación a: <strong><?php echo $_SESSION['verification_email']; ?></strong></p>
            <?php else: ?>
                <p>Hemos enviado un enlace de verificación a tu email.</p>
            <?php endif; ?>
            
            <p>Por favor, revisa tu bandeja de entrada y haz clic en el enlace para activar tu cuenta.</p>
            
            <div class="alert alert-warning mt-3">
                <strong>¿No recibiste el email?</strong>
                <ul class="text-start mt-2">
                    <li>Revisa tu carpeta de spam o correo no deseado</li>
                    <li>Verifica que escribiste correctamente tu email</li>
                    <li>Espera unos minutos (puede haber demoras)</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <a href="login.php" class="btn btn-black me-2">Ir al Login</a>
                <a href="registro.php" class="btn btn-outline-secondary">Volver al Registro</a>
            </div>
        </div>
    </main>
    <?php 
    unset($_SESSION['verification_pending'], $_SESSION['verification_email']);
    include(__DIR__ . '/estructura/pie.php'); 
    exit();
}

/* PROCESAR REGISTRO (si POST válido) */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_completo = trim($_POST['nombreyapellido']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $usuario = trim($_POST['usuario']);
    $password = $_POST['contraseña'];
    $confirm_password = $_POST['repetircontraseña'];
    
    $errors = [];

    // Validaciones
    if (empty($nombre_completo)) $errors[] = "El nombre completo es requerido";
    elseif (strlen($nombre_completo) > 50) $errors[] = "El nombre completo no puede superar 50 caracteres";

    if (empty($email)) $errors[] = "El email es requerido";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "El formato del email no es válido";
    elseif (strlen($email) > 100) $errors[] = "El email no puede superar 100 caracteres";

    if (empty($usuario)) $errors[] = "El nombre de usuario es requerido";
    elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $usuario)) $errors[] = "El nombre de usuario solo puede contener letras, números y guiones bajos, 3-20 caracteres";

    if (empty($telefono)) $errors[] = "El teléfono es requerido";
    elseif (!preg_match('/^\d{4}\s?\d{2}-\d{4}$/', $telefono)) $errors[] = "Formato de teléfono inválido. Ej: 1111 22-3344";

    if ($password !== $confirm_password) $errors[] = "Las contraseñas no coinciden";
    elseif (strlen($password) < 6) $errors[] = "La contraseña debe tener al menos 6 caracteres";
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{6,}$/', $password)) $errors[] = "La contraseña debe incluir mayúsculas, minúsculas y números";

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $token_verificacion = bin2hex(random_bytes(32));
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        try {
            $sql = "INSERT INTO usuarios (nombre_completo, usuario, email, telefono, contraseña, token_verificacion, expiracion_token, verificado) 
                    VALUES (:nombre, :usuario, :email, :telefono, :contrasena, :token, :expiracion, 0)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':nombre', $nombre_completo);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':contrasena', $password_hash);
            $stmt->bindParam(':token', $token_verificacion);
            $stmt->bindParam(':expiracion', $fecha_expiracion);
            
            if ($stmt->execute()) {
                // USAR LA FUNCIÓN DE EMAIL.PHP
                if (enviarEmailVerificacion($email, $token_verificacion, $nombre_completo)) {
                    $_SESSION['verification_pending'] = true;
                    $_SESSION['verification_email'] = $email;
                    header("Location: registro.php?verificacion=pendiente");
                    exit();
                } else {
                    $errors[] = "Error al enviar el email de verificación. Contacta al administrador.";
                }
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                if (strpos($e->getMessage(), 'usuario') !== false) $errors[] = "El nombre de usuario ya está registrado";
                elseif (strpos($e->getMessage(), 'email') !== false) $errors[] = "El email ya está registrado";
                else $errors[] = "Email o usuario duplicado";
            } else {
                error_log("Error en registro: " . $e->getMessage());
                $errors[] = "Ocurrió un error al registrar. Por favor, intente nuevamente.";
            }
        }
    }
}

/* INTERFAZ DEL FORMULARIO */
include(__DIR__ . '/estructura/cabecera.php'); 
?>

<main class="d-flex justify-content-center align-items-start py-5">
    <div class="card p-4 shadow" style="width: 100%; max-width: 700px;">
        <form method="post" novalidate>
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="mb-3">
                <h3>Registrarme</h3><br>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <strong>Importante:</strong> Después del registro, recibirás un email para verificar tu cuenta.
                </div>

                <label for="nombreyapellido" class="form-label">Nombre y Apellido</label>
                <input type="text" class="form-control" id="nombreyapellido" name="nombreyapellido"
                    value="<?php echo htmlspecialchars($_POST['nombreyapellido'] ?? ''); ?>"
                    placeholder="Ej: Ana Romero" required maxlength="50">
            </div>

            <label for="usuario" class="form-label">Nombre de Usuario</label>
            <input type="text" class="form-control" id="usuario" name="usuario"
                value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
                required pattern="[A-Za-z0-9_]{3,20}" maxlength="20"><br>

            <label for="email" class="form-label">Correo Electrónico</label>
            <input type="email" class="form-control" id="email" name="email"
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                placeholder="Ej: tunombre@gmail.com" required maxlength="100"><br>

            <label for="telefono" class="form-label">Número de teléfono</label>
            <input type="tel" class="form-control" id="telefono" name="telefono"
                value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                placeholder="Ej: 1111 22-3344" required pattern="\d{4}\s?\d{2}-\d{4}"><br>

            <label for="contraseña" class="form-label">Contraseña</label>
            <div class="input-group mb-3">
                <input type="password" class="form-control" id="contraseña" name="contraseña" required minlength="6">
                <span class="input-group-text" id="togglePass" style="cursor:pointer;">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </span>
            </div>

            <label for="repetircontraseña" class="form-label">Repetir contraseña</label>
            <div class="input-group mb-3">
                <input type="password" class="form-control" id="repetircontraseña" name="repetircontraseña" required minlength="6">
                <span class="input-group-text" id="togglePass2" style="cursor:pointer;">
                    <i class="bi bi-eye" id="eyeIcon2"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-dark w-100">Crear cuenta</button>

            <div class="text-center mt-3">
                <p>¿Ya tenés una cuenta?</p>
                <a href="login.php" class="text-secondary">Iniciar Sesión</a>
            </div>
        </form>
    </div>
</main>

<script>
function togglePassword(idInput, idIcon) {
    const input = document.getElementById(idInput);
    const icon = document.getElementById(idIcon);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
document.getElementById('togglePass').onclick = () => togglePassword('contraseña', 'eyeIcon');
document.getElementById('togglePass2').onclick = () => togglePassword('repetircontraseña', 'eyeIcon2');
</script>

<?php include(__DIR__ . '/estructura/pie.php'); ?>