<?php
include(__DIR__ . '/../config/bd.php');
require_once __DIR__ . '/../config/cache.php';

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

        $sentenciaSQL = $conexion->prepare(
            "INSERT INTO posts 
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

        $sentenciaSQL = $conexion->prepare(
            "UPDATE posts SET 
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


// OPTIMIZACIÓN: OBTENER LISTA DE POSTS CON CACHE (15 min)
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

<!-- Formulario -->
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
                        <textarea class="form-control" name="txtMetaDescripcion" rows="2" maxlength="255" placeholder="Descripción breve para motores de búsqueda (máx. 255 caracteres)"><?= htmlspecialchars($txtMetaDescripcion ?? '') ?></textarea>
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
                            <a href="posts.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Cancelar</a>
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

    <!-- Tabla de posts existentes -->
    <div class="col-md-7 p-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>POSTS PUBLICADOS</h5>
                <span class="badge bg-light text-dark">Total: <?= count($listaPosts) ?></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="tablaPosts">
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
                                    <td><?php if (!empty($post['imagen'])): ?><img src="../img/posts/<?= $post['imagen'] ?>" width="50" class="img-thumbnail"><?php endif; ?></td>
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
                                                <button type="submit" name="accion" value="Seleccionar" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit me-1"></i> Editar
                                                </button>
                                            </form>
                                            <a href="vista_previa.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-info" title="Previsualizar">
                                                <i class="fas fa-eye me-1"></i> Ver
                                            </a>
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="txtID" value="<?= $post['id'] ?>">
                                                <button type="submit" name="accion" value="Borrar" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este post?')">
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
            </div>
        </div>
    </div>
</div>

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
<script>
    // SOLO ESTE PEQUEÑO SCRIPT AL FINAL
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar búsqueda y filtros para esta tabla
        if (typeof uxManager !== 'undefined') {
            uxManager.inicializarBusquedaFiltros('tablaPosts');
        }
    });
</script>


<?php include('../administrador/estructura/pie.php'); ?>