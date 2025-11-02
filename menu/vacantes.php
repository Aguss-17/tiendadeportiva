<section  style="background-color: #dec19e;">
<?php 
require_once __DIR__ . '/../config/bd.php';

// Traer solo vacantes activas (fecha_cierre en futuro o nula)
$sentenciaSQL = $conexion->prepare("SELECT * FROM vacantes WHERE fecha_cierre IS NULL OR fecha_cierre >= CURDATE() ORDER BY id DESC");
$sentenciaSQL->execute();
$listaVacantes = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);

// Formulario de postulación
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_apellido = trim($_POST['nombreapellido']);
    $dni = trim($_POST['dni']);
    $fecha_nacimiento = $_POST['fechanacimiento'];
    $domicilio = trim($_POST['domicilio']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $puesto_interes = $_POST['puesto'];
    
    $errors = [];
    if (empty($nombre_apellido)) $errors[] = "El nombre y apellido son requeridos";
    if (empty($dni) || !is_numeric($dni)) $errors[] = "El DNI es requerido y debe ser numérico";
    if (empty($fecha_nacimiento)) $errors[] = "La fecha de nacimiento es requerida";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "El email es requerido y debe tener un formato válido";
    if (empty($puesto_interes)) $errors[] = "Debe seleccionar un puesto de interés";

    $cv_nombre = null;
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == UPLOAD_ERR_OK) {
        $archivo = $_FILES['archivo'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        $extensiones_permitidas = ['pdf' => 'application/pdf', 
                                'doc' => 'application/msword', 
                                'docx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!array_key_exists($extension, $extensiones_permitidas)) {
            $errors[] = "Solo se permiten archivos PDF o Word";
        } elseif ($archivo['size'] > $maxSize) {
            $errors[] = "El archivo supera el tamaño máximo permitido (2MB)";
        } elseif ($mimeType !== $extensiones_permitidas[$extension]) {
            $errors[] = "El tipo de archivo no coincide con la extensión";
        } else {
            $directorio_uploads = __DIR__ . '/uploads/cvs/';
            if (!file_exists($directorio_uploads)) mkdir($directorio_uploads, 0755, true);
            $cv_nombre = uniqid() . '.' . $extension;
            move_uploaded_file($archivo['tmp_name'], $directorio_uploads . $cv_nombre);
        }
    } else {
        $errors[] = "Debe adjuntar su currículum vitae";
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO postulaciones 
                    (nombre_apellido, dni, fecha_nacimiento, domicilio, telefono, email, puesto_interes, cv_path, fecha_postulacion, estado) 
                    VALUES (:nombre, :dni, :fecha_nac, :domicilio, :telefono, :email, :puesto, :cv, NOW(), 'Pendiente')";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':nombre', $nombre_apellido);
            $stmt->bindParam(':dni', $dni);
            $stmt->bindParam(':fecha_nac', $fecha_nacimiento);
            $stmt->bindParam(':domicilio', $domicilio);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':puesto', $puesto_interes);
            $stmt->bindParam(':cv', $cv_nombre);
            if ($stmt->execute()) {
                $success = "¡Postulación enviada con éxito! Nos pondremos en contacto contigo pronto.";
                // Limpiar campos
                $_POST = [];
            } else {
                $errors[] = "Error al enviar la postulación. Por favor, intente nuevamente.";
            }
        } catch (PDOException $e) {
            error_log("Error en postulación: " . $e->getMessage());
            $errors[] = "Ocurrió un error al procesar tu postulación. Por favor, intente más tarde.";
        }
    }
}

include('../estructura/cabecera.php'); 
?>

<section id="vacantes" class="container my-5 py-5 bg-light">
    <div class="text-center mb-4">
        <h2 class="fw-bold">Vacantes en Aura Sport</h2>
        <p class="lead">¿Te gustaría ser parte del equipo? ¡Queremos sumar personas con energía, compromiso y pasión por el mundo fitness!</p>
    </div>

