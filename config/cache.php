<?php
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

    // Método mejorado para consultas cacheadas con paginación
    public function cachedQuery($conexion, $sql, $params = [], $ttl = 1800, $pagination = false) {
        $key = $sql . serialize($params) . ($pagination ? '_page' : '');
        
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

    // Nuevo método para paginación cacheada
    public function cachedPagination($conexion, $baseSql, $params = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $pagedSql = $baseSql . " LIMIT $perPage OFFSET $offset";
        
        return $this->cachedQuery($conexion, $pagedSql, $params, 300); // 5 min cache
    }
}

// Instancia global
$cache = new SimpleCache();

// Función para limpiar caché cuando se modifican datos
function clearProductCache() {
    global $cache;
    $cache->clear();
}

// Función helper para paginación
function getPagination($totalItems, $currentPage = 1, $perPage = 20) {
    $totalPages = ceil($totalItems / $perPage);
    
    return [
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'offset' => ($currentPage - 1) * $perPage,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Función para generar clave de caché para búsquedas
function generateSearchKey($base, $filters) {
    $filterString = '';
    foreach ($filters as $key => $value) {
        if (!empty($value)) {
            $filterString .= "{$key}={$value}&";
        }
    }
    return $base . '?' . rtrim($filterString, '&');
}

// Función para búsquedas cacheadas
function cachedSearch($conexion, $baseSql, $filters = [], $ttl = 300) {
    global $cache;
    $builtQuery = buildSearchQuery($baseSql, $filters); // Corregido aquí
    $key = generateSearchKey($baseSql, $filters);
    return $cache->cachedQuery($conexion, $builtQuery['sql'], $builtQuery['params'], $ttl);
}

// Función para construir consultas con filtros
function buildSearchQuery($baseSql, $filters) {
    $whereConditions = [];
    $params = [];
    
    foreach ($filters as $key => $value) {
        if (!empty($value)) {
            switch ($key) {
                case 'busqueda':
                    $whereConditions[] = "(nombre LIKE :busqueda OR descripcion LIKE :busqueda)";
                    $params[':busqueda'] = "%$value%";
                    break;
                case 'categoria':
                    $whereConditions[] = "id_categoria = :categoria";
                    $params[':categoria'] = $value;
                    break;
                case 'estado':
                    $whereConditions[] = "estado = :estado";
                    $params[':estado'] = $value;
                    break;
                case 'oferta':
                    $whereConditions[] = "en_oferta = :oferta";
                    $params[':oferta'] = $value;
                    break;
                case 'vip':
                    $whereConditions[] = "vip = :vip";
                    $params[':vip'] = $value;
                    break;
                case 'fecha_desde':
                    $whereConditions[] = "fecha_creacion >= :fecha_desde";
                    $params[':fecha_desde'] = $value;
                    break;
                case 'fecha_hasta':
                    $whereConditions[] = "fecha_creacion <= :fecha_hasta";
                    $params[':fecha_hasta'] = $value;
                    break;
            }
        }
    }
    
    if (!empty($whereConditions)) {
        $baseSql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    return ['sql' => $baseSql, 'params' => $params];
}
?>