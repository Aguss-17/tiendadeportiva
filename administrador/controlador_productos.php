<?php
require_once '../config/bd.php';
require_once '../modelos/modelo_productos.php';

$modelo = new ModeloProductos($conexion);

$errores = [];
$mensajeSuccess = "";
$txtAccion = $_POST['accion'] ?? "";
$txtID = $_POST['txtID'] ?? "";
$txtNombre = $_POST['txtNombre'] ?? "";
$txtDescripcion = $_POST['txtDescripcion'] ?? "";
$txtPrecio = $_POST['txtPrecio'] ?? "";
$txtCategoria = $_POST['txtCategoria'] ?? "";
$txtOferta = isset($_POST['en_oferta']) ? 1 : 0;
$txtVIP = isset($_POST['txtVIP']) ? 1 : 0;
$txtTallesRemera = $_POST['txtTallesRemera'] ?? [];
$txtTallesPantalon = $_POST['txtTallesPantalon'] ?? [];
$txtTallesNinos = $_POST['txtTallesNinos'] ?? [];
$txtColores = $_POST['txtColores'] ?? [];
$txtImagenActual = $_POST['imagen_actual'] ?? "";

// Unificar talles en un solo texto
$txtTalles = array_merge($txtTallesRemera, $txtTallesPantalon, $txtTallesNinos);
$tallesTexto = implode(",", $txtTalles);
$coloresTexto = implode(",", $txtColores);

// Procesar imagen
if (!empty($_FILES['imagen']['name'])) {
    $nombreArchivo = time() . "_" . basename($_FILES["imagen"]["name"]);
    $rutaDestino = "../img/" . $nombreArchivo;
    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino)) {
        // Eliminar imagen anterior si existe
        if (!empty($txtImagenActual) && file_exists("../img/" . $txtImagenActual)) {
            unlink("../img/" . $txtImagenActual);
        }
    }
} else {
    $nombreArchivo = $txtImagenActual;
}

// Procesar acciones
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
        $mensajeSuccess = "Producto agregado correctamente.";
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
        $mensajeSuccess = "Producto actualizado correctamente.";
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

// Cargar listas
$categorias = $modelo->obtenerCategorias();
$listaProductos = $modelo->obtenerTodos();
?>
