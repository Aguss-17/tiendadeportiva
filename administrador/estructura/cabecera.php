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

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aura Sport - Administración</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="icon" type="image/png" href="<?php echo $url; ?>/img/favicon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $url; ?>/img/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $url; ?>/img/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $url; ?>/img/apple-touch-icon.png">
<link rel="stylesheet" href="../css/responsive.css">
<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
</head>

<body>
      <?php include(__DIR__ . '/../estructura/mejoras_ux.php'); ?>
<!-- Navbar principal -->
<nav class="navbar navbar-light bg-light navbar-expand-lg px-4 py-3 shadow-sm border-bottom">
<div class="container-fluid">
      <img src="<?php echo $url; ?>/img/logosinfondo.png" class="img-fluid d-block" style="max-width: 120px;" alt="Logo Aura Sport">

      <div class="navbar-nav flex-row gap-3 align-items-center">
      <a href="<?php echo $url; ?>/administrador/inicio.php" 
            class="nav-link text-secondary fw-medium px-3 py-2 rounded-3 <?php echo ($current_page == 'inicio.php') ? 'bg-primary text-white' : 'hover-bg-light'; ?>">
            <i class="fas fa-home me-2"></i>Inicio
      </a>
      <a href="<?php echo $url; ?>/administrador/productos.php" 
            class="nav-link text-secondary fw-medium px-3 py-2 rounded-3 <?php echo ($current_page == 'productos.php') ? 'bg-primary text-white' : 'hover-bg-light'; ?>">
            <i class="fas fa-box me-2"></i>Productos
      </a>
      <a href="<?php echo $url; ?>/administrador/blog.php" 
            class="nav-link text-secondary fw-medium px-3 py-2 rounded-3 <?php echo ($current_page == 'blog.php') ? 'bg-primary text-white' : 'hover-bg-light'; ?>">
            <i class="fas fa-blog me-2"></i>Blog
      </a>
      <a href="<?php echo $url; ?>/administrador/comentarios.php" 
            class="nav-link text-secondary fw-medium px-3 py-2 rounded-3 <?php echo ($current_page == 'comentarios.php') ? 'bg-primary text-white' : 'hover-bg-light'; ?>">
            <i class="fas fa-comments me-2"></i>Comentarios
      </a>
      <a href="<?php echo $url; ?>/administrador/foro.php" 
            class="nav-link text-secondary fw-medium px-3 py-2 rounded-3 <?php echo ($current_page == 'foro.php') ? 'bg-primary text-white' : 'hover-bg-light'; ?>">
            <i class="fas fa-comments me-2"></i>Foro
      </a>
      <a href="<?php echo $url; ?>/administrador/vacantes.php" 
            class="nav-link text-secondary fw-medium px-3 py-2 rounded-3 <?php echo ($current_page == 'vacantes.php') ? 'bg-primary text-white' : 'hover-bg-light'; ?>">
            <i class="fas fa-briefcase me-2"></i>Vacantes
      </a>
      <div class="vr d-none d-lg-block h-25 mx-2"></div>
      <a href="<?php echo $url; ?>/index.php" 
            class="nav-link text-success fw-medium px-3 py-2 rounded-3 border border-success hover-bg-success hover-text-white">
            <i class="fas fa-external-link-alt me-2"></i>Volver a la Web
      </a>
      </div>
</div>
</nav>
