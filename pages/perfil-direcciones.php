<?php
/**
 * perfil-direcciones.php
 * Permite al cliente ver, añadir y eliminar direcciones de envío.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/auth_check.php'; 
require_once '../includes/db_connection.php'; 

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// 1. Carga de datos del perfil
$email_query = "SELECT email FROM Usuarios WHERE id_usuario = $user_id";
$email_result = $conn->query($email_query);
$user_email = ($email_result && $email_result->num_rows > 0) ? $email_result->fetch_assoc()['email'] : 'Correo no disponible';

// 2. Carga de direcciones guardadas
$sql_direcciones = "SELECT * FROM Direcciones WHERE id_usuario = $user_id ORDER BY predeterminada DESC, id_direccion DESC";
$resultado_direcciones = $conn->query($sql_direcciones);
$direcciones = $resultado_direcciones ? $resultado_direcciones->fetch_all(MYSQLI_ASSOC) : [];

// Lógica de mensajes
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
    <title>Direcciones | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/perfil.css"> 
    <style>
        .alert { padding: 10px; margin: 10px 0 20px 0; border-radius: 4px; font-weight: bold; }
        .success-alert { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-alert { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .address-card { border: 1px solid #e0d9d4; padding: 20px; margin-bottom: 15px; border-radius: 6px; position: relative; background-color: #fff; }
        .address-card.default-address { border: 2px solid #a1885f; background-color: #fcf8f3; }
        .default-badge { position: absolute; top: 0; right: 0; background-color: #a1885f; color: white; padding: 5px 10px; font-size: 0.75rem; border-radius: 0 6px 0 6px; font-weight: bold; }
        .address-actions a { margin-right: 15px; color: #a1885f; font-size: 0.9rem; }
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
                    <p class="user-email"><?php echo htmlspecialchars($user_email); ?></p>
                </div>
                
                <div class="profile-nav-menu">
                    <a href="perfil-dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="perfil-pedidos.php" class="nav-item"><i class="fas fa-box-open"></i> Mis Pedidos</a>
                    <a href="perfil-deseos.php" class="nav-item"><i class="fas fa-heart"></i> Lista de Deseos</a>
                    <a href="perfil-configuracion.php" class="nav-item"><i class="fas fa-cog"></i> Configuración</a>
                    <a href="perfil-direcciones.php" class="nav-item active"><i class="fas fa-map-marker-alt"></i> Direcciones</a>
                    <hr>
                    <a href="../actions/logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </aside>

            <section class="profile-content">
                <h1 class="page-title">Gestión de Direcciones</h1>
                <p class="page-subtitle">Añade, edita o elimina direcciones de envío y facturación.</p>
                
                <?php echo $mensaje_status; ?>

                <div class="addresses-list-container">
                    <h2 class="address-list-title">Direcciones Guardadas</h2>
                    
                    <?php if (!empty($direcciones)): ?>
                        <?php foreach ($direcciones as $dir): ?>
                            <div class="address-card <?php echo $dir['predeterminada'] ? 'default-address' : ''; ?>">
                                <?php if ($dir['predeterminada']): ?>
                                    <span class="default-badge">PREDETERMINADA</span>
                                <?php endif; ?>
                                <p class="address-name"><?php echo htmlspecialchars($dir['alias']); ?></p>
                                <p class="address-detail"><?php echo htmlspecialchars($dir['calle_numero']); ?>, <?php echo htmlspecialchars($dir['colonia']); ?>, C.P. <?php echo htmlspecialchars($dir['codigo_postal']); ?></p>
                                <p class="address-detail"><?php echo htmlspecialchars($dir['ciudad']); ?>, <?php echo htmlspecialchars($dir['pais']); ?></p>
                                
                                <div class="address-actions">
                                    <?php if (!$dir['predeterminada']): ?>
                                        <a href="../actions/procesar_direccion.php?action=set_default&id=<?php echo $dir['id_direccion']; ?>" class="set-default-btn">Establecer Predeterminada</a>
                                    <?php endif; ?>
                                    <a href="#" class="edit-btn" data-id="<?php echo $dir['id_direccion']; ?>">Editar</a>
                                    
                                    <a href="../actions/procesar_direccion.php?action=delete&id=<?php echo $dir['id_direccion']; ?>" 
                                       onclick="return confirm('¿Estás seguro de eliminar esta dirección?')" 
                                       class="delete-btn">Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aún no has guardado ninguna dirección. Usa el formulario de abajo para añadir una.</p>
                    <?php endif; ?>
                </div>

                <hr class="section-divider">

                <div class="settings-block new-address-block">
                    <h2>Añadir Nueva Dirección</h2>
                    <form class="settings-form address-form-grid" action="../actions/procesar_direccion.php" method="POST">
                        <input type="hidden" name="action" value="add_address">
                        
                        <div class="form-group">
                            <label for="alias">Alias de Dirección (Ej. Casa, Oficina)</label>
                            <input type="text" id="alias" name="alias" required>
                        </div>
                        <div class="form-group">
                            <label for="calle">Calle y Número</label>
                            <input type="text" id="calle" name="calle_numero" required>
                        </div>
                        <div class="form-group">
                            <label for="colonia">Colonia</label>
                            <input type="text" id="colonia" name="colonia" required>
                        </div>
                         <div class="form-group">
                            <label for="cp">Código Postal</label>
                            <input type="text" id="cp" name="codigo_postal" required>
                        </div>
                        <div class="form-group">
                            <label for="ciudad">Ciudad</label>
                            <input type="text" id="ciudad" name="ciudad" required>
                        </div>
                        <div class="form-group">
                            <label for="pais">País</label>
                            <input type="text" id="pais" name="pais" required>
                        </div>
                        <button type="submit" name="btn_guardar_direccion" class="save-button full-width-btn">Guardar Dirección</button>
                    </form>
                </div>
            </section>
        </div>
    </main>

    </body>
</html>