<?php
/**
 * procesar_carrito.php
 * Añade un producto al Carrito (tabla Carrito). Si ya existe, incrementa la cantidad.
 * Recibe el id_producto, cantidad (opcional), y la acción (add, update, remove).
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 

// La ruta de redirección por defecto
$pagina_anterior = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';

// 1. Verificar Autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=Debes iniciar sesión para usar el carrito.");
    exit;
}

$id_usuario = $_SESSION['user_id'];
$id_producto = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$cantidad = isset($_REQUEST['qty']) ? (int)$_REQUEST['qty'] : 1; // Por defecto, añade 1
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'add';

if ($id_producto <= 0 || $cantidad <= 0) {
    header("Location: " . $pagina_anterior);
    exit;
}

// 2. Comprobar stock
// Idealmente, harías una consulta SELECT stock FROM Productos WHERE id_producto... 
// y compararías $cantidad con el stock disponible. (Pendiente de implementar)

// 3. Lógica principal: Añadir o Actualizar
$sql_check = "SELECT id_carrito, cantidad FROM Carrito WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
$resultado_check = $conn->query($sql_check);

if ($resultado_check->num_rows > 0) {
    // Si el producto ya está en el carrito, ACTUALIZAR la cantidad
    $fila = $resultado_check->fetch_assoc();
    $nueva_cantidad = ($action === 'add') ? ($fila['cantidad'] + $cantidad) : $cantidad;
    
    // Asegurarse de no exceder el stock (lógica futura)

    $sql_update = "UPDATE Carrito 
                   SET cantidad = $nueva_cantidad 
                   WHERE id_carrito = " . $fila['id_carrito'];

    if ($conn->query($sql_update) === TRUE) {
        $mensaje = "Cantidad de producto actualizada en el carrito.";
        // Si viene de la lista de deseos, eliminarlo de deseos (TOGGLE)
        if (strpos($pagina_anterior, 'perfil-deseos.php') !== false) {
             $conn->query("DELETE FROM ListaDeseos WHERE id_usuario = $id_usuario AND id_producto = $id_producto");
             $mensaje = "Producto movido al carrito.";
        }
    } else {
        $mensaje = "Error al actualizar el carrito: " . $conn->error;
    }
} elseif ($action === 'add') {
    // Si NO existe y la acción es 'add', INSERTAR
    $sql_insert = "INSERT INTO Carrito (id_usuario, id_producto, cantidad) 
                   VALUES ($id_usuario, $id_producto, $cantidad)";
    
    if ($conn->query($sql_insert) === TRUE) {
        $mensaje = "Producto añadido al carrito con éxito.";
        // Si viene de la lista de deseos, eliminarlo de deseos (TOGGLE)
        if (strpos($pagina_anterior, 'perfil-deseos.php') !== false) {
             $conn->query("DELETE FROM ListaDeseos WHERE id_usuario = $id_usuario AND id_producto = $id_producto");
             $mensaje = "Producto movido al carrito.";
        }
    } else {
        $mensaje = "Error al añadir al carrito: " . $conn->error;
    }
}

$conn->close();
header("Location: ../pages/carrito.php?success=" . urlencode($mensaje));
exit;
?>