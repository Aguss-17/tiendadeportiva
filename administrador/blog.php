<?php
include(__DIR__ . '/../config/bd.php');
require_once __DIR__ . '/../config/cache.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// VARIABLES FORMULARIO
$txtID = $_POST['txtID'] ?? "";
$txtTitulo = $_POST['txtTitulo'] ?? "";
$txtContenido = $_POST['txtContenido'] ?? "";
$txtAutor = $_POST['txtAutor'] ?? "Aura Sport";
$txtCategoria = $_POST['txtCategoria'] ?? "General";
$txtEstado = $_POST['txtEstado'] ?? "borrador";
$txtDestacado = $_POST['txtDestacado'] ?? 0;
$txtAccion = $_POST['accion'] ?? "";
$txtImagenActual = $_POST['imagen_actual'] ?? "";
$mensajeError = "";

$directorioUploads = dirname(__DIR__) . '/img/posts/';

// PROCESAR IMAGEN
include(__DIR__ . '/../config/subida_imagen.php');
$nombreImagen = $txtImagenActual;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen'])) {
    $uploadResult = handleImageUpload($_FILES['imagen'], $directorioUploads, $txtImagenActual);
    $nombreImagen = $uploadResult['fileName'];
    if (!empty($uploadResult['errors'])) {
        $mensajeError = implode(", ", $uploadResult['errors']);
    }
}

