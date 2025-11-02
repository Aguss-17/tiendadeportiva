<?php
include(__DIR__ . '/../config/bd.php');
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Variables para filtros y acciones
$filtro_estado = $_GET['estado'] ?? '';
$filtro_puesto = $_GET['puesto'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$accion = $_POST['accion'] ?? '';
$candidato_id = $_POST['candidato_id'] ?? '';

// Procesar acciones
if ($accion === "cambiar_estado" && $candidato_id) {
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    if (in_array($nuevo_estado, ['Pendiente', 'En revisión', 'Entrevista', 'Contratado', 'Rechazado'])) {
        $sentenciaSQL = $conexion->prepare("UPDATE postulaciones SET estado = :estado WHERE id = :id");
        $sentenciaSQL->execute([':estado' => $nuevo_estado, ':id' => $candidato_id]);
        header('Location: candidatos.php?success=1');
        exit();
    }
}

if ($accion === "eliminar" && $candidato_id) {
    $sentenciaSQL = $conexion->prepare("DELETE FROM postulaciones WHERE id = :id");
    $sentenciaSQL->execute([':id' => $candidato_id]);
    header('Location: candidatos.php?success=1');
    exit();
}

// Construir consulta con filtros
$sql = "SELECT * FROM postulaciones WHERE 1=1";
$params = [];

if ($filtro_estado) {
    $sql .= " AND estado = :estado";
    $params[':estado'] = $filtro_estado;
}

if ($filtro_puesto) {
    $sql .= " AND puesto_interes LIKE :puesto";
    $params[':puesto'] = "%$filtro_puesto%";
}

if ($filtro_fecha) {
    $sql .= " AND DATE(fecha_postulacion) = :fecha";
    $params[':fecha'] = $filtro_fecha;
}

$sql .= " ORDER BY fecha_postulacion DESC";

$sentenciaSQL = $conexion->prepare($sql);
$sentenciaSQL->execute($params);
$candidatos = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);

// Obtener TODOS los puestos de TODAS las vacantes (activas e inactivas)
$sentenciaPuestos = $conexion->prepare("SELECT DISTINCT puesto FROM vacantes ORDER BY puesto");
$sentenciaPuestos->execute();
$puestos = $sentenciaPuestos->fetchAll(PDO::FETCH_COLUMN);

include('../administrador/estructura/cabecera.php');
?>

