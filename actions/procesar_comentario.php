<?php
/**
 * procesar_comentario.php
 * Script para insertar una reseña (comentario y puntuación) y actualizar el promedio del producto.
 *
 * @autor: [Tu Nombre]
 * @fecha: 22/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 

$pagina_redireccion_error = '../pages/detalle-libro.php'; 

// 1. Validar Autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $pagina_redireccion_error . "?id=" . $_POST['id_producto'] . "&error=" . urlencode("Debes iniciar sesión para dejar un comentario."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_producto']) || !isset($_POST['puntuacion'])) {
    header("Location: ../index.php");
    exit;
}

$id_usuario = $_SESSION['user_id'];
$id_producto = (int)$_POST['id_producto'];
$puntuacion = (int)$_POST['puntuacion'];
$comentario = $conn->real_escape_string(trim($_POST['comentario']));
$pagina_redireccion = $pagina_redireccion_error . "?id=" . $id_producto;


try {
    // 2. Validación de Precondiciones
    if ($puntuacion < 1 || $puntuacion > 5) {
        throw new Exception("La puntuación debe ser entre 1 y 5 estrellas.");
    }
    
    // Verificar si el usuario ya comentó este producto (UNIQUE KEY constraint)
    $sql_check = "SELECT id_calificacion FROM Calificaciones WHERE id_usuario = $id_usuario AND id_producto = $id_producto LIMIT 1";
    if ($conn->query($sql_check)->num_rows > 0) {
        throw new Exception("Ya has dejado un comentario para este producto.");
    }

    // 3. Inserción de la Calificación (Transacción)
    $conn->autocommit(false);
    
    $sql_insert = "INSERT INTO Calificaciones (id_producto, id_usuario, puntuacion, comentario) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iiss", $id_producto, $id_usuario, $puntuacion, $comentario);
    
    if (!$stmt_insert->execute()) {
        throw new Exception("Fallo al insertar la calificación: " . $stmt_insert->error);
    }
    $stmt_insert->close();
    
    // 4. Actualizar el Promedio de Calificación del Producto
    
    $sql_update_avg = "UPDATE Productos 
                       SET promedio_calificacion = (
                           SELECT AVG(puntuacion) FROM Calificaciones WHERE id_producto = ?
                       )
                       WHERE id_producto = ?";
                       
    $stmt_update = $conn->prepare($sql_update_avg);
    $stmt_update->bind_param("ii", $id_producto, $id_producto);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Fallo al actualizar el promedio: " . $stmt_update->error);
    }
    $stmt_update->close();

    // COMMIT
    $conn->commit();
    $mensaje = "Tu comentario y calificación han sido publicados con éxito.";
    header("Location: " . $pagina_redireccion . "&success=" . urlencode($mensaje));
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: " . $pagina_redireccion . "&error=" . urlencode($e->getMessage()));
} finally {
    $conn->autocommit(true);
    $conn->close();
}
exit;
?>