<?php
// administrador/comentarios.php
include(__DIR__ . '/../config/bd.php');
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_token() {
    return $_SESSION['csrf_token'];
}

// Validar token CSRF al recibir POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF inválido");
    }
}

// Manejar acciones de comentarios
$accion = $_POST['accion'] ?? '';
$comentario_id = $_POST['comentario_id'] ?? '';

switch ($accion) {
    case 'aprobar':
        $sentenciaSQL = $conexion->prepare("UPDATE comentarios SET estado = 'aprobado' WHERE id = :id");
        $sentenciaSQL->execute([':id' => $comentario_id]);
        break;
    case 'rechazar':
        $sentenciaSQL = $conexion->prepare("UPDATE comentarios SET estado = 'rechazado' WHERE id = :id");
        $sentenciaSQL->execute([':id' => $comentario_id]);
        break;
    case 'eliminar':
        $sentenciaSQL = $conexion->prepare("DELETE FROM comentarios WHERE id = :id");
        $sentenciaSQL->execute([':id' => $comentario_id]);
        break;
}

/* ================================
   NUEVO: FILTROS Y BÚSQUEDA
================================ */
$busquedaComentarios = $_GET['busqueda'] ?? '';
$filtroEstadoComentarios = $_GET['estado'] ?? '';

$whereConditionsComentarios = [];
$paramsComentarios = [];

if (!empty($busquedaComentarios)) {
    $whereConditionsComentarios[] = "(c.nombre LIKE :busqueda 
        OR c.email LIKE :busqueda 
        OR c.comentario LIKE :busqueda 
        OR p.titulo LIKE :busqueda)";
    $paramsComentarios[':busqueda'] = "%" . $busquedaComentarios . "%";
}

if (!empty($filtroEstadoComentarios)) {
    $whereConditionsComentarios[] = "c.estado = :estado";
    $paramsComentarios[':estado'] = $filtroEstadoComentarios;
}

$whereClauseComentarios = !empty($whereConditionsComentarios)
    ? "WHERE " . implode(" AND ", $whereConditionsComentarios)
    : "";

/* Obtener comentarios con filtros */
$sentenciaSQL = $conexion->prepare("
    SELECT c.*, p.titulo as post_titulo 
    FROM comentarios c 
    LEFT JOIN posts p ON c.post_id = p.id 
    $whereClauseComentarios
    ORDER BY c.fecha_creacion DESC
");

foreach ($paramsComentarios as $key => $value) {
    $sentenciaSQL->bindValue($key, $value, PDO::PARAM_STR);
}

$sentenciaSQL->execute();
$comentarios = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);

include('estructura/cabecera.php');
?>

<div class="container-fluid py-4">

    <!-- ================================
         NUEVO: FORMULARIO DE FILTROS
    ================================= -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="busqueda"
                        value="<?= htmlspecialchars($busquedaComentarios) ?>"
                        placeholder="Nombre, email, comentario o post...">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente" <?= $filtroEstadoComentarios == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="aprobado" <?= $filtroEstadoComentarios == 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                        <option value="rechazado" <?= $filtroEstadoComentarios == 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>

                <?php if (!empty($busquedaComentarios) || !empty($filtroEstadoComentarios)): ?>
                <div class="col-12">
                    <a href="comentarios.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Limpiar filtros
                    </a>
                    <small class="text-muted ms-2">
                        <?= count($comentarios) ?> resultado(s) encontrado(s)
                    </small>
                </div>
                <?php endif; ?>

            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-comments me-2"></i>GESTIÓN DE COMENTARIOS</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Post</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Comentario</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($comentarios as $comentario): ?>
                        <tr>
                            <td><?= $comentario['id'] ?></td>
                            <td><?= htmlspecialchars($comentario['post_titulo']) ?></td>
                            <td><?= htmlspecialchars($comentario['nombre']) ?></td>
                            <td><?= htmlspecialchars($comentario['email']) ?></td>
                            <td><?= nl2br(htmlspecialchars(substr($comentario['comentario'], 0, 100))) ?>...</td>
                            <td><?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?></td>

                            <td>
                                <?php 
                                $estados = [
                                    'pendiente' => 'warning',
                                    'aprobado' => 'success', 
                                    'rechazado' => 'danger'
                                ];
                                $color = $estados[$comentario['estado']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= ucfirst($comentario['estado']) ?></span>
                            </td>

                            <td>
                                <div class="d-flex gap-1">

                                    <?php if($comentario['estado'] != 'aprobado'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <button type="submit" name="accion" value="aprobar" 
                                                class="btn btn-sm btn-success" title="Aprobar">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if($comentario['estado'] != 'rechazado'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <button type="submit" name="accion" value="rechazar" 
                                                class="btn btn-sm btn-warning" title="Rechazar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                                        <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                        <button type="submit" name="accion" value="eliminar" 
                                            class="btn btn-sm btn-danger" title="Eliminar"
                                            onclick="return confirm('¿Eliminar este comentario?')">
                                            <i class="fas fa-trash"></i>
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

<?php include('estructura/pie.php'); ?>
