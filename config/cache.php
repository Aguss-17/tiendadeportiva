<?php
// Sistema de caché optimizado
class SimpleCache {
    public $cacheDir;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Limpieza automática de archivos expirados (>24 horas)
        $this->limpiarCacheViejo();
    }

    // Obtener datos desde cache
    public function get($key, $ttl = 3600) {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            return unserialize(file_get_contents($cacheFile));
        }

        // Si existe pero está viejo → eliminar
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        return null;
    }

    // Guardar datos en cache
    public function set($key, $data, $ttl = 3600) {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';
        return file_put_contents($cacheFile, serialize($data));
    }

    // Eliminar clave de cache
    public function delete($key) {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';
        return file_exists($cacheFile) ? unlink($cacheFile) : false;
    }

    // Eliminar todo el cache
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        $deleted = 0;

        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    // Limpieza automática de archivos viejos
    private function limpiarCacheViejo($horas = 24) {
        $files = glob($this->cacheDir . '*.cache');
        $limiteTiempo = time() - ($horas * 3600);
        $eliminados = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $limiteTiempo) {
                if (unlink($file)) {
                    $eliminados++;
                }
            }
        }

        if ($eliminados > 0) {
            error_log("Cache: Eliminados $eliminados archivos viejos");
        }
    }

    // CACHE ESPECÍFICO PARA DATOS ESTÁTICOS (TTL más alto)

    public function getCategorias() {
        return $this->get('categorias_lista', 7200); // 2 horas
    }

    public function setCategorias($categorias) {
        return $this->set('categorias_lista', $categorias, 7200);
    }

    public function getProductosDestacados() {
        return $this->get('productos_destacados', 1800); // 30 minutos
    }

    public function setProductosDestacados($productos) {
        return $this->set('productos_destacados', $productos, 1800);
    }

    // CONSULTAS CACHEADAS
    public function cachedQuery($conexion, $sql, $params = [], $ttl = 1800) {
        $key = 'query_' . md5($sql . serialize($params));

        // Intentar tomar el cache
        if ($cached = $this->get($key, $ttl)) {
            return $cached;
        }

        try {
            $inicio = microtime(true);

            $stmt = $conexion->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $tiempo = microtime(true) - $inicio;

            // Loguear consultas lentas
            if ($tiempo > 0.1) {
                error_log("CONSULTA LENTA: {$tiempo}s - " . substr($sql, 0, 100));
            }

            // Guardar la consulta y retornar
            $this->set($key, $result, $ttl);
            return $result;

        } catch (Exception $e) {
            error_log("Error en consulta cacheada: " . $e->getMessage());
            return [];
        }
    }
}

// Instancia global
$cache = new SimpleCache();

// Función para limpiar caché cuando se modifican productos
function clearProductCache() {
    global $cache;

    // Eliminar categorías y destacados
    $cache->delete('categorias_lista');
    $cache->delete('productos_destacados');

    // Borrar consultas relacionadas con productos
    $files = glob($cache->cacheDir . 'query_*.cache');

    foreach ($files as $file) {
        if (strpos($file, 'productos') !== false) {
            unlink($file);
        }
    }

    error_log("Cache de productos limpiado");
}
?>