// CRUD + LIMPIEZA DE CACHE
switch ($txtAccion) {

    case "Agregar":
        clearProductCache();
        $txtMetaDescripcion = $_POST['txtMetaDescripcion'] ?? '';

        $sentenciaSQL = $conexion->prepare("
            INSERT INTO posts (titulo, contenido, meta_description, imagen, autor, categoria, estado, destacado) 
            VALUES (:titulo, :contenido, :meta_description, :imagen, :autor, :categoria, :estado, :destacado)
        ");

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

        header('Location: blog.php?success=1');
        exit();

    case "Modificar":
        clearProductCache();
        $txtMetaDescripcion = $_POST['txtMetaDescripcion'] ?? '';

        $sentenciaSQL = $conexion->prepare("
            UPDATE posts SET 
                titulo=:titulo, contenido=:contenido, meta_description=:meta_description,
                imagen=:imagen, autor=:autor, categoria=:categoria, estado=:estado, destacado=:destacado
            WHERE id=:id
        ");

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
        clearProductCache();

        $sentenciaSQL = $conexion->prepare("SELECT imagen FROM posts WHERE id=:id");
        $sentenciaSQL->execute([':id' => $txtID]);
        $post = $sentenciaSQL->fetch(PDO::FETCH_LAZY);

        if (!empty($post['imagen']) && file_exists($directorioUploads . $post['imagen'])) {
            @unlink($directorioUploads . $post['imagen']);
        }

        $sentenciaSQL = $conexion->prepare("DELETE FROM posts WHERE id=:id");
        $sentenciaSQL->execute([':id' => $txtID]);

        header('Location: blog.php?success=1');
        exit();
}

// PAGINACIÓN + FILTROS
$paginaActual = $_GET['pagina'] ?? 1;
$postsPorPagina = 20;

// FILTROS PARA BLOG
$busquedaBlog = $_GET['busqueda'] ?? '';
$filtroCategoriaBlog = $_GET['categoria'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';

$whereConditionsBlog = [];
$paramsBlog = [];

if (!empty($busquedaBlog)) {
    $whereConditionsBlog[] = "(titulo LIKE :busqueda OR contenido LIKE :busqueda OR autor LIKE :busqueda)";
    $paramsBlog[':busqueda'] = "%" . $busquedaBlog . "%";
}

if (!empty($filtroCategoriaBlog)) {
    $whereConditionsBlog[] = "categoria = :categoria";
    $paramsBlog[':categoria'] = $filtroCategoriaBlog;
}

if (!empty($filtroEstado)) {
    $whereConditionsBlog[] = "estado = :estado";
    $paramsBlog[':estado'] = $filtroEstado;
}

$whereClauseBlog = !empty($whereConditionsBlog) ? "WHERE " . implode(" AND ", $whereConditionsBlog) : "";

// Total posts con filtros
$totalPosts = $cache->cachedQuery(
    $conexion,
    "SELECT COUNT(*) as total FROM posts $whereClauseBlog",
    $paramsBlog,
    300
)[0]['total'] ?? 0;

$pagination = getPagination($totalPosts, $paginaActual, $postsPorPagina);

// Obtener posts paginados filtrados
$sqlPosts = "SELECT * FROM posts 
            $whereClauseBlog
            ORDER BY fecha_publicacion DESC 
            LIMIT :limit OFFSET :offset";

$sentenciaSQL = $conexion->prepare($sqlPosts);

foreach ($paramsBlog as $key => $value) {
    $sentenciaSQL->bindValue($key, $value, PDO::PARAM_STR);
}

$sentenciaSQL->bindValue(':limit', $postsPorPagina, PDO::PARAM_INT);
$sentenciaSQL->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$sentenciaSQL->execute();
$listaPosts = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);

include('../administrador/estructura/cabecera.php');
?>

<!-- Formulario principal -->
<div class="row">
    <div class="col-md-5 mb-4 p-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-newspaper me-2"></i>GESTIONAR POST</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($txtImagenActual) ?>">

                    <div class="mb-3">
                        <label class="form-label">ID</label>
                        <input type="text" readonly class="form-control bg-light" value="<?= htmlspecialchars($txtID) ?>">
                        <input type="hidden" name="txtID" value="<?= htmlspecialchars($txtID) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="txtTitulo" value="<?= htmlspecialchars($txtTitulo) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contenido</label>
                        <textarea class="form-control" id="txtContenido" name="txtContenido" rows="6" required><?= htmlspecialchars($txtContenido) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autor</label>
                        <input type="text" class="form-control" name="txtAutor" value="<?= htmlspecialchars($txtAutor) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" name="txtCategoria">
                            <option value="Fitness" <?= $txtCategoria == 'Fitness' ? 'selected' : '' ?>>Fitness</option>
                            <option value="Nutrición" <?= $txtCategoria == 'Nutrición' ? 'selected' : '' ?>>Nutrición</option>
                            <option value="Entrenamiento" <?= $txtCategoria == 'Entrenamiento' ? 'selected' : '' ?>>Entrenamiento</option>
                            <option value="Promociones" <?= $txtCategoria == 'Promociones' ? 'selected' : '' ?>>Promociones</option>
                            <option value="General" <?= $txtCategoria == 'General' ? 'selected' : '' ?>>General</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="txtEstado">
                                <option value="borrador" <?= $txtEstado == 'borrador' ? 'selected' : '' ?>>Borrador</option>
                                <option value="publicado" <?= $txtEstado == 'publicado' ? 'selected' : '' ?>>Publicado</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">¿Destacado?</label>
                            <select class="form-select" name="txtDestacado">
                                <option value="0" <?= $txtDestacado == '0' ? 'selected' : '' ?>>No</option>
                                <option value="1" <?= $txtDestacado == '1' ? 'selected' : '' ?>>Sí</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Imagen</label>
                        <input type="file" class="form-control" name="imagen" accept="image/*">
                        <?php if (!empty($txtImagenActual)): ?>
                            <div class="mt-2">
                                <img src="../img/posts/<?= htmlspecialchars($txtImagenActual) ?>" width="100" class="img-thumbnail">
                                <p class="text-muted small mt-1">Imagen actual</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Descripción (SEO)</label>
                        <textarea class="form-control" name="txtMetaDescripcion" rows="2" maxlength="255"><?= htmlspecialchars($txtMetaDescripcion ?? '') ?></textarea>
                        <small class="text-muted"><span id="contadorMeta">0</span>/255 caracteres</small>
                    </div>

                    <?php if (!empty($mensajeError)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($mensajeError) ?></div>
                    <?php endif; ?>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if ($txtAccion === "Seleccionar"): ?>
                            <button type="submit" name="accion" value="Modificar" class="btn btn-warning me-md-2">
                                <i class="fas fa-save me-1"></i> Guardar
                            </button>
                            <a href="blog.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Cancelar</a>
                        <?php else: ?>
                            <button type="submit" name="accion" value="Agregar" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i> Agregar
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- FILTROS PARA BLOG (HTML) -->
    <div class="col-md-7 p-4">

        <div class="card mb-3">
            <div class="card-body">

                <form method="GET" class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" 
                            name="busqueda" 
                            value="<?= htmlspecialchars($busquedaBlog) ?>" 
                            placeholder="Título, contenido o autor...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="">Todas</option>
                            <option value="Fitness" <?= $filtroCategoriaBlog == 'Fitness' ? 'selected' : '' ?>>Fitness</option>
                            <option value="Nutrición" <?= $filtroCategoriaBlog == 'Nutrición' ? 'selected' : '' ?>>Nutrición</option>
                            <option value="Entrenamiento" <?= $filtroCategoriaBlog == 'Entrenamiento' ? 'selected' : '' ?>>Entrenamiento</option>
                            <option value="Promociones" <?= $filtroCategoriaBlog == 'Promociones' ? 'selected' : '' ?>>Promociones</option>
                            <option value="General" <?= $filtroCategoriaBlog == 'General' ? 'selected' : '' ?>>General</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="publicado" <?= $filtroEstado == 'publicado' ? 'selected' : '' ?>>Publicado</option>
                            <option value="borrador" <?= $filtroEstado == 'borrador' ? 'selected' : '' ?>>Borrador</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>

                    <?php if (!empty($busquedaBlog) || !empty($filtroCategoriaBlog) || !empty($filtroEstado)): ?>
                        <div class="col-12">
                            <a href="blog.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Limpiar filtros
                            </a>
                            <small class="text-muted ms-2">
                                <?= count($listaPosts) ?> resultado(s) encontrado(s)
                            </small>
                        </div>
                    <?php endif; ?>

                </form>

            </div>
        </div>

        <!-- TABLA DE POSTS NUMÉRICA -->
        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>POSTS PUBLICADOS</h5>
                <span class="badge bg-light text-dark">Total: <?= $totalPosts ?></span>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Título</th>
                                <th>Autor</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Destacado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listaPosts as $post): ?>
                            <tr>
                                <td><?= $post['id'] ?></td>
                                <td>
                                    <?php if (!empty($post['imagen'])): ?>
                                        <img src="../img/posts/<?= $post['imagen'] ?>" width="50" class="img-thumbnail">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($post['titulo']) ?></td>
                                <td><?= htmlspecialchars($post['autor']) ?></td>
                                <td><?= htmlspecialchars($post['categoria']) ?></td>
                                <td><?= $post['estado'] == 'publicado' ? '<span class="badge bg-success">Publicado</span>' : '<span class="badge bg-secondary">Borrador</span>' ?></td>
                                <td><?= $post['destacado'] == 1 ? '<span class="badge bg-warning">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                <td><?= date('d/m/Y', strtotime($post['fecha_publicacion'])) ?></td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="txtID" value="<?= $post['id'] ?>">
                                            <button type="submit" name="accion" value="Seleccionar" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit me-1"></i> Editar
                                            </button>
                                        </form>

                                        <a href="vista_previa.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye me-1"></i> Ver
                                        </a>

                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="txtID" value="<?= $post['id'] ?>">
                                            <button type="submit" name="accion" value="Borrar" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este post?')">
                                                <i class="fas fa-trash-alt me-1"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN -->
                <nav>
                    <ul class="pagination justify-content-center">

                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                                <a class="page-link"
                                    href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busquedaBlog) ?>&categoria=<?= urlencode($filtroCategoriaBlog) ?>&estado=<?= urlencode($filtroEstado) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                    </ul>
                </nav>

            </div>
        </div>

    </div>
</div>

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
