<?php
/**
 * admin.php
 * Dashboard principal del Administrador.
 * Muestra KPIs, actividad reciente y modales de gestión.
 *
 * @autor: [Tu Nombre]
 * @fecha: 16/11/2025
 */

session_start();
require_once 'includes/auth_check.php'; 
require_once 'includes/db_connection.php'; 

// Lógica de autenticación centralizada:
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin');

// ----------------------------------------------------
// 1. LÓGICA DE CARGA DE KPIs y ACTIVIDAD RECIENTE
// ----------------------------------------------------
$sql_pedidos_pendientes = "SELECT COUNT(id_pedido) AS total FROM Pedidos WHERE estado IN ('Pendiente', 'Procesando')";
$pedidos_pendientes = $conn->query($sql_pedidos_pendientes)->fetch_assoc()['total'] ?? 0;

$sql_ingreso_total = "SELECT SUM(total) AS total FROM Pedidos WHERE estado IN ('Pendiente', 'Procesando', 'Enviado', 'Entregado')";
$ingreso_total = $conn->query($sql_ingreso_total)->fetch_assoc()['total'] ?? 0.00;

$sql_productos_agotados = "SELECT COUNT(id_producto) AS total FROM Productos WHERE stock <= 5";
$productos_agotados = $conn->query($sql_productos_agotados)->fetch_assoc()['total'] ?? 0;

$sql_nuevos_clientes = "SELECT COUNT(id_usuario) AS total FROM Usuarios WHERE id_rol = 2 AND fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$nuevos_clientes = $conn->query($sql_nuevos_clientes)->fetch_assoc()['total'] ?? 0;

$ingreso_formateado = number_format($ingreso_total, 2, '.', ',');

$sql_actividad_reciente = "SELECT P.id_pedido, P.fecha_pedido, P.total, P.estado, U.nombre AS nombre_cliente, U.apellido AS apellido_cliente FROM Pedidos P JOIN Usuarios U ON P.id_usuario = U.id_usuario ORDER BY P.fecha_pedido DESC LIMIT 5";
$resultado_actividad = $conn->query($sql_actividad_reciente);
$actividad_reciente = $resultado_actividad ? $resultado_actividad->fetch_all(MYSQLI_ASSOC) : [];


