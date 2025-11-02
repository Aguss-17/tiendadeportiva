<?php
session_start();
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

        $usuario_id = $_SESSION['usuario_id'] ?? null;
        if (!$usuario_id) {
            return ['exito' => false, 'error' => 'Usuario no identificado.'];
        }

        // Datos recibidos del formulario
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $metodo_pago = $_POST['metodo_pago'] ?? '';
        $datos_pago = $_POST['datos_pago'] ?? '';
        $carrito = $_SESSION['carrito'];
        $total = $this->calcularTotal();

        try {
            $this->conexion->beginTransaction();

            // Crear pedido
            $pedido_id = $this->modelo->crearPedido([
                $usuario_id, $nombre, $email, $direccion, $telefono, $total, $metodo_pago, $datos_pago
            ]);

            // Agregar ítems
            $this->modelo->agregarItemsPedido($pedido_id, $carrito);

            // Actualizar stock
            $this->modelo->actualizarStock($carrito);

            $this->conexion->commit();

            // Vaciar carrito
            unset($_SESSION['carrito']);

            return ['exito' => true];
        } catch (Exception $e) {
            $this->conexion->rollBack();
            return ['exito' => false, 'error' => 'Error al procesar el pedido: ' . $e->getMessage()];
        }
    }

    // Obtener datos para la vista
    public function obtenerDatosVista(): array {
        $usuario_id = $_SESSION['usuario_id'] ?? null;
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
