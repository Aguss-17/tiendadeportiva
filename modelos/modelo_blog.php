<?php
class ModeloBlog {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function obtenerPosts($filtros = []) {
        $postsPorPagina = $filtros['posts_por_pagina'] ?? 6;
        $offset = $filtros['offset'] ?? 0;
        $busqueda = $filtros['busqueda'] ?? '';
        $categoria = $filtros['categoria'] ?? '';
        $orden = $filtros['orden'] ?? 'fecha_desc';

        $whereConditions = ["estado = 'publicado'"];
        $params = [];

        if (!empty($busqueda)) {
            $whereConditions[] = "(titulo LIKE :busqueda OR contenido LIKE :busqueda OR autor LIKE :busqueda)";
            $params[':busqueda'] = "%" . $busqueda . "%";
        }

        if (!empty($categoria)) {
            $whereConditions[] = "categoria = :categoria";
            $params[':categoria'] = $categoria;
        }

        $orderBy = match($orden) {
            'fecha_asc' => "fecha_publicacion ASC",
            'titulo_asc' => "titulo ASC",
            'titulo_desc' => "titulo DESC",
            default => "fecha_publicacion DESC"
        };

        $whereClause = implode(" AND ", $whereConditions);

        try {
            $sql = "SELECT * FROM posts WHERE $whereClause ORDER BY $orderBy LIMIT :limit OFFSET :offset";
            $stmt = $this->conexion->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }

            $stmt->bindValue(':limit', $postsPorPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener posts: " . $e->getMessage());
            return [];
        }
    }

    public function contarPosts($filtros = []) {
        $busqueda = $filtros['busqueda'] ?? '';
        $categoria = $filtros['categoria'] ?? '';

        $whereConditions = ["estado = 'publicado'"];
        $params = [];

        if (!empty($busqueda)) {
            $whereConditions[] = "(titulo LIKE :busqueda OR contenido LIKE :busqueda OR autor LIKE :busqueda)";
            $params[':busqueda'] = "%" . $busqueda . "%";
        }

        if (!empty($categoria)) {
            $whereConditions[] = "categoria = :categoria";
            $params[':categoria'] = $categoria;
        }

        $whereClause = implode(" AND ", $whereConditions);

        try {
            $sql = "SELECT COUNT(*) as total FROM posts WHERE $whereClause";
            $stmt = $this->conexion->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Error al contar posts: " . $e->getMessage());
            return 0;
        }
    }

    public function obtenerPostsDestacados($limite = 3) {
        try {
            $sql = "SELECT * FROM posts WHERE estado = 'publicado' AND destacado = 1 ORDER BY fecha_publicacion DESC LIMIT :limite";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener posts destacados: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerComentarios($postId) {
        try {
            $sql = "SELECT * FROM comentarios WHERE post_id = :post_id AND estado = 'aprobado' ORDER BY fecha_creacion DESC";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':post_id' => $postId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener comentarios: " . $e->getMessage());
            return [];
        }
    }
}
?>
