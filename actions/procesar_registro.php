<?php
/**
 * procesar_registro.php
 * Script para manejar la inserción de un nuevo cliente en la base de datos.
 *
 * NOTA: Este script no tiene estilos HTML. Su único propósito es procesar datos
 * y luego redirigir al usuario.
 */

// Incluir la conexión a la base de datos y otras funciones necesarias
require_once '../includes/db_connection.php';

// Verificar si el formulario de registro fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_registro'])) {
    
    // 1. Obtener y Sanear Datos
    // Sanear los datos de entrada para prevenir inyecciones SQL es CRUCIAL.
    // Usamos $conn->real_escape_string() para escapar caracteres especiales.
    
    $nombre_completo = $conn->real_escape_string($_POST['nombre_completo']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Dividir Nombre Completo en Nombre y Apellido (Si solo tienes un campo)
    // Esto es un ejemplo simple, puede que necesites un manejo más sofisticado.
    $partes_nombre = explode(' ', trim($nombre_completo));
    $nombre = array_shift($partes_nombre);
    $apellido = implode(' ', $partes_nombre);
    
    // 2. Validaciones Básicas
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        // Redirigir con error: campos vacíos
        // Usaríamos variables de sesión o GET para mostrar el error en el index.php
        header("Location: ../index.php?error=campos_vacios");
        exit;
    }

    if ($password !== $confirm_password) {
        // Redirigir con error: contraseñas no coinciden
        header("Location: ../index.php?error=passwords_no_coinciden");
        exit;
    }
    
    // 3. Hashear la Contraseña (Seguridad)
    // Nunca almacenes contraseñas en texto plano. password_hash() usa un algoritmo fuerte.
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // 4. Obtener el ID del Rol 'Cliente'
    // Asumimos que ya insertaste los roles: INSERT INTO Roles (id_rol, nombre_rol) VALUES (1, 'Admin'), (2, 'Cliente');
    $rol_cliente_query = "SELECT id_rol FROM Roles WHERE nombre_rol = 'Cliente' LIMIT 1";
    $resultado_rol = $conn->query($rol_cliente_query);
    
    if ($resultado_rol && $resultado_rol->num_rows > 0) {
        $rol = $resultado_rol->fetch_assoc();
        $id_rol_cliente = $rol['id_rol'];

        // 5. Preparar la Consulta de Inserción (INSERT)
        $sql = "INSERT INTO Usuarios (id_rol, nombre, apellido, email, password) 
                VALUES ('$id_rol_cliente', '$nombre', '$apellido', '$email', '$password_hashed')";
        
        // 6. Ejecutar la Consulta
        if ($conn->query($sql) === TRUE) {
            // Éxito: Registro completado
            // Puedes iniciar sesión automáticamente o redirigir al login
            header("Location: ../pages/perfil-dashboard.php");
            exit;
        } else {
            // Error en la BD (ej. email ya existe, UNIQUE KEY constraint)
            // Error code 1062 es para 'Duplicate entry'
            $error_message = ($conn->errno == 1062) ? "email_duplicado" : "error_db";
            header("Location: ../index.php?error=" . $error_message);
            exit;
        }
    } else {
        // Si no se encuentra el rol 'Cliente', algo está mal en la configuración inicial de la BD
        header("Location: ../index.php?error=rol_no_encontrado");
        exit;
    }
    
    // Cerrar la conexión (aunque se cerrará automáticamente al final del script)
    $conn->close();
} else {
    // Si alguien intenta acceder directamente al script sin enviar el formulario
    header("Location: ../index.php");
    exit;
}
?>