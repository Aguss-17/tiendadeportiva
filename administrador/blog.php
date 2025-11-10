<?php
include(__DIR__ . '/../config/bd.php');
include(__DIR__ . '/../config/cache.php'); // ← Asegurate que exista e inicialice $cache

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Variables del formulario
$txtID = $_POST['txtID'] ?? "";
$txtTitulo = $_POST['txtTitulo'] ?? "";
$txtContenido = $_POST['txtContenido'] ?? "";
$txtAutor = $_POST['txtAutor'] ?? "Aura Sport";
$txtCategoria = $_POST['txtCategoria'] ?? "General";
$txtEstado = $_POST['txtEstado'] ?? "borrador";
$txtDestacado = $_POST['txtDestacado'] ?? 0;
$txtAccion = $_POST['accion'] ?? "";
$txtImagenActual = $_POST['imagen_actual'] ?? "";

// Procesamiento de imagen usando el handler
include(__DIR__ . '/../config/subida_imagen.php');
$nombreImagen = $txtImagenActual;
$mensajeError = "";
$directorioUploads = dirname(__DIR__) . '/img/posts/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen'])) {
    $uploadResult = handleImageUpload($_FILES['imagen'], $directorioUploads, $txtImagenActual);
    $nombreImagen = $uploadResult['fileName'];
    if (!empty($uploadResult['errors'])) {
        $mensajeError = implode(", ", $uploadResult['errors']);
    }
}

// CRUD
switch ($txtAccion) {

    case "Agregar":
        $txtMetaDescripcion = $_POST['txtMetaDescripcion'] ?? '';

        $sentenciaSQL = $conexion->prepare("INSERT INTO posts 
            (titulo, contenido, meta_description, imagen, autor, categoria, estado, destacado) 
            VALUES (:titulo, :contenido, :meta_description, :imagen, :autor, :categoria, :estado, :destacado)"
        );

        $sentenciaSQL->execute([
            ':titulo' => $txtTitulo,
            ':contenido' => $txtContenido,
            ':meta_description' => $txtMetaDescripcion,
            ':imagen' => $nombreImagen,
            ':autor' => $txtAutor,
            ':categoria' => $txtCategoria,
            ':estado' => $txtEstado,
            ':destacado' => $txtDestacado
        ]);

        // Limpia cache al agregar
        $cache->delete('posts_lista_admin');

        header('Location: blog.php?success=1');
        exit();


    case "Modificar":
        $txtMetaDescripcion = $_POST['txtMetaDescripcion'] ?? '';

        $sentenciaSQL = $conexion->prepare("UPDATE posts SET 
            titulo=:titulo, contenido=:contenido, meta_description=:meta_description, imagen=:imagen,
            autor=:autor, categoria=:categoria, estado=:estado, destacado=:destacado
            WHERE id=:id"
        );

        $sentenciaSQL->execute([
            ':titulo' => $txtTitulo,
            ':contenido' => $txtContenido,
            ':meta_description' => $txtMetaDescripcion,
            ':imagen' => $nombreImagen,
            ':autor' => $txtAutor,
            ':categoria' => $txtCategoria,
            ':estado' => $txtEstado,
            ':destacado' => $txtDestacado,
            ':id' => $txtID
        ]);

        // Limpia cache al modificar
        $cache->delete('posts_lista_admin');

        header('Location: blog.php?success=1');
        exit();


    case "Seleccionar":
        $sentenciaSQL = $conexion->prepare("SELECT * FROM posts WHERE id=:id");
        $sentenciaSQL->execute([':id' => $txtID]);
        $post = $sentenciaSQL->fetch(PDO::FETCH_LAZY);

        $txtTitulo = $post['titulo'];
        $txtContenido = $post['contenido'];
        $txtAutor = $post['autor'];
        $txtCategoria = $post['categoria'];
        $txtEstado = $post['estado'];
        $txtDestacado = $post['destacado'];
        $txtImagenActual = $post['imagen'];
        $txtMetaDescripcion = $post['meta_description'];
        break;


    case "Borrar":
        $sentenciaSQL = $conexion->prepare("SELECT imagen FROM posts WHERE id=:id");
        $sentenciaSQL->execute([':id' => $txtID]);
        $post = $sentenciaSQL->fetch(PDO::FETCH_LAZY);

        if (!empty($post['imagen']) && file_exists($directorioUploads . $post['imagen'])) {
            @unlink($directorioUploads . $post['imagen']);
        }

        $sentenciaSQL = $conexion->prepare("DELETE FROM posts WHERE id=:id");
        $sentenciaSQL->execute([':id' => $txtID]);

        // Limpia cache al borrar
        $cache->delete('posts_lista_admin');

        header('Location: blog.php?success=1');
        exit();
}


// ✅ ✅ OPTIMIZACIÓN: OBTENER LISTA DE POSTS CON CACHE (15 min)
$cacheKey = 'posts_lista_admin';
$listaPosts = $cache->get($cacheKey, 900);

if (!$listaPosts) {

    $sentenciaSQL = $conexion->prepare("
        SELECT id, titulo, imagen, autor, categoria, estado, destacado, fecha_publicacion
        FROM posts
        ORDER BY fecha_publicacion DESC
        LIMIT 100
    ");
    $sentenciaSQL->execute();

    $listaPosts = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);

    // Guardar en cache 15 minutos
    $cache->set($cacheKey, $listaPosts, 900);
}

include('../administrador/estructura/cabecera.php');
?>

<!-- (todo tu HTML igual, sin cambios) -->
<?php /* NO MODIFIQUÉ NADA DEL FORMULARIO NI TABLA, SE MANTIENE TODO EXACTO */ ?>

<!-- Formulario y tabla igual que tu versión original -->

<?php include('../administrador/estructura/pie.php'); ?>

<script>
    CKEDITOR.replace('txtContenido', {
        toolbar: [
            ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript'],
            ['NumberedList', 'BulletedList', '-', 'Blockquote'],
            ['Link', 'Unlink'],
            ['Undo', 'Redo', '-', 'RemoveFormat'],
            ['Source']
        ],
        height: 300,
        allowedContent: true
    });

    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.querySelector('[name="txtMetaDescripcion"]');
        const contador = document.getElementById('contadorMeta');

        textarea.addEventListener('input', function() {
            contador.textContent = this.value.length;
        });

        contador.textContent = textarea.value.length;
    });
</script>
