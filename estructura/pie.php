<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$root = $protocol . "://" . $_SERVER['HTTP_HOST'];

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Detectar carpeta real del proyecto (si está en subcarpeta de AlwaysData)
$pathParts = explode('/', trim($scriptDir, '/'));
$baseFolder = $pathParts[0] ?? '';

if ($baseFolder && $baseFolder !== 'menu' && $baseFolder !== 'informacion' && $baseFolder !== 'carrito' && $baseFolder !== 'administrador') {
    // Proyecto dentro de una carpeta (ej: /aura-sport)
    $basePath = '/' . $baseFolder;
} else {
    // Proyecto en la raíz
    $basePath = '';
}

$url = rtrim($root . $basePath, '/');

?>

<footer class="bg-light py-5 mt-n1">

    <section class="container-fluid px-5">
        <h2 class="visually-hidden">Informacion de Aura Sport</h2>
        <div class="row">

            <article class="col-md-3 mb-4">
                <h4 class="fw-bold">Nosotros</h4>
                <ul class="list-unstyled">
                    <li><a href="<?php echo $url; ?>/informacion/info_empresa.php" class="text-decoration-none text-dark">Información de la empresa</a></li>
                    <li><a href="<?php echo $url; ?>/informacion/blog.php" class="text-decoration-none text-dark">¡Visita nuestro Blog!</a></li>
                    <li><a href="<?php echo $url; ?>/informacion/privacidad.php" class="text-decoration-none text-dark">Políticas de privacidad</a></li>
                    <li> Medios de Pago:
                        <div class="d-flex flex-wrap gap-4 mt-3 justify-content-start">
                            <div class="text-center" title="Transferencia bancaria">
                                <i class="bi bi-bank text-dark" style="font-size: 3rem;"></i>
                            </div>
                            <div class="text-center" title="Mercado Pago">
                                <i class="bi bi-wallet2 text-dark" style="font-size: 3rem;"></i>
                            </div>
                            <div class="text-center" title="Efectivo">
                                <i class="bi bi-cash-coin text-dark" style="font-size: 3rem;"></i>
                            </div>
                            <div class="text-center" title="Tarjeta de crédito/débito">
                                <i class="bi bi-credit-card text-dark" style="font-size: 3rem;"></i>
                            </div>
                        </div>
                    </li>
                </ul>
            </article>

            <article class="col-md-3 mb-4">
                <h4 class="fw-bold">Categorías</h4>
                <ul class="list-unstyled">
                    <li><a href="<?php echo $url; ?>/menu/mujer.php" class="text-decoration-none text-dark">Mujer</a></li>
                    <li><a href="<?php echo $url; ?>/menu/hombre.php" class="text-decoration-none text-dark">Hombre</a></li>
                    <li><a href="<?php echo $url; ?>/menu/niños.php" class="text-decoration-none text-dark">Niños</a></li>
                    <li><a href="<?php echo $url; ?>/menu/accesorios.php" class="text-decoration-none text-dark">Accesorios</a></li>
                </ul>
            </article>

            <article class="col-md-3 mb-4">
                <h4 class="fw-bold">Contacto</h4>
                <ul class="list-unstyled">
                    <li><a href="<?php echo $url; ?>/informacion/contacto.php?tipo=contacto" class="text-decoration-none text-dark">Formulario de contacto</a></li>
                    <li><a href="<?php echo $url; ?>/informacion/contacto.php?tipo=consulta" class="text-decoration-none text-dark">Formulario de consultas</a></li>
                    <li><a href="<?php echo $url; ?>/informacion/ubicacion.php" class="text-decoration-none text-dark">Ubicación y horarios</a></li>
                </ul>
                <p>¡Síguenos para descuentos!</p>
                <div class="d-flex justify-content-center align-items-center gap-4 my-4">
                    <a href="https://www.instagram.com/aurasport.mc/" class="text-dark" title="aurasport.mc" style="font-size: 3rem;"><i class="bi bi-instagram icono-footer"></i></a>
                    <a href="https://www.facebook.com/aura.sport.2025" class="text-dark" title="aura.sport.2025" style="font-size: 3rem;"><i class="bi bi-facebook icono-footer"></i></a>
                    <a href="https://wa.me/543775449624" class="text-dark" title="+54 3775 44-9624" style="font-size: 3rem;"><i class="bi bi-whatsapp icono-footer"></i></a>
                    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=aurasport.mc@gmail.com" target="_blank" class="text-dark" title="aurasport.mc@gmail.com" style="font-size: 3rem;"><i class="bi bi-envelope-at icono-footer"></i></a>
                </div>
            </article>

            <article class="col-md-3 mb-4">
                <h4 class="fw-bold">Más Información</h4>
                <ul class="list-unstyled">
                    <li><a href="<?php echo $url; ?>/menu/exclusivo.php" class="text-decoration-none text-dark">Exclusivo</a></li>
                    <li><a href="<?php echo $url; ?>/menu/vacantes.php" class="text-decoration-none text-dark">Vacantes</a></li>
                    <li><a href="<?php echo $url; ?>/informacion/entregas.php" class="text-decoration-none text-dark">Entregas a domicilio</a></li>
                </ul>
            </article>
            <p class="mb-0 small text-center text-muted">
                <a href="<?php echo $url; ?>/administrador/index.php"
                    style="color: inherit; text-decoration: none; opacity: 0.2;">
                    ©
                </a>
                Micaela Monsserrat, Agustina Montiel, Sabrina Flores
            </p>

        </div>
    </section>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Función para actualizar el contador del carrito
    async function actualizarContadorCarrito() {
        try {
            const form = new URLSearchParams();
            form.append('accion', 'get');
            const res = await fetch('<?php echo $url; ?>/carrito/carrito.php', {
                method: 'POST',
                body: form
            });
            const data = await res.json();
            if (data.exito) {
                const totalItems = Object.values(data.carrito).reduce((total, item) => total + item.cantidad, 0);
                const contador = document.getElementById('contador-carrito');
                if (contador) contador.textContent = `(${totalItems})`;
            }
        } catch (error) {
            console.error('Error al actualizar contador:', error);
        }
    }
    document.addEventListener('DOMContentLoaded', actualizarContadorCarrito);

    // Detectar página activa
    document.addEventListener('DOMContentLoaded', function() {

        // Obtiene solo el nombre del archivo actual
        const currentPage = window.location.pathname.split('/').pop();

        const pageMap = {
            'mujer.php': 'Mujer',
            'hombre.php': 'Hombre',
            'niños.php': 'Niños',
            'accesorios.php': 'Accesorios',
            'ofertas.php': 'Ofertas',
            'nosotros.php': 'Nosotros',
            'exclusivo.php': 'Exclusivo',
            'vacantes.php': 'Vacantes',
            'index.php': 'Inicio'
        };

        const navLinks = document.querySelectorAll('#navbarNavDropdown .nav-link');

        navLinks.forEach(link => {
            if (pageMap[currentPage] === link.textContent.trim()) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });
</script>
</body>

</html>