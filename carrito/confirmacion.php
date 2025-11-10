<?php
session_start();
include('../estructura/cabecera.php');
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-check-circle me-2"></i>¡Pedido Confirmado!</h4>
                </div>
                <div class="card-body text-center">
                    <p class="fs-5">Tu pedido ha sido procesado exitosamente.</p>
                    <p>Hemos enviado un email de confirmación con los detalles de tu compra.</p>
                    <p>Te contactaremos pronto con los detalles del envío.</p>
                    
                    <div class="mt-4">
                        <a href="../index.php" class="btn btn-outline-secondary">Volver al Inicio</a>
                    </div><br>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../estructura/pie.php'); ?>