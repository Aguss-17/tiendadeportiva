<?php
include(__DIR__ . '/../config/bd.php');
require_once __DIR__ . '/../config/cache.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_token()
{
    return $_SESSION['csrf_token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF inválido");
    }
}

// Variables del formulario
$txtID = $_POST['txtID'] ?? "";
$txtPuesto = $_POST['txtPuesto'] ?? "";
$txtDescripcion = $_POST['txtDescripcion'] ?? "";
$txtRequisitos = $_POST['txtRequisitos'] ?? [];
$txtSalario = $_POST['txtSalario'] ?? "";
$txtUbicacion = $_POST['txtUbicacion'] ?? "";
$txtFechaInicio = $_POST['txtFechaInicio'] ?? "";
$txtFechaCierre = $_POST['txtFechaCierre'] ?? "";
$txtAccion = $_POST['accion'] ?? "";

// Combinar requisitos
$requisitosStr = !empty($txtRequisitos) ? implode(",", $txtRequisitos) : null;

// CRUD
switch ($txtAccion) {

    case "Agregar":
        clearProductCache();
        $sentenciaSQL = $conexion->prepare(
            "INSERT INTO vacantes (puesto, descripcion, requisitos, salario, ubicacion, fecha_inicio, fecha_cierre) 
            VALUES (:puesto, :descripcion, :requisitos, :salario, :ubicacion, :fecha_inicio, :fecha_cierre)"
        );
        $sentenciaSQL->execute([
            ':puesto' => $txtPuesto,
            ':descripcion' => $txtDescripcion,
            ':requisitos' => $requisitosStr,
            ':salario' => $txtSalario,
            ':ubicacion' => $txtUbicacion,
            ':fecha_inicio' => $txtFechaInicio,
            ':fecha_cierre' => $txtFechaCierre
        ]);
        header('Location: vacantes.php?success=1');
        exit();

    case "Modificar":
        clearProductCache();
        $sentenciaSQL = $conexion->prepare(
            "UPDATE vacantes 
            SET puesto=:puesto, descripcion=:descripcion, requisitos=:requisitos, salario=:salario, 
                ubicacion=:ubicacion, fecha_inicio=:fecha_inicio, fecha_cierre=:fecha_cierre 
            WHERE id=:id"
        );
        $sentenciaSQL->execute([
            ':puesto' => $txtPuesto,
            ':descripcion' => $txtDescripcion,
            ':requisitos' => $requisitosStr,
            ':salario' => $txtSalario,
            ':ubicacion' => $txtUbicacion,
            ':fecha_inicio' => $txtFechaInicio,
            ':fecha_cierre' => $txtFechaCierre,
            ':id' => $txtID
        ]);
        header('Location: vacantes.php?success=1');
        exit();

    case "Seleccionar":
        $sentenciaSQL = $conexion->prepare("SELECT * FROM vacantes WHERE id=:id");
        $sentenciaSQL->execute([':id' => $txtID]);
        $vacante = $sentenciaSQL->fetch(PDO::FETCH_LAZY);

        $txtPuesto = $vacante['puesto'];
        $txtDescripcion = $vacante['descripcion'];
        $txtRequisitos = !empty($vacante['requisitos']) ? explode(",", $vacante['requisitos']) : [];
        $txtSalario = $vacante['salario'];
        $txtUbicacion = $vacante['ubicacion'];
        $txtFechaInicio = $vacante['fecha_inicio'];
        $txtFechaCierre = $vacante['fecha_cierre'];
        break;

    case "Borrar":
        clearProductCache();
        $sentenciaSQL = $conexion->prepare("DELETE FROM vacantes WHERE id=:id");
        $sentenciaSQL->execute([':id' => $txtID]);
        header('Location: vacantes.php?success=1');
        exit();
}


// PAGINACIÓN
$paginaActual = $_GET['pagina'] ?? 1;
$vacantesPorPagina = 20;


// FILTROS PARA VACANTES
$busquedaVacantes = $_GET['busqueda'] ?? '';
$filtroUbicacion = $_GET['ubicacion'] ?? '';

$whereConditionsVacantes = [];
$paramsVacantes = [];

if (!empty($busquedaVacantes)) {
    $whereConditionsVacantes[] = "(puesto LIKE :busqueda OR descripcion LIKE :busqueda)";
    $paramsVacantes[':busqueda'] = "%" . $busquedaVacantes . "%";
}

