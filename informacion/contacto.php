<section style="background-color: #dec19e;">
<?php 
include('../config/bd.php');
include('../estructura/cabecera.php');

// CLAVES CAPTCHA
define('RECAPTCHA_SITE_KEY', '6Ldgc9orAAAAAMQduder6PADc41o5KbiXIQBXRH3');
define('RECAPTCHA_SECRET_KEY', '6Ldgc9orAAAAAI4KFx2vM-6PSp67GF9iX-ts8gbY');

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'contacto';
$titulo = ($tipo == 'consulta') ? 'Consultas y reclamos' : '¡Contáctanos!';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_apellido = trim(strip_tags($_POST['nombreyapellido'] ?? ''));
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $telefono = trim(strip_tags($_POST['telefono'] ?? ''));
    $asunto = trim(strip_tags($_POST['asunto'] ?? ''));
    $mensaje = trim(strip_tags($_POST['mensaje'] ?? ''));
    $tipo_consulta = $_POST['tipo_consulta'] ?? $tipo;
    $captcha_response = $_POST['g-recaptcha-response'] ?? '';

    $errores = [];

    // Validar CAPTCHA
    if (empty($captcha_response)) {
        $errores[] = "Por favor, verifica que no eres un robot.";
    } else {
        $captcha_valido = validarCaptcha($captcha_response);
        if (!$captcha_valido) {
            $errores[] = "Error en la verificación CAPTCHA. Intenta nuevamente.";
        }
    }

    if (empty($nombre_apellido)) $errores[] = "El nombre y apellido son obligatorios";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "Email inválido";
    if (!empty($telefono) && !preg_match("/^[0-9\-]+$/", $telefono)) $errores[] = "Teléfono inválido (solo números y guiones)";
    if (!empty($telefono) && strlen($telefono) > 15) $errores[] = "Teléfono demasiado largo (máx 15 caracteres)";
    if (empty($asunto)) $errores[] = "El asunto es obligatorio";
    if (strlen($asunto) > 100) $errores[] = "Asunto demasiado largo (máx 100 caracteres)";
    if (empty($mensaje)) $errores[] = "El mensaje es obligatorio";
    if (strlen($mensaje) > 2000) $errores[] = "El mensaje es demasiado largo (máx 2000 caracteres)";

    if (empty($errores)) {
        try {
            $stmt = $conexion->prepare(
                "INSERT INTO contactos (nombre_apellido, email, telefono, asunto, mensaje, tipo_consulta) 
                VALUES (:nombre, :email, :telefono, :asunto, :mensaje, :tipo)"
            );
            $stmt->bindParam(':nombre', $nombre_apellido);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':asunto', $asunto);
            $stmt->bindParam(':mensaje', $mensaje);
            $stmt->bindParam(':tipo', $tipo_consulta);
            $stmt->execute();

            $mensaje_exito = "¡Gracias por contactarnos! Nos pondremos en contacto contigo pronto.";

            $nombre_apellido = $email = $telefono = $asunto = $mensaje = '';
        } catch(PDOException $e) {
            $errores[] = "Error al guardar el mensaje. Por favor, intente más tarde.";
            error_log("Error en formulario contacto: " . $e->getMessage());
        }
    }
}

// FUNCIÓN VALIDAR CAPTCHA
function validarCaptcha($captcha_response) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $captcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return false;
    }
    
    $response = json_decode($result);
    return $response->success;
}
?>

<!-- reCAPTCHA API -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<main class="d-flex justify-content-center align-items-start py-5">
    <section class="card shadow formulario-contacto" style="padding: 20px;">
        <h3><?php echo htmlspecialchars($titulo); ?></h3>
        <p class="text-muted">Complete el formulario y nos pondremos en contacto a la brevedad</p>

        <form method="post" action="">
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($mensaje_exito); ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="tipo_consulta" value="<?php echo htmlspecialchars($tipo); ?>">

            <div class="mb-3">
                <label for="nombreyapellido" class="form-label">Nombre y Apellido *</label>
                <input type="text" class="form-control" id="nombreyapellido" name="nombreyapellido" 
                    placeholder="Ej: Ana Romero" value="<?php echo htmlspecialchars($nombre_apellido ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico *</label>
                <input type="email" class="form-control" id="email" name="email" 
                    placeholder="Ej: tunombre@gmail.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="tel" class="form-control" id="telefono" name="telefono" 
                    placeholder="Ej: 3775-123456" value="<?php echo htmlspecialchars($telefono ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="asunto" class="form-label">Asunto *</label>
                <input type="text" class="form-control" id="asunto" name="asunto" 
                    placeholder="Ej: Consulta sobre envíos" value="<?php echo htmlspecialchars($asunto ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="mensaje" class="form-label">Mensaje *</label>
                <textarea class="form-control" id="mensaje" name="mensaje" rows="4" 
                        placeholder="Por favor, describa su consulta en detalle..." required><?php echo htmlspecialchars($mensaje ?? ''); ?></textarea>
            </div>

            <!-- CAPTCHA AGREGADO AQUÍ -->
            <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="6Ldgc9orAAAAAMQduder6PADc41o5KbiXIQBXRH3"></div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-dark btn-lg">Enviar Mensaje</button>
                <a href="https://wa.me/543775449624" class="btn btn-outline-success" target="_blank">
                    <i class="bi bi-whatsapp"></i> Contactar por WhatsApp
                </a>
            </div>

            <div class="mt-3 text-center">
                <small class="text-muted">* Campos obligatorios</small>
            </div>
        </form>
    </section>
</main>

<script>
// Validación del CAPTCHA en el cliente
document.querySelector('form').addEventListener('submit', function(e) {
    const captcha = grecaptcha && grecaptcha.getResponse ? grecaptcha.getResponse() : '';
    
    if (captcha.length === 0) {
        e.preventDefault();
        alert('Por favor, verifica que no eres un robot haciendo clic en el CAPTCHA.');
        return false;
    }
});
</script>
<?php include('../estructura/pie.php'); ?>
</section>