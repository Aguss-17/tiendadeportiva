<?php
require_once __DIR__ . '/config/bd.php';
require_once __DIR__ . '/estructura/cabecera.php';

// Obtener búsqueda segura
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$porPagina = 3;
$pagina = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$pagina = max(1, $pagina);
$offset = ($pagina - 1) * $porPagina;

$resultados = [];
$totalResultados = 0;
$mensajeError = '';
$tipoMensaje = ''; // 'success', 'warning', 'error'

if (empty($busqueda)) {
    $mensajeError = 'Por favor, ingresa un término de búsqueda';
    $tipoMensaje = 'warning';
} else {
    try {
        // Contar resultados con búsqueda inteligente
        $sqlCount = "SELECT COUNT(*) as total 
                    FROM productos
                    JOIN categorias ON productos.id_categoria = categorias.id
                    WHERE 
                        productos.nombre LIKE :busqueda 
                        OR productos.descripcion LIKE :busqueda 
                        OR categorias.nombre LIKE :busqueda
                        OR SOUNDEX(productos.nombre) = SOUNDEX(:busqueda_soundex)
                        OR SOUNDEX(productos.descripcion) = SOUNDEX(:busqueda_soundex)
                        OR SOUNDEX(categorias.nombre) = SOUNDEX(:busqueda_soundex)
                        OR productos.nombre LIKE :busqueda_partial
                        OR productos.descripcion LIKE :busqueda_partial";

        $stmtCount = $conexion->prepare($sqlCount);
        $stmtCount->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
        $stmtCount->bindValue(':busqueda_soundex', $busqueda, PDO::PARAM_STR);
        $stmtCount->bindValue(':busqueda_partial', "%" . substr($busqueda, 0, 3) . "%", PDO::PARAM_STR);
        $stmtCount->execute();
        $totalResultados = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        $totalPaginas = ceil($totalResultados / $porPagina);
        if ($pagina > $totalPaginas && $totalPaginas > 0) {
            $pagina = $totalPaginas;
            $offset = ($pagina - 1) * $porPagina;
        }

        // Obtener resultados paginados
        if ($totalResultados > 0) {
            $sql = "SELECT productos.*, categorias.nombre AS nombre_categoria 
                    FROM productos
                    JOIN categorias ON productos.id_categoria = categorias.id
                    WHERE 
                        productos.nombre LIKE :busqueda 
                        OR productos.descripcion LIKE :busqueda 
                        OR categorias.nombre LIKE :busqueda
                        OR SOUNDEX(productos.nombre) = SOUNDEX(:busqueda_soundex)
                        OR SOUNDEX(productos.descripcion) = SOUNDEX(:busqueda_soundex)
                        OR SOUNDEX(categorias.nombre) = SOUNDEX(:busqueda_soundex)
                        OR productos.nombre LIKE :busqueda_partial
                        OR productos.descripcion LIKE :busqueda_partial
                    ORDER BY 
                        CASE 
                            WHEN productos.nombre LIKE :busqueda_exacta THEN 1
                            WHEN productos.descripcion LIKE :busqueda_exacta THEN 2
                            WHEN categorias.nombre LIKE :busqueda_exacta THEN 3
                            WHEN SOUNDEX(productos.nombre) = SOUNDEX(:busqueda_soundex2) THEN 4
                            ELSE 5
                        END,
                        productos.id DESC
                    LIMIT :offset, :limite";

            $stmt = $conexion->prepare($sql);
            $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
            $stmt->bindValue(':busqueda_exacta', "%$busqueda%", PDO::PARAM_STR);
            $stmt->bindValue(':busqueda_soundex', $busqueda, PDO::PARAM_STR);
            $stmt->bindValue(':busqueda_soundex2', $busqueda, PDO::PARAM_STR);
            $stmt->bindValue(':busqueda_partial', "%" . substr($busqueda, 0, 3) . "%", PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        error_log("Error en búsqueda: " . $e->getMessage());
        $mensajeError = 'Lo sentimos, hubo un error en la búsqueda. Por favor, intenta nuevamente.';
        $tipoMensaje = 'error';
        $resultados = [];
    }
}
$totalPaginas = ceil($totalResultados / $porPagina);
?>

<main class="container my-5">
    <h2 class="text-center mb-4">
        <span class="d-block display-5 fw-bold" style="color: #5a3e36;">Resultados de búsqueda</span>
        <?php if (!empty($busqueda)): ?>
            <span class="d-block fs-4" style="color: #7a5c44;">Para "<?= htmlspecialchars($busqueda) ?>"</span>
        <?php endif; ?>
    </h2>

    <?php if (!empty($mensajeError)): ?>
        <div class="alert text-center border-0" style="background-color: <?= $tipoMensaje === 'error' ? '#f8d7da' : ($tipoMensaje === 'warning' ? '#fff3cd' : '#f5e7d9') ?>; color: #5a3e36;">
            <?php if ($tipoMensaje === 'error'): ?>
                <strong>⚠️ Error:</strong>
            <?php elseif ($tipoMensaje === 'warning'): ?>
                <strong>ℹ️ Aviso:</strong>
            <?php else: ?>
                <strong>🔍:</strong>
            <?php endif; ?>
            <?= htmlspecialchars($mensajeError) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($resultados)): ?>
        <div class="row justify-content-center g-4">
            <?php foreach ($resultados as $producto): ?>
                <article class="col-12 col-sm-6 col-md-3 d-flex justify-content-center">
                    <div class="card border-0 text-center shadow-sm w-100 producto-card">
                        <a href="menu/producto.php?id=<?= (int)$producto['id'] ?>" class="position-relative">
                            <img src="img/<?= htmlspecialchars($producto['imagen']) ?>" 
                                class="img-fluid" 
                                alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                                style="width: 100%; height: 420px; object-fit: cover;">
                            <?php if ($producto['en_oferta'] == 1): ?>
                                <span class="badge bg-success position-absolute top-0 start-0 m-2">-40%</span>
                            <?php endif; ?>
                        </a>
                        <div class="card-body p-2">
                            <h6 class="card-title mb-1"><?= htmlspecialchars($producto['nombre']) ?></h6>
                            <div>
                                <?php if ($producto['en_oferta'] == 1): ?>
                                    <span class="text-muted text-decoration-line-through me-2">
                                        $<?= number_format($producto['precio']*1.25,0,',','.') ?>
                                    </span>
                                    <span class="fw-bold text-dark">
                                        $<?= number_format($producto['precio'],0,',','.') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="fw-bold text-dark">
                                        $<?= number_format($producto['precio'],0,',','.') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

<!-- Paginación -->
<?php if ($totalPaginas > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link bg-dark text-white" href="buscar.php?busqueda=<?= urlencode($busqueda) ?>&page=<?= $pagina-1 ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <?php
            $inicio = max(1, $pagina-2);
            $fin = min($totalPaginas, $inicio+4);
            if ($fin-$inicio<4) $inicio = max(1,$fin-4);
            for($i=$inicio;$i<=$fin;$i++): ?>
                <li class="page-item <?= ($i==$pagina)?'active':'' ?>">
                    <a class="page-link bg-dark text-white" href="buscar.php?busqueda=<?= urlencode($busqueda) ?>&page=<?= $i ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($pagina < $totalPaginas): ?>
                <li class="page-item">
                    <a class="page-link bg-dark text-white" href="buscar.php?busqueda=<?= urlencode($busqueda) ?>&page=<?= $pagina+1 ?>">Siguiente</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>


    <?php elseif (empty($mensajeError) && !empty($busqueda)): ?>
        <div class="col-12 text-center py-5">
            <div class="alert py-4 border-0" style="background-color: #f5e7d9; color: #5a3e36;">
                <span class="d-block fs-1 mb-3">🔍</span>
                <h3 class="fw-light">No se encontraron resultados</h3>
                <p class="mb-2">No hay productos que coincidan con "<?= htmlspecialchars($busqueda) ?>"</p>
                <p class="mb-0"><small>Intenta con palabras clave diferentes o menos específicas</small></p>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/estructura/pie.php'; ?>
