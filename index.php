<?php
session_start();

// Manejo de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Incluir archivos con manejo de errores
try {
    if (!file_exists('estructura/cabecera.php')) {
        throw new Exception('No se pudo encontrar el archivo de cabecera');
    }
    include('estructura/cabecera.php');

    if (!file_exists('config/bd.php')) {
        throw new Exception('No se pudo encontrar el archivo de configuración de base de datos');
    }
    include('config/bd.php');

    register_shutdown_function(function () use ($cache) {
        // Limpiar caché antiguo periódicamente (1% de probabilidad)
        if (rand(1, 100) === 1) {
            $cache->clearOldCache(86400); // Limpiar caché de más de 24 horas
        }
    });

    // Verificar conexión a base de datos
    if (!isset($conexion) || !$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
} catch (Exception $e) {
    error_log('Error en index.php: ' . $e->getMessage());
    die('<div class="alert alert-danger m-4" role="alert">Error al cargar la página. Por favor, intente más tarde.</div>');
}

// Mensaje de bienvenida
if (isset($_SESSION['welcome_message'])) {
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert" style="z-index: 1000;">';
    echo htmlspecialchars($_SESSION['welcome_message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar mensaje"></button>';
    echo '</div>';
    echo '</div>';
    unset($_SESSION['welcome_message']);
}
?>

<main>
    <!-- Carrusel principal -->
    <section aria-labelledby="carousel-heading">
        <h2 id="carousel-heading" class="visually-hidden">Ofertas principales y promociones de Aura Sport</h2>
        <div id="myCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#myCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#myCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            </div>

            <div class="carousel-inner">
                <div class="carousel-item active position-relative">
                    <img src="img/banner.jpg" class="d-block w-100" alt="Mujer sonriente usando conjunto deportivo azul Aura Sport en un gimnasio">
                    <div class="boton-carrusel">
                        <a href="menu/nosotros.php" class="btn btn-dark">Descubrí nuestra historia...</a>
                    </div>
                </div>

                <div class="carousel-item position-relative">
                    <img src="img/banner2.jpg" class="d-block w-100" alt="Hombre y mujer haciendo ejercicio con ropa deportiva Aura Sport en un parque">
                    <div class="boton-carrusel">
                        <a href="menu/exclusivo.php" class="btn btn-dark">¡Accedé ahora!</a>
                    </div>
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>

            <button class="carousel-control-next" type="button" data-bs-target="#myCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    </section>

    <!-- Banner envío -->
    <div class="text-center my-4">
        <img src="img/banner3.jpg" class="banner-img" alt="Envío gratis en compras superiores a $100.000 - Camión de reparto Aura Sport">
    </div>

    <!-- Productos destacados por categoría -->
    <section class="container my-1" aria-labelledby="productos-destacados-heading">
        <h2 id="productos-destacados-heading" class="h3 titulos text-center mb-4">¡Productos destacados!</h2>
        <div class="row justify-content-center">
            <?php
            try {
                $categoriasIds = [1, 2, 3, 4];
                $placeholders = implode(',', array_fill(0, count($categoriasIds), '?'));
                $sql = "SELECT p.*, c.nombre as categoria_nombre 
                        FROM productos p 
                        INNER JOIN categorias c ON p.id_categoria = c.id 
                        WHERE p.id IN (
                            SELECT MAX(id) 
                            FROM productos 
                            WHERE id_categoria IN ($placeholders) 
                            GROUP BY id_categoria
                        )";

                // USAR CACHÉ
                $productosDestacados = $cache->cachedQuery($conexion, $sql, $categoriasIds, 1800);

                $productosPorCategoria = [];
                foreach ($productosDestacados as $producto) {
                    $productosPorCategoria[$producto['id_categoria']] = $producto;
                }

                $nombresCategorias = [1 => 'mujer', 2 => 'hombre', 3 => 'niños', 4 => 'accesorios'];

                if (empty($productosPorCategoria)) {
                    echo '<div class="col-12 text-center py-5">';
                    echo '<div class="alert alert-info" role="status">';
                    echo '<p>No hay productos destacados en este momento. Visita nuestras categorías para ver todos los productos disponibles.</p>';
                    foreach ($nombresCategorias as $id => $cat) {
                        echo "<a href='menu/$cat.php' class='btn btn-outline-primary me-2'>" . ucfirst($cat) . "</a>";
                    }
                    echo '</div></div>';
                } else {
                    foreach ($nombresCategorias as $idCategoria => $catNombre) {
                        if (isset($productosPorCategoria[$idCategoria])) {
                            $producto = $productosPorCategoria[$idCategoria];
                            echo '<article class="col-md-4 col-lg-3 d-flex justify-content-center mb-4">';
                            echo '<div class="card shadow-sm" style="width: 100%; max-width: 300px;">';
                            echo '<img src="img/' . htmlspecialchars($producto['imagen']) . '" class="card-img-top" alt="' . htmlspecialchars($producto['nombre']) . ' - Producto de ' . $catNombre . ' de Aura Sport" style="height: 200px; object-fit: cover;" onerror="this.src=\'img/placeholder-producto.jpg\'; this.alt=\'Imagen no disponible\'">';
                            echo '<div class="card-body text-center">';
                            echo '<h3 class="card-title fw-bold mb-3 h5">' . htmlspecialchars($producto['nombre']) . '</h3>';
                            echo '<p class="mb-3">' . (!empty($producto['descripcion']) ? htmlspecialchars($producto['descripcion']) : 'Sin descripción disponible') . '</p>';
                            echo '<div class="mb-3">';
                            echo '<span class="text-danger text-decoration-line-through me-2">$' . number_format($producto['precio'] * 1.25, 0, ',', '.') . '</span>';
                            echo '<span class="text-success fw-bold fs-5">$' . number_format($producto['precio'], 0, ',', '.') . '</span>';
                            echo '</div>';
                            echo '<a href="menu/producto.php?id=' . $producto['id'] . '" class="btn btn-dark w-70 mx-auto mb-3 fw-bold">¡Comprar Ahora!</a>';
                            echo '</div></div></article>';
                        } else {
                            echo '<div class="col-md-4 col-lg-3 d-flex justify-content-center mb-4">';
                            echo '<div class="card text-center py-4">';
                            echo '<div class="card-body">';
                            echo '<p class="text-muted">Próximamente<br>más productos de ' . $catNombre . '</p>';
                            echo "<a href='menu/$catNombre.php' class='btn btn-outline-dark mt-2'>Ver categoría</a>";
                            echo '</div></div></div>';
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Error al cargar productos destacados: ' . $e->getMessage());
                echo '<div class="col-12 text-center py-5">';
                echo '<div class="alert alert-warning" role="alert">';
                echo '<p>Error temporal al cargar los productos. Por favor, intenta nuevamente.</p>';
                echo '<button onclick="window.location.reload()" class="btn btn-outline-secondary">Reintentar</button>';
                echo '</div></div>';
            }
            ?>
        </div>
    </section>

    <!-- Sección Exclusivo VIP -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <section id="exclusivo" class="container my-5" aria-labelledby="exclusivo-vip-heading">
            <h2 id="exclusivo-vip-heading" class="h3 titulos text-center mb-4" style="color:#5a3e36;">Sección Exclusivo VIP</h2>
            <div class="row g-4 justify-content-center">
                <?php
                try {
                    // USAR CACHÉ
                    $productos_vip = $cache->cachedQuery(
                        $conexion,
                        "SELECT * FROM productos_vip ORDER BY id DESC",
                        [],
                        1800
                    );

                    if (!empty($productos_vip)) {
                        foreach ($productos_vip as $producto) {
                            echo '<article class="col-md-3 d-flex justify-content-center">';
                            echo '<div class="card shadow h-100" style="width: 18rem;">';
                            echo '<img src="./img/' . htmlspecialchars($producto['imagen']) . '" class="card-img-top" alt="' . htmlspecialchars($producto['nombre']) . ' - Producto exclusivo Aura Sport" style="height: 250px; object-fit: cover;" onerror="this.src=\'img/placeholder-producto.jpg\'; this.alt=\'Imagen no disponible\'">';
                            echo '<div class="card-body d-flex flex-column text-center">';
                            echo '<h3 class="card-title h5">' . htmlspecialchars($producto['nombre']) . '</h3>';
                            echo '<p class="card-text">' . htmlspecialchars($producto['descripcion']) . '</p>';
                            echo '<a href="menu/producto.php?id=' . $producto['id'] . '" class="btn btn-dark mx-auto mt-auto mb-2" style="font-size:0.9rem; padding: 0.4rem 0.8rem;">¡Conocé más!</a>';
                            echo '</div></div></article>';
                        }
                    } else {
                        echo '<div class="col-12 text-center">';
                        echo '<div class="alert alert-info py-4" role="status">';
                        echo '<p>No hay productos VIP disponibles en este momento.</p>';
                        echo '<p>¡Vuelve pronto para descubrir nuestras novedades exclusivas!</p>';
                        echo '</div></div>';
                    }
                } catch (Exception $e) {
                    error_log('Error al cargar productos VIP: ' . $e->getMessage());
                    echo '<div class="col-12 text-center">';
                    echo '<div class="alert alert-warning py-4" role="alert">';
                    echo '<p>Error al cargar los productos exclusivos. Por favor, intenta más tarde.</p>';
                    echo '</div></div>';
                }
                ?>
            </div>
        </section>
    <?php else: ?>
        <section class="container my-5">
            <div class="alert alert-warning text-center" role="alert">
                <p>Debes iniciar sesión para ver todo el contenido.</p>
                <a href="<?php echo $url; ?>/login.php" class="btn btn-dark">Iniciar Sesión</a>
            </div>
        </section>
    <?php endif; ?>

    <!-- Banner descuento -->
    <div class="text-center my-4 mb-0">
        <img src="img/bannerdescuento.jpg" class="banner-img" alt="Hasta 50% de descuento en productos seleccionados - Ofertas por tiempo limitado" onerror="this.src='img/placeholder-banner.jpg'; this.alt='Ofertas especiales'">
    </div>

    <!-- Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1100;">
        <div id="toastAuraSport" class="toast align-items-center" role="alert" aria-live="polite" aria-atomic="true">
            <div class="toast-header" style="background-color: #dec19e; border-color: #c0a586;">
                <strong class="me-auto">Aura Sport</strong>
                <small class="text-body-secondary">Ahora</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar notificación"></button>
            </div>
            <div class="toast-body" style="background-color: #f0e4d6;">
                Aura Sport es una tienda de indumentaria deportiva comprometida con ofrecer productos de calidad, estilo y comodidad para toda la familia.<br>
                <a href="informacion/info_empresa.php" class="btn me-3 small mt-2" style="background-color: #dec19e; border: none; color: #000; padding: 0.25rem 0.5rem;">Ver información</a>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const toastElement = document.getElementById('toastAuraSport');
            if (toastElement) {
                const toast = new bootstrap.Toast(toastElement, {
                    delay: 600000
                });
                if (!localStorage.getItem('toastDismissed')) toast.show();
                toastElement.addEventListener('hidden.bs.toast', function() {
                    localStorage.setItem('toastDismissed', 'true');
                });
            }
        });
    </script>

</main>

<?php
if (isset($conexion)) $conexion = null;
include('estructura/pie.php');
?>