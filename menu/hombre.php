<?php 
require_once __DIR__ . '/../config/bd.php'; 
require_once __DIR__ . '/../estructura/cabecera.php'; 
// Paginación
$porPagina = 12; 
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $porPagina;

// Ordenamiento
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'id_desc';
switch($orden){
    case 'precio_asc': $ordenSQL = 'precio ASC'; break;
    case 'precio_desc': $ordenSQL = 'precio DESC'; break;
    case 'nombre_asc': $ordenSQL = 'nombre ASC'; break;
    case 'nombre_desc': $ordenSQL = 'nombre DESC'; break;
    default: $ordenSQL = 'id DESC';
}

try {
    $idCategoriaHombre = 1; // HOMBRE

    $sentenciaSQL = $conexion->prepare("SELECT * FROM productos WHERE id_categoria = :id_categoria ORDER BY $ordenSQL LIMIT :inicio, :porPagina");
    $sentenciaSQL->bindValue(':id_categoria', $idCategoriaHombre, PDO::PARAM_INT);
    $sentenciaSQL->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $sentenciaSQL->bindValue(':porPagina', $porPagina, PDO::PARAM_INT);
    $sentenciaSQL->execute();
    $productos = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);

    $totalSQL = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE id_categoria = :id_categoria");
    $totalSQL->bindValue(':id_categoria', $idCategoriaHombre, PDO::PARAM_INT);
    $totalSQL->execute();
    $totalProductos = $totalSQL->fetchColumn();
    $totalPaginas = ceil($totalProductos / $porPagina);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error al cargar productos: " . htmlspecialchars($e->getMessage()) . "</div>";
    $productos = [];
}
?>

<section style="background-color: #dec19e; padding: 20px 0;">

    <!-- Banner -->
    <div class="text-center mb-2">
        <img src="../img/bannerhombre.jpg" class="img-fluid" alt="banner hombre">
    </div>

    <!-- Ordenamiento -->
    <div class="container mb-3">
        <form method="get" class="d-flex justify-content-end">
            <select name="orden" class="form-select w-auto me-2" onchange="this.form.submit()">
                <option value="id_desc" <?= $orden == 'id_desc' ? 'selected' : '' ?>>Más recientes</option>
                <option value="precio_asc" <?= $orden == 'precio_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
                <option value="precio_desc" <?= $orden == 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
                <option value="nombre_asc" <?= $orden == 'nombre_asc' ? 'selected' : '' ?>>Nombre A-Z</option>
                <option value="nombre_desc" <?= $orden == 'nombre_desc' ? 'selected' : '' ?>>Nombre Z-A</option>
            </select>
        </form>
    </div>

    <!-- Productos de hombre -->
    <section class="container">
        <div class="row justify-content-center g-4">

            <h2 class="text-center mb-1">
                <span class="d-block display-5 fw-bold" style="color: #5a3e36;">MODA DEPORTIVA</span>
                <span class="d-block fs-4" style="color: #7a5c44;">PARA HOMBRE</span>
            </h2>

            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                <div class="col-12 col-sm-6 col-md-3 d-flex justify-content-center">
                    <div class="card border-0 text-center shadow-sm w-100" style="max-width: 280px;">
                        <a href="producto.php?id=<?= htmlspecialchars($producto['id']); ?>">
                            <img src="../img/<?= htmlspecialchars($producto['imagen']); ?>" class="img-fluid" alt="<?= htmlspecialchars($producto['nombre']); ?>" style="width: 100%; height: 420px; object-fit: cover;">
                            <?php if ($producto['en_oferta'] == 1): ?>
                                <span class="badge bg-success position-absolute top-0 start-0 m-2">-40%</span>
                            <?php endif; ?>
                        </a>

                        <div class="card-body p-2">
                            <h6 class="card-title mb-1"><?= htmlspecialchars($producto['nombre']); ?></h6>

                            <!-- Precio -->
                            <div>
                                <?php if ($producto['en_oferta'] == 1): ?>
                                    <span class="text-decoration-line-through text-muted me-2">$<?= number_format($producto['precio']*1.25,0,',','.'); ?></span>
                                    <span class="fw-bold text-dark">$<?= number_format($producto['precio'],0,',','.'); ?></span>
                                <?php else: ?>
                                    <span class="fw-bold text-dark">$<?= number_format($producto['precio'],0,',','.'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="alert py-4" style="background-color: #f5e7d9; color: #5a3e36;">
                        <h3 class="fw-light">No hay productos disponibles</h3>
                        <p class="mb-0">Pronto tendremos nuevas colecciones</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>

<!-- Paginación -->
<?php if ($totalPaginas > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= ($i == $pagina) ? 'active' : ''; ?>">
                    <a class="page-link bg-dark text-white" href="?pagina=<?= $i; ?>&orden=<?= urlencode($orden); ?>">
                        <?= $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

    </section>
</section>

<?php require_once __DIR__ . '/../estructura/pie.php'; ?>
