<?php
/**
 * admin-pedidos.php
 * Gestión completa del listado de pedidos para el Administrador.
 * Permite filtrar por estado y fecha, ver detalles y actualizar el estado.
 *
 * @autor: [Tu Nombre]
 * @fecha: 16/11/2025
 */

session_start();
require_once '../includes/auth_check.php'; 
require_once '../includes/db_connection.php'; 

// Asegurar que SÓLO los administradores accedan
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php?error=Acceso denegado.");
    exit;
}

// ----------------------------------------------------
// 1. LÓGICA DE FILTRADO
// ----------------------------------------------------

$condiciones = [];
$estado_seleccionado = isset($_GET['estado']) ? $_GET['estado'] : 'all';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Filtro por Estado
if ($estado_seleccionado != 'all' && !empty($estado_seleccionado)) {
    $estado_escapado = $conn->real_escape_string($estado_seleccionado);
    $condiciones[] = "P.estado = '$estado_escapado'";
}

// Filtro por Rango de Fecha
if (!empty($fecha_inicio)) {
    $fecha_inicio_escapada = $conn->real_escape_string($fecha_inicio);
    $condiciones[] = "DATE(P.fecha_pedido) >= '$fecha_inicio_escapada'";
}
if (!empty($fecha_fin)) {
    $fecha_fin_escapada = $conn->real_escape_string($fecha_fin);
    $condiciones[] = "DATE(P.fecha_pedido) <= '$fecha_fin_escapada'";
}

$where_clause = empty($condiciones) ? '' : 'WHERE ' . implode(' AND ', $condiciones);

// ----------------------------------------------------
// 2. CONSULTA PRINCIPAL DE PEDIDOS
// ----------------------------------------------------

$sql_pedidos = "SELECT 
                    P.id_pedido, 
                    P.fecha_pedido, 
                    P.total, 
                    P.estado, 
                    P.fecha_actualizacion,
                    U.nombre AS nombre_cliente,
                    U.apellido AS apellido_cliente
                FROM Pedidos P
                JOIN Usuarios U ON P.id_usuario = U.id_usuario
                $where_clause
                ORDER BY P.fecha_pedido DESC";
                           
$resultado_pedidos = $conn->query($sql_pedidos);
$pedidos = $resultado_pedidos ? $resultado_pedidos->fetch_all(MYSQLI_ASSOC) : [];
$total_pedidos = count($pedidos);

// 3. Obtener conteos para los filtros
$sql_conteo_estados = "SELECT estado, COUNT(id_pedido) as total FROM Pedidos GROUP BY estado";
$resultado_conteo = $conn->query($sql_conteo_estados);
$conteo_estados = [];
if ($resultado_conteo) {
    while ($fila = $resultado_conteo->fetch_assoc()) {
        $conteo_estados[$fila['estado']] = $fila['total'];
    }
}


