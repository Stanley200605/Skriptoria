<?php
/**
 * procesar_direccion.php
 * Maneja las operaciones de creación, eliminación y establecimiento como predeterminada 
 * de las direcciones del cliente (tabla Direcciones).
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 
require_once '../includes/auth_check.php'; 

$user_id = $_SESSION['user_id'];
$pagina_redireccion = '../pages/perfil-direcciones.php';

// Función para redirigir con mensajes
function redirigir($mensaje, $tipo = 'error') {
    global $pagina_redireccion; 
    header("Location: " . $pagina_redireccion . "?" . $tipo . "=" . urlencode($mensaje));
    exit;
}

// ----------------------------------------------------
// MANEJO DE ACCIONES (POST para Añadir, GET para Eliminar/Default)
// ----------------------------------------------------

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$conn->autocommit(false); // Iniciar transacción para Set Default

if ($_SERVER["REQUEST_METHOD"] == "POST" && $action == 'add_address') {
    
    // --- ACCIÓN 1: AÑADIR DIRECCIÓN (INSERT) ---
    
    $alias = $conn->real_escape_string($_POST['alias']);
    $calle_numero = $conn->real_escape_string($_POST['calle_numero']);
    $colonia = $conn->real_escape_string($_POST['colonia']);
    $codigo_postal = $conn->real_escape_string($_POST['codigo_postal']);
    $ciudad = $conn->real_escape_string($_POST['ciudad']);
    $pais = $conn->real_escape_string($_POST['pais']);

    // Comprobar si es la primera dirección para hacerla predeterminada
    $sql_count = "SELECT COUNT(id_direccion) AS total FROM Direcciones WHERE id_usuario = $user_id";
    $es_primera = ($conn->query($sql_count)->fetch_assoc()['total'] == 0);
    $predeterminada = $es_primera ? 1 : 0;

    $sql_insert = "INSERT INTO Direcciones (id_usuario, alias, calle_numero, colonia, codigo_postal, ciudad, pais, predeterminada) 
                   VALUES ($user_id, '$alias', '$calle_numero', '$colonia', '$codigo_postal', '$ciudad', '$pais', $predeterminada)";

    if ($conn->query($sql_insert)) {
        $conn->commit();
        redirigir("Nueva dirección guardada con éxito.", 'success');
    } else {
        $conn->rollback();
        redirigir("Error al guardar la dirección: " . $conn->error, 'error');
    }

} elseif ($action == 'delete') {
    
    // --- ACCIÓN 2: ELIMINAR DIRECCIÓN (DELETE) ---
    
    $id_direccion = (int)$_GET['id'];

    // Evitar eliminar la dirección si es la única predeterminada (aunque la BD lo permitiría, es mejor prevenir)
    $sql_check = "SELECT predeterminada FROM Direcciones WHERE id_direccion = $id_direccion AND id_usuario = $user_id";
    $res_check = $conn->query($sql_check);
    if (!$res_check || $res_check->num_rows == 0) {
        redirigir("Dirección no encontrada.", 'error');
    }
    
    $dir = $res_check->fetch_assoc();
    if ($dir['predeterminada']) {
        redirigir("No se puede eliminar la dirección predeterminada. ¡Establece otra como predeterminada primero!", 'error');
    }

    $sql_delete = "DELETE FROM Direcciones WHERE id_direccion = $id_direccion AND id_usuario = $user_id";

    if ($conn->query($sql_delete)) {
        $conn->commit();
        redirigir("Dirección eliminada correctamente.", 'success');
    } else {
        $conn->rollback();
        redirigir("Error al eliminar la dirección: " . $conn->error, 'error');
    }
    
} elseif ($action == 'set_default') {
    
    // --- ACCIÓN 3: ESTABLECER PREDETERMINADA (UPDATE en Transacción) ---
    
    $id_direccion = (int)$_GET['id'];
    
    // 1. Desactivar todas las direcciones predeterminadas del usuario
    $sql_reset = "UPDATE Direcciones SET predeterminada = 0 WHERE id_usuario = $user_id";
    if ($conn->query($sql_reset) === FALSE) {
        $conn->rollback();
        redirigir("Error al restablecer la dirección predeterminada.", 'error');
    }

    // 2. Activar la dirección seleccionada
    $sql_set = "UPDATE Direcciones SET predeterminada = 1 WHERE id_direccion = $id_direccion AND id_usuario = $user_id";
    if ($conn->query($sql_set) === TRUE) {
        $conn->commit();
        redirigir("Dirección establecida como predeterminada.", 'success');
    } else {
        $conn->rollback();
        redirigir("Error al establecer la nueva dirección predeterminada: " . $conn->error, 'error');
    }
} else {
    redirigir("Acción no válida.", 'error');
}

$conn->autocommit(true); // Restaurar autocommit
$conn->close();
exit;
?>