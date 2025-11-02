<section style="background-color: #dec19e;">
<?php require_once __DIR__ . '/../estructura/cabecera.php'; ?>
<div class="container my-5 masinfo-container">
    <?php
    $seccion = $_GET['tipo'] ?? '';

    if ($seccion === 'entregas') {
        echo '
        <div class="row justify-content-center masinfo-row">
            <div class="col-lg-10 masinfo-col">
                <div class="masinfo-card p-4 mb-4 masinfo-main-card">
                    <h1 class="titulo-seccion masinfo-main-title">
                        <i class="bi bi-truck feature-icon"></i>
                        Entregas a Domicilio – Aura Sport
                    </h1>
                    
                    <p class="lead fs-6 text-muted masinfo-description">
                        En <strong class="text-primary">Aura Sport</strong> ofrecemos un servicio de <strong>entregas a domicilio</strong> 
                        en Monte Caseros, Corrientes. Nos aseguramos de que tu pedido llegue en 
                        <strong>perfectas condiciones</strong> y en el menor tiempo posible.
                    </p>
                </div>

                <div class="row mt-4 masinfo-grid">
                    <div class="col-md-6 mb-4 masinfo-grid-col">
                        <div class="card h-100 masinfo-content-card masinfo-grid-item">
                            <div class="card-body masinfo-grid-body">
                                <h3 class="subtitulo masinfo-grid-title">
                                    <i class="bi bi-geo-alt text-primary me-2 masinfo-grid-icon"></i>
                                    Zonas de entrega
                                </h3>
                                <p class="card-text masinfo-grid-text">
                                    Realizamos envíos a todo Monte Caseros y alrededores. 
                                    Consultanos si tu zona está disponible.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4 masinfo-grid-col">
                        <div class="card h-100 masinfo-content-card masinfo-grid-item">
                            <div class="card-body masinfo-grid-body">
                                <h3 class="subtitulo masinfo-grid-title">
                                    <i class="bi bi-clock text-primary me-2 masinfo-grid-icon"></i>
                                    Tiempos de entrega
                                </h3>
                                <p class="card-text masinfo-grid-text">
                                    Los envíos se realizan dentro de las <strong>24 a 48 horas hábiles</strong> posteriores 
                                    a la confirmación de tu compra.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4 masinfo-grid-col">
                        <div class="card h-100 masinfo-content-card masinfo-grid-item">
                            <div class="card-body masinfo-grid-body">
                                <h3 class="subtitulo masinfo-grid-title">
                                    <i class="bi bi-currency-dollar text-primary me-2 masinfo-grid-icon"></i>
                                    Costo de envío
                                </h3>
                                <p class="card-text masinfo-grid-text">
                                    El costo del envío varía según la zona. En compras superiores a un monto 
                                    determinado, el envío puede ser <strong class="text-success">gratuito</strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4 masinfo-grid-col">
                        <div class="card h-100 masinfo-content-card masinfo-grid-item">
                            <div class="card-body masinfo-grid-body">
                                <h3 class="subtitulo masinfo-grid-title">
                                    <i class="bi bi-headset text-primary me-2 masinfo-grid-icon"></i>
                                    ¿Necesitás ayuda?
                                </h3>
                                <p class="card-text masinfo-grid-text">
                                    <strong>WhatsApp:</strong> +54 9 3775 44-9624<br>
                                    <strong>Correo electrónico:</strong> 
                                    <a href="mailto:aurasport.mc@gmail.com" class="link-email masinfo-link">aurasport.mc@gmail.com</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
    } elseif ($seccion === 'privacidad') {
        echo '
        <div class="row justify-content-center masinfo-row">
            <div class="col-lg-10 masinfo-col">
                <div class="masinfo-card p-4 mb-4 masinfo-main-card">
                    <h1 class="titulo-seccion masinfo-main-title">
                        <i class="bi bi-shield-lock feature-icon"></i>
                        Política de Privacidad – Aura Sport
                    </h1>
                    
                    <p class="lead fs-6 text-muted masinfo-description">
                        En <strong class="text-primary">Aura Sport</strong> respetamos tu privacidad y protegemos 
                        tus datos personales. Esta política explica cómo recolectamos, usamos 
                        y cuidamos tu información.
                    </p>
                </div>

                <div class="card masinfo-content-card masinfo-privacy-card">
                    <div class="card-body masinfo-privacy-body">
                        <h3 class="subtitulo masinfo-privacy-title">
                            <i class="bi bi-1-circle text-primary me-2 masinfo-privacy-icon"></i>
                            Información que recopilamos
                        </h3>
                        <p class="masinfo-privacy-text">
                            Podemos recopilar datos como nombre, correo electrónico, teléfono 
                            y dirección de envío cuando realizás una compra o te registrás.
                        </p>

                        <h3 class="subtitulo masinfo-privacy-title">
                            <i class="bi bi-2-circle text-primary me-2 masinfo-privacy-icon"></i>
                            Uso de la información
                        </h3>
                        <p class="masinfo-privacy-text">
                            Los datos se usan únicamente para procesar tus pedidos, brindarte 
                            atención personalizada y enviarte novedades si lo autorizás.
                        </p>

                        <h3 class="subtitulo masinfo-privacy-title">
                            <i class="bi bi-3-circle text-primary me-2 masinfo-privacy-icon"></i>
                            Protección de datos
                        </h3>
                        <p class="masinfo-privacy-text">
                            Tomamos medidas de seguridad para evitar accesos no autorizados 
                            o usos indebidos de tu información.
                        </p>

                        <h3 class="subtitulo masinfo-privacy-title">
                            <i class="bi bi-4-circle text-primary me-2 masinfo-privacy-icon"></i>
                            Contacto
                        </h3>
                        <p class="masinfo-privacy-text">
                            Si tenés dudas sobre nuestra política, podés escribirnos a: 
                            <a href="mailto:aurasport.mc@gmail.com" class="link-email masinfo-link">aurasport.mc@gmail.com</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>';
        
    } else {
        echo '
        <div class="row justify-content-center masinfo-row">
            <div class="col-lg-6 masinfo-col">
                <div class="card masinfo-content-card masinfo-error-card text-center">
                    <div class="card-body py-5 masinfo-error-body">
                        <i class="bi bi-exclamation-triangle display-1 text-warning masinfo-error-icon"></i>
                        <h1 class="titulo-seccion border-0 masinfo-error-title">Información no disponible</h1>
                        <p class="lead text-muted masinfo-error-text">
                            La sección seleccionada no existe o no tiene contenido cargado.
                        </p>
                        <a href="../index.php" class="btn btn-primary mt-3 masinfo-error-btn">
                            <i class="bi bi-house me-2"></i>Volver al Inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>';
    }
    ?>
</div>
<?php require_once __DIR__ . '/../estructura/pie.php'; ?>

</section>