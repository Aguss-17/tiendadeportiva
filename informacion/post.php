<?php
require_once __DIR__ . '/../config/bd.php';

// GENERAR TOKEN CSRF SI NO EXISTE
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = $_GET['id'] ?? 0;
$sentenciaSQL = $conexion->prepare("SELECT * FROM posts WHERE id = :id AND estado = 'publicado'");
$sentenciaSQL->bindParam(':id', $id);
$sentenciaSQL->execute();
$post = $sentenciaSQL->fetch(PDO::FETCH_ASSOC);

// Si no existe el post, redirigir
if (!$post) {
    header('Location: blog.php');
    exit();
}

include('../estructura/cabecera.php');
?>
<!-- Incluimos estilos solo para blog -->
<link rel="stylesheet" href="/tiendadeportiva/css/estilos_blog.css">

<main style="background-color: #dec19e; padding: 20px 0;">
<article class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="../index.php" class="text-dark">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="blog.php" class="text-dark">Blog</a>
                    </li>
                    <li class="breadcrumb-item active text-dark" aria-current="page">
                        <?= htmlspecialchars($post['titulo']) ?>
                    </li>
                </ol>
            </nav>

            <!-- Contenedor con fondo blanco -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="row g-0">
                    
                    <!-- Columna imagen -->
                    <?php if (!empty($post['imagen'])): ?>
                    <div class="col-md-5">
                        <img src="../img/posts/<?= htmlspecialchars($post['imagen']) ?>" 
                            class="img-fluid h-100 rounded-start" 
                            alt="<?= htmlspecialchars($post['titulo']) ?>" 
                            style="object-fit: cover;">
                    </div>
                    <?php endif; ?>

                    <!-- Columna contenido -->
                    <div class="<?= !empty($post['imagen']) ? 'col-md-7' : 'col-12' ?>">
                        <div class="card-body bg-white p-4 rounded-end">
                            <h1 class="mb-3"><?= htmlspecialchars($post['titulo']) ?></h1>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <span class="text-muted">Por: <strong><?= htmlspecialchars($post['autor']) ?></strong></span>
                                    <span class="text-muted mx-2">•</span>
                                    <span class="text-muted"><?= date('d/m/Y', strtotime($post['fecha_publicacion'])) ?></span>
                                </div>
                                <?php 
                                // Asignar clase de color según categoría
                                $badgeClass = '';
                                switch($post['categoria']) {
                                    case 'Fitness':
                                        $badgeClass = 'bg-fitness';
                                        break;
                                    case 'Nutrición':
                                        $badgeClass = 'bg-nutricion';
                                        break;
                                    case 'Entrenamiento':
                                        $badgeClass = 'bg-entrenamiento';
                                        break;
                                    case 'Promociones':
                                        $badgeClass = 'bg-promociones';
                                        break;
                                    default:
                                        $badgeClass = 'bg-general';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($post['categoria']) ?></span>
                            </div>

                            <div class="content text-dark" style="line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($post['contenido'])) ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</article>

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
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <!-- TOKEN CSRF -->
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
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
                    <?php
                    $sentenciaSQL = $conexion->prepare("
                        SELECT * FROM comentarios 
                        WHERE post_id = :post_id AND estado = 'aprobado' 
                        ORDER BY fecha_creacion DESC
                    ");
                    $sentenciaSQL->execute([':post_id' => $post['id']]);
                    $comentarios = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (!empty($comentarios)): ?>
                    <div class="comentarios-lista">
                        <h5 class="mb-4">Comentarios (<?= count($comentarios) ?>)</h5>
                        <?php foreach($comentarios as $comentario): ?>
                        <div class="comentario-item mb-4">
                            <div class="d-flex align-items-start">
                                <!-- Avatar del usuario -->
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-user text-white fs-6"></i>
                                    </div>
                                </div>
                                
                                <!-- Contenido del comentario con forma de mensaje -->
                                <div class="flex-grow-1 ms-2">
                                    <div class="comentario-burbuja p-3 rounded" style="background-color: #e9ecef; position: relative;">
                                        <!-- Triángulo de la burbuja -->
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
</div>
</main>

<?php include('../estructura/pie.php'); ?>