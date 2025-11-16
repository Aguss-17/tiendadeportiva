<?php
class ModeloProductos {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Obtener todos los productos
    public function obtenerTodos() {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre 
                FROM productos p 
                LEFT JOIN categorias c ON p.id_categoria = c.id 
                ORDER BY p.id DESC";
        return $this->conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener producto por ID
    public function obtenerPorId($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear producto
    public function crear($datos) {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, id_categoria, imagen, en_oferta, vip, talles, colores)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $datos['nombre'], $datos['descripcion'], $datos['precio'], $datos['categoria'],
            $datos['imagen'], $datos['oferta'], $datos['vip'], $datos['talles'], $datos['colores']
        ]);
    }

    // Actualizar producto
    public function actualizar($id, $datos) {
        $sql = "UPDATE productos SET nombre=?, descripcion=?, precio=?, id_categoria=?, imagen=?, en_oferta=?, vip=?, talles=?, colores=? WHERE id=?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $datos['nombre'], $datos['descripcion'], $datos['precio'], $datos['categoria'],
            $datos['imagen'], $datos['oferta'], $datos['vip'], $datos['talles'], $datos['colores'], $id
        ]);
    }

    // Eliminar producto
    public function eliminar($id) {
        $stmt = $this->conexion->prepare("DELETE FROM productos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Obtener todas las categorías
    public function obtenerCategorias() {
        return $this->conexion->query("SELECT * FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener imagen del producto
    public function obtenerImagenProducto($id) {
        $stmt = $this->conexion->prepare("SELECT imagen FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['imagen'] ?? null;
    }
}
?>
