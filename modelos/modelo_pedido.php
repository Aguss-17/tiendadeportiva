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
        $sql = "INSERT INTO pedidos (usuario_id, nombre, email, direccion, telefono, total, metodo_pago, datos_pago)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($datos);
        return $this->conexion->lastInsertId();
    }

    // Agregar ítems al pedido
    public function agregarItemsPedido($pedido_id, $items) {
        $stmt = $this->conexion->prepare("INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio']]);
        }
    }

    // Actualizar stock de productos
    public function actualizarStock($items) {
        $stmt = $this->conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        foreach ($items as $item) {
            $stmt->execute([$item['cantidad'], $item['id']]);
        }
    }
}
?>