<div class="container-fluid py-4" style="background-color: #dec19e;">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-users me-2"></i>Gestión de Candidatos</h4>
                    <span class="badge text-dark">Total: <?= count($candidatos) ?></span>
                </div>
                <div class="card-body">

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-heade">
                            <h5 class="mb-0">Filtros</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos los estados</option>
                                        <option value="Pendiente" <?= $filtro_estado === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                        <option value="En revisión" <?= $filtro_estado === 'En revisión' ? 'selected' : '' ?>>En revisión</option>
                                        <option value="Entrevista" <?= $filtro_estado === 'Entrevista' ? 'selected' : '' ?>>Entrevista</option>
                                        <option value="Contratado" <?= $filtro_estado === 'Contratado' ? 'selected' : '' ?>>Contratado</option>
                                        <option value="Rechazado" <?= $filtro_estado === 'Rechazado' ? 'selected' : '' ?>>Rechazado</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Puesto</label>
                                    <select name="puesto" class="form-select">
                                        <option value="">Todos los puestos</option>
                                        <?php foreach ($puestos as $puesto): ?>
                                            <option value="<?= htmlspecialchars($puesto) ?>" <?= $filtro_puesto === $puesto ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($puesto) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Fecha de postulación</label>
                                    <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filtro_fecha) ?>">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-dark me-2">
                                        <i class="fas fa-search me-1"></i> Filtrar
                                    </button>
                                    <a href="candidatos.php" class="btn btn-dark">
                                        <i class="fas fa-undo me-1"></i> Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Estadísticas rápidas compactas -->
                    <ul class="estadisticas-rapidas list-inline mb-4 text-center">
                        <li class="list-inline-item mx-3">
                            <strong><?= count($candidatos) ?></strong><br>
                            <small>Total</small>
                        </li>
                        <li class="list-inline-item mx-3">
                            <strong><?= count(array_filter($candidatos, fn($c) => $c['estado'] === 'Pendiente')) ?></strong><br>
                            <small>Pendientes</small>
                        </li>
                        <li class="list-inline-item mx-3">
                            <strong><?= count(array_filter($candidatos, fn($c) => $c['estado'] === 'En revisión')) ?></strong><br>
                            <small>En revisión</small>
                        </li>
                        <li class="list-inline-item mx-3">
                            <strong><?= count(array_filter($candidatos, fn($c) => $c['estado'] === 'Contratado')) ?></strong><br>
                            <small>Contratados</small>
                        </li>
                        <li class="list-inline-item mx-3">
                            <strong><?= count(array_filter($candidatos, fn($c) => $c['estado'] === 'Rechazado')) ?></strong><br>
                            <small>Rechazados</small>
                        </li>
                    </ul>

                    <!-- Tabla de candidatos -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Puesto</th>
                                    <th>Fecha Postulación</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($candidatos)): ?>
                                    <?php foreach ($candidatos as $candidato): ?>
                                        <tr>
                                            <td><?= $candidato['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($candidato['nombre_apellido']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?= !empty($candidato['fecha_nacimiento']) ? date('d/m/Y', strtotime($candidato['fecha_nacimiento'])) : 'N/A' ?>
                                                </small>
                                            </td>
                                            <td><?= htmlspecialchars($candidato['dni']) ?></td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($candidato['email']) ?>">
                                                    <?= htmlspecialchars($candidato['email']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($candidato['telefono']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($candidato['puesto_interes']) ?></span>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($candidato['fecha_postulacion'])) ?>
                                            </td>
                                            <td>
                                                <span class="badge 
                                            <?= match ($candidato['estado']) {
                                            'Pendiente' => 'bg-warning',
                                            'En revisión' => 'bg-info',
                                            'Entrevista' => 'bg-primary',
                                            'Contratado' => 'bg-success',
                                            'Rechazado' => 'bg-danger',
                                            default => 'bg-secondary'
                                        } ?>">
                                                    <?= $candidato['estado'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <ol class="acciones-candidato list-unstyled mb-0">
                                                    <!-- Ver detalles -->
                                                    <li class="mb-1">
                                                        <button type="button" class="btn btn-sm btn-outline-dark w-100" data-bs-toggle="modal" data-bs-target="#modalDetalles<?= $candidato['id'] ?>">
                                                            Ver detalles
                                                        </button>
                                                    </li>

                                                    <!-- Cambiar estado (vertical) -->
                                                    <li class="mb-1">
                                                        <form method="POST">
                                                            <input type="hidden" name="candidato_id" value="<?= $candidato['id'] ?>">
                                                            <input type="hidden" name="nuevo_estado" value="Pendiente">
                                                            <button type="submit" name="accion" value="cambiar_estado" class="btn btn-sm btn-outline-dark w-100">
                                                                Marcar Pendiente
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li class="mb-1">
                                                        <form method="POST">
                                                            <input type="hidden" name="candidato_id" value="<?= $candidato['id'] ?>">
                                                            <input type="hidden" name="nuevo_estado" value="En revisión">
                                                            <button type="submit" name="accion" value="cambiar_estado" class="btn btn-sm btn-outline-dark w-100">
                                                                En revisión
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li class="mb-1">
                                                        <form method="POST">
                                                            <input type="hidden" name="candidato_id" value="<?= $candidato['id'] ?>">
                                                            <input type="hidden" name="nuevo_estado" value="Entrevista">
                                                            <button type="submit" name="accion" value="cambiar_estado" class="btn btn-sm btn-outline-dark w-100">
                                                                Entrevista
                                                            </button>
                                                        </form>
                                                    </li>

                                                    <!-- Contratado, Rechazado y Eliminar (horizontal con iconos) -->
                                                    <li class="d-flex justify-content-between mt-2">
                                                        <form method="POST" class="flex-fill me-1">
                                                            <input type="hidden" name="candidato_id" value="<?= $candidato['id'] ?>">
                                                            <input type="hidden" name="nuevo_estado" value="Contratado">
                                                            <button type="submit" name="accion" value="cambiar_estado" class="btn btn-sm btn-dark w-100">
                                                                <i class="fas fa-check"></i> Contratado
                                                            </button>
                                                        </form>

                                                        <form method="POST" class="flex-fill mx-1">
                                                            <input type="hidden" name="candidato_id" value="<?= $candidato['id'] ?>">
                                                            <input type="hidden" name="nuevo_estado" value="Rechazado">
                                                            <button type="submit" name="accion" value="cambiar_estado" class="btn btn-sm btn-dark w-100">
                                                                <i class="fas fa-times"></i> Rechazado
                                                            </button>
                                                        </form>

                                                        <form method="POST" class="flex-fill ms-1" onsubmit="return confirm('¿Estás seguro de eliminar este candidato?')">
                                                            <input type="hidden" name="candidato_id" value="<?= $candidato['id'] ?>">
                                                            <button type="submit" name="accion" value="eliminar" class="btn btn-sm btn-dark w-100">
                                                                <i class="fas fa-trash"></i> Eliminar
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ol>
                                            </td>
                                        </tr>

                                        <!-- Modal de detalles -->
                                        <div class="modal fade" id="modalDetalles<?= $candidato['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-dark text-white">
                                                        <h5 class="modal-title">Detalles del Candidato</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Información Personal</h6>
                                                                <p><strong>Nombre:</strong> <?= htmlspecialchars($candidato['nombre_apellido']) ?></p>
                                                                <p><strong>DNI:</strong> <?= htmlspecialchars($candidato['dni']) ?></p>
                                                                <p><strong>Fecha Nacimiento:</strong> <?= !empty($candidato['fecha_nacimiento']) ? date('d/m/Y', strtotime($candidato['fecha_nacimiento'])) : 'N/A' ?></p>
                                                                <p><strong>Domicilio:</strong> <?= htmlspecialchars($candidato['domicilio']) ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Información de Contacto</h6>
                                                                <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($candidato['email']) ?>"><?= htmlspecialchars($candidato['email']) ?></a></p>
                                                                <p><strong>Teléfono:</strong> <a href="tel:<?= htmlspecialchars($candidato['telefono']) ?>"><?= htmlspecialchars($candidato['telefono']) ?></a></p>
                                                                <p><strong>Puesto de interés:</strong> <?= htmlspecialchars($candidato['puesto_interes']) ?></p>
                                                                <p><strong>Fecha Postulación:</strong> <?= date('d/m/Y H:i', strtotime($candidato['fecha_postulacion'])) ?></p>
                                                                <p><strong>Estado:</strong> <span class="badge 
                                                                    <?= match ($candidato['estado']) {
                                                                        'Pendiente' => 'bg-warning',
                                                                        'En revisión' => 'bg-info',
                                                                        'Entrevista' => 'bg-primary',
                                                                        'Contratado' => 'bg-success',
                                                                        'Rechazado' => 'bg-danger',
                                                                        default => 'bg-secondary'
                                                                    } ?>"><?= $candidato['estado'] ?></span></p>
                                                            </div>
                                                        </div>
                                                        <?php if (!empty($candidato['cv_path'])): ?>
                                                            <div class="mt-3">
                                                                <h6>Currículum Vitae</h6>
                                                                <a href="../uploads/cvs/<?= htmlspecialchars($candidato['cv_path']) ?>" target="_blank" class="btn btn-outline-dark btn-sm">
                                                                    <i class="fas fa-download me-1"></i>Descargar CV
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-users fa-2x mb-3"></i>
                                                <h5>No se encontraron candidatos</h5>
                                                <p>No hay postulaciones que coincidan con los filtros aplicados.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../administrador/estructura/pie.php'); ?>