<?php

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../modelos/modelo_pedido.php';

class ControladorCheckout {

    private PDO $conexion;
    private ModeloPedido $modelo;

    public function __construct(PDO $conexion) {
        $this->conexion = $conexion;
        $this->modelo = new ModeloPedido($conexion);
    }

    // Procesar pedido (POST)
    public function procesarCheckout(): array {

        if (empty($_SESSION['carrito'])) {
            return ['exito' => false, 'error' => 'El carrito está vacío.'];
        }

        $usuario_id = $_SESSION['user_id'] ?? null;
        if (!$usuario_id) {
            return ['exito' => false, 'error' => 'Usuario no identificado.'];
        }

        // Datos recibidos del formulario
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $metodo_pago = $_POST['metodo_pago'] ?? '';
        
        // Preparar datos de pago
        $datos_pago = $this->prepararDatosPago($_POST);
        
        $carrito = $_SESSION['carrito'];
        $total = $this->calcularTotal();

        try {
            $this->conexion->beginTransaction();

            // Crear pedido
            $pedido_id = $this->modelo->crearPedido([
                $usuario_id, $nombre, $email, $direccion, $telefono, 
                $total, $metodo_pago, $datos_pago
            ]);

            // Agregar ítems
            $this->modelo->agregarItemsPedido($pedido_id, $carrito);

            // Confirmar transacción
            $this->conexion->commit();

            // ✅ Enviar email de confirmación
            $email_enviado = $this->modelo->enviarEmailConfirmacion(
                $email,
                $pedido_id,
                $carrito,
                $total,
                $direccion
            );

            // ✅ Logging del resultado del email
            if ($email_enviado) {
                error_log("✅ Email de confirmación enviado - Pedido #$pedido_id a: $email");
            } else {
                error_log("❌ Error al enviar email - Pedido #$pedido_id a: $email");
                $_SESSION['email_warning'] = "Pedido procesado, pero no pudimos enviar el email de confirmación.";
            }

            // Vaciar carrito
            unset($_SESSION['carrito']);

            return ['exito' => true, 'pedido_id' => $pedido_id];

        } catch (Exception $e) {
            $this->conexion->rollBack();
            return ['exito' => false, 'error' => 'Error al procesar el pedido: ' . $e->getMessage()];
        }
    }

    private function prepararDatosPago($postData): string {
        $metodo_pago = $postData['metodo_pago'] ?? '';
        $datos = [];
        
        switch ($metodo_pago) {
            case 'tarjeta':
                $datos = [
                    'tipo' => 'tarjeta',
                    'numero' => substr($postData['numero_tarjeta'] ?? '', -4),
                    'vencimiento' => $postData['fecha_vencimiento'] ?? '',
                    'titular' => $postData['nombre_titular'] ?? ''
                ];
                break;
                
            case 'transferencia':
                $datos = [
                    'tipo' => 'transferencia',
                    'metodo' => $postData['tipo_transferencia'] ?? '',
                    'alias' => $postData['alias_mp'] ?? ''
                ];
                break;
                
            case 'efectivo':
                $datos = [
                    'tipo' => 'efectivo',
                    'sucursal' => $postData['sucursal'] ?? ''
                ];
                break;
                
            default:
                $datos = ['tipo' => $metodo_pago];
        }
        
        return json_encode($datos);
    }

    // Obtener datos para la vista
    public function obtenerDatosVista(): array {

        $usuario_id = $_SESSION['user_id'] ?? null;
        $usuario_data = $usuario_id ? $this->modelo->obtenerUsuario($usuario_id) : null;

        return [
            'usuario_data' => $usuario_data,
            'carrito' => $_SESSION['carrito'] ?? [],
            'total' => $this->calcularTotal(),
            'csrf_token' => $this->generarCsrfToken()
        ];
    }

    // Calcular total del carrito
    private function calcularTotal(): float {
        $total = 0;
        if (!empty($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
        }
        return $total;
    }

    // Generar token CSRF
    private function generarCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Crear instancia del controlador pasando la conexión
$controlador = new ControladorCheckout($conexion);
$datos_vista = $controlador->obtenerDatosVista();

// Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controlador->procesarCheckout();
    if ($resultado['exito']) {
        header('Location: confirmacion.php');
        exit;
    } else {
        $error = $resultado['error'];
    }
}

?>
