<?php
/**
 * perfil-dashboard.php
 * Vista principal del Dashboard del Cliente.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
// 1. INCLUIR LA GUARDIA DE SEGURIDAD
require_once '../includes/auth_check.php'; 

// 2. INCLUIR LA CONEXIÓN A LA BD (Necesaria para cargar datos)
require_once '../includes/db_connection.php'; 

// *************************************************************************
// LÓGICA DE CARGA DE DATOS DEL CLIENTE
// *************************************************************************

// Recuperar datos de la sesión (garantizados por auth_check.php)
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name']; 

// Lógica para cargar el email del usuario desde la BD
$email_query = "SELECT email FROM Usuarios WHERE id_usuario = $user_id";
$email_result = $conn->query($email_query);
$user_email = ($email_result && $email_result->num_rows > 0) ? $email_result->fetch_assoc()['email'] : 'Correo no disponible';

// ----------------------------------------------------
// 3. CARGA DINÁMICA DE CONTADORES (KPIs)
// ----------------------------------------------------

// Pedidos Completados (Asumiendo que 'Entregado' es el estado final)
$sql_pedidos_completados = "SELECT COUNT(id_pedido) AS total FROM Pedidos WHERE id_usuario = $user_id AND estado = 'Entregado'";
$pedidos_completados = $conn->query($sql_pedidos_completados)->fetch_assoc()['total'];

// Libros en Deseos
$sql_libros_deseos = "SELECT COUNT(id_deseo) AS total FROM ListaDeseos WHERE id_usuario = $user_id";
$libros_en_deseos = $conn->query($sql_libros_deseos)->fetch_assoc()['total'];

// Reseñas Enviadas
$sql_resenas_enviadas = "SELECT COUNT(id_calificacion) AS total FROM Calificaciones WHERE id_usuario = $user_id";
$resenas_enviadas = $conn->query($sql_resenas_enviadas)->fetch_assoc()['total'];


// ----------------------------------------------------
// 4. CARGA DINÁMICA DEL ÚLTIMO PEDIDO
// ----------------------------------------------------
$sql_ultimo_pedido = "SELECT 
                        id_pedido, 
                        fecha_pedido, 
                        total, 
                        estado 
                      FROM Pedidos 
                      WHERE id_usuario = $user_id 
                      ORDER BY fecha_pedido DESC 
                      LIMIT 1";
                      
$resultado_ultimo = $conn->query($sql_ultimo_pedido);
$ultimo_pedido = [
    'id' => null,
    'fecha' => 'N/A',
    'total' => 'N/A',
    'estado' => 'Ninguno'
];

if ($resultado_ultimo && $resultado_ultimo->num_rows > 0) {
    $data = $resultado_ultimo->fetch_assoc();
    $ultimo_pedido = [
        'id' => '#' . $data['id_pedido'],
        'fecha' => date('d/m/Y', strtotime($data['fecha_pedido'])),
        'total' => '$' . number_format($data['total'], 2) . ' MXN',
        'estado' => $data['estado']
    ];
}

// Mapeo simple de estado a clase CSS (Asegúrate de tener estas clases en perfil.css)
function get_status_class($estado) {
    switch ($estado) {
        case 'Enviado': return 'status-shipped';
        case 'Entregado': return 'status-delivered';
        case 'Procesando': return 'status-processing';
        case 'Pendiente': return 'status-pending';
        default: return '';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/perfil.css"> 
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
                    <a href="perfil-dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="perfil-pedidos.php" class="nav-item"><i class="fas fa-box-open"></i> Mis Pedidos</a>
                    <a href="perfil-deseos.php" class="nav-item"><i class="fas fa-heart"></i> Lista de Deseos</a>
                    <a href="perfil-configuracion.php" class="nav-item"><i class="fas fa-cog"></i> Configuración</a>
                    <a href="perfil-direcciones.php" class="nav-item"><i class="fas fa-map-marker-alt"></i> Direcciones</a>
                    <hr>
                    <a href="../actions/logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </aside>

            <section class="profile-content">
                <h1 class="page-title">Bienvenido, <?php echo htmlspecialchars($user_name); ?></h1>
                <p class="page-subtitle">Panel de control de tu cuenta de librería.</p>
                
                <div class="data-cards-grid">
                    <div class="profile-card order-count">
                        <i class="fas fa-box"></i>
                        <h4>Pedidos Completados</h4>
                        <p class="big-number"><?php echo $pedidos_completados; ?></p> 
                    </div>
                    <div class="profile-card wishlist-count">
                        <i class="fas fa-heart"></i>
                        <h4>Libros en Deseos</h4>
                        <p class="big-number"><?php echo $libros_en_deseos; ?></p>
                    </div>
                    <div class="profile-card reviews-count">
                        <i class="fas fa-star"></i>
                        <h4>Reseñas Enviadas</h4>
                        <p class="big-number"><?php echo $resenas_enviadas; ?></p> 
                    </div>
                </div>
                
                <div class="recent-orders-block">
                    <h2>Último Pedido</h2>
                    <div class="order-summary">
                        <?php if ($ultimo_pedido['id']): ?>
                            <p>Pedido <?php echo $ultimo_pedido['id']; ?> - <strong>Fecha:</strong> <?php echo $ultimo_pedido['fecha']; ?></p>
                            <p>Total: <strong><?php echo $ultimo_pedido['total']; ?></strong> | Estado: <span class="<?php echo get_status_class($ultimo_pedido['estado']); ?>"><?php echo $ultimo_pedido['estado']; ?></span></p>
                            <a href="perfil-pedidos.php?view=<?php echo str_replace('#', '', $ultimo_pedido['id']); ?>" id="open-receipt" class="view-order-link">Ver Detalles</a>
                        <?php else: ?>
                            <p>Aún no has realizado pedidos.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    
    <script>
        // Lógica para el Ticket Modal (Mantener el JS para futuras implementaciones)
        var receiptModal = document.getElementById("receipt-modal");
        var receiptBtn = document.getElementById("open-receipt");
        var receiptClose = document.querySelector(".receipt-close"); 
        
        var loginModal = document.getElementById("login-modal");
        
        if (receiptBtn && receiptModal) { 
            receiptBtn.onclick = function(e) {
                e.preventDefault(); 
                // En lugar de abrir el modal con JS, redirigiremos a la página de pedidos con el ID para ver el detalle
                // Para mantener el diseño, asumimos que "Ver Detalles" ahora es un enlace PHP directo a la página de pedidos
                window.location.href = receiptBtn.href;
            }
        }
        
        // ... (El resto de tu lógica JS se mantiene) ...
        window.onclick = function(event) {
            if (loginModal && event.target == loginModal) {
                loginModal.style.display = "none";
            }
            if (receiptModal && event.target == receiptModal) {
                receiptModal.style.display = "none";
            }
        }
    </script>
</body>
</html>