<?php
/**
 * admin-perfil.php
 * Permite al Administrador ver y actualizar su información de contacto y contraseña.
 *
 * @autor: [Tu Nombre]
 * @fecha: 16/11/2025
 */

session_start();
require_once '../includes/auth_check.php'; 
require_once '../includes/db_connection.php'; 

// 1. Asegurar que SÓLO el administrador pueda acceder y obtener su ID
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php?error=Acceso denegado.");
    exit;
}
$user_id = $_SESSION['user_id'];
$user_name_session = $_SESSION['user_name']; // Nombre de la sesión (usado en el sidebar)

// 2. Consulta para cargar los datos completos del Administrador
$sql_datos = "SELECT nombre, apellido, email, telefono, password FROM Usuarios WHERE id_usuario = $user_id AND id_rol = 1";
$resultado_datos = $conn->query($sql_datos);

if (!$resultado_datos || $resultado_datos->num_rows == 0) {
    // Esto no debería suceder si está logueado como Admin, pero es una buena seguridad.
    header("Location: ../actions/logout.php?error=Perfil de administrador no encontrado.");
    exit;
}

$datos_admin = $resultado_datos->fetch_assoc();
$nombre_completo = trim($datos_admin['nombre'] . ' ' . $datos_admin['apellido']);


// Lógica para mostrar mensajes de estado después de un UPDATE
$mensaje_status = '';
if (isset($_GET['success'])) {
    $mensaje_status = '<div class="alert success-alert">✅ ¡Éxito! ' . htmlspecialchars($_GET['success']) . '</div>';
} elseif (isset($_GET['error'])) {
    $mensaje_status = '<div class="alert error-alert">❌ Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil de Administrador | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .alert { padding: 10px; margin: 10px 0 20px 0; border-radius: 4px; font-weight: bold; }
        .success-alert { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-alert { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <div class="logo-area">SKRIPTORIA <span class="admin-label">ADMIN</span></div>
        <nav class="main-nav">
            <a href="../admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin-pedidos.php" class="nav-item"><i class="fas fa-box-open"></i> Pedidos</a>
            <a href="admin-productos.php" class="nav-item"><i class="fas fa-book"></i> Productos</a>
            <a href="admin-clientes.php" class="nav-item"><i class="fas fa-users"></i> Clientes</a>
            <a href="admin-reportes.php" class="nav-item"><i class="fas fa-chart-line"></i> Reportes</a>
            <a href="admin-perfil.php" class="nav-item active"><i class="fas fa-user-circle"></i> Mi Perfil</a>
        </nav>
        <div class="logout-area">
            <a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </aside>

    <div class="admin-main-content">
        
        <header class="admin-header"></header>

        <section class="admin-content-area">
            <h1 class="content-header-title">Configuración de Mi Perfil</h1>

            <?php echo $mensaje_status; // Mostrar mensajes de éxito/error ?>

            <div class="profile-settings-block">
                <h2>Información de Contacto</h2>
                <form class="settings-form" action="../actions/procesar_configuracion.php" method="POST">
                    <input type="hidden" name="action" value="update_info">
                    
                    <div class="form-group">
                        <label for="admin-name">Nombre Completo</label>
                        <input type="text" id="admin-name" name="nombre_completo" 
                               value="<?php echo htmlspecialchars($nombre_completo); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="admin-email">Correo Electrónico</label>
                        <input type="email" id="admin-email" name="email" 
                               value="<?php echo htmlspecialchars($datos_admin['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="admin-phone">Teléfono de Contacto (Opcional)</label>
                        <input type="tel" id="admin-phone" name="telefono" 
                               value="<?php echo htmlspecialchars($datos_admin['telefono']); ?>">
                    </div>
                    <button type="submit" name="btn_guardar_info" class="control-button primary">Guardar Información</button>
                </form>
            </div>

            <div class="profile-settings-block password-block">
                <h2>Cambiar Contraseña</h2>
                <form class="settings-form" action="../actions/procesar_configuracion.php" method="POST">
                    <input type="hidden" name="action" value="update_password">

                    <div class="form-group">
                        <label for="current-password">Contraseña Actual</label>
                        <input type="password" id="current-password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new-password">Nueva Contraseña</label>
                        <input type="password" id="new-password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirm-password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="btn_cambiar_pass" class="control-button primary change-password-btn">Actualizar Contraseña</button>
                </form>
            </div>

        </section>

    </div>

</body>
</html>