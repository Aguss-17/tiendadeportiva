<?php
include(__DIR__ . '/config/bd.php');

$error = '';
$success = '';
$user = null;

// Verificar token
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    try {
        // Buscar en tabla remember_tokens
        $sql = "SELECT u.id, u.nombre_completo, r.expires 
                FROM remember_tokens r 
                INNER JOIN usuarios u ON r.user_id = u.id 
                WHERE r.token = :token";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = [
                'id' => $result['id'],
                'nombre_completo' => $result['nombre_completo']
            ];

            // Verificar expiración
            if ($result['expires'] && strtotime($result['expires']) < time()) {
                $error = "El enlace ha expirado. Solicita uno nuevo.";
                $user = null;
            }
        } else {
            $error = "Enlace inválido o ya utilizado.";
        }
    } catch (PDOException $e) {
        error_log("Error en restablecer: " . $e->getMessage());
        $error = "Error del sistema. Intenta más tarde.";
    }
} else {
    $error = "Enlace de recuperación no válido.";
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $user) {
    $password = $_POST['contraseña'];
    $confirm = $_POST['confirmar'];

    // Validaciones
    if (empty($password) || empty($confirm)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{6,}$/', $password)) {
        $error = "La contraseña debe incluir mayúsculas, minúsculas y números.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Actualizar contraseña y eliminar token
            $conexion->beginTransaction();
            
            // Actualizar contraseña
            $sqlUpdate = "UPDATE usuarios SET contraseña = :pass WHERE id = :id";
            $stmtUpdate = $conexion->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':pass' => $hash,
                ':id' => $user['id']
            ]);

            // Eliminar token usado
            $sqlDelete = "DELETE FROM remember_tokens WHERE user_id = :user_id";
            $stmtDelete = $conexion->prepare($sqlDelete);
            $stmtDelete->execute([':user_id' => $user['id']]);

            $conexion->commit();

            $success = "Contraseña cambiada con éxito. Ya podés iniciar sesión.";
            $user = null;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al actualizar contraseña: " . $e->getMessage());
            $error = "Error al actualizar la contraseña. Intenta nuevamente.";
        }
    }
}
?>

<?php include(__DIR__ . '/estructura/cabecera.php'); ?>

<main class="d-flex justify-content-center align-items-start py-5">
    <div class="card p-4 shadow" style="width: 100%; max-width: 400px;">
        <h3>🔄 Restablecer Contraseña</h3>

        <?php if ($error && !$user): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <div class="text-center mt-3">
                <a href="recuperar.php" class="btn btn-dark">Solicitar nuevo enlace</a>
            </div>

        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-dark">Ir al Login</a>
            </div>

        <?php elseif (!$error && $user): ?>
            <p class="text-muted">Ingresa tu nueva contraseña</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="contraseña" class="form-label">Nueva Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="contraseña" name="contraseña" required minlength="6">
                        <span class="input-group-text" style="cursor: pointer;" id="togglePass">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                    <div class="form-text">Mínimo 6 caracteres, con mayúsculas, minúsculas y números.</div>
                </div>

                <div class="mb-3">
                    <label for="confirmar" class="form-label">Confirmar Contraseña</label>
                    <input type="password" class="form-control" id="confirmar" name="confirmar" required minlength="6">
                </div>

                <button type="submit" class="btn btn-dark w-100">Cambiar Contraseña</button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-3">
            <a href="login.php" class="text-secondary">← Volver al login</a>
        </div>
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