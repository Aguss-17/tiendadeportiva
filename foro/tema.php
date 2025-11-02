<?php
session_start();
require_once __DIR__ . '/../config/bd.php';

if (!isset($_GET['id'])) {
    header("Location: foro.php");
    exit();
}

$tema_id = (int)$_GET['id'];

// Obtener tema
try {
    $stmt = $conexion->prepare("
        SELECT t.*, u.usuario, u.id as usuario_id, c.nombre as categoria_nombre, c.id as categoria_id
        FROM foro_temas t 
        JOIN usuarios u ON t.usuario_id = u.id 
        JOIN foro_categorias c ON t.categoria_id = c.id 
        WHERE t.id = ? AND t.estado = 'activo'
    ");
    $stmt->execute([$tema_id]);
    $tema = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tema) {
        die("Tema no encontrado");
    }
    
    // Incrementar vistas
    $conexion->prepare("UPDATE foro_temas SET vistas = vistas + 1 WHERE id = ?")->execute([$tema_id]);
    
    // Obtener respuestas
    $stmt = $conexion->prepare("
        SELECT r.*, u.usuario, u.id as usuario_id
        FROM foro_respuestas r 
        JOIN usuarios u ON r.usuario_id = u.id 
        WHERE r.tema_id = ? AND r.estado = 'activo' 
        ORDER BY r.creado_en ASC
    ");
    $stmt->execute([$tema_id]);
    $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al cargar el tema");
}

// Procesar nueva respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $contenido = trim($_POST['contenido']);
    
    if (!empty($contenido) && strlen($contenido) >= 2) {
        try {
            $stmt = $conexion->prepare("
                INSERT INTO foro_respuestas (contenido, usuario_id, tema_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$contenido, $_SESSION['user_id'], $tema_id]);
            
            // Actualizar fecha de modificación del tema
            $conexion->prepare("UPDATE foro_temas SET actualizado_en = NOW() WHERE id = ?")->execute([$tema_id]);
            
            $_SESSION['success'] = "Respuesta publicada exitosamente";
            header("Location: tema.php?id=" . $tema_id);
            exit();
        } catch (PDOException $e) {
            $error = "Error al publicar la respuesta";
        }
    } else {
        $error = "La respuesta debe tener al menos 2 caracteres";
    }
}

include(__DIR__ . '/../estructura/cabecera.php');
?>

<link rel="stylesheet" href="/tiendadeportiva/css/estilo_foro.css">

