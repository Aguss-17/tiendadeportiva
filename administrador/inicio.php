<?php include('../estructura/cabecera.php'); ?>

<section class="container my-5">
    <div class="card shadow border-0 text-center w-100">
        <div class="card-body p-5">
            <h1 class="display-4 fw-bold mb-4">¡Bienvenidos al Panel de Administración!</h1>
            <p class="mb-4">Desde aquí podés gestionar tus productos, blog, comentarios, foro y las vacantes de Aura Sport.</p>
            <hr class="my-4">

            <!-- FILA 1 -->
            <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mb-4">
                <a href="../administrador/productos.php" class="btn btn-dark btn-lg">
                    <i class="fas fa-box me-2"></i>Administrar Productos
                </a>
                <a href="../administrador/blog.php" class="btn btn-dark btn-lg">
                    <i class="fas fa-blog me-2"></i>Gestionar Blog
                </a>
                <a href="../administrador/comentarios.php" class="btn btn-dark btn-lg">
                    <i class="fas fa-comments me-2"></i>Revisar Comentarios
                </a>
            </div>

            <!-- FILA 2 - FORO SIMPLIFICADO -->
            <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mb-4">
                <a href="../administrador/foro.php" class="btn btn-dark btn-lg">
                    <i class="fas fa-comments me-2"></i>Gestionar Foro Completo
                </a>
                <a href="../administrador/vacantes.php" class="btn btn-dark btn-lg">
                    <i class="fas fa-briefcase me-2"></i>Supervisar Vacantes
                </a>
            </div>

            <!-- FILA 3 - ACCIONES -->
            <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                <a href="../administrador/foro.php?modulo=categorias" class="btn btn-outline-dark btn-lg">
                    <i class="fas fa-tags me-2"></i>Categorías Foro
                </a>
                <a href="../administrador/foro.php?modulo=temas" class="btn btn-outline-dark btn-lg">
                    <i class="fas fa-comment me-2"></i>Moderar Temas
                </a>
                <a href="../administrador/foro.php?modulo=respuestas" class="btn btn-outline-dark btn-lg">
                    <i class="fas fa-reply me-2"></i>Moderar Respuestas
                </a>
            </div>

            <!-- FILA 4 - CERRAR SESIÓN -->
            <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-4 pt-4 border-top">
                <a href="cerrar.php" class="btn btn-danger btn-lg">
                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                </a>
            </div>

        </div>
    </div>
</section>

<?php include('../estructura/pie.php'); ?>
