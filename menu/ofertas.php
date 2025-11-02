<?php 
require_once __DIR__ . '/../config/bd.php'; 
require_once __DIR__ . '/../estructura/cabecera.php'; 

// Mapeo de categorías según tu tabla categories
$categorias = [
    'all' => 'Todos', 
    '2' => 'Mujer',      // id 2 = mujer
    '1' => 'Hombre',     // id 1 = hombre  
    '3' => 'Niños',      // id 3 = niños
    '4' => 'Accesorios'  // id 4 = accessories
];

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'all';
if (!array_key_exists($categoria, $categorias)) {
    $categoria = 'all';
}

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);

$productos_por_pagina = 8;
$inicio = ($pagina - 1) * $productos_por_pagina;

if ($categoria === 'all') {
    $sql = "SELECT * FROM productos WHERE en_oferta = 1 ORDER BY id DESC LIMIT :inicio, :cantidad";
    $sentenciaSQL = $conexion->prepare($sql);
} else {
    // Usar id_categoria en lugar de categoria
    $sql = "SELECT * FROM productos WHERE en_oferta = 1 AND id_categoria = :categoria ORDER BY id DESC LIMIT :inicio, :cantidad";
    $sentenciaSQL = $conexion->prepare($sql);
    $sentenciaSQL->bindParam(':categoria', $categoria);
}

$sentenciaSQL->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$sentenciaSQL->bindValue(':cantidad', $productos_por_pagina, PDO::PARAM_INT);
$sentenciaSQL->execute();
$productos = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);

if ($categoria === 'all') {
    $totalProductos = $conexion->query("SELECT COUNT(*) FROM productos WHERE en_oferta = 1")->fetchColumn();
} else {
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE en_oferta = 1 AND id_categoria = :categoria");
    $stmt->bindParam(':categoria', $categoria);
    $stmt->execute();
    $totalProductos = $stmt->fetchColumn();
}

$totalPaginas = ceil($totalProductos / $productos_por_pagina);
?>
<br>

<!-- Mensaje de atención -->
<div class="container mb-4">
    <div class="alert alert-warning text-center py-3" role="alert">
        <h4 class="mb-2">🔥 ¡Ofertas Especiales! 🔥</h4>
        <p class="mb-0">Aprovecha nuestros productos con <strong>25% de descuento</strong>. ¡Tiempo limitado!</p>
    </div>
</div>

<!-- Filtros por categoría -->
<div class="text-center mb-4">
    <?php
    foreach ($categorias as $key => $nombre) {
        $active = ($categoria === $key) ? 'active' : '';
        echo "<a href='ofertas.php?categoria=$key' class='btn btn-outline-dark btn-sm $active'>$nombre</a> ";
    }
    ?>
</div>

<!-- Productos -->
<section class="container">
    <div class="row justify-content-center g-4">
        <?php if (!empty($productos)): ?>
            <?php foreach ($productos as $producto): ?>
            <div class="col-12 col-sm-6 col-md-3 d-flex justify-content-center offer-card">
                <div class="card border-0 text-center shadow-sm w-100" style="max-width: 280px;">
                    <!-- Imagen clickeable -->
                    <a href="producto.php?id=<?= htmlspecialchars($producto['id']); ?>">
                        <img src="../img/<?= htmlspecialchars($producto['imagen']); ?>" class="img-fluid" alt="<?= htmlspecialchars($producto['nombre']); ?>" style="width: 100%; height: 420px; object-fit: cover;">
                        <span class="badge bg-success position-absolute top-0 start-0 m-2">-25%</span>
                    </a>

                    <!-- Info debajo de la imagen -->
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1"><?= htmlspecialchars($producto['nombre']); ?></h6>
                        <div>
                            <span class="text-muted text-decoration-line-through me-2">$<?= number_format($producto['precio']*1.25,0,',','.'); ?></span>
                            <span class="fw-bold text-dark">$<?= number_format($producto['precio'],0,',','.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="alert py-4" style="background-color: #f5e7d9; color: #5a3e36;">
                    <h3 class="fw-light">No hay productos en oferta en <?= $categorias[$categoria] ?? 'esta categoría' ?></h3>
                    <p class="mb-0">Pronto agregaremos nuevas promociones</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Paginación -->
<?php if ($totalPaginas > 1): ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <?php for ($i=1; $i <= $totalPaginas; $i++): ?>
            <li class="page-item <?= ($i==$pagina) ? 'active' : '' ?>">
                <a class="page-link bg-dark text-white" href="ofertas.php?categoria=<?= $categoria ?>&pagina=<?= $i ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<br>

</main>

<?php require_once __DIR__ . '/../estructura/pie.php'; ?>