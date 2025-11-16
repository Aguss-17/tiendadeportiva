<?php
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../config/cache.php';
require_once __DIR__ . '/../modelos/modelo_foro.php';

class ControladorForoAdmin {
    private $modelo;
    private $errores = [];
    private $mensajeSuccess = "";
    private $conexion;

    public function __construct() {
        global $conexion, $cache;
        $this->conexion = $conexion;
        $this->modelo = new ModeloForo($conexion);
    }

    public function procesarModulo($modulo, $accion, $datos) {
        switch ($modulo) {
            case 'categorias': return $this->procesarCategorias($accion, $datos);
            case 'temas': return $this->procesarTemas($accion, $datos);
            case 'respuestas': return $this->procesarRespuestas($accion, $datos);
        }
        return null;
    }

    // CATEGORÍAS
    private function procesarCategorias($accion, $datos) {
        global $cache;

        $id = $datos['txtID'] ?? '';
        $nombre = $datos['txtNombre'] ?? '';
        $descripcion = $datos['txtDescripcion'] ?? '';
        $color = $datos['txtColor'] ?? '#5a3e36';

        if (in_array($accion, ['Agregar', 'Modificar'])) {
            if (empty($nombre))  $this->errores[] = "El nombre de la categoría es obligatorio.";
            if (empty($descripcion))  $this->errores[] = "La descripción es obligatoria.";
        }

        if (empty($this->errores)) {
            switch ($accion) {

                case 'Agregar':
                    if ($this->modelo->crearCategoria($nombre, $descripcion, $color)) {
                        $this->mensajeSuccess = "Categoría agregada correctamente.";
                        clearProductCache();
                    }
                    break;

                case 'Modificar':
                    if ($this->modelo->actualizarCategoria($id, $nombre, $descripcion, $color)) {
                        $this->mensajeSuccess = "Categoría modificada correctamente.";
                        clearProductCache();
                    }
                    break;

                case 'Borrar':
                    $resultado = $this->modelo->eliminarCategoria($id);

                    if (isset($resultado['error'])) {
                        $this->errores[] = $resultado['error'];
                    } else {
                        $this->mensajeSuccess = "Categoría eliminada correctamente.";
                        clearProductCache();
                    }
                    break;
            }
        }

        return $this->modelo->obtenerCategoria($id);
    }

    // TEMAS
    private function procesarTemas($accion, $datos) {
        $id = $datos['txtID'] ?? '';

        switch ($accion) {
            case 'Anclar':  
                $this->modelo->cambiarEstadoTema($id, 'es_anclado', 1);
                break;
            case 'Desanclar': 
                $this->modelo->cambiarEstadoTema($id, 'es_anclado', 0);
                break;
            case 'Cerrar': 
                $this->modelo->cambiarEstadoTema($id, 'estado', 'cerrado');
                break;
            case 'Reabrir': 
                $this->modelo->cambiarEstadoTema($id, 'estado', 'activo');
                break;
            case 'Eliminar': 
                $this->modelo->cambiarEstadoTema($id, 'estado', 'eliminado');
                break;
        }

        clearProductCache();
        $this->mensajeSuccess = "Acción realizada correctamente en el tema.";
    }

    // RESPUESTAS
    private function procesarRespuestas($accion, $datos) {
        $id = $datos['txtID'] ?? '';

        if ($accion == 'Eliminar') {
            $this->modelo->cambiarEstadoRespuesta($id, 'eliminado');
            $this->mensajeSuccess = "Respuesta eliminada correctamente.";
        } elseif ($accion == 'Restaurar') {
            $this->modelo->cambiarEstadoRespuesta($id, 'activo');
            $this->mensajeSuccess = "Respuesta restaurada correctamente.";
        }

        clearProductCache();
    }

    // GETTERS CON CACHE
    public function getErrores() {
        return $this->errores;
    }

    public function getMensajeSuccess() {
        return $this->mensajeSuccess;
    }

    // Categorías cacheadas por 2 horas
    public function getCategorias() {
        global $cache;

        return $cache->cachedQuery(
            $this->conexion,
            "SELECT * FROM foro_categorias ORDER BY nombre ASC",
            [],
            7200
        );
    }

    // Temas cacheados por 5 minutos
    public function getTemas() {
        global $cache;

        return $cache->cachedQuery(
            $this->conexion,
            "SELECT t.*, c.nombre AS categoria_nombre
            FROM foro_temas t
            LEFT JOIN foro_categorias c ON t.id_categoria = c.id
            ORDER BY t.fecha_creado DESC",
            [],
            300
        );
    }

    // Respuestas cacheadas por 3 minutos
    public function getRespuestas() {
        global $cache;

        return $cache->cachedQuery(
            $this->conexion,
            "SELECT r.*, u.usuario AS autor
            FROM foro_respuestas r
            LEFT JOIN usuarios u ON r.id_usuario = u.id
            ORDER BY r.fecha_creado ASC",
            [],
            180
        );
    }
}

// CSRF
function csrf_token() {
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF inválido");
    }
}

// iNICIALIZACIÓN
$controlador = new ControladorForoAdmin();
$modulo = $_GET['modulo'] ?? 'categorias';
$accion = $_POST['accion'] ?? "";

$categoriaSeleccionada = null;

if (!empty($accion)) {
    $categoriaSeleccionada = $controlador->procesarModulo($modulo, $accion, $_POST);
}

$datosVista = [
    'modulo' => $modulo,
    'errores' => $controlador->getErrores(),
    'mensajeSuccess' => $controlador->getMensajeSuccess(),
    'listaCategorias' => $controlador->getCategorias(),
    'listaTemas' => $controlador->getTemas(),
    'listaRespuestas' => $controlador->getRespuestas(),
    'categoriaSeleccionada' => $categoriaSeleccionada,
    'csrf_token' => csrf_token()
];

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $datosVista['mensajeSuccess'] = "Operación realizada correctamente.";
}
?>
