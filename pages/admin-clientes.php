<?php
/**
 * admin-clientes.php
 * Muestra la gestión de clientes y administradores, incluyendo estadísticas de gasto,
 * y permite ver un detalle completo mediante un modal AJAX.
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
// 1. LÓGICA DE FILTRADO Y CONSULTA
// ----------------------------------------------------

$condiciones = ["U.id_rol IN (1, 2)"]; // Incluir Admins (1) y Clientes (2)
$fecha_registro = isset($_GET['fecha_registro']) ? $_GET['fecha_registro'] : '';
$gasto_minimo = isset($_GET['gasto_minimo']) ? (float)$_GET['gasto_minimo'] : 0.00;

// Filtro por Fecha de Registro
if (!empty($fecha_registro)) {
    $fecha_escapada = $conn->real_escape_string($fecha_registro);
    $condiciones[] = "DATE(U.fecha_registro) >= '$fecha_escapada'";
}

// Filtro por Gasto Mínimo (aplicado al HAVING)
$having_clause = ""; 
if ($gasto_minimo > 0) {
    $having_clause = "HAVING total_gastado >= $gasto_minimo";
}

$where_clause = "WHERE " . implode(' AND ', $condiciones);

// CONSULTA PRINCIPAL: Se obtienen todos los datos necesarios
$sql_clientes = "
    SELECT 
        U.id_usuario, U.nombre, U.apellido, U.email, U.telefono, U.fecha_registro, U.id_rol,
        
        -- Total Gastado (Suma de todos los pedidos válidos)
        COALESCE(SUM(P.total), 0.00) AS total_gastado,
        
        -- Último Pedido
        (
            SELECT id_pedido 
            FROM Pedidos 
            WHERE id_usuario = U.id_usuario 
            ORDER BY fecha_pedido DESC 
            LIMIT 1
        ) AS ultimo_pedido_id
        
    FROM Usuarios U
    LEFT JOIN Pedidos P ON U.id_usuario = P.id_usuario 
        AND P.estado IN ('Entregado', 'Enviado', 'Procesando', 'Pendiente')
    
    $where_clause
    GROUP BY U.id_usuario, U.id_rol
    $having_clause
    ORDER BY U.fecha_registro DESC
";

$resultado_clientes = $conn->query($sql_clientes);
$clientes = $resultado_clientes ? $resultado_clientes->fetch_all(MYSQLI_ASSOC) : [];
$total_clientes_mostrados = count($clientes);

$sql_total_reg = "SELECT COUNT(id_usuario) AS total FROM Usuarios WHERE id_rol IN (1, 2)";
$total_clientes_reg = $conn->query($sql_total_reg)->fetch_assoc()['total'];

/** Función para mapear el gasto a una clase de color */
function get_gasto_class($gasto) {
    if ($gasto >= 10000) return 'high';
    if ($gasto >= 1000) return 'medium';
    if ($gasto > 0) return 'low';
    return 'out'; // Para 0 MXN
}

