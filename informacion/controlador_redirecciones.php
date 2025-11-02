<?php
if (isset($_GET['tipo'])) {
    $tipo = $_GET['tipo'];
    
    switch($tipo) {
        case 'entregas':
            header("Location: mas_info.php?tipo=entregas");
            break;
        case 'consulta':
            header("Location: contacto.php?tipo=consulta");
            break;
        case 'contacto':
            header("Location: contacto.php?tipo=contacto");
            break;
        case 'privacidad':
            header("Location: mas_info.php?tipo=privacidad");
            break;
        default:
            header("Location: index.php");
            break;
    }
} else {
    header("Location: index.php");
}
exit();
?>