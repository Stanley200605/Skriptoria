<?php
/**
 * perfil-configuracion.php
 * Permite al cliente ver y actualizar su información personal y cambiar su contraseña.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/auth_check.php'; 
require_once '../includes/db_connection.php'; 

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name']; // Nombre de la sesión

// 1. Consulta para cargar los datos completos del usuario
$sql_datos = "SELECT nombre, apellido, email, telefono FROM Usuarios WHERE id_usuario = $user_id";
$resultado_datos = $conn->query($sql_datos);
$datos_usuario = $resultado_datos->fetch_assoc();

// Combinar Nombre y Apellido para el campo único del formulario
$nombre_completo = trim($datos_usuario['nombre'] . ' ' . $datos_usuario['apellido']);


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
    <title>Configuración | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/perfil.css"> 
    <style>
        .alert { padding: 10px; margin: 10px 0 20px 0; border-radius: 4px; font-weight: bold; }
        .success-alert { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-alert { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="top-nav-bar">
        <div class="search-box">
            <input type="text" placeholder="Buscar...">
            <i class="fas fa-search"></i>
        </div>
        
        <div class="user-actions">
            <a href="../index.php"><i class="fas fa-home" style="color: white; font-size: 25px;"></i></a>
            <a href="perfil-dashboard.php"><i class="fas fa-user" style="color: white; font-size: 25px;"></i></a>
            <a href="perfil-deseos.php"><i class="fas fa-heart" style="color: white; font-size: 25px;"></i></a>
            <a href="carrito.php"><i class="fas fa-shopping-cart" style="color: white; font-size: 25px;"></i></a>
        </div>
    </div>

    <main class="profile-container">
        <div class="main-profile-layout">
            
            <aside class="profile-sidebar">
                <div class="user-profile-summary">
                    <i class="fas fa-user-circle profile-icon"></i>
                    <h3 class="user-name"><?php echo htmlspecialchars($user_name); ?></h3>
                    <p class="user-email"><?php echo htmlspecialchars($datos_usuario['email']); ?></p>
                </div>
                
                <div class="profile-nav-menu">
                    <a href="perfil-dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="perfil-pedidos.php" class="nav-item"><i class="fas fa-box-open"></i> Mis Pedidos</a>
                    <a href="perfil-deseos.php" class="nav-item"><i class="fas fa-heart"></i> Lista de Deseos</a>
                    <a href="perfil-configuracion.php" class="nav-item active"><i class="fas fa-cog"></i> Configuración</a>
                    <a href="perfil-direcciones.php" class="nav-item"><i class="fas fa-map-marker-alt"></i> Direcciones</a>
                    <hr>
                    <a href="../actions/logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </aside>

            <section class="profile-content">
                <h1 class="page-title">Configuración de Cuenta</h1>
                <p class="page-subtitle">Actualiza tu información personal y credenciales de acceso.</p>
                
                <?php echo $mensaje_status; // Mostrar mensajes de éxito/error ?>

                <div class="settings-block">
                    <h2>Información Personal</h2>
                    <form class="settings-form" action="../actions/procesar_configuracion.php" method="POST">
                        <input type="hidden" name="action" value="update_info">

                        <div class="form-group">
                            <label for="name_full">Nombre Completo</label>
                            <input type="text" id="name_full" name="nombre_completo" 
                                   value="<?php echo htmlspecialchars($nombre_completo); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($datos_usuario['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Teléfono (Opcional)</label>
                            <input type="tel" id="phone" name="telefono" 
                                   value="<?php echo htmlspecialchars($datos_usuario['telefono']); ?>">
                        </div>
                        <button type="submit" name="btn_guardar_info" class="save-button">Guardar Cambios</button>
                    </form>
                </div>

                <div class="settings-block password-block">
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
                        <button type="submit" name="btn_cambiar_pass" class="save-button change-password-btn">Actualizar Contraseña</button>
                    </form>
                </div>
            </section>
        </div>
    </main>

    </body>
</html>