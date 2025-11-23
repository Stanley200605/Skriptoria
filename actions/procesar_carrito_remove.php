<?php
/**
 * procesar_carrito_remove.php
 * Elimina un producto del carrito de compras por su ID de carrito (id_carrito).
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 

// La ruta de redirección es siempre al carrito
$pagina_redireccion = '../pages/carrito.php';

// 1. Verificar Autenticación y ID
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=Debes iniciar sesión para modificar el carrito.");
    exit;
}

$id_usuario = $_SESSION['user_id'];
$id_carrito = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_carrito <= 0) {
    header("Location: " . $pagina_redireccion . "?error=Item de carrito no especificado.");
    exit;
}

// 2. Ejecutar la consulta DELETE
// Nos aseguramos de que solo el dueño del carrito pueda eliminar el ítem
$sql_delete = "DELETE FROM Carrito 
               WHERE id_carrito = $id_carrito AND id_usuario = $id_usuario";

if ($conn->query($sql_delete) === TRUE) {
    $mensaje = "Producto eliminado del carrito.";
    header("Location: " . $pagina_redireccion . "?success=" . urlencode($mensaje));
} else {
    $mensaje = "Error al eliminar el producto del carrito: " . $conn->error;
    header("Location: " . $pagina_redireccion . "?error=" . urlencode($mensaje));
}

$conn->close();
exit;
?>