if (!empty($filtroUbicacion)) {
    $whereConditionsVacantes[] = "ubicacion LIKE :ubicacion";
    $paramsVacantes[':ubicacion'] = "%" . $filtroUbicacion . "%";
}

$whereClauseVacantes = !empty($whereConditionsVacantes)
    ? "WHERE " . implode(" AND ", $whereConditionsVacantes)
    : "";

// Total con filtros
$totalVacantes = $cache->cachedQuery(
    $conexion,
    "SELECT COUNT(*) as total FROM vacantes $whereClauseVacantes",
    $paramsVacantes,
    300
)[0]['total'] ?? 0;

// Paginación dependiendo de filtros
$pagination = getPagination($totalVacantes, $paginaActual, $vacantesPorPagina);

// Obtener vacantes filtradas
$sqlVacantes = "SELECT * FROM vacantes 
                $whereClauseVacantes
                ORDER BY id DESC 
                LIMIT :limit OFFSET :offset";

$sentenciaSQL = $conexion->prepare($sqlVacantes);

foreach ($paramsVacantes as $key => $value) {
    $sentenciaSQL->bindValue($key, $value, PDO::PARAM_STR);
}

$sentenciaSQL->bindValue(':limit', $vacantesPorPagina, PDO::PARAM_INT);
$sentenciaSQL->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$sentenciaSQL->execute();

$listaVacantes = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);

include('../administrador/estructura/cabecera.php');
?>

