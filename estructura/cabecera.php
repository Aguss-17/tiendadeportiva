<?php
// Genera la URL base dinámica
$url = "http://" . $_SERVER['HTTP_HOST']; 
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aura Sport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Ícono de la pestaña del navegador -->
    <link rel="icon" type="image/png" href="<?php echo $url; ?>/img/favicon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $url; ?>/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $url; ?>/img/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $url; ?>/img/apple-touch-icon.png">

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $url; ?>/css/estilo.css">
    <link rel="stylesheet" href="<?php echo $url; ?>/css/responsive.css">
    <link rel="stylesheet" href="<?php echo $url; ?>/css/modo_oscuro.css">

    <script>
        // Sistema MÍNIMO de Modo Claro/Oscuro
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('themeToggleContainer')) {
                const toggleContainer = document.createElement('div');
                toggleContainer.id = 'themeToggleContainer';
                toggleContainer.className = 'theme-toggle-container';
                toggleContainer.innerHTML = `
                    <div class="theme-toggle" id="themeToggle">
                        <i class="fa-regular fa-sun fa-lg" style="color: #FFA500;"></i>
                        <div class="toggle-switch"><div class="toggle-slider"></div></div>
                        <i class="fa-regular fa-moon fa-lg" style="color: #4B0082;"></i>
                    </div>
                `;
                document.body.appendChild(toggleContainer);
            }

            const themeToggle = document.getElementById('themeToggle');
            const savedTheme = localStorage.getItem('theme');

            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }

            themeToggle.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        });
    </script>
</head>

<body>
    <nav class="navbar navbar-light bg-light navbar-expand-lg px-5 py-3">
        <div class="container-fluid d-flex align-items-center">
            <a class="navbar-brand me-3" href="<?php echo $url; ?>/index.php">
                <img src="<?php echo $url; ?>/img/logosinfondo.png" class="img-fluid d-block" style="max-width: 120px;" alt="Logo Aura Sport">
            </a>
            <form class="d-none d-md-flex w-50 mx-4" action="buscar.php" method="GET" style="max-width: 45%;">
                <input class="form-control me-2" type="text" name="busqueda" placeholder="Buscar">
                <button class="btn btn-dark me-3" type="submit">Buscar</button>
            </form>
            <div class="d-flex gap-3 flex-wrap justify-content-end">
                <a href="<?php echo $url; ?>/carrito/index.php" class="text-dark fs-4 position-relative text-decoration-none" title="Carrito de compras">
                    <i class="bi bi-cart4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-transparent text-dark fs-7 fw-normal" id="contador-carrito" style="font-size: 0.6rem !important;">
                        (0)
                    </span>
                </a>
                <a href="<?php echo $url; ?>/foro/foro.php" class="text-dark fs-4" title="Foro comunicativo"><i class="bi bi-chat-dots"></i></a>
                <a href="<?php echo $url; ?>/login.php" class="text-dark fs-4" title="Iniciar sesión"><i class="bi bi-person-circle"></i></a>
                <a href="<?php echo $url; ?>/registro.php" class="text-dark fs-4" title="Registrarme"><i class="bi bi-fingerprint"></i></a>
            </div>
        </div>
    </nav>

    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNavDropdown">
                <ul class="navbar-nav gap-4">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/menu/mujer.php">Mujer</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/menu/hombre.php">Hombre</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/menu/niños.php">Niños</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/menu/accesorios.php">Accesorios</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/menu/ofertas.php">Ofertas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/menu/nosotros.php">Nosotros</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/menu/exclusivo.php">Exclusivo</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/menu/vacantes.php">Vacantes</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $url; ?>/administrador/index.php">Administrador</a></li>
                </ul>
            </div>
        </div>
    </nav>
