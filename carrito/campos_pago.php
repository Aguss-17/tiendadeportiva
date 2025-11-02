<?php
$metodo_pago = $_POST['metodo_pago'] ?? '';
?>

<?php if ($metodo_pago === 'tarjeta'): ?>
<!-- TARJETA -->
<div class="card border-primary">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i>Datos de la Tarjeta</h6>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Número de tarjeta *</label>
            <input type="text" class="form-control" name="numero_tarjeta" 
                placeholder="1234 5678 9012 3456" maxlength="19"
                oninput="formatCardNumber(this)" required>
            <div class="form-text">Ingrese el número sin guiones</div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Fecha de vencimiento *</label>
                <input type="text" class="form-control" name="fecha_vencimiento" 
                    placeholder="MM/AA" maxlength="5"
                    oninput="formatExpiryDate(this)" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CVV *</label>
                <input type="text" class="form-control" name="cvv" 
                    placeholder="123" maxlength="4" 
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                <div class="form-text">Los 3 dígitos en el reverso</div>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Nombre del titular *</label>
            <input type="text" class="form-control" name="nombre_titular" 
                placeholder="Como aparece en la tarjeta" required>
        </div>
        
        <div class="alert alert-info small">
            <i class="bi bi-shield-lock me-2"></i>
            Sus datos de tarjeta están protegidos con encriptación SSL.
        </div>
    </div>
</div>

<?php elseif ($metodo_pago === 'transferencia'): ?>
<!-- TRANSFERENCIA / MERCADO PAGO UNIFICADO -->
<div class="card border-info">
    <div class="card-header bg-info text-white">
        <h6 class="mb-0"><i class="bi bi-bank me-2"></i>Pago por Transferencia</h6>
    </div>
    <div class="card-body">
        <!-- Selección de tipo de transferencia -->
        <div class="mb-3">
            <label class="form-label">Tipo de transferencia *</label>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="tipo_transferencia" 
                    id="transferencia_bancaria" value="bancaria" checked 
                    onchange="toggleTransferenciaFields()">
                <label class="form-check-label" for="transferencia_bancaria">
                    <i class="bi bi-building me-1"></i> Transferencia Bancaria
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="tipo_transferencia" 
                    id="mercadopago" value="mercadopago"
                    onchange="toggleTransferenciaFields()">
                <label class="form-check-label" for="mercadopago">
                    <i class="bi bi-wallet2 me-1"></i> Mercado Pago
                </label>
            </div>
        </div>

        <!-- Campos para Transferencia Bancaria -->
        <div id="campos-bancarios">
            <div class="mb-3">
                <h6>Datos para la transferencia:</h6>
                <div class="bg-light p-3 rounded">
                    <p class="mb-1"><strong>Banco:</strong> Banco Example</p>
                    <p class="mb-1"><strong>Titular:</strong> Aura Sport S.A.</p>
                    <p class="mb-1"><strong>CBU:</strong> 0123456789012345678901</p>
                    <p class="mb-1"><strong>Alias:</strong> AURA.SPORT.MP</p>
                    <p class="mb-0"><strong>CUIT:</strong> 30-12345678-9</p>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Comprobante de transferencia (opcional)</label>
                <input type="file" class="form-control" name="comprobante" 
                    accept=".jpg,.jpeg,.png,.pdf">
                <div class="form-text">Suba una imagen o PDF del comprobante para agilizar la verificación</div>
            </div>
        </div>

        <!-- Campos para Mercado Pago -->
        <div id="campos-mercadopago" style="display: none;">
            <div class="mb-3">
                <label class="form-label">Alias de Mercado Pago *</label>
                <input type="text" class="form-control" name="alias_mp" 
                    placeholder="tu.alias.mp">
                <div class="form-text">Ingrese su alias para enviarle la solicitud de pago</div>
            </div>
            
            <div class="text-center mb-3">
                <img src="../img/mercadopago-qr.png" alt="QR Mercado Pago" class="img-fluid" style="max-width: 200px;">
                <div class="mt-2">
                    <small class="text-muted">También puede escanear el código QR</small>
                </div>
            </div>
        </div>

        <div class="alert alert-warning small">
            <h6 class="alert-heading" id="titulo-instrucciones">Instrucciones para Transferencia Bancaria:</h6>
            <ul class="mb-0" id="instrucciones-transferencia">
                <li>Realice la transferencia a los datos proporcionados</li>
                <li>Envie el comprobante a: comprobantes@aurasport.com</li>
                <li>Su pedido se procesará una vez confirmado el pago</li>
                <li>El procesamiento puede tomar hasta 24 horas hábiles</li>
                <li>Incluya su número de pedido en el concepto</li>
            </ul>
            <ul class="mb-0" id="instrucciones-mercadopago" style="display: none;">
                <li>Le enviaremos una solicitud de pago a su alias</li>
                <li>Debe aceptar la solicitud en Mercado Pago</li>
                <li>El pedido se activa automáticamente al confirmarse el pago</li>
                <li>Recibirá la confirmación por email</li>
                <li>Procesamiento inmediato una vez aceptado el pago</li>
            </ul>
        </div>
    </div>
