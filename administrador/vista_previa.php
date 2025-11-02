<?php
// vista_previa.php
require_once __DIR__ . '/../config/bd.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

$id = $_GET['id'] ?? 0;
$sentenciaSQL = $conexion->prepare("SELECT * FROM posts WHERE id = :id");
$sentenciaSQL->bindParam(':id', $id);
$sentenciaSQL->execute();
$post = $sentenciaSQL->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: posts.php');
    exit();
}

include('../estructura/cabecera.php');
?>

<main style="background-color: #dec19e; padding: 20px 0;">
<article class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Banner de previsualización -->
            <div class="alert alert-warning text-center">
                <h4><i class="fas fa-eye"></i> VISTA PREVIA</h4>
                <p>Esta es una previsualización del post. Los visitantes normales no pueden ver esta página.</p>
                <div class="mt-2">
                    <a href="./blog.php" class="btn btn-secondary me-2">Volver al administrador</a>
                    <?php if($post['estado'] == 'borrador'): ?>
                    <a href="../blog/post.php?id=<?= $post['id'] ?>" class="btn btn-success">Publicar ahora</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contenido del post -->
            <div class="card border-0 shadow-sm">
                <div class="row g-0">
                    <?php if (!empty($post['imagen'])): ?>
                    <div class="col-md-4">
                        <img src="../img/posts/<?= htmlspecialchars($post['imagen']) ?>" 
                            class="img-fluid h-100" 
                            alt="<?= htmlspecialchars($post['titulo']) ?>" 
                            style="object-fit: cover;">
                    </div>
                    <?php endif; ?>

                    <div class="col-md-8">
                        <div class="card-body bg-white">
                            <h1 class="mb-3" style="color:#7a5c44;"><?= htmlspecialchars($post['titulo']) ?></h1>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <span class="text-muted">Por: <strong><?= htmlspecialchars($post['autor']) ?></strong></span>
                                    <span class="text-muted mx-2">•</span>
                                    <span class="text-muted"><?= date('d/m/Y', strtotime($post['fecha_publicacion'])) ?></span>
                                </div>
                                <span class="badge bg-dark"><?= htmlspecialchars($post['categoria']) ?></span>
                            </div>

                            <div class="content text-dark">
                                <?= $post['contenido'] ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>
</main>

<?php include('../estructura/pie.php'); ?>