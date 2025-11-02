<?php
require_once 'controlador_blog.php';
include('../estructura/cabecera.php');
?>

<link rel="stylesheet" href="../css/estilos_blog.css">

<!-- Banner Blog -->
<div class="text-center my-0">
    <img src="../img/banner_blog.jpg" class="img-fluid" alt="Banner Blog" style="width: 100%; height: auto;">
</div><br>

<!-- Filtros -->
<div class="filtros-container-1">
    <div class="row mb-3 justify-content-center">
        <div class="col-md-4 mb-2">
            <select class="form-select" id="filtroCategoria" onchange="filtrarPosts()">
                <option value="">Todas las categorías</option>
                <option value="Fitness" <?= $categoriaFiltro == 'Fitness' ? 'selected' : '' ?>>Fitness</option>
                <option value="Nutrición" <?= $categoriaFiltro == 'Nutrición' ? 'selected' : '' ?>>Nutrición</option>
                <option value="Entrenamiento" <?= $categoriaFiltro == 'Entrenamiento' ? 'selected' : '' ?>>Entrenamiento</option>
                <option value="Promociones" <?= $categoriaFiltro == 'Promociones' ? 'selected' : '' ?>>Promociones</option>
                <option value="General" <?= $categoriaFiltro == 'General' ? 'selected' : '' ?>>General</option>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" id="filtroOrden" onchange="filtrarPosts()">
                <option value="fecha_desc" <?= $ordenFiltro == 'fecha_desc' ? 'selected' : '' ?>>Más recientes primero</option>
                <option value="fecha_asc" <?= $ordenFiltro == 'fecha_asc' ? 'selected' : '' ?>>Más antiguos primero</option>
                <option value="titulo_asc" <?= $ordenFiltro == 'titulo_asc' ? 'selected' : '' ?>>Título A-Z</option>
                <option value="titulo_desc" <?= $ordenFiltro == 'titulo_desc' ? 'selected' : '' ?>>Título Z-A</option>
            </select>
        </div>
    </div>
</div>

<!-- Posts Destacados -->
<?php if (!empty($postsDestacados)): ?>
    <section class="container my-5">
        <h2 class="text-center mb-4">Artículos Destacados</h2>
        <div class="row g-4">
            <?php foreach ($postsDestacados as $post): ?>
                <div class="col-md-4">
                    <a href="post.php?id=<?= $post['id'] ?>" class="text-decoration-none">
                        <div class="card h-100 shadow-sm">
                            <?php if (!empty($post['imagen'])): ?>
                                <img src="../img/posts/<?= htmlspecialchars($post['imagen']) ?>" class="card-img-top" alt="<?= htmlspecialchars($post['titulo']) ?>" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($post['titulo']) ?></h5>
                                <p class="card-text"><?= substr(strip_tags($post['contenido']), 0, 150) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Por: <?= htmlspecialchars($post['autor']) ?></small>
                                    <small class="text-muted"><?= date('d/m/Y', strtotime($post['fecha_publicacion'])) ?></small>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Lista de Posts -->
<section class="container my-5">
    <h2 class="text-center mb-4">
        <?= !empty($busqueda) ? 'Resultados de búsqueda para "' . htmlspecialchars($busqueda) . '"' : (!empty($categoriaFiltro) ? 'Categoría: ' . htmlspecialchars($categoriaFiltro) : 'Últimas Publicaciones') ?>
    </h2>

    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <form action="blog.php" method="GET" class="d-flex">
                <div class="input-group">
                    <input type="text" class="form-control" name="busqueda" placeholder="Buscar artículos..." value="<?= htmlspecialchars($busqueda) ?>">
                    <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i> Buscar</button>
                    <?php if (!empty($busqueda) || !empty($categoriaFiltro) || $ordenFiltro != 'fecha_desc'): ?>
                        <a href="blog.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($post['imagen'])): ?>
                            <img src="../img/posts/<?= htmlspecialchars($post['imagen']) ?>" class="card-img-top" style="height:200px; object-fit:cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($post['titulo']) ?></h5>
                            <p class="card-text flex-grow-1"><?= substr(strip_tags($post['contenido']), 0, 120) ?>...</p>
                            <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-dark btn-sm mt-auto">Leer más</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="alert alert-warning">No hay artículos disponibles.</div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Paginación Mejorada -->
