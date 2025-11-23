<?php
/**
 * logout.php
 * Script para cerrar la sesión del usuario.
 * Destruye todas las variables de sesión, las cookies de sesión y redirige al index.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

// 1. Iniciar la sesión
// Esto es necesario para poder acceder a las variables de sesión y destruirlas.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Limpiar todas las variables de sesión
// Esto desvincula la información del usuario de la sesión actual.
$_SESSION = array();

// 3. Destruir la cookie de sesión (opcional, pero buena práctica)
// Si el servidor usa cookies para mantener la sesión (lo más común), esta línea la borra.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión actual por completo
session_destroy();

// 5. Redirigir al usuario a la página de inicio (index.php)
// Añadimos un parámetro '?logout=success' por si quieres mostrar un mensaje de "Sesión cerrada con éxito" en el index.
header("Location: ../index.php?logout=success"); 
exit; // Es crucial llamar a exit después de un header Location para detener la ejecución del script.
?>