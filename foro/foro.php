<?php
require_once __DIR__ . '/../config/bd.php';
include(__DIR__ . '/../estructura/cabecera.php');

// Obtener categorías
try {
    $stmt = $conexion->query("SELECT * FROM foro_categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas de cada categoría
    foreach($categorias as &$categoria) {
        // Total de temas
        $stmt = $conexion->prepare("SELECT COUNT(*) as total_temas FROM foro_temas WHERE categoria_id = ? AND estado = 'activo'");
        $stmt->execute([$categoria['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $categoria['total_temas'] = $result ? $result['total_temas'] : 0;
        
        // Total de respuestas
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as total_respuestas 
            FROM foro_respuestas r 
            JOIN foro_temas t ON r.tema_id = t.id 
            WHERE t.categoria_id = ? AND r.estado = 'activo' AND t.estado = 'activo'
        ");
        $stmt->execute([$categoria['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $categoria['total_respuestas'] = $result ? $result['total_respuestas'] : 0;
        
        // Último tema
        $stmt = $conexion->prepare("
            SELECT t.id, t.titulo, t.creado_en, u.usuario 
            FROM foro_temas t 
            JOIN usuarios u ON t.usuario_id = u.id 
            WHERE t.categoria_id = ? AND t.estado = 'activo' 
            ORDER BY t.creado_en DESC LIMIT 1
        ");
        $stmt->execute([$categoria['id']]);
        $ultimo_tema = $stmt->fetch(PDO::FETCH_ASSOC);
        $categoria['ultimo_tema'] = $ultimo_tema ? $ultimo_tema : null;
    }
    unset($categoria); // Desreferenciar el último elemento
} catch (PDOException $e) {
    error_log("Error en foro.php: " . $e->getMessage());
    $error = "Error al cargar las categorías del foro";
}

// Obtener temas anclados
try {
    $stmt = $conexion->query("
        SELECT t.*, u.usuario, c.nombre as categoria_nombre 
        FROM foro_temas t 
        JOIN usuarios u ON t.usuario_id = u.id 
        JOIN foro_categorias c ON t.categoria_id = c.id 
        WHERE t.es_anclado = TRUE AND t.estado = 'activo' 
        ORDER BY t.creado_en DESC LIMIT 5
    ");
    $temas_anclados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $temas_anclados = [];
    error_log("Error al cargar temas anclados: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="/css/estilo_foro.css">

<main class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="text-dark fw-bold mb-2">Foro Fitness & Salud</h1>
                    <p class="text-muted mb-0">Comparte tus experiencias y conecta con la comunidad</p>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="crear_tema.php" class="btn btn-dark btn-lg px-4 shadow-sm">
                        <i class="bi bi-plus-circle me-2"></i> Nuevo Tema
                    </a>
                <?php else: ?>
                    <a href="../login.php?redirect=foro" class="btn btn-dark btn-lg px-4 shadow-sm">
                        <i class="bi bi-person me-2"></i> Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                    <div>
                        <strong class="d-block">¡Bienvenido al foro!</strong>
                        Comparte tus experiencias, haz preguntas y conecta con la comunidad fitness.
                    </div>
                </div>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="row mb-5 g-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-chat-text fs-1 text-primary mb-3"></i>
                            <h3 class="text-dark fw-bold"><?php echo array_sum(array_column($categorias, 'total_temas')); ?></h3>
                            <p class="text-muted mb-0">Temas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-reply fs-1 text-success mb-3"></i>
                            <h3 class="text-dark fw-bold"><?php echo array_sum(array_column($categorias, 'total_respuestas')); ?></h3>
                            <p class="text-muted mb-0">Respuestas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-collection fs-1 text-warning mb-3"></i>
                            <h3 class="text-dark fw-bold"><?php echo count($categorias); ?></h3>
                            <p class="text-muted mb-0">Categorías</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-people fs-1 text-info mb-3"></i>
                            <h3 class="text-dark fw-bold"><?php echo date('Y'); ?></h3>
                            <p class="text-muted mb-0">Comunidad Activa</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de categorías -->
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-gradient bg-dark text-white py-3">
                    <h4 class="mb-0">
                        <i class="bi bi-grid-3x3-gap me-2"></i>Categorías del Foro
                    </h4>
                </div>
                <div class="card-body p-0">
                    <?php foreach($categorias as $categoria): ?>
                        <div class="row align-items-center border-bottom p-4 hover-shadow">
                            <div class="col-md-1 text-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-sm" 
                                    style="width: 60px; height: 60px; background-color: <?php echo $categoria['color']; ?>;">
                                    <i class="bi bi-chat-text fs-5 text-white"></i>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-2">
                                    <a href="categoria_foro.php?id=<?php echo $categoria['id']; ?>" class="text-decoration-none text-dark fw-bold">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </a>
                                </h5>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="bg-light rounded-3 p-2 shadow-sm">
                                    <div class="fw-bold fs-5 text-dark"><?php echo $categoria['total_temas']; ?></div>
                                    <small class="text-muted">Temas</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <?php if ($categoria['ultimo_tema'] && !empty($categoria['ultimo_tema']['id'])): ?>
                                    <div class="bg-light rounded-3 p-3 shadow-sm">
                                        <small class="text-muted d-block mb-1">Último tema:</small>
                                        <a href="tema.php?id=<?php echo $categoria['ultimo_tema']['id']; ?>" class="text-decoration-none fw-bold text-dark d-block text-truncate">
                                            <?php echo htmlspecialchars($categoria['ultimo_tema']['titulo']); ?>
                                        </a>
                                        <small class="text-muted">
                                            por <?php echo htmlspecialchars($categoria['ultimo_tema']['usuario']); ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-light rounded-3 p-3 text-center shadow-sm">
                                        <small class="text-muted">Aún no hay temas</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Temas anclados -->
            <?php if (!empty($temas_anclados)): ?>
                <div class="card border-0 shadow-lg mt-4">
                    <div class="card-header bg-gradient bg-warning text-dark py-3">
                        <h4 class="mb-0">
                            <i class="bi bi-pin-angle-fill me-2"></i>Temas Anclados
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php foreach($temas_anclados as $tema): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 hover-bg-light">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="tema.php?id=<?php echo $tema['id']; ?>" class="text-decoration-none text-dark fw-bold">
                                            <?php echo htmlspecialchars($tema['titulo']); ?>
                                        </a>
                                        <span class="badge bg-warning text-dark ms-2 shadow-sm">
                                            <i class="bi bi-pin-angle-fill me-1"></i>Anclado
                                        </span>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="bi bi-folder me-1"></i>en <?php echo htmlspecialchars($tema['categoria_nombre']); ?> 
                                        • <i class="bi bi-person me-1"></i>por <?php echo htmlspecialchars($tema['usuario']); ?>
                                        • <i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y', strtotime($tema['creado_en'])); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="bg-light rounded-3 px-3 py-2 shadow-sm">
                                        <small class="text-muted d-block">Vistas</small>
                                        <span class="fw-bold text-dark"><?php echo $tema['vistas']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include(__DIR__ . '/../estructura/pie.php'); ?>