<main class="container my-5">
    <div class="row">
        <div class="col-12">
            <!-- Migas de pan -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb bg-light rounded-3 px-3 py-2 shadow-sm">
                    <li class="breadcrumb-item"><a href="foro.php" class="text-decoration-none">Foro</a></li>
                    <li class="breadcrumb-item"><a href="categoria_foro.php?id=<?php echo $tema['categoria_id']; ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($tema['categoria_nombre']); ?>
                    </a></li>
                    <li class="breadcrumb-item active text-truncate"><?php echo htmlspecialchars($tema['titulo']); ?></li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Tema principal -->
            <div class="card border-0 shadow-lg mb-4">
                <div class="card-header bg-gradient bg-light border-0 py-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-dark fw-bold"><?php echo htmlspecialchars($tema['titulo']); ?></h4>
                    <?php if ($tema['es_anclado']): ?>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm">
                            <i class="bi bi-pin-angle-fill me-1"></i> Anclado
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <!-- Columna del usuario (avatar e info) -->
                        <div class="col-md-3 col-lg-2">
                            <div class="text-center">
                                <div class="bg-light rounded-3 p-4 shadow-sm mb-3">
                                    <i class="bi bi-person-circle fs-1 text-primary"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($tema['usuario']); ?></h6>
                                <small class="text-muted">Miembro</small>
                            </div>
                        </div>
                        <!-- Columna del contenido -->
                        <div class="col-md-9 col-lg-10">
                            <div class="bg-light rounded-3 p-4 shadow-sm mb-3">
                                <p class="mb-0 fs-6 lh-lg text-start"><?php echo nl2br(htmlspecialchars($tema['contenido'])); ?></p>
                            </div>
                            <div class="text-muted small d-flex flex-wrap gap-3">
                                <span class="d-flex align-items-center">
                                    <i class="bi bi-clock me-1"></i> Publicado el <?php echo date('d/m/Y H:i', strtotime($tema['creado_en'])); ?>
                                </span>
                                <?php if ($tema['creado_en'] != $tema['actualizado_en']): ?>
                                    <span class="d-flex align-items-center">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Editado el <?php echo date('d/m/Y H:i', strtotime($tema['actualizado_en'])); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="d-flex align-items-center">
                                    <i class="bi bi-eye me-1"></i> <?php echo $tema['vistas'] + 1; ?> vistas
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Respuestas -->
            <div class="d-flex align-items-center mb-3">
                <h5 class="text-dark fw-bold mb-0 me-3">Respuestas</h5>
                <span class="badge bg-primary rounded-pill fs-6 px-3 py-2 shadow-sm"><?php echo count($respuestas); ?></span>
            </div>
            
            <?php if (empty($respuestas)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-chat-dots fs-1 text-muted mb-3"></i>
                        <h5 class="text-dark fw-bold">¡Sé el primero en responder!</h5>
                        <p class="text-muted">Aún no hay respuestas en este tema. Comparte tu opinión o conocimiento.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach($respuestas as $respuesta): ?>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <!-- Columna del usuario (avatar e info) -->
                                <div class="col-md-3 col-lg-2">
                                    <div class="text-center">
                                        <div class="bg-light rounded-3 p-3 shadow-sm mb-2">
                                            <i class="bi bi-person-circle fs-3 text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($respuesta['usuario']); ?></h6>
                                        <small class="text-muted">Miembro</small>
                                    </div>
                                </div>
                                <!-- Columna del contenido -->
                                <div class="col-md-9 col-lg-10">
                                    <div class="bg-light rounded-3 p-4 shadow-sm mb-3">
                                        <p class="mb-0 lh-lg text-start"><?php echo nl2br(htmlspecialchars($respuesta['contenido'])); ?></p>
                                    </div>
                                    <div class="text-muted small d-flex flex-wrap gap-3">
                                        <span class="d-flex align-items-center">
                                            <i class="bi bi-clock me-1"></i> Respondido el <?php echo date('d/m/Y H:i', strtotime($respuesta['creado_en'])); ?>
                                        </span>
                                        <?php if ($respuesta['creado_en'] != $respuesta['actualizado_en']): ?>
                                            <span class="d-flex align-items-center">
                                                <i class="bi bi-arrow-clockwise me-1"></i> Editado el <?php echo date('d/m/Y H:i', strtotime($respuesta['actualizado_en'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Formulario de respuesta -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card border-0 shadow-lg mt-4">
                    <div class="card-header bg-gradient bg-light border-0 py-3">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-reply-fill me-2"></i>Responder al Tema
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="post">
                            <div class="mb-3">
                                <textarea class="form-control border-2 shadow-sm" id="contenido" name="contenido" rows="5" 
                                        placeholder="Escribe tu respuesta aquí..." required minlength="2"></textarea>
                                <div class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>Sé respetuoso y constructivo en tus respuestas.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark btn-lg px-4 shadow-sm">
                                <i class="bi bi-send-fill me-2"></i>Publicar Respuesta
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm text-center py-4">
                    <div class="card-body">
                        <h5 class="text-dark fw-bold mb-3">¿Quieres participar?</h5>
                        <p class="text-muted mb-3">Debes iniciar sesión o registrarte para poder responder en este tema.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="../login.php?redirect=tema&id=<?php echo $tema_id; ?>" class="btn btn-dark px-4 shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </a>
                            <a href="../registro.php" class="btn btn-outline-dark px-4 shadow-sm">
                                <i class="bi bi-person-plus me-2"></i>Registrarse
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include(__DIR__ . '/../estructura/pie.php'); ?>