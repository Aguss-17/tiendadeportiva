<?php
require_once __DIR__ . '/../config/cache.php';
require_once 'controlador_productos.php';
include('estructura/cabecera.php');

// BÚSQUEDA Y FILTROS
$busqueda = $_GET['busqueda'] ?? '';
$filtroCategoria = $_GET['categoria'] ?? '';
$filtroOferta = $_GET['oferta'] ?? '';

$whereConditions = [];
$params = [];

if (!empty($busqueda)) {
    $whereConditions[] = "(p.nombre LIKE :busqueda OR p.descripcion LIKE :busqueda)";
    $params[':busqueda'] = "%" . $busqueda . "%";
}

if (!empty($filtroCategoria)) {
    $whereConditions[] = "p.id_categoria = :categoria";
    $params[':categoria'] = $filtroCategoria;
}

if (!empty($filtroOferta)) {
    $whereConditions[] = "p.en_oferta = :oferta";
    $params[':oferta'] = $filtroOferta;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Total productos filtrados
$totalProductos = $cache->cachedQuery(
    $conexion,
    "SELECT COUNT(*) as total FROM productos p $whereClause",
    $params,
    300
)[0]['total'] ?? 0;

// Productos paginados
$sql = "SELECT p.*, c.nombre AS categoria_nombre
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id
        $whereClause
        ORDER BY p.id DESC
        LIMIT :limit OFFSET :offset";

$listaProductos = $cache->cachedPagination(
    $conexion,
    $sql,
    $params,
    $paginaActual,
    $productosPorPagina
);

?>

<div class="row">
    <!-- FORMULARIO DE PRODUCTO -->
    <div class="col-md-5 mb-4 p-4">
        <div class="card shadow-lg">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-tshirt me-2"></i>DATOS DEL PRODUCTO</h5>
            </div>
            <div class="card-body">

                <!-- Mensajes -->
                <?php if (!empty($mensajeSuccess)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($mensajeSuccess) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><strong>Errores:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errores as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" autocomplete="off" id="formProducto">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($txtImagenActual) ?>">

                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="txtNombre" value="<?= htmlspecialchars($txtNombre) ?>" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Descripción</label>
                        <textarea name="txtDescripcion" class="form-control" rows="3"><?= htmlspecialchars($txtDescripcion) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label>Precio *</label>
                        <input type="number" name="txtPrecio" step="0.01" value="<?= $txtPrecio ?>" class="form-control <?= isset($errores['precio']) ? 'is-invalid' : '' ?>" required min="0">
                        <?php if (isset($errores['precio'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errores['precio']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label>Categoría *</label>
                        <select name="txtCategoria" class="form-select">
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $txtCategoria == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- OFERTA Y VIP -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>¿En oferta?</label>
                            <select name="en_oferta" class="form-select">
                                <option value="0" <?= $txtOferta == 0 ? 'selected' : '' ?>>No</option>
                                <option value="1" <?= $txtOferta == 1 ? 'selected' : '' ?>>Sí</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label>Producto VIP</label>
                            <select name="txtVIP" class="form-select">
                                <option value="0" <?= $txtVIP == 0 ? 'selected' : '' ?>>No</option>
                                <option value="1" <?= $txtVIP == 1 ? 'selected' : '' ?>>Sí</option>
                            </select>
                        </div>
                    </div>

                    <!-- TALLES -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Selección de Talles</label>
                        <div class="row">

                            <div class="col-md-4">
                                <label class="form-label">Remeras/Camisetas:</label>
                                <select name="talles_remera[]" class="form-select" multiple size="3">
                                    <option value="Sin talles">Sin talles</option>
                                    <option value="XS">XS</option>
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Pantalones/Calzas/Jogging:</label>
                                <select name="talles_pantalon[]" class="form-select" multiple size="3">
                                    <option value="Sin talles">Sin talles</option>
                                    <option value="36">36</option>
                                    <option value="38">38</option>
                                    <option value="40">40</option>
                                    <option value="42">42</option>
                                    <option value="44">44</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Ropa Infantil:</label>
                                <select name="talles_infantil[]" class="form-select" multiple size="3">
                                    <option value="Sin talles">Sin talles</option>
                                    <option value="2">2</option>
                                    <option value="4">4</option>
                                    <option value="6">6</option>
                                    <option value="8">8</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Color</label>
                        <select name="txtColores[]" class="form-select" multiple>
                            <option value="Negra">Negra</option>
                            <option value="Azul">Azul</option>
                            <option value="Verde">Verde</option>
                            <option value="Rosa">Rosa</option>
                            <option value="Morado">Morado</option>
                            <option value="Turquesa">Turquesa</option>
                            <option value="Borde">Borde</option>
                            <option value="Gira">Gira</option>
                            <option value="Unisex">Unisex</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Imagen</label>
                        <input type="file" name="imagen" class="form-control">
                        <?php if (!empty($txtImagenActual)): ?>
                            <img src="../img/<?= $txtImagenActual ?>" width="80" class="mt-2 rounded">
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2">
                        <?php if ($txtAccion === "Seleccionar"): ?>
                            <div class="d-flex justify-content-center gap-2 pb-3 border-bottom">
                                <button type="submit" name="accion" value="Modificar" class="btn btn-warning">
                                    <i class="fas fa-save me-1"></i> Guardar Cambios
                                </button>
                                <a href="productos.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center pb-3 border-bottom">
                                <button type="submit" name="accion" value="Agregar" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Agregar Producto
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- LISTA DE PRODUCTOS -->
    <div class="col-md-7 p-4">
        <div class="card shadow-lg">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>LISTA DE PRODUCTOS</h5>
                <span class="badge bg-light text-dark fs-6">Total: <?= $totalProductos ?></span>
            </div>

            <div class="card-body px-4">

                <!-- FILTROS Y BÚSQUEDA -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">

                            <div class="col-md-4">
                                <label class="form-label">Buscar</label>
                                <input type="text" class="form-control" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Nombre o descripción...">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $filtroCategoria == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Oferta</label>
                                <select name="oferta" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="1" <?= $filtroOferta === '1' ? 'selected' : '' ?>>En oferta</option>
                                    <option value="0" <?= $filtroOferta === '0' ? 'selected' : '' ?>>Sin oferta</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                            </div>

                            <?php if (!empty($busqueda) || !empty($filtroCategoria) || !empty($filtroOferta)): ?>
                            <div class="col-12">
                                <a href="productos.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Limpiar filtros
                                </a>
                                <small class="text-muted ms-2">
                                    <?= count($listaProductos) ?> resultado(s) encontrado(s)
                                </small>
                            </div>
                            <?php endif; ?>

                        </form>
                    </div>
                </div>

                <!-- TABLA -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Categoría</th>
                                <th>Oferta</th>
                                <th>VIP</th>
                                <th>Talle</th>
                                <th>Color</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php foreach ($listaProductos as $producto): ?>
                            <tr>
                                <td><?= $producto['id'] ?></td>
                                <td>
                                    <?php if (!empty($producto['imagen'])): ?>
                                        <img src="../img/<?= $producto['imagen'] ?>" width="50">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                <td>$<?= number_format($producto['precio'], 2) ?></td>
                                <td>
                                    <?php
                                    $catColor = match (strtolower($producto['categoria_nombre'])) {
                                        'mujer'      => 'bg-danger text-white',
                                        'hombre'     => 'bg-primary text-white',
                                        'niños'      => 'bg-success text-white',
                                        'accesorios' => 'bg-warning text-dark',
                                        default      => 'bg-secondary text-white'
                                    };
                                    ?>
                                    <span class="badge <?= $catColor ?>"><?= htmlspecialchars($producto['categoria_nombre']) ?></span>
                                </td>

                                <td>
                                    <?= $producto['en_oferta'] ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?>
                                </td>

                                <td>
                                    <?= $producto['vip'] ? '<span class="badge bg-success text-dark">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?>
                                </td>

                                <td><?= htmlspecialchars($producto['talles']) ?></td>
                                <td><?= htmlspecialchars($producto['colores']) ?></td>

                                <td>
                                    <form method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="txtID" value="<?= $producto['id'] ?>">
                                        <button type="submit" name="accion" value="Seleccionar" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="submit" name="accion" value="Borrar" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro desea eliminar el producto <?= htmlspecialchars($producto['nombre']) ?> (ID: <?= $producto['id'] ?>)?');">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <nav aria-label="Navegación de productos">
            <ul class="pagination justify-content-center">

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

    </div>
</div>

<?php include('estructura/pie.php'); ?>
