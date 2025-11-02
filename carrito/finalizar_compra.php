<?php
require_once __DIR__ . '/controlador_finalizar_compra.php';
include('../estructura/cabecera.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Finalizar compra</title>
</head>

<body>
    <div class="container my-5">
        <h2 class="text-center mb-4">Finalizar Compra</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($mensaje_exito)): ?>
            <?= $mensaje_exito ?>
        <?php else: ?>
            <div class="row">
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-person me-2"></i>Datos de envío</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="form-checkout" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($datos_vista['csrf_token']) ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nombre y apellido *</label>
                                        <input type="text" class="form-control" name="nombre"
                                            value="<?= htmlspecialchars($datos_vista['usuario_data']['nombre_completo'] ?? '') ?>"
                                            required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Teléfono *</label>
                                        <input type="tel" class="form-control" name="telefono"
                                            value="<?= htmlspecialchars($datos_vista['usuario_data']['telefono'] ?? '') ?>"
                                            required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Correo electrónico *</label>
                                    <input type="email" class="form-control" name="email"
                                        value="<?= htmlspecialchars($datos_vista['usuario_data']['email'] ?? '') ?>"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Dirección de envío *</label>
                                    <textarea class="form-control" name="direccion" rows="3"
                                        placeholder="Calle, número, piso, departamento, ciudad, código postal" required><?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Método de pago *</label>
                                    <select class="form-select" name="metodo_pago" id="metodo_pago" required>
                                        <option value="">Seleccionar método de pago</option>
                                        <option value="tarjeta" <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'tarjeta') ? 'selected' : '' ?>>Tarjeta de crédito/débito</option>
                                        <option value="transferencia" <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'transferencia') ? 'selected' : '' ?>>Transferencia/Mercado Pago</option>
                                        <option value="efectivo" <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'efectivo') ? 'selected' : '' ?>>Pago en efectivo</option>
                                    </select>
                                </div>

                                <!-- Campos específicos para cada método de pago -->
                                <div id="campos-pago">
                                    <?php if (isset($_POST['metodo_pago'])): ?>
                                        <?php
                                        $metodo_pago_post = $_POST['metodo_pago'];
                                        include 'campos_pago.php';
                                        ?>
                                    <?php endif; ?>
                                </div>

                                <div class="text-center mt-3">
                                    <button type="submit" class="btn btn-success btn-md">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Confirmar pedido - $<?= number_format($datos_vista['total'], 2) ?>
                                    </button>
                                </div><br>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-cart me-2"></i>Resumen del pedido</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($datos_vista['carrito'] as $key => $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold"><?= htmlspecialchars($item['nombre']) ?></div>
                                            <small class="text-muted">
                                                Talle: <?= $item['talle'] ?: '-' ?> |
                                                Color: <?= $item['color'] ?: '-' ?>
                                            </small>
                                            <br>
                                            <small>Cantidad: <?= $item['cantidad'] ?> x $<?= number_format($item['precio'], 2) ?></small>
                                        </div>
                                        <span class="fw-bold">$<?= number_format($item['precio'] * $item['cantidad'], 2) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="fs-5">Total:</strong>
                                <strong class="fs-5 text-success">$<?= number_format($datos_vista['total'], 2) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3 border-warning">
                        <div class="card-body">
                            <h6 class="card-title text-warning"><i class="bi bi-shield-check me-2"></i>Compra segura</h6>
                            <p class="card-text small">Tu información está protegida. No almacenamos datos sensibles de tarjetas.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const metodoPagoSelect = document.getElementById('metodo_pago');
            const camposPagoDiv = document.getElementById('campos-pago');

            metodoPagoSelect.addEventListener('change', function() {
                const metodo = this.value;

                if (metodo) {
                    // Crear form data para enviar el método seleccionado
                    const formData = new FormData();
                    formData.append('metodo_pago', metodo);

                    // Cargar campos específicos via AJAX
                    fetch('campos_pago.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(html => {
                            camposPagoDiv.innerHTML = html;
                        })
                        .catch(error => {
                            camposPagoDiv.innerHTML = '<div class="alert alert-warning">Error al cargar los campos de pago</div>';
                            console.error('Error:', error);
                        });
                } else {
                    camposPagoDiv.innerHTML = '';
                }
            });

            // Validación adicional del formulario
            document.getElementById('form-checkout').addEventListener('submit', function(e) {
                const btn = this.querySelector('button[type="submit"]');
                const metodoPago = document.getElementById('metodo_pago').value;
                let isValid = true;

                // Validaciones específicas por método de pago
                if (metodoPago === 'tarjeta') {
                    const numeroTarjeta = document.querySelector('input[name="numero_tarjeta"]');
                    const fechaVencimiento = document.querySelector('input[name="fecha_vencimiento"]');
                    const cvv = document.querySelector('input[name="cvv"]');
                    const nombreTitular = document.querySelector('input[name="nombre_titular"]');

                    if (numeroTarjeta && numeroTarjeta.value.replace(/\s/g, '').length < 13) {
                        alert('Por favor, ingrese un número de tarjeta válido');
                        isValid = false;
                    }
                    if (fechaVencimiento && !fechaVencimiento.value.match(/^(0[1-9]|1[0-2])\/[0-9]{2}$/)) {
                        alert('Por favor, ingrese una fecha de vencimiento válida (MM/AA)');
                        isValid = false;
                    }
                    if (cvv && (cvv.value.length < 3 || !cvv.value.match(/^[0-9]+$/))) {
                        alert('Por favor, ingrese un CVV válido');
                        isValid = false;
                    }
                    if (nombreTitular && nombreTitular.value.trim() === '') {
                        alert('Por favor, ingrese el nombre del titular de la tarjeta');
                        isValid = false;
                    }
                } else if (metodoPago === 'transferencia') {
                    const tipoTransferencia = document.querySelector('input[name="tipo_transferencia"]:checked');
                    if (!tipoTransferencia) {
                        alert('Por favor, seleccione un tipo de transferencia');
                        isValid = false;
                    } else if (tipoTransferencia.value === 'mercadopago') {
                        const aliasMP = document.querySelector('input[name="alias_mp"]');
                        if (aliasMP && aliasMP.value.trim() === '') {
                            alert('Por favor, ingrese su alias de Mercado Pago');
                            isValid = false;
                        }
                    }
                } else if (metodoPago === 'efectivo') {
                    const sucursal = document.querySelector('select[name="sucursal"]');
                    if (sucursal && sucursal.value === '') {
                        alert('Por favor, seleccione una sucursal para el pago en efectivo');
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Procesando pedido...';
            });
        });
    </script>

    <?php include('../estructura/pie.php'); ?>
</body>

</html>