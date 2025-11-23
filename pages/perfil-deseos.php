<?php
/**
 * perfil-deseos.php
 * Muestra la Lista de Deseos del cliente (tabla ListaDeseos).
 * Permite eliminar productos y moverlos al carrito.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

// 1. GUARDA DE SEGURIDAD Y CONEXIÓN
require_once '../includes/auth_check.php'; 
require_once '../includes/db_connection.php'; 

// La guarda ya nos garantiza que el usuario está logueado
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// 2. Obtener el email del usuario (para el sidebar)
$email_query = "SELECT email FROM Usuarios WHERE id_usuario = $user_id";
$email_result = $conn->query($email_query);
$user_email = ($email_result && $email_result->num_rows > 0) ? $email_result->fetch_assoc()['email'] : 'Correo no disponible';

// 3. Consulta para obtener los productos en la lista de deseos
$sql_deseos = "SELECT 
                LD.id_deseo, 
                P.id_producto, 
                P.titulo, 
                P.autor, 
                P.precio, 
                P.stock,
                P.portada_url 
              FROM ListaDeseos LD
              JOIN Productos P ON LD.id_producto = P.id_producto
              WHERE LD.id_usuario = $user_id
              ORDER BY LD.fecha_agregado DESC";
              
$resultado_deseos = $conn->query($sql_deseos);
$deseos = [];
if ($resultado_deseos) {
    while ($fila = $resultado_deseos->fetch_assoc()) {
        $deseos[] = $fila;
    }
}

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
    <title>Lista de Deseos | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/perfil.css"> 
    <style>
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; font-weight: bold; }
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
                    <p class="user-email"><?php echo htmlspecialchars($user_email); ?></p>
                </div>
                
                <div class="profile-nav-menu">
                    <a href="perfil-dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="perfil-pedidos.php" class="nav-item"><i class="fas fa-box-open"></i> Mis Pedidos</a>
                    <a href="perfil-deseos.php" class="nav-item active"><i class="fas fa-heart"></i> Lista de Deseos</a>
                    <a href="perfil-configuracion.php" class="nav-item"><i class="fas fa-cog"></i> Configuración</a>
                    <a href="perfil-direcciones.php" class="nav-item"><i class="fas fa-map-marker-alt"></i> Direcciones</a>
                    <hr>
                    <a href="../actions/logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </aside>

            <section class="profile-content">
                <h1 class="page-title">Mi Lista de Deseos</h1>
                <p class="page-subtitle">Libros que has guardado para comprar más tarde.</p>
                
                <?php echo $mensaje_status; ?>

                <div class="wishlist-block">
                    <table class="wishlist-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deseos)): ?>
                                <?php foreach ($deseos as $item): 
                                    $disponible = ($item['stock'] > 0);
                                    $estado_texto = $disponible ? 'Disponible' : ($item['stock'] <= 5 && $item['stock'] > 0 ? 'Pocas Unidades' : 'Agotado');
                                    $estado_class = $disponible ? 'status-available' : ($item['stock'] == 0 ? 'status-out-of-stock' : 'status-low-stock');
                                ?>
                                <tr>
                                    <td class="product-info">
                                        <a href="detalle-libro.php?id=<?php echo $item['id_producto']; ?>">
                                            <img src="../<?php echo htmlspecialchars($item['portada_url']); ?>" alt="<?php echo htmlspecialchars($item['titulo']); ?>">
                                        </a>
                                        <div>
                                            <h5 class="book-title"><?php echo htmlspecialchars($item['titulo']); ?></h5>
                                            <p class="book-author"><?php echo htmlspecialchars($item['autor']); ?></p>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['precio'], 2); ?> MXN</td>
                                    <td><span class="<?php echo $estado_class; ?>"><?php echo $estado_texto; ?></span></td>
                                    <td class="actions">
                                        <a href="../actions/procesar_carrito.php?id=<?php echo $item['id_producto']; ?>&action=add">
                                            <button class="add-to-cart-btn" title="Mover al Carrito" <?php echo !$disponible ? 'disabled' : ''; ?>>
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                        </a>
                                        
                                        <a href="../actions/procesar_deseo.php?id=<?php echo $item['id_producto']; ?>">
                                            <button type="button" class="remove-btn" title="Eliminar de Deseos"><i class="fas fa-trash-alt"></i></button>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px;">
                                        Tu lista de deseos está vacía. ¡Explora el <a href="../index.php">catálogo</a>!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    </body>
</html>