<!-- FORMULARIO PARA FILTROS -->

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" name="busqueda"
                    value="<?= htmlspecialchars($busquedaVacantes) ?>"
                    placeholder="Puesto o descripción...">
            </div>

            <div class="col-md-4">
                <label class="form-label">Ubicación</label>
                <input type="text" class="form-control" name="ubicacion"
                    value="<?= htmlspecialchars($filtroUbicacion) ?>"
                    placeholder="Ej: Centro, Remoto...">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>

            <?php if (!empty($busquedaVacantes) || !empty($filtroUbicacion)): ?>
            <div class="col-12">
                <a href="vacantes.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Limpiar filtros
                </a>

                <small class="text-muted ms-2">
                    <?= count($listaVacantes) ?> resultado(s) encontrado(s)
                </small>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-5 mb-4 p-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>DATOS DE LA VACANTE</h5>
            </div>
            <div class="card-body">
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="txtID" value="<?= htmlspecialchars($txtID) ?>">

                    <div class="mb-3">
                        <label class="form-label">ID</label>
                        <input type="text" readonly class="form-control bg-light" value="<?= htmlspecialchars($txtID) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Puesto *</label>
                        <input type="text" class="form-control" name="txtPuesto" value="<?= htmlspecialchars($txtPuesto) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción *</label>
                        <textarea class="form-control" name="txtDescripcion" rows="3" required><?= htmlspecialchars($txtDescripcion) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Salario</label>
                        <input type="text" class="form-control" name="txtSalario" value="<?= htmlspecialchars($txtSalario) ?>" placeholder="Ej: $500,000 - $700,000">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ubicación</label>
                        <input type="text" class="form-control" name="txtUbicacion" value="<?= htmlspecialchars($txtUbicacion) ?>" placeholder="Ej: Sucursal Centro, Remoto, etc.">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" name="txtFechaInicio" value="<?= htmlspecialchars($txtFechaInicio) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Cierre</label>
                            <input type="date" class="form-control" name="txtFechaCierre" value="<?= htmlspecialchars($txtFechaCierre) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Requisitos</label>
                        <div class="mb-2">
                            <small class="text-muted">Seleccione los requisitos existentes o agregue nuevos:</small>
                        </div>

                        <!-- Requisitos predefinidos -->
                        <select class="form-select mb-2" name="txtRequisitos[]" multiple size="4">
                            <?php foreach (['Experiencia comprobable', 'Estudios universitarios', 'Disponibilidad horaria', 'Inglés intermedio', 'Manejo de Office', 'Licencia de conducir', 'Trabajo en equipo'] as $req): ?>
                                <option value="<?= $req ?>" <?= in_array($req, $txtRequisitos) ? 'selected' : '' ?>><?= $req ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Campo para agregar requisitos personalizados -->
                        <div class="input-group">
                            <input type="text" class="form-control" id="nuevoRequisito" placeholder="Agregar requisito personalizado">
                            <button type="button" class="btn btn-outline-secondary" onclick="agregarRequisito()">Agregar</button>
                        </div>

                        <!-- Lista de requisitos seleccionados -->
                        <div id="listaRequisitos" class="mt-2">
                            <?php foreach ($txtRequisitos as $req): ?>
                                <?php if (!in_array($req, ['Experiencia comprobable', 'Estudios universitarios', 'Disponibilidad horaria', 'Inglés intermedio', 'Manejo de Office', 'Licencia de conducir', 'Trabajo en equipo'])): ?>
                                    <span class="badge bg-primary me-1 mb-1"><?= htmlspecialchars($req) ?>
                                        <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7rem;" onclick="removerRequisito(this)"></button>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <small class="text-muted">Ctrl para seleccionar múltiples requisitos predefinidos</small>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if ($txtAccion === "Seleccionar"): ?>
                            <button type="submit" name="accion" value="Modificar" class="btn btn-warning me-md-2">
                                <i class="fas fa-save me-1"></i> Guardar
                            </button>
                            <a href="vacantes.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Cancelar</a>
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

    <div class="col-md-7 p-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>LISTA DE VACANTES</h5>
                <span class="badge bg-light text-dark">Total: <?= count($listaVacantes) ?></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Puesto</th>
                                <th>Salario</th>
                                <th>Ubicación</th>
                                <th>Fecha Cierre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listaVacantes as $vacante): ?>
                                <tr>
                                    <td><?= $vacante['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($vacante['puesto']) ?></strong>
                                        <br><small class="text-muted"><?= substr(htmlspecialchars($vacante['descripcion']), 0, 50) ?>...</small>
                                    </td>
                                    <td><?= !empty($vacante['salario']) ? htmlspecialchars($vacante['salario']) : 'No especificado' ?></td>
                                    <td><?= !empty($vacante['ubicacion']) ? htmlspecialchars($vacante['ubicacion']) : 'No especificada' ?></td>
                                    <td>
                                        <?php if (!empty($vacante['fecha_cierre'])): ?>
                                            <?= date('d/m/Y', strtotime($vacante['fecha_cierre'])) ?>
                                            <?php if (strtotime($vacante['fecha_cierre']) < time()): ?>
                                                <span class="badge bg-danger">Expirada</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Abierta
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="txtID" value="<?= $vacante['id'] ?>">
                                                <button type="submit" name="accion" value="Seleccionar" class="btn btn-sm btn-primary">Editar</button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="txtID" value="<?= $vacante['id'] ?>">
                                                <button type="submit" name="accion" value="Borrar" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta vacante?')">Eliminar</button>
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

        <?php if ($pagination['total_pages'] > 1): ?>
            <nav aria-label="Navegación de vacantes">
                <ul class="pagination justify-content-center mt-3">
                    <?php if ($pagination['has_prev']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?= $pagination['current_page'] - 1 ?>">Anterior</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?= $pagination['current_page'] + 1 ?>">Siguiente</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- Panel de gestión de candidatos -->
        <div class="card shadow mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>GESTIÓN DE CANDIDATOS</h5>
            </div>
            <div class="card-body text-center">
                <a href="../menu/candidatos.php" class="btn btn-light btn-sm">
                    <i class="fas fa-eye me-1"></i> Ver todos los candidatos
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function agregarRequisito() {
        const input = document.getElementById('nuevoRequisito');
        const requisito = input.value.trim();

        if (requisito) {
            const lista = document.getElementById('listaRequisitos');
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary me-1 mb-1';
            badge.innerHTML = `${requisito} <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7rem;" onclick="removerRequisito(this)"></button>`;
            lista.appendChild(badge);

            // Agregar al select hidden para enviar con el formulario
            const select = document.querySelector('select[name="txtRequisitos[]"]');
            const option = document.createElement('option');
            option.value = requisito;
            option.selected = true;
            option.style.display = 'none';
            select.appendChild(option);

            input.value = '';
        }
    }

    function removerRequisito(button) {
        const badge = button.parentElement;
        const requisito = badge.textContent.trim();

        // Remover del select
        const select = document.querySelector('select[name="txtRequisitos[]"]');
        const options = select.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === requisito) {
                select.removeChild(options[i]);
                break;
            }
        }

        badge.remove();
    }
</script>

<?php include('../administrador/estructura/pie.php'); ?>