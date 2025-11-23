<?php
/**
 * procesar_estado.php
 * Script para actualizar el estado de un pedido desde el panel de administración.
 *
 * @autor: [Tu Nombre]
 * @fecha: 16/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 
require_once '../includes/auth_check.php'; 

$pagina_redireccion = '../admin.php';

// Aseguramos que solo el administrador pueda ejecutar este script
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php?error=Acceso denegado.");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_actualizar_estado'])) {
    
    $id_pedido = (int)$_POST['id_pedido'];
    $nuevo_estado = $conn->real_escape_string($_POST['nuevo_estado']);

    if ($id_pedido <= 0) {
        header("Location: " . $pagina_redireccion . "?error=ID de pedido no válido.");
        exit;
    }

    // Consulta UPDATE para cambiar el estado
    $sql_update = "UPDATE Pedidos 
                   SET estado = ?, 
                       fecha_actualizacion = NOW() 
                   WHERE id_pedido = ?";
                   
    $stmt = $conn->prepare($sql_update);
    // 'si' -> string, integer
    $stmt->bind_param("si", $nuevo_estado, $id_pedido);

    if ($stmt->execute()) {
        $mensaje = "Estado del pedido #$id_pedido actualizado a '$nuevo_estado' con éxito.";
        $stmt->close();
        $conn->close();
        header("Location: " . $pagina_redireccion . "?success=" . urlencode($mensaje));
    } else {
        $mensaje = "Error al actualizar el estado: " . $stmt->error;
        $stmt->close();
        $conn->close();
        header("Location: " . $pagina_redireccion . "?error=" . urlencode($mensaje));
    }

} else {
    header("Location: " . $pagina_redireccion);
    exit;
}
?>