/** Función para obtener el nombre y clase del rol */
function get_role_info($id_rol) {
    switch ($id_rol) {
        case 1: 
            return ['nombre' => 'Admin', 'clase' => 'role-admin'];
        case 2: 
            return ['nombre' => 'Cliente', 'clase' => 'role-client'];
        default: 
            return ['nombre' => 'Otro', 'clase' => 'role-other'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes | SKRIPTORIA ADMIN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .role-admin { background-color: #a1885f; color: white; padding: 3px 6px; border-radius: 4px; font-size: 0.8em; }
        .role-client { background-color: #e8e8e8; color: #333; padding: 3px 6px; border-radius: 4px; font-size: 0.8em; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 25px; border-radius: 8px; width: 90%; max-width: 700px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .client-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .client-detail-grid h3 { color: #4a3b30; margin-top: 0; }
        .client-orders-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <div class="logo-area">SKRIPTORIA <span class="admin-label">ADMIN</span></div>
        <nav class="main-nav">
            <a href="../admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin-pedidos.php" class="nav-item"><i class="fas fa-box-open"></i> Pedidos</a>
            <a href="admin-productos.php" class="nav-item"><i class="fas fa-book"></i> Productos</a>
            <a href="admin-clientes.php" class="nav-item active"><i class="fas fa-users"></i> Clientes</a>
            <a href="admin-reportes.php" class="nav-item"><i class="fas fa-chart-line"></i> Reportes</a>
            <a href="admin-perfil.php" class="nav-item"><i class="fas fa-user-circle"></i> Mi Perfil</a>
        </nav>
        <div class="logout-area"><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
    </aside>

    <div class="admin-main-content">
        
        <header class="admin-header"></header>

        <section class="admin-content-area">
            <h1 class="content-header-title">Gestión de Usuarios y Clientes</h1>

            <div class="table-controls-bar">
                <form action="admin-clientes.php" method="GET" class="filter-group">
                    <label for="reg-date-filter">Fecha de Registro (Mínima):</label>
                    <input type="date" name="fecha_registro" class="control-input" value="<?php echo htmlspecialchars($fecha_registro); ?>">
                    
                    <label for="min-spent-filter">Gasto Mínimo ($):</label>
                    <select id="min-spent-filter" name="gasto_minimo" class="control-select">
                        <option value="0" <?php echo ($gasto_minimo == 0) ? 'selected' : ''; ?>>Cualquier Gasto</option>
                        <option value="1000" <?php echo ($gasto_minimo == 1000) ? 'selected' : ''; ?>>Más de $1000</option>
                        <option value="5000" <?php echo ($gasto_minimo == 5000) ? 'selected' : ''; ?>>Más de $5000</option>
                        <option value="10000" <?php echo ($gasto_minimo == 10000) ? 'selected' : ''; ?>>VIP (+ $10000)</option>
                    </select>
                
                    <div class="actions-group">
                        <button type="submit" class="control-button primary"><i class="fas fa-search"></i> Filtrar</button>
                    </div>
                </form>
            </div>
            
            <div class="data-table-container">
                <table class="admin-data-table client-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Rol</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Reg.</th>
                            <th>Total Gastado</th>
                            <th>Último Pedido</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($total_clientes_mostrados > 0): 
                            foreach ($clientes as $cliente): 
                                $nombre_completo = trim($cliente['nombre'] . ' ' . $cliente['apellido']);
                                $fecha_reg_formateada = date('d/m/Y', strtotime($cliente['fecha_registro']));
                                
                                $rol_info = get_role_info($cliente['id_rol']);
                                
                                // Corrección: La clase y el formato se aplican al gasto real.
                                $gasto_clase = get_gasto_class($cliente['total_gastado']);
                                $ultimo_pedido_id = $cliente['ultimo_pedido_id'] ? '#' . $cliente['ultimo_pedido_id'] : 'N/A';
                                $gasto_formateado = number_format($cliente['total_gastado'], 2, '.', ',');
                        ?>
                        <tr>
                            <td>#C<?php echo htmlspecialchars($cliente['id_usuario']); ?></td>
                            <td><span class="<?php echo $rol_info['clase']; ?>"><?php echo $rol_info['nombre']; ?></span></td>
                            <td><?php echo htmlspecialchars($nombre_completo); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo $fecha_reg_formateada; ?></td>
                            
                            <td>
                                <span class="stock-tag <?php echo $gasto_clase; ?>">
                                    $<?php echo $gasto_formateado; ?> MXN
                                </span>
                            </td>
                            
                            <td><?php echo $ultimo_pedido_id; ?></td>
                            <td>
                                <button class="action-btn view open-client-detail" data-id="<?php echo $cliente['id_usuario']; ?>">Ver Detalle</button>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        else: 
                        ?>
                            <tr><td colspan="8" style="text-align: center; padding: 20px;">No se encontraron usuarios que coincidan con los filtros.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="pagination-admin">
                    <span>Mostrando 1 a <?php echo $total_clientes_mostrados; ?> de <?php echo $total_clientes_reg; ?> usuarios registrados</span>
                    <div class="pagination-buttons">
                        <button class="control-button disabled">&laquo; Anterior</button>
                        <button class="control-button">Siguiente &raquo;</button>
                    </div>
                </div>
            </div>

        </section>

    </div>
    
    <div id="clientDetailModal" class="modal">
        <div class="modal-content">
            <span class="close-btn client-detail-close">&times;</span>
            <h2 class="modal-title">Detalle de Usuario #<span id="client-id-display"></span></h2>
            
            <div id="client-details-body">
                <p style="text-align: center;">Cargando...</p>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const clientModal = document.getElementById('clientDetailModal');
            const closeBtn = document.querySelector('.client-detail-close');
            const detailsContainer = document.getElementById('client-details-body');
            
            function toggleModal(modal, display) {
                if (modal) modal.style.display = display;
            }

            // Cierre del modal
            closeBtn.onclick = function() { toggleModal(clientModal, "none"); }
            window.onclick = function(event) {
                if (event.target == clientModal) { toggleModal(clientModal, "none"); }
            }

            // ------------------------------------------
            // LÓGICA DE VER DETALLES (Abrir Modal)
            // ------------------------------------------
            document.querySelectorAll(".open-client-detail").forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const clientId = this.getAttribute('data-id');
                    
                    fetchClientDetalle(clientId); 
                    toggleModal(clientModal, "block");
                });
            });

            // Función AJAX para obtener detalles y llenar el modal
            function fetchClientDetalle(clientId) {
                document.getElementById('client-id-display').textContent = clientId;
                detailsContainer.innerHTML = '<p style="text-align: center;">Cargando detalles...</p>';
                
                // Llamada al script de acción
                fetch(`../actions/obtener_detalle_cliente.php?id=${clientId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            detailsContainer.innerHTML = `<p style="color: red;">Error: ${data.error}</p>`;
                            return;
                        }
                        
                        // Generar el contenido del modal
                        let html = `
                            <div class="client-detail-grid">
                                <div>
                                    <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Información de Contacto</h3>
                                    <p><strong>Nombre:</strong> ${data.info.nombre_completo}</p>
                                    <p><strong>Rol:</strong> <span class="${get_role_info_js(data.info.id_rol).clase}">${get_role_info_js(data.info.id_rol).nombre}</span></p>
                                    <p><strong>Email:</strong> ${data.info.email}</p>
                                    <p><strong>Teléfono:</strong> ${data.info.telefono || 'N/A'}</p>
                                    <p><strong>Fecha Reg.:</strong> ${data.info.fecha_registro}</p>
                                </div>
                                <div>
                                    <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Estadísticas</h3>
                                    <p><strong>Total Gastado:</strong> <strong style="color: #007bff;">$${parseFloat(data.stats.total_gastado).toFixed(2)} MXN</strong></p>
                                    <p><strong>Total Pedidos:</strong> ${data.stats.total_pedidos}</p>
                                    <p><strong>Último Pedido:</strong> ${data.stats.ultimo_pedido_id || 'N/A'}</p>
                                </div>
                            </div>

                            <h3 style="margin-top: 25px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Historial de Pedidos (Últimos 10)</h3>
                            ${data.pedidos.length > 0 ? generateOrdersTable(data.pedidos) : '<p>El usuario no tiene pedidos válidos registrados.</p>'}
                        `;
                        detailsContainer.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error al obtener detalles:', error);
                        detailsContainer.innerHTML = '<p style="color: red;">Error al cargar la información.</p>';
                    });
            }

            // --- Funciones Auxiliares JS ---
            function get_role_info_js(id_rol) {
                // Debe coincidir con las clases CSS en el head o admin.css
                if (id_rol == 1) return { nombre: 'Admin', clase: 'role-admin' };
                return { nombre: 'Cliente', clase: 'role-client' };
            }

            function generateOrdersTable(pedidos) {
                let table = '<table class="client-orders-table admin-data-table"><thead><tr><th>ID</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead><tbody>';
                pedidos.forEach(p => {
                    // Nota: Asegúrate de tener las clases .status-tag y las clases de estado (ej: .delivered, .shipped) en tu admin.css
                    const statusClass = get_status_class_js(p.estado); 
                    table += `
                        <tr>
                            <td>${p.id_pedido}</td>
                            <td>${p.fecha_pedido}</td>
                            <td>$${parseFloat(p.total).toFixed(2)} MXN</td>
                            <td><span class="status-tag ${statusClass}">${p.estado}</span></td>
                        </tr>
                    `;
                });
                table += '</tbody></table>';
                return table;
            }
            
            function get_status_class_js(estado) {
                switch (estado) {
                    case 'Enviado': return 'shipped';
                    case 'Entregado': return 'delivered'; 
                    case 'Procesando': return 'processing'; 
                    case 'Pendiente': return 'pending'; 
                    case 'Cancelado': return 'cancelled';
                    default: return 'unknown';
                }
            }
            
        });
    </script>
</body>
</html>