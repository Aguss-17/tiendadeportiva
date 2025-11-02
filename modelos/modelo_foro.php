<?php
class ModeloForo {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // --- CATEGORÍAS ---
    public function obtenerCategorias() {
        $stmt = $this->conexion->prepare("SELECT * FROM foro_categorias ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCategoria($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM foro_categorias WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crearCategoria($nombre, $descripcion, $color) {
        $stmt = $this->conexion->prepare("INSERT INTO foro_categorias (nombre, descripcion, color) VALUES (?, ?, ?)");
        return $stmt->execute([$nombre, $descripcion, $color]);
    }

    public function actualizarCategoria($id, $nombre, $descripcion, $color) {
        $stmt = $this->conexion->prepare("UPDATE foro_categorias SET nombre=?, descripcion=?, color=? WHERE id=?");
        return $stmt->execute([$nombre, $descripcion, $color, $id]);
    }

    public function eliminarCategoria($id) {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM foro_temas WHERE categoria_id=?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado['total'] > 0) {
            return ['error' => 'No se puede eliminar la categoría porque tiene temas asociados.'];
        }

        $stmt = $this->conexion->prepare("DELETE FROM foro_categorias WHERE id=?");
        return $stmt->execute([$id]);
    }

    // --- TEMAS ---
    public function obtenerTemas() {
        $sql = "SELECT t.*, u.usuario, c.nombre as categoria_nombre,
                (SELECT COUNT(*) FROM foro_respuestas r WHERE r.tema_id = t.id AND r.estado = 'activo') as total_respuestas
                FROM foro_temas t 
                JOIN usuarios u ON t.usuario_id = u.id 
                JOIN foro_categorias c ON t.categoria_id = c.id 
                ORDER BY t.es_anclado DESC, t.creado_en DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cambiarEstadoTema($id, $campo, $valor) {
        $stmt = $this->conexion->prepare("UPDATE foro_temas SET $campo = ? WHERE id = ?");
        return $stmt->execute([$valor, $id]);
    }

    // --- RESPUESTAS ---
    public function obtenerRespuestas() {
        $sql = "SELECT r.*, u.usuario, t.titulo as tema_titulo
                FROM foro_respuestas r 
                JOIN usuarios u ON r.usuario_id = u.id 
                JOIN foro_temas t ON r.tema_id = t.id 
                ORDER BY r.creado_en DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cambiarEstadoRespuesta($id, $estado) {
        $stmt = $this->conexion->prepare("UPDATE foro_respuestas SET estado = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }
}
?>
