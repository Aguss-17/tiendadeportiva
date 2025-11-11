<?php
class ModeloProductos {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Obtener todos los productos
    public function obtenerTodos() {
        try {
            $sql = "SELECT p.*, c.nombre AS categoria_nombre 
                    FROM productos p 
                    LEFT JOIN categorias c ON p.id_categoria = c.id 
                    ORDER BY p.id DESC";

            $stmt = $this->conexion->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener todos los productos: " . $e->getMessage());
            return [];
        }
    }

    // Obtener producto por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->conexion->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener producto ID $id: " . $e->getMessage());
            return false;
        }
    }

    // Crear producto
    public function crear($datos) {
        try {
            $sql = "INSERT INTO productos 
                    (nombre, descripcion, precio, id_categoria, imagen, en_oferta, vip, talles, colores)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conexion->prepare($sql);

            return $stmt->execute([
                $datos['nombre'], $datos['descripcion'], $datos['precio'], $datos['categoria'],
                $datos['imagen'], $datos['oferta'], $datos['vip'], $datos['talles'], $datos['colores']
            ]);

        } catch (PDOException $e) {
            error_log("Error al crear producto: " . $e->getMessage());
            return false;
        }
    }

    // Actualizar producto
    public function actualizar($id, $datos) {
        try {
            $sql = "UPDATE productos 
                    SET nombre=?, descripcion=?, precio=?, id_categoria=?, imagen=?, en_oferta=?, vip=?, talles=?, colores=? 
                    WHERE id=?";

            $stmt = $this->conexion->prepare($sql);

            return $stmt->execute([
                $datos['nombre'], $datos['descripcion'], $datos['precio'], $datos['categoria'],
                $datos['imagen'], $datos['oferta'], $datos['vip'], $datos['talles'], $datos['colores'], $id
            ]);

        } catch (PDOException $e) {
            error_log("Error al actualizar producto ID $id: " . $e->getMessage());
            return false;
        }
    }

    // Eliminar producto (versión mejorada con verificación y FK)
    public function eliminar($id) {
        try {
            // Verificar que el producto existe primero
            $producto = $this->obtenerPorId($id);
            if (!$producto) {
                error_log("Producto no encontrado para eliminar - ID: $id");
                return false;
            }

            $stmt = $this->conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);

            $rowCount = $stmt->rowCount();
            error_log("Producto eliminado - ID: $id, Filas afectadas: $rowCount");

            return $rowCount > 0;

        } catch (PDOException $e) {
            error_log("Error al eliminar producto ID $id: " . $e->getMessage());

            // Si es error de clave foránea
            if ($e->getCode() == '23000') {
                error_log("No se puede eliminar producto ID $id - Tiene pedidos asociados");
            }

            return false;
        }
    }

    // Obtener imagen del producto
    public function obtenerImagenProducto($id) {
        try {
            $stmt = $this->conexion->prepare("SELECT imagen FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado['imagen'] ?? null;

        } catch (PDOException $e) {
            error_log("Error al obtener imagen de producto ID $id: " . $e->getMessage());
            return null;
        }
    }

    // Obtener todas las categorías
    public function obtenerCategorias() {
        try {
            $stmt = $this->conexion->query("SELECT * FROM categorias ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            return [];
        }
    }
}
?>

