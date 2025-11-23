<?php
/**
 * procesar_configuracion.php
 * Maneja la actualización de información personal y cambio de contraseña del cliente.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 
require_once '../includes/auth_check.php'; 

$user_id = $_SESSION['user_id'];
$pagina_redireccion = '../pages/perfil-configuracion.php'; // Definición movida aquí

// Función para redirigir con mensajes (Ahora usa $pagina_redireccion del ámbito global)
function redirigir($mensaje, $tipo = 'error') {
    // Usamos 'global' para acceder a la variable definida fuera de la función
    global $pagina_redireccion; 
    
    header("Location: " . $pagina_redireccion . "?" . $tipo . "=" . urlencode($mensaje));
    exit;
}
// ----------------------------------------------------------------------------------


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // ----------------------------------------------------
    // ACCIÓN 1: ACTUALIZAR INFORMACIÓN PERSONAL
    // ----------------------------------------------------
    if ($action == 'update_info' && isset($_POST['btn_guardar_info'])) {
        
        $nombre_completo = trim($conn->real_escape_string($_POST['nombre_completo']));
        $email = $conn->real_escape_string($_POST['email']);
        $telefono = $conn->real_escape_string($_POST['telefono']);

        // Dividir nombre completo en nombre y apellido
        $partes_nombre = explode(' ', $nombre_completo, 2);
        $nombre = $partes_nombre[0];
        $apellido = isset($partes_nombre[1]) ? $partes_nombre[1] : '';

        // Consulta UPDATE
        $sql = "UPDATE Usuarios SET 
                nombre = '$nombre', 
                apellido = '$apellido', 
                email = '$email', 
                telefono = '$telefono' 
                WHERE id_usuario = $user_id";

        if ($conn->query($sql) === TRUE) {
            // Actualizar el nombre de la sesión
            $_SESSION['user_name'] = $nombre; 
            redirigir("Información personal actualizada con éxito.", 'success');
        } else {
            // Error 1062 es por entrada duplicada (ej. email ya existe)
            if ($conn->errno == 1062) {
                redirigir("El correo electrónico ya está en uso. Por favor, elige otro.", 'error');
            } else {
                redirigir("Error al actualizar la información: " . $conn->error, 'error');
            }
        }
    } 

    // ----------------------------------------------------
    // ACCIÓN 2: CAMBIAR CONTRASEÑA
    // ----------------------------------------------------
    elseif ($action == 'update_password' && isset($_POST['btn_cambiar_pass'])) {
        
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // 1. Validar que las nuevas contraseñas coincidan
        if ($new_password !== $confirm_password) {
            redirigir("La nueva contraseña y la confirmación no coinciden.", 'error');
        }

        // 2. Obtener el hash actual de la BD
        $sql_get_pass = "SELECT password FROM Usuarios WHERE id_usuario = $user_id";
        $resultado_pass = $conn->query($sql_get_pass);

        if ($resultado_pass->num_rows == 1) {
            $fila = $resultado_pass->fetch_assoc();
            $hash_actual_bd = $fila['password'];

            // 3. Verificar Contraseña Actual
            if (password_verify($current_password, $hash_actual_bd)) {
                
                // 4. Hashear la nueva contraseña
                $nuevo_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $nuevo_hash_escapado = $conn->real_escape_string($nuevo_hash);

                // 5. Actualizar la BD
                $sql_update_pass = "UPDATE Usuarios SET password = '$nuevo_hash_escapado' WHERE id_usuario = $user_id";
                
                if ($conn->query($sql_update_pass) === TRUE) {
                    redirigir("Contraseña actualizada con éxito.", 'success');
                } else {
                    redirigir("Error al actualizar la contraseña: " . $conn->error, 'error');
                }
            } else {
                redirigir("La contraseña actual ingresada es incorrecta.", 'error');
            }
        } else {
            redirigir("Usuario no encontrado.", 'error');
        }
    } 
    
    // ----------------------------------------------------
    // ACCIÓN NO RECONOCIDA
    // ----------------------------------------------------
    else {
        redirigir("Acción no reconocida.", 'error');
    }
} else {
    // Si se accede directamente sin POST
    header("Location: " . $pagina_redireccion);
    exit;
}
?>