/** Función para mapear el estado de la BD a una clase CSS */
function get_status_class($estado) {
    switch ($estado) {
        case 'Enviado': return 'shipped';
        case 'Entregado': return 'delivered'; 
        case 'Procesando': return 'processing'; 
        case 'Pendiente': return 'pending'; 
        case 'Cancelado': return 'cancelled';
        default: return 'unknown';
    }
}
function time_ago($timestamp) {
    $now = new DateTime();
    $ago = new DateTime($timestamp);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' años';
    if ($diff->m > 0) return $diff->m . ' meses';
    if ($diff->d > 0) return $diff->d . ' días';
    if ($diff->h > 0) return $diff->h . ' horas';
    if ($diff->i > 0) return $diff->i . ' min';
    return 'justo ahora';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos | SKRIPTORIA ADMIN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Estilos del modal que se pueden añadir a admin.css */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close-btn:hover, .close-btn:focus { color: #000; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <div class="logo-area">SKRIPTORIA <span class="admin-label">ADMIN</span></div>
        <nav class="main-nav">
            <a href="../admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin-pedidos.php" class="nav-item active"><i class="fas fa-box-open"></i> Pedidos</a>
            <a href="admin-productos.php" class="nav-item"><i class="fas fa-book"></i> Productos</a>
            <a href="admin-clientes.php" class="nav-item"><i class="fas fa-users"></i> Clientes</a>
            <a href="admin-reportes.php" class="nav-item"><i class="fas fa-chart-line"></i> Reportes</a>
            <a href="admin-perfil.php" class="nav-item"><i class="fas fa-user-circle"></i> Mi Perfil</a>
        </nav>
        <div class="logout-area"><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
    </aside>

    <div class="admin-main-content">
        
        <header class="admin-header"></header>

        <section class="admin-content-area">
            <h1 class="content-header-title">Gestión de Pedidos</h1>
            
            <div class="table-controls-bar">
                <!-- Formulario de Filtros GET -->
                <form action="admin-pedidos.php" method="GET" class="filter-group">
                    <label for="status-filter">Filtrar por Estado:</label>
                    <select id="status-filter" name="estado" class="control-select">
                        <option value="all">Todos los Pedidos (<?php echo $total_pedidos; ?>)</option>
                        <?php 
                        $estados_permitidos = ['Pendiente', 'Procesando', 'Enviado', 'Entregado', 'Cancelado'];
                        foreach ($estados_permitidos as $estado): 
                            $conteo = $conteo_estados[$estado] ?? 0;
                        ?>
                            <option value="<?php echo $estado; ?>" <?php echo ($estado_seleccionado == $estado) ? 'selected' : ''; ?>>
                                <?php echo $estado; ?> (<?php echo $conteo; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="date-filter-start">Rango de Fecha:</label>
                    <input type="date" id="date-filter-start" name="fecha_inicio" class="control-input" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                    <label>a</label>
                    <input type="date" id="date-filter-end" name="fecha_fin" class="control-input" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                
                    <div class="actions-group">
                        <button type="submit" class="control-button primary"><i class="fas fa-sync-alt"></i> Actualizar Listado</button>
                    </div>
                </form>
            </div>
            
            <div class="data-table-container">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Última Actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pedidos)): ?>
                            <?php foreach ($pedidos as $pedido): 
                                $nombre_completo = trim($pedido['nombre_cliente'] . ' ' . $pedido['apellido_cliente']);
                                $clase_estado = get_status_class($pedido['estado']);
                                $total_formateado = number_format($pedido['total'], 2, '.', ',');
                                $ultima_actualizacion = $pedido['fecha_actualizacion'] ? time_ago($pedido['fecha_actualizacion']) : 'N/A';
                            ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($pedido['id_pedido']); ?></td>
                                <td><?php echo htmlspecialchars($nombre_completo); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pedido['fecha_pedido'])); ?></td>
                                <td>$<?php echo $total_formateado; ?> MXN</td>
                                <td><span class="status-tag <?php echo $clase_estado; ?>"><?php echo htmlspecialchars($pedido['estado']); ?></span></td>
                                <td>Hace <?php echo $ultima_actualizacion; ?></td>
                                <td>
                                    <!-- Botón Ver Detalles -->
                                    <button class="action-btn view open-admin-receipt" data-id="<?php echo $pedido['id_pedido']; ?>">Ver</button>
                                    
                                    <?php if ($pedido['estado'] != 'Entregado' && $pedido['estado'] != 'Cancelado'): ?>
                                        <!-- Botón Actualizar (Solo si no está finalizado) -->
                                        <button class="action-btn update open-admin-update" 
                                                data-id="<?php echo $pedido['id_pedido']; ?>" 
                                                data-estado="<?php echo htmlspecialchars($pedido['estado']); ?>">
                                            Actualizar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; padding: 20px;">No se encontraron pedidos con los filtros aplicados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="pagination-admin">
                    <span>Mostrando 1 a <?php echo min(10, $total_pedidos); ?> de <?php echo $total_pedidos; ?> pedidos</span>
                    <div class="pagination-buttons">
                        <button class="control-button disabled">&laquo; Anterior</button>
                        <button class="control-button">Siguiente &raquo;</button>
                    </div>
                </div>
            </div>

        </section>

    </div>

    <!-- ============================================== -->
    <!-- MODAL 1: DETALLE DEL RECIBO (Ver) -->
    <!-- ============================================== -->
    <div id="receipt-modal-admin" class="modal"> 
        <div class="modal-content receipt-content">
            <span class="close-btn receipt-admin-close">&times;</span>
            <h2 class="modal-title">Detalle de Pedido #<span id="admin-receipt-id"></span></h2>
            <div id="admin-receipt-details">
                <!-- Contenido dinámico cargado por AJAX -->
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 2: ACTUALIZAR ESTADO (Misma estructura que en admin.php) -->
    <!-- ============================================== -->
    <div id="update-modal" class="modal">
        <div class="modal-content update-content">
            <span class="close-btn update-close">&times;</span>
            <h2 class="modal-title">Actualizar Pedido #<span id="admin-update-id"></span></h2>
            
            <form id="update-status-form" action="../actions/procesar_estado.php" method="POST" class="update-form">
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
                    
                    document.getElementById('admin-update-id').textContent = pedidoId;
                    document.getElementById('update-pedido-id').value = pedidoId;
                    document.querySelector('#update-modal #status-select').value = estadoActual;
                    
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
                    
                    // Cargar contenido con AJAX (usando el script de la carpeta actions/)
                    fetchAdminPedidoDetalle(pedidoId); 
                    
                    toggleModal(receiptModalAdmin, "block");
                });
            });

            // Función AJAX para obtener detalles y llenar el modal de recibo
            function fetchAdminPedidoDetalle(pedidoId) {
                document.getElementById('admin-receipt-id').textContent = pedidoId;
                const detailsContainer = document.getElementById('admin-receipt-details');
                detailsContainer.innerHTML = '<p style="text-align: center;">Cargando detalles...</p>';
                
                // RUTA CORRECTA: Salir de pages/ e ir a actions/
                fetch(`../actions/obtener_detalle_pedido.php?id=${pedidoId}`) 
                    .then(response => {
                         // Verificar si la respuesta es JSON o si falló el script PHP (status 200)
                        if (!response.ok) {
                            throw new Error('Error al conectar con el servidor (código: ' + response.status + ')');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            detailsContainer.innerHTML = `<p style="color: red;">Error SQL: ${data.error}</p>`;
                            return;
                        }
                        
                        // Generar el contenido del recibo
                        let html = `
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
        
        /** Función para mapear el estado de la BD a una clase CSS (DEBE ESTAR DISPONIBLE EN EL GLOBAL SCOPE) */
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