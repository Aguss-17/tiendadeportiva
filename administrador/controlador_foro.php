<?php
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../modelos/modelo_foro.php';

class ControladorForoAdmin {
    private $modelo;
    private $errores = [];
    private $mensajeSuccess = "";

    public function __construct() {
        global $conexion;
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

    // --- CATEGORÍAS ---
    private function procesarCategorias($accion, $datos) {
        $id = $datos['txtID'] ?? '';
        $nombre = $datos['txtNombre'] ?? '';
        $descripcion = $datos['txtDescripcion'] ?? '';
        $color = $datos['txtColor'] ?? '#5a3e36';

        if (in_array($accion, ['Agregar', 'Modificar'])) {
            if (empty($nombre)) $this->errores[] = "El nombre de la categoría es obligatorio.";
            if (empty($descripcion)) $this->errores[] = "La descripción es obligatoria.";
        }

        if (empty($this->errores)) {
            switch ($accion) {
                case 'Agregar':
                    if ($this->modelo->crearCategoria($nombre, $descripcion, $color))
                        $this->mensajeSuccess = "Categoría agregada correctamente.";
                    break;

                case 'Modificar':
                    if ($this->modelo->actualizarCategoria($id, $nombre, $descripcion, $color))
                        $this->mensajeSuccess = "Categoría modificada correctamente.";
                    break;

                case 'Borrar':
                    $resultado = $this->modelo->eliminarCategoria($id);
                    if (isset($resultado['error']))
                        $this->errores[] = $resultado['error'];
                    else
                        $this->mensajeSuccess = "Categoría eliminada correctamente.";
                    break;
            }
        }

        return $this->modelo->obtenerCategoria($id);
    }

    // --- TEMAS ---
    private function procesarTemas($accion, $datos) {
        $id = $datos['txtID'] ?? '';
        switch ($accion) {
            case 'Anclar': $this->modelo->cambiarEstadoTema($id, 'es_anclado', 1); break;
            case 'Desanclar': $this->modelo->cambiarEstadoTema($id, 'es_anclado', 0); break;
            case 'Cerrar': $this->modelo->cambiarEstadoTema($id, 'estado', 'cerrado'); break;
            case 'Reabrir': $this->modelo->cambiarEstadoTema($id, 'estado', 'activo'); break;
            case 'Eliminar': $this->modelo->cambiarEstadoTema($id, 'estado', 'eliminado'); break;
        }
        $this->mensajeSuccess = "Acción realizada correctamente en el tema.";
    }

    // --- RESPUESTAS ---
    private function procesarRespuestas($accion, $datos) {
        $id = $datos['txtID'] ?? '';
        if ($accion == 'Eliminar') {
            $this->modelo->cambiarEstadoRespuesta($id, 'eliminado');
            $this->mensajeSuccess = "Respuesta eliminada correctamente.";
        } elseif ($accion == 'Restaurar') {
            $this->modelo->cambiarEstadoRespuesta($id, 'activo');
            $this->mensajeSuccess = "Respuesta restaurada correctamente.";
        }
    }

    // --- Getters ---
    public function getErrores() { return $this->errores; }
    public function getMensajeSuccess() { return $this->mensajeSuccess; }
    public function getCategorias() { return $this->modelo->obtenerCategorias(); }
    public function getTemas() { return $this->modelo->obtenerTemas(); }
    public function getRespuestas() { return $this->modelo->obtenerRespuestas(); }
}

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

// --- Inicialización ---
$controlador = new ControladorForoAdmin();
$modulo = $_GET['modulo'] ?? 'categorias';
$accion = $_POST['accion'] ?? '';

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
