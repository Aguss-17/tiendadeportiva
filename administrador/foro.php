<?php
// Incluir controlador que tiene toda la lógica
require_once 'controlador_foro.php';

// Incluir cabecera
include('estructura/cabecera.php');

// Extraer variables del controlador
extract($datosVista);
?>

<div class="container-fluid">
    <!-- NAVEGACIÓN ENTRE MÓDULOS -->
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-comments me-2"></i>GESTIÓN DEL FORO</h5>
        </div>
        <div class="card-body p-2">
            <div class="d-flex flex-wrap gap-2">
                <a href="?modulo=categorias" class="btn <?= $modulo == 'categorias' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-tags me-1"></i> Categorías
                </a>
                <a href="?modulo=temas" class="btn <?= $modulo == 'temas' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-comments me-1"></i> Temas
                </a>
                <a href="?modulo=respuestas" class="btn <?= $modulo == 'respuestas' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-reply me-1"></i> Respuestas
                </a>
            </div>
        </div>
    </div>

    <!-- MENSAJES -->
    <?php if (!empty($mensajeSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($mensajeSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Errores encontrados:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- MÓDULO CATEGORÍAS -->
    <?php if ($modulo == 'categorias'): ?>
        <div class="row">
            <!-- FORMULARIO DE CATEGORÍAS -->
            <div class="col-md-5 mb-4">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">GESTIONAR CATEGORÍAS</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="txtID" value="<?= htmlspecialchars($categoriaSeleccionada['id'] ?? '') ?>">
                            <div class="mb-3">
                                <label class="form-label">Nombre de la Categoría</label>
                                <input type="text" class="form-control" name="txtNombre" value="<?= htmlspecialchars($categoriaSeleccionada['nombre'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="txtDescripcion" rows="3" required><?= htmlspecialchars($categoriaSeleccionada['descripcion'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Color</label>
                                <input type="color" class="form-control form-control-color" name="txtColor" value="<?= htmlspecialchars($categoriaSeleccionada['color'] ?? '#5a3e36') ?>">
                            </div>
                            <div class="d-grid gap-2">
                                <?php if (!empty($categoriaSeleccionada)): ?>
                                    <button type="submit" name="accion" value="Modificar" class="btn btn-warning">
                                        <i class="fas fa-save me-1"></i> Guardar Cambios
                                    </button>
                                    <a href="?modulo=categorias" class="btn btn-secondary">Cancelar</a>
                                <?php else: ?>
                                    <button type="submit" name="accion" value="Agregar" class="btn btn-success">
                                        <i class="fas fa-plus me-1"></i> Agregar Categoría
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- LISTA DE CATEGORÍAS -->
            <div class="col-md-7">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">LISTA DE CATEGORÍAS</h6>
                        <span class="badge bg-light text-dark">Total: <?= count($listaCategorias) ?></span>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Color</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listaCategorias as $categoria): ?>
                                    <tr>
                                        <td>
                                            <div style="width: 20px; height: 20px; background-color: <?= $categoria['color'] ?>; border-radius: 3px;"></div>
                                        </td>
                                        <td><?= htmlspecialchars($categoria['nombre']) ?></td>
                                        <td><?= htmlspecialchars($categoria['descripcion']) ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <form method="POST" class="m-0">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="txtID" value="<?= $categoria['id'] ?>">
                                                    <button type="submit" name="accion" value="Seleccionar" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="m-0" onsubmit="return confirm('¿Eliminar categoría?')">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="txtID" value="<?= $categoria['id'] ?>">
                                                    <button type="submit" name="accion" value="Borrar" class="btn btn-sm btn-danger">
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
    <?php endif; ?>

    <!-- MÓDULO TEMAS -->
    <?php if ($modulo == 'temas'): ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">LISTA DE TEMAS</h6>
                <span class="badge bg-light text-dark">Total: <?= count($listaTemas) ?></span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Usuario</th>
                            <th>Respuestas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaTemas as $tema): ?>
                            <tr>
                                <td><?= htmlspecialchars($tema['titulo']) ?></td>
                                <td><?= htmlspecialchars($tema['categoria_nombre']) ?></td>
                                <td><?= htmlspecialchars($tema['usuario']) ?></td>
                                <td><?= $tema['total_respuestas'] ?></td>
                                <td><?= htmlspecialchars($tema['estado']) ?> <?= $tema['es_anclado'] ? "(Anclado)" : "" ?></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="txtID" value="<?= $tema['id'] ?>">
                                            <?php if ($tema['es_anclado']): ?>
                                                <button type="submit" name="accion" value="Desanclar" class="btn btn-sm btn-secondary">Desanclar</button>
                                            <?php else: ?>
                                                <button type="submit" name="accion" value="Anclar" class="btn btn-sm btn-primary">Anclar</button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="txtID" value="<?= $tema['id'] ?>">
                                            <?php if ($tema['estado'] == 'activo'): ?>
                                                <button type="submit" name="accion" value="Cerrar" class="btn btn-sm btn-warning">Cerrar</button>
                                            <?php elseif ($tema['estado'] == 'cerrado'): ?>
                                                <button type="submit" name="accion" value="Reabrir" class="btn btn-sm btn-success">Reabrir</button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="POST" class="m-0" onsubmit="return confirm('¿Eliminar tema?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="txtID" value="<?= $tema['id'] ?>">
                                            <button type="submit" name="accion" value="Eliminar" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- MÓDULO RESPUESTAS -->
    <?php if ($modulo == 'respuestas'): ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">LISTA DE RESPUESTAS</h6>
                <span class="badge bg-light text-dark">Total: <?= count($listaRespuestas) ?></span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tema</th>
                            <th>Usuario</th>
                            <th>Contenido</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaRespuestas as $respuesta): ?>
                            <tr>
                                <td><?= htmlspecialchars($respuesta['tema_titulo']) ?></td>
                                <td><?= htmlspecialchars($respuesta['usuario']) ?></td>
                                <td><?= nl2br(htmlspecialchars($respuesta['contenido'])) ?></td>
                                <td><?= htmlspecialchars($respuesta['estado']) ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="txtID" value="<?= $respuesta['id'] ?>">
                                            <?php if ($respuesta['estado'] == 'activo'): ?>
                                                <button type="submit" name="accion" value="Eliminar" class="btn btn-sm btn-danger">Eliminar</button>
                                            <?php else: ?>
                                                <button type="submit" name="accion" value="Restaurar" class="btn btn-sm btn-success">Restaurar</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('estructura/pie.php'); ?>
