<?php
session_start();
require_once __DIR__ . '/../config/bd.php';

if (!isset($_GET['id'])) {
    header("Location: foro.php");
    exit();
}

$categoria_id = (int)$_GET['id'];

// Obtener categoría
$stmt = $conexion->prepare("SELECT * FROM foro_categorias WHERE id = ?");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    die("Categoría no encontrada");
}

// Obtener temas de la categoría
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

$stmt = $conexion->prepare("
    SELECT t.*, u.usuario, 
        (SELECT COUNT(*) FROM foro_respuestas r WHERE r.tema_id = t.id AND r.estado = 'activo') as total_respuestas
    FROM foro_temas t 
    JOIN usuarios u ON t.usuario_id = u.id 
    WHERE t.categoria_id = ? AND t.estado = 'activo' 
    ORDER BY t.es_anclado DESC, t.actualizado_en DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $categoria_id, PDO::PARAM_INT);
$stmt->bindValue(2, $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total de temas para paginación
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM foro_temas WHERE categoria_id = ? AND estado = 'activo'");
$stmt->execute([$categoria_id]);
$total_temas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_temas / $por_pagina);

include(__DIR__ . '/../estructura/cabecera.php');
?>

<!-- CSS corregido -->
<link rel="stylesheet" href="/css/estilo_foro.css">

<main class="container my-5">
    <div class="row">
        <div class="col-12">
            <!-- Migas de pan -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb bg-light rounded-3 px-3 py-2 shadow-sm">
                    <li class="breadcrumb-item"><a href="foro.php" class="text-decoration-none">Foro</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($categoria['nombre']); ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="text-dark fw-bold mb-2"><?php echo htmlspecialchars($categoria['nombre']); ?></h1>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="crear_tema.php?categoria=<?php echo $categoria_id; ?>" class="btn btn-dark btn-lg px-4 shadow-sm">
                        <i class="bi bi-plus-circle me-2"></i> Nuevo Tema
                    </a>
                <?php endif; ?>
            </div>

            <!-- Lista de temas -->
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-gradient bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold">Temas (<?php echo $total_temas; ?>)</span>
                    <div class="badge bg-light text-dark fs-6 px-3 py-2 shadow-sm">
                        Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($temas)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-text fs-1 text-muted mb-3"></i>
                            <h5 class="text-dark fw-bold mb-3">Aún no hay temas en esta categoría</h5>
                            <p class="text-muted mb-4">Sé el primero en crear un tema de discusión.</p>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="crear_tema.php?categoria=<?php echo $categoria_id; ?>" class="btn btn-dark btn-lg px-4 shadow-sm">
                                    <i class="bi bi-plus-circle me-2"></i>Crear Primer Tema
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach($temas as $tema): ?>
                            <div class="row align-items-center border-bottom p-4 hover-shadow">
                                <div class="col-md-1 text-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-sm" 
                                        style="width: 50px; height: 50px;">
                                        <i class="bi bi-person-circle text-primary fs-5"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-2">
                                        <a href="tema.php?id=<?php echo $tema['id']; ?>" class="text-decoration-none text-dark fw-bold">
                                            <?php echo htmlspecialchars($tema['titulo']); ?>
                                        </a>
                                        <?php if ($tema['es_anclado']): ?>
                                            <span class="badge bg-warning text-dark ms-2 shadow-sm">
                                                <i class="bi bi-pin-angle-fill me-1"></i>Anclado
                                            </span>
                                        <?php endif; ?>
                                    </h5>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i>por <?php echo htmlspecialchars($tema['usuario']); ?> • 
                                        <i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y H:i', strtotime($tema['creado_en'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div class="bg-light rounded-3 p-2 shadow-sm">
                                        <div class="fw-bold fs-5 text-primary"><?php echo $tema['total_respuestas']; ?></div>
                                        <small class="text-muted">Respuestas</small>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div class="bg-light rounded-3 p-2 shadow-sm">
                                        <div class="fw-bold fs-5 text-info"><?php echo $tema['vistas']; ?></div>
                                        <small class="text-muted">Vistas</small>
                                    </div>
                                </div>
                                <div class="col-md-1 text-center">
                                    <div class="bg-light rounded-3 p-2 shadow-sm">
                                        <div class="fw-bold text-dark"><?php echo date('d/m', strtotime($tema['actualizado_en'])); ?></div>
                                        <small class="text-muted">Última</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center shadow-sm">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link border-0 text-dark" href="?id=<?php echo $categoria_id; ?>&pagina=<?php echo $pagina-1; ?>">
                                    <i class="bi bi-chevron-left me-1"></i>Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for($i = max(1, $pagina-2); $i <= min($total_paginas, $pagina+2); $i++): ?>
                            <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                                <a class="page-link border-0 text-dark <?php echo ($i == $pagina) ? 'bg-primary text-white' : ''; ?>" 
                                href="?id=<?php echo $categoria_id; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagina < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link border-0 text-dark" href="?id=<?php echo $categoria_id; ?>&pagina=<?php echo $pagina+1; ?>">
                                    Siguiente<i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include(__DIR__ . '/../estructura/pie.php'); ?>