<?php if ($totalPaginas > 1): ?>
    <nav aria-label="Paginación de artículos" class="mt-5">
        <ul class="pagination justify-content-center">
            <!-- Botón Anterior -->
            <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="blog.php?<?=
                                                    (!empty($busqueda) ? 'busqueda=' . urlencode($busqueda) . '&' : '') .
                                                        (!empty($categoriaFiltro) ? 'categoria=' . urlencode($categoriaFiltro) . '&' : '') .
                                                        ($ordenFiltro != 'fecha_desc' ? 'orden=' . urlencode($ordenFiltro) . '&' : '') .
                                                        'pagina=' . ($paginaActual - 1)
                                                    ?>" aria-label="Anterior">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <!-- Números de página -->
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <?php if ($i == 1 || $i == $totalPaginas || ($i >= $paginaActual - 2 && $i <= $paginaActual + 2)): ?>
                    <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                        <a class="page-link" href="blog.php?<?=
                                                            (!empty($busqueda) ? 'busqueda=' . urlencode($busqueda) . '&' : '') .
                                                                (!empty($categoriaFiltro) ? 'categoria=' . urlencode($categoriaFiltro) . '&' : '') .
                                                                ($ordenFiltro != 'fecha_desc' ? 'orden=' . urlencode($ordenFiltro) . '&' : '') .
                                                                'pagina=' . $i
                                                            ?>"><?= $i ?></a>
                    </li>
                <?php elseif ($i == $paginaActual - 3 || $i == $paginaActual + 3): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Botón Siguiente -->
            <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="blog.php?<?=
                                                    (!empty($busqueda) ? 'busqueda=' . urlencode($busqueda) . '&' : '') .
                                                        (!empty($categoriaFiltro) ? 'categoria=' . urlencode($categoriaFiltro) . '&' : '') .
                                                        ($ordenFiltro != 'fecha_desc' ? 'orden=' . urlencode($ordenFiltro) . '&' : '') .
                                                        'pagina=' . ($paginaActual + 1)
                                                    ?>" aria-label="Siguiente">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<!-- Sección de Comentarios -->
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <!-- Formulario de comentario -->
                    <div class="mb-5 p-4 border rounded" style="background-color: #f8f9fa;">
                        <h5 class="mb-3">Deja tu comentario</h5>
                        <form action="./guardar_comentarios.php" method="POST">
                            <?php if (!empty($posts)): ?>
                                <input type="hidden" name="post_id" value="<?= $posts[0]['id'] ?>">
                            <?php endif; ?>

                            <!-- TOKEN CSRF -->
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="nombre" placeholder="Tu nombre" required style="border-radius: 8px; padding: 12px;">
                                </div>
                                <div class="col-md-6">
                                    <input type="email" class="form-control" name="email" placeholder="Tu email" required style="border-radius: 8px; padding: 12px;">
                                </div>
                            </div>
                            <div class="mt-3">
                                <textarea class="form-control" name="comentario" rows="4" placeholder="Escribe tu comentario..." required style="border-radius: 8px; padding: 12px;"></textarea>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-dark">Enviar Comentario</button>
                            </div>
                        </form>
                    </div>

                    <!-- Lista de comentarios aprobados -->
                    <?php if (!empty($comentarios)): ?>
                        <div class="comentarios-lista">
                            <h5 class="mb-4">Comentarios (<?= count($comentarios) ?>)</h5>
                            <?php foreach ($comentarios as $comentario): ?>
                                <div class="comentario-item mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-user text-white fs-6"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <div class="comentario-burbuja p-3 rounded" style="background-color: #e9ecef; position: relative;">
                                                <div style="position: absolute; left: -10px; top: 15px; width: 0; height: 0; border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-right: 10px solid #e9ecef;"></div>
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0 fw-bold" style="color: #5a3e36;"><?= htmlspecialchars($comentario['nombre']) ?></h6>
                                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?></small>
                                                </div>
                                                <p class="card-text mb-0" style="color: #495057; line-height: 1.5;"><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay comentarios aún. ¡Sé el primero en comentar!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div><br>

<?php include('../estructura/pie.php'); ?>