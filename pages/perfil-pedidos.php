<?php
/**
 * perfil-pedidos.php
 * Muestra el historial de pedidos del cliente, cargando datos desde la tabla Pedidos.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/auth_check.php'; 
require_once '../includes/db_connection.php'; 

// La guarda nos asegura que estas variables existen.
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// 2. Obtener el email del usuario (para el sidebar)
$email_query = "SELECT email FROM Usuarios WHERE id_usuario = $user_id";
$email_result = $conn->query($email_query);
$user_email = ($email_result && $email_result->num_rows > 0) ? $email_result->fetch_assoc()['email'] : 'Correo no disponible';

// 3. Consulta para obtener el historial de pedidos
$sql_pedidos = "SELECT 
                    id_pedido, 
                    fecha_pedido, 
                    total, 
                    estado 
                FROM Pedidos 
                WHERE id_usuario = $user_id
                ORDER BY fecha_pedido DESC"; 
              
$resultado_pedidos = $conn->query($sql_pedidos);
$pedidos = [];
if ($resultado_pedidos) {
    while ($fila = $resultado_pedidos->fetch_assoc()) {
        $pedidos[] = $fila;
    }
}

// Lógica para mostrar mensajes
$mensaje_status = '';
if (isset($_GET['success'])) {
    $mensaje_status = '<div class="alert success-alert">✅ ¡Éxito! ' . htmlspecialchars($_GET['success']) . '</div>';
} elseif (isset($_GET['error'])) {
    $mensaje_status = '<div class="alert error-alert">❌ Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}

/** Función para mapear el estado de la BD a una clase CSS */
function get_status_class($estado) {
    switch ($estado) {
        case 'Enviado': return 'status-shipped';
        case 'Entregado': return 'status-delivered';
        case 'Procesando': return 'status-processing';
        case 'Pendiente': return 'status-pending';
        case 'Cancelado': return 'status-cancelled';
        default: return 'status-unknown';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/perfil.css"> 
    <style>
        /* Estilos de modales y alertas */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close-btn:hover, .close-btn:focus { color: #000; text-decoration: none; cursor: pointer; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .receipt-table th, .receipt-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; font-weight: bold; }
        .success-alert { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-alert { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        /* Estilos de la tabla de estado */
        .orders-table .status-shipped { background-color: #FFC107; color: #332b21; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .orders-table .status-delivered { background-color: #179917; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .orders-table .status-processing { background-color: #2196F3; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .orders-table .status-pending { background-color: #e0d9d4; color: #4a3b30; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
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
                    <a href="perfil-pedidos.php" class="nav-item active"><i class="fas fa-box-open"></i> Mis Pedidos</a>
                    <a href="perfil-deseos.php" class="nav-item"><i class="fas fa-heart"></i> Lista de Deseos</a>
                    <a href="perfil-configuracion.php" class="nav-item"><i class="fas fa-cog"></i> Configuración</a>
                    <a href="perfil-direcciones.php" class="nav-item"><i class="fas fa-map-marker-alt"></i> Direcciones</a>
                    <hr>
                    <a href="../actions/logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </aside>

            <section class="profile-content">
                <h1 class="page-title">Historial de Pedidos</h1>
                <p class="page-subtitle">Revisa el estado y los detalles de tus compras recientes.</p>
                
                <?php echo $mensaje_status; ?>

                <div class="orders-history-block">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>ID Pedido</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pedidos)): ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <?php $clase_estado = get_status_class($pedido['estado']); ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($pedido['id_pedido']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($pedido['fecha_pedido'])); ?></td>
                                    <td>$<?php echo number_format($pedido['total'], 2); ?> MXN</td>
                                    <td><span class="<?php echo $clase_estado; ?>"><?php echo htmlspecialchars($pedido['estado']); ?></span></td>
                                    <td>
                                        <a href="#" class="action-link view-receipt" data-id="<?php echo $pedido['id_pedido']; ?>">Ver Detalles</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px;">
                                        Aún no has realizado ningún pedido.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="pagination-controls">
                        <a href="#">&laquo; Anterior</a>
                        <span class="current-page">Página 1 de 1</span>
                        <a href="#">Siguiente &raquo;</a>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">Detalle del Pedido #<span id="pedido-id"></span></h2>
            
            <div id="receipt-details">
                <p><strong>Fecha:</strong> <span id="detalle-fecha"></span></p>
                <p><strong>Estado:</strong> <span id="detalle-estado"></span></p>
                <p><strong>Método de Pago:</strong> <span id="detalle-metodo"></span></p>
                <p><strong>Envío a:</strong> <span id="detalle-direccion"></span></p>

                <h3 style="margin-top: 20px;">Productos:</h3>
                <table class="receipt-table">
                    <thead>
                        <tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody id="detalle-productos">
                        </tbody>
                </table>
                
                <p style="text-align: right; margin-top: 10px;"><strong>Costo de Envío:</strong> $<span id="detalle-envio"></span> MXN</p>
                <h3 style="text-align: right;">Total: $<span id="detalle-total"></span> MXN</h3>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('receiptModal');
            const closeBtn = document.querySelector('.close-btn');
            const viewReceiptButtons = document.querySelectorAll('.view-receipt');
            const detalleProductos = document.getElementById('detalle-productos');

            // Cierra el modal
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            }

            // Cierra el modal haciendo clic fuera
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            // Abre el modal y carga los datos
            viewReceiptButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pedidoId = this.getAttribute('data-id');
                    
                    // Llama a una función AJAX para obtener los detalles del pedido
                    fetchPedidoDetalle(pedidoId);
                    modal.style.display = 'block';
                });
            });

            // Función AJAX para obtener los detalles del pedido
            function fetchPedidoDetalle(pedidoId) {
                // Limpiar contenido previo
                document.getElementById('pedido-id').textContent = pedidoId;
                detalleProductos.innerHTML = '<tr><td colspan="4">Cargando detalles...</td></tr>';
                
                // NOTA: Debes crear este script procesador: '../actions/obtener_detalle_pedido.php'
                fetch(`../actions/obtener_detalle_pedido.php?id=${pedidoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            detalleProductos.innerHTML = `<tr><td colspan="4">${data.error}</td></tr>`;
                            return;
                        }
                        
                        // 1. Cargar Datos Principales
                        document.getElementById('detalle-fecha').textContent = data.pedido.fecha_pedido;
                        document.getElementById('detalle-estado').textContent = data.pedido.estado;
                        document.getElementById('detalle-metodo').textContent = data.pedido.metodo_pago;
                        document.getElementById('detalle-direccion').textContent = `${data.direccion.calle_numero}, ${data.direccion.colonia}, ${data.direccion.ciudad}, ${data.direccion.pais}`;
                        
                        document.getElementById('detalle-envio').textContent = parseFloat(data.pedido.costo_envio).toFixed(2);
                        document.getElementById('detalle-total').textContent = parseFloat(data.pedido.total).toFixed(2);
                        
                        // 2. Cargar Productos del Detalle
                        let productosHTML = '';
                        data.detalles.forEach(item => {
                            productosHTML += `
                                <tr>
                                    <td>${item.titulo} (${item.autor})</td>
                                    <td>${item.cantidad}</td>
                                    <td>$${parseFloat(item.precio_unitario).toFixed(2)}</td>
                                    <td>$${(item.cantidad * parseFloat(item.precio_unitario)).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        detalleProductos.innerHTML = productosHTML;

                    })
                    .catch(error => {
                        console.error('Error al obtener detalles:', error);
                        detalleProductos.innerHTML = '<tr><td colspan="4">Error al cargar la información del servidor.</td></tr>';
                    });
            }
        });
    </script>
</body>
</html>