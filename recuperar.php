<?php
include(__DIR__ . '/config/bd.php');
include(__DIR__ . '/config/email.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Ingresa un correo válido.";
    } else {
        try {
            // Buscar usuario
            $sql = "SELECT id, nombre_completo, verificado FROM usuarios WHERE LOWER(email) = LOWER(:email)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generar token seguro
                $token = bin2hex(random_bytes(32));
                $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

                // Guardar en tabla remember_tokens
                $sqlInsert = "INSERT INTO remember_tokens (user_id, token, expires, created_at) 
                            VALUES (:user_id, :token, :expires, NOW())";
                
                $stmtInsert = $conexion->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':user_id' => $user['id'],
                    ':token' => $token,
                    ':expires' => $expira
                ]);

                // Enviar email con link de recuperación
                if (enviarEmailRecuperacion($email, $token, $user['nombre_completo'])) {
                    $_SESSION['success'] = "Se ha enviado un correo con instrucciones para restablecer la contraseña.";
                } else {
                    $_SESSION['error'] = "Error al enviar el email. Por favor, contacta al administrador.";
                }
            } else {
                $_SESSION['error'] = "No existe una cuenta con este email.";
            }
        } catch (PDOException $e) {
            error_log("Error en recuperación: " . $e->getMessage());
            $_SESSION['error'] = "Error del sistema. Intenta más tarde.";
        }
    }
    
    header("Location: recuperar.php");
    exit();
}
?>

<?php include(__DIR__ . '/estructura/cabecera.php'); ?>
<main class="d-flex justify-content-center align-items-start py-5">
    <div class="card p-4 shadow" style="width: 100%; max-width: 400px;">
        <form method="post" novalidate>
            <h3>Recuperar Contraseña</h3><br>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                ?></div>
            <?php elseif (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']); 
                ?></div>
            <?php endif; ?>
            
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email" 
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            <button type="submit" class="btn btn-dark w-100 mt-3">Enviar</button>
            <div class="text-center mt-3">
                <a href="login.php" class="text-secondary">Volver al login</a>
            </div>
        </form>
    </div>
</main>
<?php include(__DIR__ . '/estructura/pie.php'); ?>