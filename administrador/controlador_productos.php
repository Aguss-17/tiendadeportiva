<?php
require_once __DIR__ . '/../config/bd.php';   
require_once __DIR__ . '/../config/cache.php';
require_once '../modelos/modelo_productos.php';

$modelo = new ModeloProductos($conexion);

$errores = [];
$mensajeSuccess = "";

// Datos principales
$txtAccion = $_POST['accion'] ?? "";
$txtID = $_POST['txtID'] ?? "";
$txtNombre = $_POST['txtNombre'] ?? "";
$txtDescripcion = $_POST['txtDescripcion'] ?? "";
$txtPrecio = $_POST['txtPrecio'] ?? "";
$txtCategoria = $_POST['txtCategoria'] ?? "";
$txtOferta = isset($_POST['en_oferta']) ? 1 : 0;
$txtVIP = isset($_POST['txtVIP']) ? 1 : 0;

// Talles y colores
$txtTallesRemera = $_POST['txtTallesRemera'] ?? [];
$txtTallesPantalon = $_POST['txtTallesPantalon'] ?? [];
$txtTallesNinos = $_POST['txtTallesNinos'] ?? [];
$txtColores = $_POST['txtColores'] ?? [];
$txtImagenActual = $_POST['imagen_actual'] ?? "";

// Unificar talles
$txtTalles = array_merge($txtTallesRemera, $txtTallesPantalon, $txtTallesNinos);
$tallesTexto = implode(",", $txtTalles);
$coloresTexto = implode(",", $txtColores);

// Paginación
$paginaActual = $_GET['pagina'] ?? 1;
$productosPorPagina = 20;

// Cuando se modifica la BD → limpiar cache
if ($txtAccion && in_array($txtAccion, ['Agregar', 'Modificar', 'Borrar'])) {
    clearProductCache();
}

// Procesar imagen
if (!empty($_FILES['imagen']['name'])) {
    $nombreArchivo = time() . "_" . basename($_FILES["imagen"]["name"]);
    $rutaDestino = "../img/" . $nombreArchivo;

    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino)) {
        // Eliminar imagen anterior
        if (!empty($txtImagenActual) && file_exists("../img/" . $txtImagenActual)) {
            unlink("../img/" . $txtImagenActual);
        }
    }
} else {
    $nombreArchivo = $txtImagenActual;
}

// Acciones CRUD
switch ($txtAccion) {

    case "Agregar":
        $modelo->crear([
            'nombre' => $txtNombre,
            'descripcion' => $txtDescripcion,
            'precio' => $txtPrecio,
            'categoria' => $txtCategoria,
            'imagen' => $nombreArchivo,
            'oferta' => $txtOferta,
            'vip' => $txtVIP,
            'talles' => $tallesTexto,
            'colores' => $coloresTexto
        ]);
        $mensajeSuccess = "✅ Producto agregado correctamente.";
        break;

    case "Modificar":
        $modelo->actualizar($txtID, [
            'nombre' => $txtNombre,
            'descripcion' => $txtDescripcion,
            'precio' => $txtPrecio,
            'categoria' => $txtCategoria,
            'imagen' => $nombreArchivo,
            'oferta' => $txtOferta,
            'vip' => $txtVIP,
            'talles' => $tallesTexto,
            'colores' => $coloresTexto
        ]);
        $mensajeSuccess = "✅ Producto actualizado correctamente.";
        break;

    case "Borrar":
        $imagen = $modelo->obtenerImagenProducto($txtID);
        if ($imagen && file_exists("../img/" . $imagen)) {
            unlink("../img/" . $imagen);
        }
        $modelo->eliminar($txtID);
        $mensajeSuccess = "🗑️ Producto eliminado correctamente.";
        break;

    case "Seleccionar":
        $productoSel = $modelo->obtenerPorId($txtID);
        if ($productoSel) {
            $txtNombre = $productoSel['nombre'];
            $txtDescripcion = $productoSel['descripcion'];
            $txtPrecio = $productoSel['precio'];
            $txtCategoria = $productoSel['id_categoria'];
            $txtOferta = $productoSel['en_oferta'];
            $txtVIP = $productoSel['vip'];
            $txtTalles = explode(",", $productoSel['talles']);
            $txtColores = explode(",", $productoSel['colores']);
            $txtImagenActual = $productoSel['imagen'];
            $txtAccion = "Seleccionar";
        }
        break;
}

// Cargar listas con caché y paginación
// Categorías (cache 2 horas)
$categorias = $cache->cachedQuery(
    $conexion,
    "SELECT * FROM categorias ORDER BY nombre ASC",
    [],
    7200
);

// Total productos
$totalProductos = $cache->cachedQuery(
    $conexion,
    "SELECT COUNT(*) as total FROM productos",
    [],
    300
)[0]['total'] ?? 0;

// Datos de paginación calculados
$pagination = getPagination($totalProductos, $paginaActual, $productosPorPagina);

// Traer productos paginados con cache
$listaProductos = $cache->cachedPagination(
    $conexion,
    "SELECT p.*, c.nombre AS categoria_nombre
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id
    ORDER BY p.id DESC",
    [],
    $paginaActual,
    $productosPorPagina
);
?>
