<?php
require_once __DIR__ . '/../config/bd.php'; // Ya incia sesión

// Obtener productos VIP desde la base de datos
try {
    $stmt = $conexion->prepare("SELECT id, nombre, descripcion, precio, imagen, link FROM productos_vip ORDER BY id DESC");
    $stmt->execute();
    $productos_vip = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener productos VIP: " . $e->getMessage());
    $productos_vip = [];
}

include('../estructura/cabecera.php');
?>

<?php if (isset($_SESSION['user_id'])): ?>

    <!-- Sección Exclusiva VIP -->
    <div class="container my-4"><br>
        <h2 class="mb-3 text-center text-dark fw-bold">¡Productos Exclusivos VIP!</h2><br>
        <div class="row g-4 justify-content-center">
            <?php if (!empty($productos_vip)): ?>
                <?php foreach ($productos_vip as $producto): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow">
                            <img src="../img/<?= htmlspecialchars($producto['imagen']) ?>"
                                class="card-img-top"
                                alt="<?= htmlspecialchars($producto['nombre']) ?>"
                                style="height: 250px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($producto['nombre']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($producto['descripcion']) ?></p>
                                <p class="fw-bold">$<?= number_format($producto['precio'], 0, ',', '.') ?></p>
                                <div class="text-center mt-auto">
                                    <a href="producto.php?id=<?= (int)$producto['id'] ?>"
                                        class="btn btn-dark btn-sm">
                                        Ver Producto
                                    </a>
                                </div><br>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">No hay productos VIP disponibles en este momento.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sección Fitness: Looks deportivos -->
    <section class="container my-5">
        <div class="text-center mb-5">
            <h2 style="color: #DEC19E; font-weight: 700;">Fitness & Estilo</h2>
            <p class="lead">La importancia de sentirte bien y cómoda con tu look para arrancar en este mundo fitness</p>
        </div>
        <div class="row justify-content-center g-4">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="col-6 col-md-3 d-flex justify-content-center">
                    <div class="card shadow-sm border-0 h-100 rounded" style="max-width: 250px;">
                        <img src="../img/look<?= $i ?>.jpg" class="card-img-top" style="height: 250px; object-fit: cover;" alt="Look <?= $i ?>">
                        <div class="card-body text-center">
                            <h6 class="card-title">Look Deportivo <?= $i ?></h6>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Sección Comer Saludable -->
    <section class="container my-5">
        <div class="text-center mb-5">
            <h2 style="color: #DEC19E; font-weight: 700;">Comer saludable</h2>
            <p class="lead">Recetas, tips y snacks que te ayudan a mantenerte fit</p>
        </div>
        <div class="d-flex flex-column flex-md-row align-items-center gap-4 mb-5">
            <div class="flex-shrink-0" style="max-width: 250px;">
                <img src="../img/comidasaludable.jpg" class="w-100 rounded" alt="Comida saludable" style="height: auto;">
            </div>
            <div class="flex-grow-1">
                <p class="fs-5 mb-3">Aquí encontrarás recetas fáciles, snacks rápidos y consejos para mantener una alimentación balanceada sin aburrirte. Ideal para tu vida fitness y para sentirte lleno de energía.</p>
                <ul class="mb-0">
                    <li>Recetas rápidas y deliciosas</li>
                    <li>Tips de nutrición y snacks saludables</li>
                    <li>Ideas para organizar tus comidas</li>
                </ul>
            </div>
        </div>

        <!-- Recetas destacadas -->
        <div class="mb-5">
            <h4 style="color: #DEC19E; font-weight: 600;" class="mb-4 text-center">Recetas destacadas</h4>
            <div class="row justify-content-center g-4">
                <?php
                $recetas = [
                    ['img' => 'smoothiei.jpg', 'titulo' => 'Smoothie Energético', 'desc' => 'Aprende a preparar un smoothie lleno de vitaminas en 5 minutos.'],
                    ['img' => 'ensalada.jpg', 'titulo' => 'Ensalada Proteica', 'desc' => 'Una ensalada rápida y deliciosa para tus comidas principales.'],
                    ['img' => 'frutas.jpg', 'titulo' => 'Snack de Frutas', 'desc' => 'Perfecto para media tarde, rápido y saludable.']
                ];
                foreach ($recetas as $r): ?>
                    <div class="col-md-4 d-flex justify-content-center">
                        <div class="card shadow-sm border-0 h-100 rounded" style="max-width: 350px; transition: transform 0.3s;">
                            <img src="../img/<?= $r['img'] ?>" class="card-img-top" style="height: 250px; object-fit: cover;" alt="<?= $r['titulo'] ?>">
                            <div class="card-body text-center">
                                <h5 class="card-title fw-bold" style="color: #DEC19E;"><?= $r['titulo'] ?></h5>
                                <p class="card-text"><?= $r['desc'] ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Videos -->
        <div class="mb-5">
            <h4 style="color: #DEC19E; font-weight: 600;" class="mb-4 text-center">Videos</h4>
            <div class="row justify-content-center g-4">
                <?php
                $videos = [
                    ['url' => 'LddELpU5_F0', 'titulo' => 'Smoothie Energético'],
                    ['url' => 'BhuO4vn3KIw', 'titulo' => 'Ensalada Proteica'],
                    ['url' => '7YoowMpO6tg', 'titulo' => 'Snack de Frutas']
                ];
                foreach ($videos as $v): ?>
                    <div class="col-md-4 d-flex flex-column align-items-center">
                        <div class="ratio ratio-16x9 shadow-sm rounded mb-2">
                            <iframe src="https://www.youtube.com/embed/<?= $v['url'] ?>" frameborder="0" allowfullscreen></iframe>
                        </div>
                        <p class="text-center"><?= $v['titulo'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tips rápidos -->
        <div class="mb-5">
            <h4 style="color: #DEC19E; font-weight: 600;" class="mb-4 text-center">Tips rápidos</h4>
            <div class="row justify-content-center g-3">
                <?php
                $tips = [
                    ['titulo' => 'Hidratación', 'desc' => 'Bebe agua antes de cada comida'],
                    ['titulo' => 'Proteínas', 'desc' => 'Incluye proteínas en cada comida'],
                    ['titulo' => 'Frutas y verduras', 'desc' => 'Al menos 5 porciones al día'],
                    ['titulo' => 'Evita azúcares', 'desc' => 'Reduce azúcares refinados']
                ];
                foreach ($tips as $t): ?>
                    <div class="col-md-3">
                        <div class="card shadow-sm text-center p-3 rounded">
                            <h6><?= $t['titulo'] ?></h6>
                            <p><?= $t['desc'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sección Ejercicio en Casa -->
        <div class="mb-5">
            <div class="text-center mb-5">
                <h2 style="color: #DEC19E; font-weight: 700;">Ejercicio en Casa</h2>
                <p class="lead">Videos, tips y suplementos para entrenar en casa de forma efectiva</p>
            </div>

            <h4 style="color: #DEC19E; font-weight: 600;" class="mb-4 text-center">Rutinas de Youtubers</h4>
            <div class="row justify-content-center g-4">
                <?php
                $rutinas = [
                    ['url' => 'fjQHymlBex8', 'titulo' => 'Rutina Full Body'],
                    ['url' => 'e7CmBFvy_b8', 'titulo' => 'Cardio Express'],
                    ['url' => 'oUpHEpXg1Ik', 'titulo' => 'Tonificación Core']
                ];
                foreach ($rutinas as $r): ?>
                    <div class="col-md-4 d-flex flex-column align-items-center">
                        <div class="ratio ratio-16x9 shadow-sm rounded mb-2">
                            <iframe src="https://www.youtube.com/embed/<?= $r['url'] ?>" frameborder="0" allowfullscreen></iframe>
                        </div>
                        <p class="text-center"><?= $r['titulo'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tips para entrenar -->
        <div class="mb-5">
            <h4 style="color: #DEC19E; font-weight: 600;" class="mb-4 text-center">Tips para entrenar</h4>
            <div class="row justify-content-center g-3">
                <?php
                $tipsEntreno = [
                    ['titulo' => 'Calentamiento', 'desc' => 'Dedica 5-10 minutos antes de entrenar para evitar lesiones.'],
                    ['titulo' => 'Hidratación', 'desc' => 'Bebe agua antes, durante y después de la rutina.'],
                    ['titulo' => 'Postura correcta', 'desc' => 'Mantén la técnica correcta en cada ejercicio.'],
                    ['titulo' => 'Estiramiento', 'desc' => 'Estira los músculos al finalizar para mejorar la recuperación.']
                ];
                foreach ($tipsEntreno as $t): ?>
                    <div class="col-md-3">
                        <div class="card shadow-sm text-center p-3 rounded">
                            <h6><?= $t['titulo'] ?></h6>
                            <p><?= $t['desc'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Suplementos -->
        <div class="mb-5">
            <h4 style="color: #DEC19E; font-weight: 600;" class="mb-4 text-center">Suplementos</h4>
            <div class="row justify-content-center g-4">
                <?php
                $suplementos = [
                    ['img' => 'preentreno.jpg', 'titulo' => 'Pre-entreno', 'desc' => 'Consume antes de entrenar para mejorar energía y concentración.'],
                    ['img' => 'proteina.jpg', 'titulo' => 'Proteína', 'desc' => 'Ayuda a la recuperación y crecimiento muscular después del entrenamiento.'],
                    ['img' => 'creatina.jpg', 'titulo' => 'Creatina', 'desc' => 'Mejora fuerza y resistencia durante entrenamientos intensos.']
                ];
                foreach ($suplementos as $s): ?>
                    <div class="col-md-4 text-center">
                        <img src="../img/<?= $s['img'] ?>" class="w-100 rounded shadow-sm mb-2" style="height: 200px; object-fit: cover;" alt="<?= $s['titulo'] ?>">
                        <h6><?= $s['titulo'] ?></h6>
                        <p><?= $s['desc'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </section>

<?php else: ?>
    <div class="container my-5">
        <div class="alert alert-warning text-center" role="alert">
            Debes iniciar sesión para ver todo el contenido.
            <a href="<?= $url; ?>/login.php" class="alert-link">Iniciar Sesión</a>
        </div>
    </div>
<?php endif; ?>

<?php include('../estructura/pie.php'); ?>