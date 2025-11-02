<?php
// Función para comprimir imágenes
function compressImage($source, $destination, $quality = 75) {
    $info = getimagesize($source);
    
    if ($info === false) return false;

    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagepng($image, $destination, 9); // PNG usa compresión 0-9
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            imagegif($image, $destination);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            imagewebp($image, $destination, $quality);
            break;
        default:
            return false;
    }

    if (isset($image)) {
        imagedestroy($image);
        return true;
    }

    return false;
}

// Función principal para manejar la subida de imágenes
function handleImageUpload($file, $uploadDir, $currentImage = '') {
    $errors = [];
    $fileName = $currentImage;
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Validaciones
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Solo se permiten imágenes JPG, PNG, GIF o WebP";
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = "La imagen no debe superar 5MB";
        }
        
        if (empty($errors)) {
            // Eliminar imagen anterior si existe
            if (!empty($currentImage) && file_exists($uploadDir . $currentImage)) {
                @unlink($uploadDir . $currentImage);
            }
            
            // Generar nombre seguro
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $baseName = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $fileName = uniqid() . '_' . $baseName . '.' . $extension;
            
            $tempFile = $file['tmp_name'];
            $finalPath = $uploadDir . $fileName;

            // Intentar comprimir la imagen antes de guardarla
            if (!compressImage($tempFile, $finalPath, 75)) {
                // Si falla la compresión, guardar la imagen sin modificar
                if (!move_uploaded_file($tempFile, $finalPath)) {
                    $errors[] = "Error al guardar la imagen";
                    $fileName = $currentImage;
                }
            }
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Error en la subida del archivo: " . $file['error'];
    }
    
    return ['fileName' => $fileName, 'errors' => $errors];
}
?>
