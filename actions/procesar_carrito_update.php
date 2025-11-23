<?php
/**
 * procesar_carrito_update.php
 * Procesa la actualización de cantidades en el carrito desde la vista pages/carrito.php.
 * Recorre todos los campos 'qty_[id_carrito]' y actualiza la BD.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 

$pagina_redireccion = '../pages/carrito.php';

// 1. Verificar Autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=Debes iniciar sesión para actualizar el carrito.");
    exit;
}

$id_usuario = $_SESSION['user_id'];
$actualizaciones_exitosas = 0;
$errores = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    
    // 2. Recorrer todos los datos POST para encontrar los campos de cantidad
    foreach ($_POST as $key => $valor) {
        
        // El nombre del campo de cantidad tiene el formato: qty_[id_carrito]
        if (strpos($key, 'qty_') === 0) {
            
            // Extraer el id_carrito de la clave
            $id_carrito = (int)str_replace('qty_', '', $key);
            $nueva_cantidad = (int)$valor;
            
            // 3. Validaciones básicas
            if ($id_carrito > 0 && $nueva_cantidad >= 1) {
                
                // 4. Verificar si la cantidad excede el stock disponible
                // (Se necesita una consulta JOIN para obtener el stock del producto)
                $sql_stock = "SELECT P.stock FROM Carrito C 
                              JOIN Productos P ON C.id_producto = P.id_producto
                              WHERE C.id_carrito = $id_carrito AND C.id_usuario = $id_usuario";
                
                $res_stock = $conn->query($sql_stock);
                
                if ($res_stock && $res_stock->num_rows > 0) {
                    $stock_actual = $res_stock->fetch_assoc()['stock'];

                    if ($nueva_cantidad > $stock_actual) {
                        $errores[] = "No hay suficiente stock (solo quedan $stock_actual) para el ítem con ID $id_carrito.";
                        continue; // Saltar esta actualización y seguir con el siguiente ítem
                    }

                    // 5. Ejecutar la actualización (UPDATE)
                    $sql_update = "UPDATE Carrito 
                                   SET cantidad = $nueva_cantidad 
                                   WHERE id_carrito = $id_carrito AND id_usuario = $id_usuario";
                    
                    if ($conn->query($sql_update) === TRUE) {
                        $actualizaciones_exitosas++;
                    } else {
                        $errores[] = "Fallo al actualizar el ítem $id_carrito: " . $conn->error;
                    }
                }
            }
        }
    }

    $conn->close();

    // 6. Redireccionar con el mensaje de estado
    if (!empty($errores)) {
        // Redirigir con el primer error y un indicador de múltiples fallos
        $mensaje_final = "Error(es) de actualización: " . implode(", ", $errores);
        header("Location: " . $pagina_redireccion . "?error=" . urlencode($mensaje_final));
    } elseif ($actualizaciones_exitosas > 0) {
        header("Location: " . $pagina_redireccion . "?success=" . urlencode("Carrito actualizado correctamente."));
    } else {
        header("Location: " . $pagina_redireccion . "?success=" . urlencode("No se realizaron cambios en el carrito."));
    }
    exit;

} else {
    // Si se accede directamente
    header("Location: " . $pagina_redireccion);
    exit;
}
?>