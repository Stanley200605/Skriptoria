<?php
/**
 * auth_check.php
 * Verifica si el usuario ha iniciado sesión. Si no, lo redirige al index.
 * También verifica si el usuario tiene un rol específico (opcional, para admin).
 * * NOTA: Este archivo debe incluirse con 'require_once' al inicio de CADA página privada.
 */

// Iniciar la sesión si aún no se ha iniciado.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificar si la sesión está activa
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    
    // Si NO está logueado, redirigir al index (página pública)
    // Se puede añadir un mensaje de error si se desea: index.php?error=acceso_denegado
    header("Location: index.php?error=sesion_requerida");
    // header("Location: ../index.php"); 
    exit;
}

/**
 * Función opcional para verificar roles
 * Uso: verificar_rol(['Admin'], '../index.php');
 */
function verificar_rol($roles_permitidos, $pagina_denegada) {
    if (!in_array($_SESSION['user_role'], $roles_permitidos)) {
        // Si el rol del usuario NO está en la lista de roles permitidos, redirigir
        header("Location: " . $pagina_denegada);
        exit;
    }
}
?>