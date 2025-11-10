<?php
class ModeloPedido {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Obtener datos del usuario
    public function obtenerUsuario($usuario_id) {
        $stmt = $this->conexion->prepare("SELECT nombre_completo, email, telefono FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear un nuevo pedido
    public function crearPedido($datos) {
        $sql = "INSERT INTO pedidos (usuario_id, nombre, email, direccion, telefono, total, metodo_pago, datos_pago, estado, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($datos);
        return $this->conexion->lastInsertId();
    }

    // Agregar ítems al pedido - CORREGIDO
    public function agregarItemsPedido($pedido_id, $items) {
        $sql = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio, talle, color) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        
        foreach ($items as $item) {
            $stmt->execute([
                $pedido_id, 
                $item['id'], 
                $item['cantidad'], 
                $item['precio'],
                $item['talle'] ?? '',
                $item['color'] ?? ''
            ]);
        }
    }

    // FUNCIÓN PARA ENVIAR EMAIL - USANDO PHPMailer
public function enviarEmailConfirmacion($usuario_email, $pedido_id, $carrito, $total, $direccion) {
    // Incluir PHPMailer (mismo que en email.php)
    require_once __DIR__ . '/../config/email.php';
    
    try {
        // Configuración IDÉNTICA a la de registro/recuperación
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configuración SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aurasport.mc@gmail.com';
        $mail->Password = 'mxfa yaiw nlzc byur';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Configuración de caracteres
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom('aurasport.mc@gmail.com', 'Aura Sport');
        $mail->addAddress($usuario_email);
        $mail->addReplyTo('aurasport.mc@gmail.com', 'Aura Sport');
        
        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = '✅ Confirmación de Pedido #' . $pedido_id . ' - Aura Sport';
        
        // Generar contenido HTML
        $mail->Body = $this->generarHtmlConfirmacion($pedido_id, $carrito, $total, $direccion);
        $mail->AltBody = $this->generarTextoConfirmacion($pedido_id, $carrito, $total, $direccion);
        
        $enviado = $mail->send();
        
        if (!$enviado) {
            error_log("Error PHPMailer - Pedido #$pedido_id: " . $mail->ErrorInfo);
        } else {
            error_log("Email enviado exitosamente - Pedido #$pedido_id a: $usuario_email");
        }
        
        return $enviado;
        
    } catch (Exception $e) {
        error_log("Exception PHPMailer - Pedido #$pedido_id: " . $e->getMessage());
        return false;
    }
}

// Función para generar HTML del email
private function generarHtmlConfirmacion($pedido_id, $carrito, $total, $direccion) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #343a40; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { padding: 20px; background: #f8f9fa; }
            .producto { border-bottom: 1px solid #ddd; padding: 10px 0; }
            .total { font-size: 18px; font-weight: bold; color: #28a745; margin-top: 20px; }
            .footer { background: #343a40; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; }
            .info-box { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>¡Gracias por tu compra en Aura Sport!</h2>
                <p>Número de pedido: <strong>#<?= $pedido_id ?></strong></p>
            </div>
            
            <div class="content">
                <p>Hemos recibido tu pedido y está siendo procesado.</p>
                
                <div class="info-box">
                    <h3>📦 Resumen de tu compra:</h3>
                    
                    <?php foreach ($carrito as $item): ?>
                    <div class="producto">
                        <strong><?= htmlspecialchars($item['nombre']) ?></strong><br>
                        📏 Talle: <?= $item['talle'] ?? '-' ?> | 
                        🎨 Color: <?= $item['color'] ?? '-' ?><br>
                        🔢 Cantidad: <?= $item['cantidad'] ?> x $<?= number_format($item['precio'], 2) ?><br>
                        💰 Subtotal: <strong>$<?= number_format($item['precio'] * $item['cantidad'], 2) ?></strong>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="total">
                        💵 Total del pedido: $<?= number_format($total, 2) ?>
                    </div>
                </div>
                
                <div class="info-box">
                    <h3>🏠 Dirección de envío:</h3>
                    <p><?= nl2br(htmlspecialchars($direccion)) ?></p>
                </div>
                
                <p>📞 Te contactaremos pronto con los detalles del envío.</p>
                <p>¡Gracias por confiar en nosotros!</p>
            </div>
            
            <div class="footer">
                <p>Aura Sport - Tu tienda deportiva de confianza</p>
                <p>📧 aurasport.mc@gmail.com</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Función para generar versión texto plano
private function generarTextoConfirmacion($pedido_id, $carrito, $total, $direccion) {
    $texto = "CONFIRMACIÓN DE PEDIDO #$pedido_id\n\n";
    $texto .= "¡Gracias por tu compra en Aura Sport!\n\n";
    $texto .= "Resumen de tu compra:\n\n";
    
    foreach ($carrito as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $texto .= "- " . $item['nombre'] . "\n";
        $texto .= "  Talle: " . ($item['talle'] ?? '-') . " | Color: " . ($item['color'] ?? '-') . "\n";
        $texto .= "  Cantidad: " . $item['cantidad'] . " x $" . number_format($item['precio'], 2) . " = $" . number_format($subtotal, 2) . "\n\n";
    }
    
    $texto .= "TOTAL: $" . number_format($total, 2) . "\n\n";
    $texto .= "Dirección de envío:\n" . $direccion . "\n\n";
    $texto .= "Te contactaremos pronto con los detalles del envío.\n\n";
    $texto .= "Aura Sport - Tu tienda deportiva de confianza\n";
    $texto .= "aurasport.mc@gmail.com";
    
    return $texto;
}
}
?>