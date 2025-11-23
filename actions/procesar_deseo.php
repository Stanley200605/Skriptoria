<?php
/**
 * procesar_deseo.php
 * Añade o elimina un producto de la Lista de Deseos (tabla ListaDeseos).
 * Recibe el id_producto y redirige a la página anterior o al login si no hay sesión.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 

// 1. Verificar Autenticación
if (!isset($_SESSION['user_id'])) {
    // Si el usuario no está logueado, redirigir al index con mensaje de error
    header("Location: ../index.php?error=Inicia sesión para agregar a favoritos");
    exit;
}

$id_usuario = $_SESSION['user_id'];
$id_producto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagina_anterior = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';

if ($id_producto <= 0) {
    header("Location: $pagina_anterior");
    exit;
}

// 2. Comprobar si el producto YA está en la lista de deseos
$sql_check = "SELECT id_deseo FROM ListaDeseos 
              WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
$resultado_check = $conn->query($sql_check);

if ($resultado_check->num_rows > 0) {
    // 3. Si ya existe, ELIMINAR de la lista (Toggle OFF)
    $sql_delete = "DELETE FROM ListaDeseos 
                   WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
    
    if ($conn->query($sql_delete) === TRUE) {
        $mensaje = "Producto eliminado de tus favoritos.";
    } else {
        $mensaje = "Error al eliminar de favoritos: " . $conn->error;
    }
} else {
    // 4. Si NO existe, AÑADIR a la lista (Toggle ON)
    $sql_insert = "INSERT INTO ListaDeseos (id_usuario, id_producto) 
                   VALUES ($id_usuario, $id_producto)";
    
    if ($conn->query($sql_insert) === TRUE) {
        $mensaje = "Producto añadido a tus favoritos con éxito.";
    } else {
        $mensaje = "Error al añadir a favoritos: " . $conn->error;
    }
}

$conn->close();

// 5. Redirigir a la página anterior con mensaje de estado
header("Location: " . $pagina_anterior . "?success=" . urlencode($mensaje));
exit;
?>