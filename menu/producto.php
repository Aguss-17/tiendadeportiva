<?php
require_once __DIR__ . '/../config/bd.php'; // Ya incluye cache.php

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ../index.php');
    exit;
}

// Consulta del producto CON CACHÉ (versión corregida y optimizada)
$producto = $cache->cachedQuery(
    $conexion,
    "SELECT * FROM productos WHERE id = :id",
    [':id' => $id],
    3600
);

if (!empty($producto)) {
    $producto = $producto[0];
} else {
    // Si no hay resultados en caché ni en BD, redirigir
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../estructura/cabecera.php';

if (!$producto) {
    echo '<div class="container my-5"><div class="alert alert-warning text-center">Producto no encontrado.</div></div>';
    require_once __DIR__ . '/../estructura/pie.php';
    exit;
}
?>

<main class="container my-5">
    <div class="row">
        <div class="col-md-6 text-center position-relative">
            <?php if ($producto['en_oferta'] == 1): ?>
                <span class="badge bg-success position-absolute top-0 start-0 fs-6 m-2">OFERTA</span>
            <?php endif; ?>

            <img src="../img/<?= htmlspecialchars($producto['imagen']) ?>" class="img-fluid mb-2" id="imagenPrincipal" alt="<?= htmlspecialchars($producto['nombre']) ?>">
            <?php if (!empty($producto['imagen_hover'])): ?>
                <img src="../img/<?= htmlspecialchars($producto['imagen_hover']) ?>" class="img-fluid" id="imagenHover" style="display:none;" alt="<?= htmlspecialchars($producto['nombre']) ?>">
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <h2><?= htmlspecialchars($producto['nombre']) ?></h2>
            <p class="fs-6"><?= htmlspecialchars($producto['descripcion']) ?></p>

            <?php if ($producto['en_oferta'] == 1): ?>
                <div class="mb-3">
                    <span class="text-muted text-decoration-line-through me-2 fs-5">
                        $<?= number_format($producto['precio'] * 1.25, 0, ',', '.') ?>
                    </span>
                    <span class="text-success fw-bold fs-3">
                        $<?= number_format($producto['precio'], 0, ',', '.') ?>
                    </span>
                </div>
            <?php else: ?>
                <p class="fs-4"><strong>Precio:</strong> $<?= number_format($producto['precio'], 0, ',', '.') ?></p>
            <?php endif; ?>

            <!-- Talles -->
            <div class="mb-3">
                <p class="fs-6"><strong>Talles:</strong></p>
                <?php foreach (explode(",", $producto['talles']) as $talle): 
                    $talle = trim($talle);
                    if ($talle === '') continue;
                    $idTalle = htmlspecialchars($talle);
                ?>
                    <input type="radio" class="btn-check" name="talle" id="talle-<?= $idTalle ?>" autocomplete="off">
                    <label class="btn btn-outline-secondary btn-sm mb-1" for="talle-<?= $idTalle ?>"><?= $idTalle ?></label>
                <?php endforeach; ?>
            </div>

            <!-- Colores -->
            <div class="mb-3">
                <p class="fs-6"><strong>Colores:</strong></p>
                <?php foreach (explode(",", $producto['colores']) as $color):
                    $color = trim($color);
                    if ($color === '') continue;
                    $idColor = htmlspecialchars($color);
                ?>
                    <input type="radio" class="btn-check" name="color" id="color-<?= $idColor ?>" autocomplete="off">
                    <label class="btn btn-outline-secondary btn-sm mb-1" for="color-<?= $idColor ?>"><?= $idColor ?></label>
                <?php endforeach; ?>
            </div>

            <button class="btn btn-dark w-50 d-block mx-auto mb-3" onclick="agregarAlCarrito(<?= (int)$producto['id'] ?>)">
                Agregar al carrito
            </button>

            <div class="text-center mt-2">
                <p class="mb-2 fs-6">¿No sabes tu talle?</p>
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#tablaTalles">
                    <i class="bi bi-rulers me-1"></i> Ver tabla
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de talles -->
    <div class="modal fade" id="tablaTalles" tabindex="-1" aria-labelledby="tablaTallesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tablaTallesLabel">Tabla de Talles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../img/talles.jpg" alt="Tabla de Talles" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <!-- Productos relacionados -->
    <section class="mt-5 pt-4 border-top">
        <h3 class="h4 titulos text-center mb-4">También te puede interesar</h3>
        <div class="row g-3 justify-content-center">
            <?php
            try {
                $productosRelacionados = $cache->cachedQuery(
                    $conexion,
                    "SELECT p.*, c.nombre as categoria_nombre 
                    FROM productos p 
                    INNER JOIN categorias c ON p.id_categoria = c.id 
                    WHERE p.id_categoria = :id_categoria 
                    AND p.id != :id_actual 
                    ORDER BY p.en_oferta DESC, p.precio ASC 
                    LIMIT 4",
                    [
                        ':id_categoria' => $producto['id_categoria'],
                        ':id_actual' => $id
                    ],
                    1800
                );

                if (empty($productosRelacionados)) {
                    $productosRelacionados = $cache->cachedQuery(
                        $conexion,
                        "SELECT p.*, c.nombre as categoria_nombre 
                        FROM productos p 
                        INNER JOIN categorias c ON p.id_categoria = c.id 
                        WHERE p.id != :id_actual 
                        ORDER BY RAND() 
                        LIMIT 4",
                        [':id_actual' => $id],
                        1800
                    );
                }

                foreach ($productosRelacionados as $relacionado) {
                    echo '<div class="col-6 col-sm-4 col-lg-3">';
                    echo '<div class="card h-100 border-0 shadow-sm">';
                    echo '<div class="position-relative">';
                    echo '<a href="producto.php?id=' . $relacionado['id'] . '">';
                    if ($relacionado['en_oferta'] == 1) {
                        echo '<span class="badge bg-danger position-absolute top-0 start-0 m-1 small">OFERTA</span>';
                    }
                    echo '<img src="../img/' . htmlspecialchars($relacionado['imagen']) . '" class="card-img-top" alt="' . htmlspecialchars($relacionado['nombre']) . '" style="height: 250px; object-fit: cover;" onerror="this.src=\'../img/placeholder-producto.jpg\'">';
                    echo '</a></div>';
                    echo '<div class="card-body p-2 d-flex flex-column">';
                    echo '<h6 class="card-title fw-bold mb-1 small">' . htmlspecialchars(mb_strimwidth($relacionado['nombre'], 0, 40, "...")) . '</h6>';
                    echo '<div class="mt-auto">';
                    if ($relacionado['en_oferta'] == 1) {
                        echo '<div class="d-flex align-items-center justify-content-between">';
                        echo '<span class="text-muted text-decoration-line-through small">$' . number_format($relacionado['precio'] * 1.25, 0, ',', '.') . '</span>';
                        echo '<span class="text-success fw-bold">$' . number_format($relacionado['precio'], 0, ',', '.') . '</span>';
                        echo '</div>';
                    } else {
                        echo '<span class="fw-bold text-dark">$' . number_format($relacionado['precio'], 0, ',', '.') . '</span>';
                    }
                    echo '</div>';
                    echo '<a href="producto.php?id=' . $relacionado['id'] . '" class="btn btn-outline-dark btn-sm w-100 mt-2">Ver</a>';
                    echo '</div></div></div>';
                }
            } catch (Exception $e) {
                echo '<div class="col-12 text-center py-3"><div class="alert alert-warning small">Error al cargar productos relacionados.</div></div>';
            }
            ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../estructura/pie.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const imagenPrincipal = document.getElementById('imagenPrincipal');
    const imagenHover = document.getElementById('imagenHover');

    if (imagenHover) {
        imagenPrincipal.addEventListener('mouseover', () => {
            imagenPrincipal.style.display = 'none';
            imagenHover.style.display = 'block';
        });
        imagenHover.addEventListener('mouseout', () => {
            imagenHover.style.display = 'none';
            imagenPrincipal.style.display = 'block';
        });
    }
});

function agregarAlCarrito(idProducto) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('Debes iniciar sesión para agregar productos al carrito');
        window.location.href = '../login.php?redirect=producto&id=' + idProducto;
        return;
    <?php endif; ?>

    const talleSel = document.querySelector('input[name="talle"]:checked');
    const colorSel = document.querySelector('input[name="color"]:checked');
    const talle = talleSel ? talleSel.id.replace('talle-', '') : '';
    const color = colorSel ? colorSel.id.replace('color-', '') : '';
    const cantidad = 1;

    const form = new URLSearchParams();
    form.append('accion', 'add');
    form.append('producto_id', idProducto);
    form.append('talle', talle);
    form.append('color', color);
    form.append('cantidad', cantidad);

    fetch('../carrito/carrito.php', {
        method: 'POST',
        body: form
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje || 'Producto agregado al carrito');
        if (window.actualizarContadorCarrito) {
            actualizarContadorCarrito();
        }
    })
    .catch(() => alert('Error al agregar al carrito'));
}
</script>
