<?php
/**
 * procesar_edicion.php
 * Script para manejar la subida de imagen (opcional) y la actualización de un producto (UPDATE).
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

require_once '../includes/db_connection.php'; 

// Rutas de almacenamiento:
define('ROOT_DIR', dirname(__DIR__) . '/');
define('UPLOAD_DIR', ROOT_DIR . 'assets/img/'); 
define('PUBLIC_IMG_PATH', 'assets/img/'); 

function redirigir_productos($mensaje, $tipo = 'error') {
    header("Location: ../pages/admin-productos.php?" . $tipo . "=" . urlencode($mensaje));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_editar_producto'])) {

    // 1. Obtener Datos
    $id_producto = (int)$_POST['id_producto'];
    $titulo = $conn->real_escape_string($_POST['titulo']);
    $autor = $conn->real_escape_string($_POST['autor']);
    $editorial = $conn->real_escape_string($_POST['editorial']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];
    $idioma = $conn->real_escape_string($_POST['idioma']);
    $genero = $conn->real_escape_string($_POST['genero']);
    $num_paginas = (int)$_POST['num_paginas'];
    $fecha_publicacion = $conn->real_escape_string($_POST['fecha_publicacion']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $portada_url_bd = $conn->real_escape_string($_POST['portada_actual']); // Ruta de la imagen actual
    
    $actualizar_imagen = false;

    // 2. Manejo de la Subida de Nueva Imagen (si se seleccionó una)
    if (!empty($_FILES['portada_file']['name']) && $_FILES['portada_file']['error'] === UPLOAD_ERR_OK) {
        
        $portada_file = $_FILES['portada_file'];
        // Generar un nombre único
        $extension = pathinfo($portada_file['name'], PATHINFO_EXTENSION);
        $nombre_archivo_unico = uniqid('portada_edit_', true) . '.' . $extension;
        $ruta_destino_servidor = UPLOAD_DIR . $nombre_archivo_unico;
        
        // Mover la nueva imagen
        if (move_uploaded_file($portada_file['tmp_name'], $ruta_destino_servidor)) {
            $actualizar_imagen = true;
            $nueva_portada_url = PUBLIC_IMG_PATH . $nombre_archivo_unico;
            
            // Opcional: Eliminar la imagen anterior del servidor
            if (!empty($portada_url_bd) && file_exists(ROOT_DIR . $portada_url_bd)) {
                @unlink(ROOT_DIR . $portada_url_bd);
            }
            $portada_url_bd = $nueva_portada_url;

        } else {
            redirigir_productos("Error al subir la nueva imagen.", 'error');
        }
    }
    
    // 3. Preparar la Consulta de Actualización (UPDATE)
    $sql_update = "UPDATE Productos SET 
        titulo = '$titulo',
        autor = '$autor',
        editorial = '$editorial',
        precio = $precio,
        stock = $stock,
        idioma = '$idioma',
        genero = '$genero',
        num_paginas = $num_paginas,
        fecha_publicacion = '$fecha_publicacion',
        descripcion = '$descripcion'";

    if ($actualizar_imagen) {
        $sql_update .= ", portada_url = '$portada_url_bd'";
    }

    $sql_update .= " WHERE id_producto = $id_producto";

    // 4. Ejecutar la Consulta
    if ($conn->query($sql_update) === TRUE) {
        redirigir_productos("Producto #$id_producto actualizado con éxito.", 'success');
    } else {
        redirigir_productos("Error SQL al actualizar producto: " . $conn->error);
    }

    $conn->close();

} else {
    redirigir_productos("Acceso no autorizado.", 'error');
}
?>