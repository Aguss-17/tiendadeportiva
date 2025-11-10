<?php
// Sistema de caché
class SimpleCache {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get($key, $ttl = 3600) {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            return unserialize(file_get_contents($cacheFile));
        }
        
        return null;
    }

    public function set($key, $data) {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';
        return file_put_contents($cacheFile, serialize($data));
    }

    public function delete($key) {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';
        return file_exists($cacheFile) ? unlink($cacheFile) : false;
    }

    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return count($files);
    }

    // Método helper para consultas cacheadas
    public function cachedQuery($conexion, $sql, $params = [], $ttl = 1800) {
        $key = $sql . serialize($params);
        
        if ($cached = $this->get($key, $ttl)) {
            return $cached;
        }
        
        try {
            $stmt = $conexion->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->set($key, $result);
            return $result;
        } catch (Exception $e) {
            error_log("Error en consulta cacheada: " . $e->getMessage());
            return [];
        }
    }
}

// Instancia global
$cache = new SimpleCache();

// Función para limpiar caché cuando se modifican datos
function clearProductCache() {
    global $cache;
    // Limpiar todo el caché (simple pero efectivo)
    $cache->clear();
}
?>