</div>

<?php elseif ($metodo_pago === 'efectivo'): ?>
<!-- EFECTIVO -->
<div class="card border-warning">
    <div class="card-header bg-warning text-white">
        <h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Pago en Efectivo</h6>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Seleccione sucursal para pagar *</label>
            <select class="form-select" name="sucursal" required>
                <option value="">Seleccionar sucursal</option>
                <option value="sucursal_centro">Sucursal Pricipal - Eva Duarte de Perón 1234</option>
            </select>
        </div>
        
        <div class="alert alert-info small">
            <h6 class="alert-heading">Instrucciones para pago en efectivo:</h6>
            <ul class="mb-0">
                <li>Acérquese a la sucursal seleccionada</li>
                <li>Mencione su número de pedido al pagar</li>
                <li>Disponible de lunes a viernes de 08:00 a 12:00hs / 17:00 a 20:30hs</li>
                <li>Traiga su DNI para identificar el pedido</li>
                <li>Una vez pagado, su pedido se enviará inmediatamente</li>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Funciones para tarjeta
function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, '');
    value = value.replace(/(\d{4})/g, '$1 ').trim();
    input.value = value.substring(0, 19);
}

function formatExpiryDate(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    input.value = value.substring(0, 5);
}

// Funciones para transferencia/mercadopago
function toggleTransferenciaFields() {
    const tipo = document.querySelector('input[name="tipo_transferencia"]:checked').value;
    const camposBancarios = document.getElementById('campos-bancarios');
    const camposMercadopago = document.getElementById('campos-mercadopago');
    const instruccionesTransferencia = document.getElementById('instrucciones-transferencia');
    const instruccionesMercadopago = document.getElementById('instrucciones-mercadopago');
    const tituloInstrucciones = document.getElementById('titulo-instrucciones');
    
    if (tipo === 'bancaria') {
        camposBancarios.style.display = 'block';
        camposMercadopago.style.display = 'none';
        instruccionesTransferencia.style.display = 'block';
        instruccionesMercadopago.style.display = 'none';
        tituloInstrucciones.textContent = 'Instrucciones para Transferencia Bancaria:';
        
        // Hacer requerido el campo de alias si estaba lleno
        const aliasInput = document.querySelector('input[name="alias_mp"]');
        if (aliasInput) {
            aliasInput.removeAttribute('required');
            aliasInput.value = '';
        }
        
    } else {
        camposBancarios.style.display = 'none';
        camposMercadopago.style.display = 'block';
        instruccionesTransferencia.style.display = 'none';
        instruccionesMercadopago.style.display = 'block';
        tituloInstrucciones.textContent = 'Instrucciones para Mercado Pago:';
        
        // Hacer requerido el campo de alias
        const aliasInput = document.querySelector('input[name="alias_mp"]');
        if (aliasInput) aliasInput.setAttribute('required', 'required');
    }
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('transferencia_bancaria')) {
        toggleTransferenciaFields();
    }
});
</script>