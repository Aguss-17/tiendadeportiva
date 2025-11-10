<?php
session_start();
require_once __DIR__ . '/../config/bd.php';

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Función para imprimir el token
function csrf_token() {
    return htmlspecialchars($_SESSION['csrf_token']);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=crear_tema");
    exit();
}

// Obtener categorías
$stmt = $conexion->query("SELECT * FROM foro_categorias ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // VALIDAR CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF inválido. Por seguridad, recarga la página e intenta nuevamente.");
    }

    $titulo = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $categoria_id = (int)$_POST['categoria_id'];

    $errors = [];

    if (empty($titulo)) {
        $errors[] = "El título es obligatorio";
    } elseif (strlen($titulo) < 5) {
        $errors[] = "El título debe tener al menos 5 caracteres";
    }

    if (empty($contenido)) {
        $errors[] = "El contenido es obligatorio";
    } elseif (strlen($contenido) < 10) {
        $errors[] = "El contenido debe tener al menos 10 caracteres";
    }

    if (empty($errors)) {
        try {
            $stmt = $conexion->prepare("
                INSERT INTO foro_temas (titulo, contenido, usuario_id, categoria_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$titulo, $contenido, $_SESSION['user_id'], $categoria_id]);

            $tema_id = $conexion->lastInsertId();
            $_SESSION['success'] = "Tema creado exitosamente";
            header("Location: tema.php?id=" . $tema_id);
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error al crear el tema: " . $e->getMessage();
        }
    }
}

include(__DIR__ . '/../estructura/cabecera.php');
?>

<link rel="stylesheet" href="/css/estilo_foro.css">

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-gradient bg-dark text-white py-3">
                    <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Crear Nuevo Tema</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($errors) && count($errors) > 0): ?>
                        <div class="alert alert-danger border-0 shadow-sm">
                            <?php foreach($errors as $error): ?>
                                <p class="mb-1"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                        <div class="mb-4">
                            <label for="categoria_id" class="form-label fw-bold text-dark">Categoría</label>
                            <select class="form-select form-select-lg border-2 shadow-sm" id="categoria_id" name="categoria_id" required>
                                <option value="">Selecciona una categoría</option>
                                <?php foreach($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" 
                                        <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?> >
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="titulo" class="form-label fw-bold text-dark">Título del Tema</label>
                            <input type="text" class="form-control form-control-lg border-2 shadow-sm" id="titulo" name="titulo" 
                                value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>" 
                                required minlength="5" maxlength="200" placeholder="Escribe un título descriptivo...">
                            <div class="form-text text-muted">
                                <i class="bi bi-lightbulb me-1"></i>Sé específico con tu título para obtener mejores respuestas.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="contenido" class="form-label fw-bold text-dark">Contenido</label>
                            <textarea class="form-control border-2 shadow-sm" id="contenido" name="contenido" rows="8" 
                                    required minlength="10" placeholder="Describe tu pregunta, experiencia o consejo en detalle..."><?php echo isset($_POST['contenido']) ? htmlspecialchars($_POST['contenido']) : ''; ?></textarea>
                            <div class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Puedes usar formato básico con **negrita** o *cursiva*.
                            </div>
                        </div>
                        
                        <div class="alert alert-info border-0 shadow-sm">
                            <div class="d-flex">
                                <i class="bi bi-lightbulb me-3 fs-5"></i>
                                <div>
                                    <strong class="d-block">Consejo:</strong>
                                    Antes de publicar, revisa si tu pregunta ya ha sido respondida en otros temas.
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3 pt-3">
                            <button type="submit" class="btn btn-dark btn-lg px-4 shadow-sm">
                                <i class="bi bi-check-lg me-2"></i>Crear Tema
                            </button>
                            <a href="foro.php" class="btn btn-outline-secondary btn-lg px-4 shadow-sm">
                                <i class="bi bi-x-lg me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include(__DIR__ . '/../estructura/pie.php'); ?>