<div class="row justify-content-center g-4">
            <!-- Puestos disponibles -->
            <div class="col-md-6 mb-4">
                <div class="p-4 bg-white shadow-sm h-100 border">
                    <h4 class="text-center mb-3 fw-semibold">Puestos disponibles</h4>
                    <ul class="list-group list-group-flush">
                        <?php if (!empty($listaVacantes)): ?>
                            <?php foreach ($listaVacantes as $vacante): ?>
                                <li class="list-group-item bg-transparent border-0 border-bottom py-2">
                                    <strong><?= htmlspecialchars($vacante['puesto']) ?>:</strong>
                                    <?= htmlspecialchars($vacante['descripcion']) ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item bg-transparent text-center border-0">
                                No hay vacantes disponibles por ahora.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Requisitos generales -->
            <div class="col-md-6 mb-4">
                <div class="p-4 bg-white shadow-sm h-100 border">
                    <h4 class="text-center mb-3 fw-semibold">Requisitos generales</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item bg-transparent border-0 border-bottom py-2">• Idiomas</li>
                        <li class="list-group-item bg-transparent border-0 border-bottom py-2">• Habilidades</li>
                        <li class="list-group-item bg-transparent border-0 border-bottom py-2">• Disponibilidad</li>
                        <li class="list-group-item bg-transparent border-0 border-bottom py-2">• Experiencia</li>
                        <li class="list-group-item bg-transparent border-0 border-bottom py-2">• Estudios</li>
                        <li class="list-group-item bg-transparent border-0 border-bottom py-2">• Creatividad</li>
                    </ul>
                </div>
            </div>

    <!-- Formulario de postulación -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form method="post" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">
                <h3 class="mb-4 text-center text-dark">¡Postúlate ahora!</h3>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6>Por favor corrige los siguientes errores:</h6>
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-1">• <?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombreapellido" class="form-label">Nombre y Apellido *</label>
                        <input type="text" class="form-control" id="nombreapellido" name="nombreapellido" 
                            value="<?= isset($_POST['nombreapellido']) ? htmlspecialchars($_POST['nombreapellido']) : '' ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="dni" class="form-label">DNI *</label>
                        <input type="number" class="form-control" id="dni" name="dni" 
                            value="<?= isset($_POST['dni']) ? htmlspecialchars($_POST['dni']) : '' ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fechanacimiento" class="form-label">Fecha de Nacimiento *</label>
                        <input type="date" class="form-control" id="fechanacimiento" name="fechanacimiento" 
                            value="<?= isset($_POST['fechanacimiento']) ? htmlspecialchars($_POST['fechanacimiento']) : '' ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono *</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                            value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="domicilio" class="form-label">Domicilio *</label>
                    <input type="text" class="form-control" id="domicilio" name="domicilio" 
                        value="<?= isset($_POST['domicilio']) ? htmlspecialchars($_POST['domicilio']) : '' ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico *</label>
                    <input type="email" class="form-control" id="email" name="email" 
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>

                <div class="mb-3">
                    <label for="puesto" class="form-label">Puesto de interés *</label>
                    <select class="form-select" id="puesto" name="puesto" required>
                        <option value="">Seleccione un puesto</option>
                        <?php foreach ($listaVacantes as $vacante): ?>
                            <option value="<?= htmlspecialchars($vacante['puesto']) ?>" 
                                <?= (isset($_POST['puesto']) && $_POST['puesto'] === $vacante['puesto']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vacante['puesto']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($listaVacantes)): ?>
                            <option value="General">Postulación General</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="archivo" class="form-label">Adjuntar Currículum Vitae (PDF o Word, máximo 2MB) *</label>
                    <input type="file" class="form-control" id="archivo" name="archivo" accept=".pdf,.doc,.docx" required>
                    <div class="form-text">Formatos aceptados: PDF, DOC, DOCX. Tamaño máximo: 2MB.</div>
                </div>

                <button type="submit" class="btn btn-dark btn-lg w-100">Enviar Postulación</button>
            </form>
        </div>
    </div>
</section>

<?php include('../estructura/pie.php'); ?>
</section>