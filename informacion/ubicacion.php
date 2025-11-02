<section style="background-color: #dec19e;">
    <?php require_once __DIR__ . '/../estructura/cabecera.php'; ?>

    <div class="container my-5 bg-light" style="min-height: 100vh; padding: 20px;">

        <!-- Título -->
        <h2 class="text-center p-3 fw-bold">Nuestra Ubicación</h2>
        <p class="lead text-center mb-4">
            ¡Encontranos fácilmente! Estamos ubicados en un punto estratégico de la ciudad para que puedas visitarnos cómodamente.
            Vení a conocernos y ser parte de nuestra comunidad.</p>


        <!-- Mapa -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body p-0">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d672.8448869119451!2d-57.64238983025597!3d-30.25246593270741!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95acd620dd3d2651%3A0x7ddf2d0eb4b65cb7!2sEva%20Duarte%20de%20Per%C3%B3n%201410%2C%20W3220%20Monte%20Caseros%2C%20Corrientes!5e0!3m2!1ses-419!2sar!4v1755284505408!5m2!1ses-419!2sar"
                    width="100%"
                    height="450"
                    class="border-0 rounded-0"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>

    <div class="container my-5 bg-light" style="min-height: 40vh;padding: 20px;">
        <!-- Horarios de Atención -->
        <div class="p-4">
            <h3 class="text-center mb-4 fw-bold">Horarios de Atención</h3>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Lunes a Viernes:</strong>
                            <span>08:00 a 12:00hs / 17:00 a 20:30hs</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Sábado:</strong>
                            <span>09:00 a 11:00hs</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Domingo:</strong>
                            <span class="badge bg-secondary">Cerrado</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../estructura/pie.php'; ?>
</section>