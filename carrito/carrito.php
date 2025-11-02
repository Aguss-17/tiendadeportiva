<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/bd.php';

// Función para respuestas JSON
function jsonResponse($exito, $mensaje = '', $data = []) {
    $response = ['exito' => $exito];
    if ($mensaje) $response['mensaje'] = $mensaje;
    if (!empty($data)) $response = array_merge($response, $data);
    echo json_encode($response);
    exit;
}

// Verificar si usuario está logueado
$usuario_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'add':
            // Verificar que el usuario esté logueado
            if (!$usuario_id) {
                jsonResponse(false, 'Debes iniciar sesión para agregar productos al carrito');
            }

            $producto_id = (int)($_POST['producto_id'] ?? 0);
            $talle = trim($_POST['talle'] ?? '');
            $color = trim($_POST['color'] ?? '');
            $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));

            if ($producto_id <= 0) {
                jsonResponse(false, 'Producto inválido');
            }

            // Verificar producto
            $sentencia = $conexion->prepare("SELECT * FROM productos WHERE id = :id");
            $sentencia->bindParam(':id', $producto_id, PDO::PARAM_INT);
            $sentencia->execute();
            $producto = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                jsonResponse(false, 'Producto no encontrado');
            }

            $key = $producto_id . '_' . $talle . '_' . $color;

            // Agregar al carrito
            if (isset($_SESSION['carrito'][$key])) {
                $_SESSION['carrito'][$key]['cantidad'] += $cantidad;
            } else {
                $_SESSION['carrito'][$key] = [
                    'id' => $producto_id,
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'talle' => $talle,
                    'color' => $color,
                    'cantidad' => $cantidad,
                    'imagen' => $producto['imagen']
                ];
            }

            $total_items = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
            jsonResponse(true, 'Producto agregado al carrito', ['total_items' => $total_items]);
            break;

        case 'remove':
            $key = $_POST['key'] ?? '';
            if ($key && isset($_SESSION['carrito'][$key])) {
                unset($_SESSION['carrito'][$key]);
                jsonResponse(true, 'Producto eliminado del carrito');
            } else {
                jsonResponse(false, 'Producto no encontrado en el carrito');
            }
            break;

        case 'update':
            $key = $_POST['key'] ?? '';
            $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
            
            if ($key && isset($_SESSION['carrito'][$key])) {
                $_SESSION['carrito'][$key]['cantidad'] = $cantidad;
                jsonResponse(true, 'Cantidad actualizada');
            } else {
                jsonResponse(false, 'Producto no encontrado en el carrito');
            }
            break;

        case 'get':
            $total = 0;
            $total_items = 0;
            foreach ($_SESSION['carrito'] as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $total += $subtotal;
                $total_items += $item['cantidad'];
            }
            
            jsonResponse(true, '', [
                'carrito' => $_SESSION['carrito'],
                'total' => $total,
                'total_items' => $total_items
            ]);
            break;

        case 'clear':
            $_SESSION['carrito'] = [];
            jsonResponse(true, 'Carrito vaciado');
            break;

        default:
            jsonResponse(false, 'Acción inválida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error en el servidor: ' . $e->getMessage());
}
?>