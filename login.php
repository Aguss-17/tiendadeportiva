<?php 
// Headers de seguridad HTTP
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once __DIR__ . '/config/bd.php';

// Verificar conexión
if (!isset($conexion) || $conexion === null) {
    die("Error: No se pudo establecer conexión con la base de datos");
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Función para obtener token (para el formulario)
function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

// Validar CSRF si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF inválido. Por seguridad, recarga la página e intenta nuevamente.");
    }
}

// FIN DE SEGURIDAD CSRF

// Manejo de redirección seguro
$allowed_redirects = ['exclusivo', 'perfil'];
$redirect = isset($_GET['redirect']) && in_array($_GET['redirect'], $allowed_redirects) ? $_GET['redirect'] : '';

// Inicializar contador de intentos de login
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;

// Bloqueo temporal por fuerza bruta
$max_attempts = 5;
$lockout_time = 300; // 5 minutos
if (!isset($_SESSION['last_attempt'])) $_SESSION['last_attempt'] = time();

if ($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['last_attempt']) < $lockout_time) {
    $error = "Demasiados intentos fallidos. Intente nuevamente más tarde.";
}

// Verificar si hay una cookie de "Recordarme" válida (CORREGIDO)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $token = $_COOKIE['remember_token'];
        
        // Buscar token válido en la base de datos
        $sql = "SELECT u.id, u.usuario 
                FROM usuarios u 
                INNER JOIN remember_tokens rt ON u.id = rt.user_id 
                WHERE rt.token = :token AND rt.expires > NOW()";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Login automático exitoso
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            $_SESSION['welcome_message'] = "¡Bienvenido/a de nuevo, " . htmlspecialchars($user['usuario']) . "!";

            // Redirección INMEDIATA
            if (!empty($redirect)) {
                if ($redirect === 'exclusivo') {
                    header("Location: index.php#exclusivo");
                    exit();
                } elseif ($redirect === 'perfil') {
                    header("Location: perfil.php");
                    exit();
                }
            }
            
            header("Location: index.php");
            exit();
            
        } else {
            // Token inválido o expirado, eliminar cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    } catch (PDOException $e) {
        error_log("Error en recordarme: " . $e->getMessage());
        // En caso de error, eliminar cookie por seguridad
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

// Procesar login si no está bloqueado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($error)) {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['contraseña'];
    $recordarme = isset($_POST['recordarme']) ? true : false;

    // Validaciones básicas
    if (empty($usuario) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (strlen($usuario) > 50 || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $usuario)) {
        $error = "Usuario inválido. Solo letras, números y guiones bajos, 3-50 caracteres.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    }

    // Si no hay errores, intentar login
    if (!isset($error)) {
        try {
            $sql = "SELECT id, usuario, contraseña FROM usuarios WHERE usuario = :usuario";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (password_verify($password, $user['contraseña'])) {
                    // Login exitoso
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['usuario'];
                    $_SESSION['welcome_message'] = "¡Bienvenido/a, " . htmlspecialchars($user['usuario']) . "!";

                    // Manejar opción "Recordarme" con base de datos (CORREGIDO)
                    if ($recordarme) {
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + (30 * 24 * 60 * 60); // 30 días
                        $expires_db = date('Y-m-d H:i:s', $expires);
                        
                        // Eliminar tokens antiguos del usuario
                        $sql_delete = "DELETE FROM remember_tokens WHERE user_id = :user_id";
                        $stmt_delete = $conexion->prepare($sql_delete);
                        $stmt_delete->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                        $stmt_delete->execute();
                        
                        // Insertar nuevo token
                        $sql_token = "INSERT INTO remember_tokens (user_id, token, expires) 
                                    VALUES (:user_id, :token, :expires)";
                        $stmt_token = $conexion->prepare($sql_token);
                        $stmt_token->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                        $stmt_token->bindParam(':token', $token, PDO::PARAM_STR);
                        $stmt_token->bindParam(':expires', $expires_db, PDO::PARAM_STR);
                        
                        if ($stmt_token->execute()) {
                            setcookie('remember_token', $token, $expires, '/', '', false, true);
                        }
                    } else {
                        if (isset($_COOKIE['remember_token'])) {
                            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
                            $sql_delete = "DELETE FROM remember_tokens WHERE token = :token";
                            $stmt_delete = $conexion->prepare($sql_delete);
                            $stmt_delete->bindParam(':token', $_COOKIE['remember_token'], PDO::PARAM_STR);
                            $stmt_delete->execute();
                        }
                    }

                    $_SESSION['login_attempts'] = 0;

                    if ($redirect === 'exclusivo') {
                        header("Location: index.php#exclusivo");
                    } elseif ($redirect === 'perfil') {
                        header("Location: perfil.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error = "Contraseña incorrecta.";
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt'] = time();
                }
            } else {
                $error = "Usuario no encontrado.";
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt'] = time();
            }
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = "Error en el sistema. Por favor, intente más tarde.";
        }
    }
}
?>

<?php include(__DIR__ . '/estructura/cabecera.php'); ?>

<main class="d-flex justify-content-center align-items-start py-5">
    <div class="card p-4 shadow" style="width: 100%; max-width: 400px;">
        <form method="post" novalidate>
            <!-- 🔒 TOKEN CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="mb-3">
                <h3>Iniciar Sesión</h3><br>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['welcome_message'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['welcome_message']); ?></div>
                    <?php unset($_SESSION['welcome_message']); ?>
                <?php endif; ?>

                <label for="usuario" class="form-label">Nombre de Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" 
                    value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" 
                    required maxlength="50" pattern="[A-Za-z0-9_]{3,50}" 
                    title="Solo letras, números y guiones bajos, 3-50 caracteres">
            </div>

            <div class="mb-3">
                <label for="contraseña" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="contraseña" name="contraseña" required minlength="6">
                    <span class="input-group-text" style="cursor: pointer;" id="togglePass">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="recordarme" name="recordarme" 
                    <?php echo (isset($_POST['recordarme']) && $_POST['recordarme']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="recordarme">Recordarme en este dispositivo</label>
            </div>

            <button type="submit" class="btn btn-dark w-100">Ingresar</button>

            <div class="text-center mt-2">
                <a href="recuperar.php" class="text-secondary">¿Olvidaste tu contraseña?</a>
            </div>

            <div class="text-center mt-3">
                <p>¿No tenés cuenta aún?</p>
                <a href="registro.php" class="text-secondary">Registrarme</a>
            </div>
        </form>
    </div>
</main>

<script>
function togglePassword() {
    const passInput = document.getElementById('contraseña');
    const eyeIcon = document.getElementById('eyeIcon');

    if (passInput.type === 'password') {
        passInput.type = 'text';
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
    } else {
        passInput.type = 'password';
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
    }
}

document.getElementById('togglePass').addEventListener('click', togglePassword);
</script>

<?php include(__DIR__ . '/estructura/pie.php'); ?>