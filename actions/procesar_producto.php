<?php
/**
 * procesar_producto.php
 * Script para manejar la subida de la imagen y la inserción/edición/eliminación de productos.
 *
 * @autor: [Tu Nombre]
 * @fecha: 21/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 

// Rutas de almacenamiento
// __DIR__ es C:\xampp\htdocs\skriptoria\actions
// dirname(__DIR__) sube a C:\xampp\htdocs\skriptoria
define('ROOT_DIR', dirname(__DIR__) . '/'); 
define('UPLOAD_DIR', ROOT_DIR . 'assets/img/'); // C:\xampp\htdocs\skriptoria/assets/img/
define('PUBLIC_IMG_PATH', 'assets/img/'); // Ruta que se guarda en la BD (accesible desde el navegador)


function redirigir_productos($mensaje, $tipo = 'error') {
    $pagina_redireccion = '../pages/admin-productos.php';
    header("Location: " . $pagina_redireccion . "?" . $tipo . "=" . urlencode($mensaje));
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // ----------------------------------------------------
    // LÓGICA DE ELIMINACIÓN (DELETE)
    // ----------------------------------------------------
    if (isset($_POST['btn_eliminar_producto'])) {
        // ... (Tu lógica de eliminación aquí) ...
        // Este bloque ya está completo en los pasos anteriores.
        // Asumo que la lógica de DELETE y unlink funciona.
        
        // Simplemente cerramos la llave para que el código no se rompa aquí.
    } 
    
    // ----------------------------------------------------
    // LÓGICA DE INSERCIÓN (INSERT)
    // ----------------------------------------------------
    elseif (isset($_POST['btn_guardar_producto'])) {
        
        $conn->autocommit(false); // Iniciar transacción
        $transaccion_exitosa = true;
        
        try {
            // 1. Obtener y Sanear Datos
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
            
            $portada_file = $_FILES['portada_file'];
            
            // 2. Manejo de Archivos (El punto de fallo más probable)
            if ($portada_file['error'] !== UPLOAD_ERR_OK) {
                 // Capturamos errores comunes de PHP.ini
                throw new Exception("Error de subida: Código " . $portada_file['error'] . ". (Verifique permisos o límites de PHP.ini)");
            }

            $extension = pathinfo($portada_file['name'], PATHINFO_EXTENSION);
            $nombre_archivo_unico = uniqid('portada_', true) . '.' . $extension;
            $ruta_destino_servidor = UPLOAD_DIR . $nombre_archivo_unico;
            $portada_url_bd = PUBLIC_IMG_PATH . $nombre_archivo_unico; 
            
            // INTENTO DE MOVIMIENTO
            if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0777, true)) {
                throw new Exception("El directorio de subida no existe y no pudo ser creado. Verifique permisos.");
            }
            
            if (!move_uploaded_file($portada_file['tmp_name'], $ruta_destino_servidor)) {
                // Si move_uploaded_file falla, lanza la excepción
                throw new Exception("Fallo de subida: Error de permisos al mover el archivo a " . UPLOAD_DIR);
            }
            
            // 3. Preparar e Insertar en la BD (Usando sentencias preparadas)
            $sql = "INSERT INTO Productos (titulo, autor, editorial, precio, stock, genero, idioma, num_paginas, fecha_publicacion, descripcion, portada_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            // Tipos: sssdiiiisss (string x4, int x3, int, string, string, string)
            $stmt->bind_param("sssdiiissss", 
                $titulo, $autor, $editorial, $precio, $stock, $genero, $idioma, $num_paginas, $fecha_publicacion, $descripcion, $portada_url_bd
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Fallo de BD: " . $stmt->error);
            }
            $stmt->close();
            
            // COMMIT
            $conn->commit();
            redirigir_productos("Producto '$titulo' registrado con éxito.", 'success');
            
        } catch (Exception $e) {
            // ROLLBACK y manejo de errores
            $conn->rollback();
            
            // Si la imagen ya se movió al servidor, eliminarla para limpiar
            if (isset($ruta_destino_servidor) && file_exists($ruta_destino_servidor)) {
                 @unlink($ruta_destino_servidor);
            }
            redirigir_productos($e->getMessage(), 'error');
            
        } finally {
            $conn->autocommit(true);
            $conn->close();
        }
        
    }
    
    // ----------------------------------------------------
    // LÓGICA DE EDICIÓN (UPDATE)
    // ----------------------------------------------------
    elseif (isset($_POST['btn_editar_producto'])) {
        // Este bloque se mantiene igual o usa try/catch similar
    }
    // ----------------------------------------------------
    // ACCIÓN NO RECONOCIDA
    // ----------------------------------------------------
    else {
        redirigir_productos("Acción no reconocida.", 'error');
    }
}
// El archivo ya usa redirigir_productos($e->getMessage(), 'error'); que resuelve el problema de la pausa.
?>