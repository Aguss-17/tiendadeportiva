<?php
session_start();
require_once __DIR__ . '/../estructura/cabecera.php';
?>

<main class="container my-5">
    <h2 class="mb-4">Tu carrito</h2>
    <div id="carrito-contenedor"></div>

    <div class="mt-4 d-flex justify-content-between">
        <a href="../index.php" class="btn btn-outline-secondary">Seguir comprando</a>
        <div>
            <button id="vaciarCarrito" class="btn btn-danger me-2">Vaciar carrito</button>
            <a href="../carrito/finalizar_compra.php" class="btn btn-success">Proceder a pagar</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../estructura/pie.php'; ?>

<script>
async function fetchCarrito() {
    try {
        const form = new URLSearchParams();
        form.append('accion', 'get');
        const res = await fetch('../carrito/carrito.php', { method: 'POST', body: form });
        const data = await res.json();
        return data;
    } catch (error) {
        console.error('Error fetching carrito:', error);
        return { exito: false, carrito: {} };
    }
}

function formatCurrency(n) {
    return '$' + Number(n).toLocaleString('es-AR', { maximumFractionDigits: 0 });
}

function renderCarrito(data) {
    const contenedor = document.getElementById('carrito-contenedor');
    
    if (!data.exito || !data.carrito || Object.keys(data.carrito).length === 0) {
        contenedor.innerHTML = '<div class="alert alert-info">Tu carrito está vacío.</div>';
        // Actualizar contador en la cabecera
        if (window.actualizarContadorCarrito) {
            actualizarContadorCarrito();
        }
        return;
    }

    const items = data.carrito;
    let html = '<div class="table-responsive"><table class="table align-middle">';
    html += '<thead><tr><th>Producto</th><th>Talle/Color</th><th>Cantidad</th><th>Precio unit.</th><th>Subtotal</th><th></th></tr></thead><tbody>';

    let total = 0;
    Object.keys(items).forEach(key => {
        const it = items[key];
        const subtotal = it.precio * it.cantidad;
        total += subtotal;
        html += `<tr>
            <td>
                <div class="d-flex align-items-center">
                    <img src="../img/${it.imagen}" alt="${it.nombre}" class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                    <strong>${it.nombre}</strong>
                </div>
            </td>
            <td>${it.talle || '-'} / ${it.color || '-'}</td>
            <td><input type="number" min="1" value="${it.cantidad}" class="form-control form-control-sm cantidad-input" data-key="${key}" style="width:80px"></td>
            <td>${formatCurrency(it.precio)}</td>
            <td>${formatCurrency(subtotal)}</td>
            <td><button class="btn btn-sm btn-outline-danger eliminar-btn" data-key="${key}">Eliminar</button></td>
        </tr>`;
    });

    html += `</tbody></table></div>`;
    html += `<div class="d-flex justify-content-end"><h4>Total: ${formatCurrency(total)}</h4></div>`;
    contenedor.innerHTML = html;

    // Agregar event listeners
    document.querySelectorAll('.cantidad-input').forEach(input => {
        input.addEventListener('change', async e => {
            const key = e.target.dataset.key;
            const cantidad = parseInt(e.target.value) || 1;
            const form = new URLSearchParams();
            form.append('accion', 'update');
            form.append('key', key);
            form.append('cantidad', cantidad);
            await fetch('../carrito/carrito.php', { method: 'POST', body: form });
            loadCarrito();
            // Actualizar contador global
            if (window.actualizarContadorCarrito) {
                actualizarContadorCarrito();
            }
        });
    });

    document.querySelectorAll('.eliminar-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const key = btn.dataset.key;
            const form = new URLSearchParams();
            form.append('accion', 'remove');
            form.append('key', key);
            await fetch('../carrito/carrito.php', { method: 'POST', body: form });
            loadCarrito();
            // Actualizar contador global
            if (window.actualizarContadorCarrito) {
                actualizarContadorCarrito();
            }
        });
    });

    // Actualizar contador global
    if (window.actualizarContadorCarrito) {
        actualizarContadorCarrito();
    }
}

async function loadCarrito() {
    const data = await fetchCarrito();
    renderCarrito(data);
}

// Vaciar carrito
document.getElementById('vaciarCarrito').addEventListener('click', async () => {
    if (!confirm('¿Vaciar carrito?')) return;
    const form = new URLSearchParams();
    form.append('accion', 'clear');
    await fetch('../carrito/carrito.php', { method: 'POST', body: form });
    loadCarrito();
    // Actualizar contador global
    if (window.actualizarContadorCarrito) {
        actualizarContadorCarrito();
    }
});

// Cargar carrito al iniciar
loadCarrito();
</script>