/** Función para mapear el estado de la BD a una clase CSS */
function get_status_class($estado) {
    switch ($estado) {
        case 'Enviado': return 'shipped';
        case 'Entregado': return 'delivered'; 
        case 'Procesando': return 'processing'; 
        case 'Pendiente': return 'pending'; 
        case 'Cancelado': return 'cancelled';
        default: return 'unknown'; // Retorna 'unknown' si no coincide.
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

    <?php if ($is_admin): ?>
        
        <aside class="admin-sidebar">
            <div class="logo-area">
                SKRIPTORIA <span class="admin-label">ADMIN</span>
            </div>
            <nav class="main-nav">
                <a href="admin.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="pages/admin-pedidos.php" class="nav-item">
                    <i class="fas fa-box-open"></i> Pedidos
                </a>
                <a href="pages/admin-productos.php" class="nav-item">
                    <i class="fas fa-book"></i> Productos
                </a>
                <a href="pages/admin-clientes.php" class="nav-item">
                    <i class="fas fa-users"></i> Clientes
                </a>
                <a href="pages/admin-reportes.php" class="nav-item">
                    <i class="fas fa-chart-line"></i> Reportes
                </a>
                <a href="pages/admin-perfil.php" class="nav-item">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </nav>
            <div class="logout-area">
                <a href="actions/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </aside>

        <div class="admin-main-content">
            
            <header class="admin-header"></header>

            <section class="admin-content-area">
                
                <h1 class="content-header-title">Dashboard Administrativo</h1>

                <div class="kpi-grid">
                    <div class="kpi-card pending"><div class="kpi-icon"><i class="fas fa-clipboard-list"></i></div><div class="kpi-data"><p class="kpi-value"><?php echo $pedidos_pendientes; ?></p><p class="kpi-label">Pedidos Pendientes</p></div></div>
                    <div class="kpi-card revenue"><div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div><div class="kpi-data"><p class="kpi-value">$<?php echo $ingreso_formateado; ?></p><p class="kpi-label">Ingreso Total</p></div></div>
                    <div class="kpi-card low-stock"><div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="kpi-data"><p class="kpi-value"><?php echo $productos_agotados; ?></p><p class="kpi-label">Stock Bajo/Agotados</p></div></div>
                    <div class="kpi-card clients"><div class="kpi-icon"><i class="fas fa-user-plus"></i></div><div class="kpi-data"><p class="kpi-value"><?php echo $nuevos_clientes; ?></p><p class="kpi-label">Nuevos Clientes (30 días)</p></div></div>
                </div>

                <div class="recent-activity-table">
                    <h2>Actividad Reciente</h2>
                    
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID Pedido</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acción Rápida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($actividad_reciente)): ?>
                                <?php foreach ($actividad_reciente as $pedido): 
                                    $nombre_completo = trim($pedido['nombre_cliente'] . ' ' . $pedido['apellido_cliente']);
                                    $clase_estado = get_status_class($pedido['estado']);
                                    $total_formateado = number_format($pedido['total'], 2, '.', ',');
                                ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($pedido['id_pedido']); ?></td>
                                    <td><?php echo htmlspecialchars($nombre_completo); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($pedido['fecha_pedido'])); ?></td>
                                    <td>$<?php echo $total_formateado; ?> MXN</td>
                                    <td><span class="status-tag <?php echo $clase_estado; ?>"><?php echo htmlspecialchars($pedido['estado']); ?></span></td>
                                    <td>
                                        <button class="action-btn view open-admin-receipt" data-id="<?php echo $pedido['id_pedido']; ?>">Ver</button>
                                        
                                        <?php if ($pedido['estado'] == 'Pendiente' || $pedido['estado'] == 'Procesando' || $pedido['estado'] == 'Enviado'): ?>
                                            <button class="action-btn update open-admin-update" data-id="<?php echo $pedido['id_pedido']; ?>" data-estado="<?php echo htmlspecialchars($pedido['estado']); ?>">Actualizar</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 20px;">No hay pedidos recientes.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div id="receipt-modal-admin" class="modal"> 
            <div class="modal-content receipt-content">
                <span class="close-btn receipt-admin-close">&times;</span>
                <h2 class="modal-title">Detalle de Pedido #<span id="admin-receipt-id"></span></h2>
                
                <div id="admin-receipt-details">
                    </div>
            </div>
        </div>

        <div id="update-modal" class="modal">
            <div class="modal-content update-content">
                <span class="close-btn update-close">&times;</span>
                <h2 class="modal-title">Actualizar Pedido #<span id="admin-update-id"></span></h2>
                
                <form id="update-status-form" action="actions/procesar_estado.php" method="POST" class="update-form">
                    <input type="hidden" name="id_pedido" id="update-pedido-id">
                    
                    <h3 class="update-section-title">Cambiar Estado</h3>
                    <div class="form-group">
                        <label for="status-select">Nuevo Estado</label>
                        <select id="status-select" name="nuevo_estado" required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Procesando">Procesando</option>
                            <option value="Enviado">Enviado</option>
                            <option value="Entregado">Entregado</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="btn_actualizar_estado" class="admin-update-button primary">
                        Guardar Actualización
                    </button>
                </form>
            </div>
        </div>
        
    <?php else: // MUESTRA SOLO EL LOGIN SI NO ES ADMIN ?>

        <div id="admin-login-modal" class="modal" style="display: block;"> 
            <div class="modal-content" style="margin-top: 20vh;">
                <h2 class="modal-title">Iniciar Sesión Admin</h2>
                <form class="login-form" action="actions/procesar_login.php" method="POST">
                    <input type="email" name="email" placeholder="Correo Electrónico Admin" required>
                    <input type="password" name="password" placeholder="Contraseña Admin" required>
                    <button type="submit" name="btn_login" class="login-button primary">Entrar</button>
                    <p style="text-align: center; margin-top: 15px;"><a href="index.php" style="color: #a1885f;">Volver al Catálogo</a></p>
                </form>
            </div>
        </div>
        
    <?php endif; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const receiptModalAdmin = document.getElementById("receipt-modal-admin");
            const updateModal = document.getElementById("update-modal");
            
            function toggleModal(modal, display) {
                if (modal) modal.style.display = display;
            }

            // Cierre de modales internos
            document.querySelector(".receipt-admin-close").onclick = function() { toggleModal(receiptModalAdmin, "none"); };
            document.querySelector(".update-close").onclick = function() { toggleModal(updateModal, "none"); };
            
            // Cierre al hacer clic fuera
            window.onclick = function(event) {
                if (event.target == receiptModalAdmin) { toggleModal(receiptModalAdmin, "none"); }
                if (event.target == updateModal) { toggleModal(updateModal, "none"); }
            }
            
            // ------------------------------------------
            // LÓGICA DE ACTUALIZAR ESTADO (Abrir Modal)
            // ------------------------------------------
            document.querySelectorAll(".open-admin-update").forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pedidoId = this.getAttribute('data-id');
                    const estadoActual = this.getAttribute('data-estado');
                    
                    // Llenar el modal
                    document.getElementById('admin-update-id').textContent = pedidoId;
                    document.getElementById('update-pedido-id').value = pedidoId;
                    
                    // Seleccionar la opción actual en el dropdown
                    document.getElementById('status-select').value = estadoActual;
                    
                    toggleModal(updateModal, "block");
                });
            });

            // ------------------------------------------
            // LÓGICA DE VER DETALLES (Abrir Modal)
            // ------------------------------------------
            document.querySelectorAll(".open-admin-receipt").forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pedidoId = this.getAttribute('data-id');
                    
                    // Cargar contenido con AJAX (usando el script ya creado: obtener_detalle_pedido.php)
                    fetchAdminPedidoDetalle(pedidoId); 
                    
                    toggleModal(receiptModalAdmin, "block");
                });
            });

            // Función AJAX para obtener detalles y llenar el modal de recibo
            function fetchAdminPedidoDetalle(pedidoId) {
                document.getElementById('admin-receipt-id').textContent = pedidoId;
                const detailsContainer = document.getElementById('admin-receipt-details');
                detailsContainer.innerHTML = '<p style="text-align: center;">Cargando detalles...</p>';
                
                // RUTA CORRECTA: Salir de la raíz e ir a actions/
                fetch(`actions/obtener_detalle_pedido.php?id=${pedidoId}`) 
                    .then(response => {
                        if (!response.ok) {
                            // Este error ocurre si PHP tiene un error fatal
                            throw new Error('Error al conectar con el servidor (código: ' + response.status + ')');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            detailsContainer.innerHTML = `<p style="color: red;">Error: ${data.error}</p>`;
                            return;
                        }
                        
                        // Generar el contenido del recibo (simplificado para el admin)
                        let html = `
                            <p><strong>Pedido ID:</strong> #${data.pedido.id_pedido}</p>
                            <p><strong>Cliente:</strong> ${data.pedido.cliente}</p>
                            <p><strong>Fecha de Pedido:</strong> ${data.pedido.fecha_pedido}</p>
                            <p><strong>Estado:</strong> <span class="status-tag ${get_status_class(data.pedido.estado)}">${data.pedido.estado}</span></p>
                            <p><strong>Método de Pago:</strong> ${data.pedido.metodo_pago}</p>
                            <p><strong>Dirección:</strong> ${data.direccion.calle_numero}, ${data.direccion.colonia}, ${data.direccion.ciudad}, ${data.direccion.pais}</p>

                            <h3 style="margin-top: 15px;">Productos:</h3>
                            <table class="receipt-table" style="width: 100%;">
                                <thead><tr><th>Producto</th><th>Cant.</th><th>P. Unitario</th><th>Subtotal</th></tr></thead>
                                <tbody>
                        `;
                        data.detalles.forEach(item => {
                            const subtotal = item.cantidad * parseFloat(item.precio_unitario);
                            html += `
                                <tr>
                                    <td>${item.titulo} (${item.autor})</td>
                                    <td>${item.cantidad}</td>
                                    <td>$${parseFloat(item.precio_unitario).toFixed(2)}</td>
                                    <td>$${subtotal.toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        html += `
                                </tbody>
                            </table>
                            <p style="text-align: right; margin-top: 10px;"><strong>Costo de Envío:</strong> $${parseFloat(data.pedido.costo_envio).toFixed(2)} MXN</p>
                            <h3 style="text-align: right;">Total: $${parseFloat(data.pedido.total).toFixed(2)} MXN</h3>
                        `;

                        detailsContainer.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error al obtener detalles:', error);
                        detailsContainer.innerHTML = '<p style="color: red;">Error al cargar la información. Consulte la consola para ver el error del servidor.</p>';
                    });
            }
        });
        
        /** * Función para mapear el estado de la BD a una clase CSS (DEBE ESTAR DISPONIBLE EN EL GLOBAL SCOPE)
         * Nota: Esta función debe coincidir EXACTAMENTE con la definición de PHP en el servidor.
         */
        function get_status_class(estado) {
            switch (estado) {
                case 'Enviado': return 'shipped';
                case 'Entregado': return 'delivered'; 
                case 'Procesando': return 'processing'; 
                case 'Pendiente': return 'pending'; 
                case 'Cancelado': return 'cancelled';
                default: return 'unknown';
            }
        }
    </script>

</body>
</html>