<?php
/**
 * procesar_login.php
 * Gestiona la autenticación del usuario verificando email y contraseña contra la BD.
 * * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

// 1. Iniciar la sesión PHP y Conexión a la BD
session_start(); // Inicia la sesión al principio para usar $_SESSION
require_once '../includes/db_connection.php'; // Usa la ruta correcta según tu jerarquía

// Función para redirigir con mensajes de error
function redirigir_login($mensaje_error) {
    // Redireccionamos de vuelta al index para mostrar el modal de login
    header("Location: ../index.php?login_error=" . $mensaje_error . "#login-modal");
    exit;
}

// 2. Verificar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_login'])) {
    
    // 3. Obtener y Sanear Datos
    // Sanear el email es importante para la consulta SQL
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; 
    
    // 4. Consulta para obtener el usuario
    // Seleccionamos todos los datos que necesitamos, incluyendo el hash de la contraseña y el id_rol.
    $sql = "SELECT U.id_usuario, U.nombre, U.password, R.nombre_rol 
            FROM Usuarios U
            JOIN Roles R ON U.id_rol = R.id_rol
            WHERE U.email = '$email'";
            
    $resultado = $conn->query($sql);
    
    // 5. Verificar la existencia del usuario
    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        // 6. Verificar la Contraseña
        // password_verify() es la función estándar de PHP para comparar el texto plano con el hash.
        if (password_verify($password, $usuario['password'])) {
            
            // ÉXITO: Contraseña correcta
            
            // 7. Iniciar Sesión (Almacenar datos del usuario en $_SESSION)
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $usuario['id_usuario'];
            $_SESSION['user_name'] = $usuario['nombre'];
            $_SESSION['user_role'] = $usuario['nombre_rol']; // 'Cliente' o 'Admin'
            
            // 8. Redirección basada en el Rol (Dashboard)
            if ($usuario['nombre_rol'] === 'Admin') {
                // Redirigir al Dashboard del Administrador
                header("Location: ../admin.php"); 
            } else {
                // Redirigir al Dashboard del Cliente
                header("Location: ../pages/perfil-dashboard.php"); 
            }
            $conn->close();
            exit;
            
        } else {
            // ERROR: Contraseña incorrecta
            redirigir_login("credenciales_invalidas");
        }
        
    } else {
        // ERROR: Usuario no encontrado (Email inexistente)
        redirigir_login("credenciales_invalidas");
    }
    
    $conn->close();
} else {
    // Acceso directo o formulario no enviado
    header("Location: ../index.php");
    exit;
}
?>