<?php
require_once __DIR__ . '/../config/bd.php';

// Validar que solo acepte POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔐 VALIDAR CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF inválido. Por seguridad, recarga la página e intenta nuevamente.");
    }

    $post_id = $_POST['post_id'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $comentario = $_POST['comentario'] ?? '';

    if (!empty($post_id) && !empty($nombre) && !empty($email) && !empty($comentario)) {
        $sentenciaSQL = $conexion->prepare("
            INSERT INTO comentarios (post_id, nombre, email, comentario, estado) 
            VALUES (:post_id, :nombre, :email, :comentario, 'pendiente')
        ");
        
        $sentenciaSQL->execute([
            ':post_id' => $post_id,
            ':nombre' => $nombre,
            ':email' => $email,
            ':comentario' => $comentario
        ]);

        // Redirigir de vuelta al post con mensaje de éxito
        header('Location: post.php?id=' . $post_id . '&comentario=exito');
        exit();
    }
}

// Si hay error, redirigir al post
header('Location: post.php?id=' . ($_POST['post_id'] ?? '') . '&comentario=error');
exit();
?>