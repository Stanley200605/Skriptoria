<?php
/**
 * admin-reportes.php
 * Muestra métricas clave (KPIs, Ticket Promedio) y listados de productos más vendidos 
 * basándose en un rango de fechas filtrado.
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
// 1. OBTENER Y SANEAR EL RANGO DE FECHAS
// ----------------------------------------------------

$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-30 days')); // Default: 30 días atrás
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d'); // Default: Hoy

$fecha_inicio_escapada = $conn->real_escape_string($fecha_inicio);
$fecha_fin_escapada = $conn->real_escape_string($fecha_fin);

// Cláusula WHERE base para las consultas de reporte (pedidos válidos)
$fecha_clause = "WHERE P.estado IN ('Entregado', 'Enviado', 'Procesando') AND DATE(P.fecha_pedido) BETWEEN '$fecha_inicio_escapada' AND '$fecha_fin_escapada'";

// ----------------------------------------------------
// 2. CONSULTAS DE KPIs Y ANALÍTICAS
// ----------------------------------------------------

// A. Ingresos Totales (SUM)
$sql_ingresos = "SELECT COALESCE(SUM(total), 0.00) AS ingresos FROM Pedidos P $fecha_clause";
$ingresos_totales = $conn->query($sql_ingresos)->fetch_assoc()['ingresos'];

// B. Pedidos Completados (COUNT)
$sql_pedidos_completados = "SELECT COUNT(id_pedido) AS total FROM Pedidos P $fecha_clause";
$pedidos_completados = $conn->query($sql_pedidos_completados)->fetch_assoc()['total'];

// C. Ticket Promedio (AVG)
$ticket_promedio = ($pedidos_completados > 0) ? ($ingresos_totales / $pedidos_completados) : 0.00;

// D. Productos Más Vendidos (Listado por Cantidad)
$sql_mas_vendidos = "
    SELECT 
        Pr.titulo, 
        Pr.autor, 
        SUM(DP.cantidad) AS total_vendido
    FROM DetallePedido DP
    JOIN Pedidos P ON DP.id_pedido = P.id_pedido
    JOIN Productos Pr ON DP.id_producto = Pr.id_producto
    $fecha_clause
    GROUP BY Pr.id_producto
    ORDER BY total_vendido DESC
    LIMIT 10
";
$resultado_vendidos = $conn->query($sql_mas_vendidos);
$productos_vendidos = $resultado_vendidos ? $resultado_vendidos->fetch_all(MYSQLI_ASSOC) : [];


// Formatear resultados
$ingresos_formateados = number_format($ingresos_totales, 2, '.', ',');
$pedidos_formateados = number_format($pedidos_completados);
$ticket_formateado = number_format($ticket_promedio, 2, '.', ',');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Analíticas | SKRIPTORIA ADMIN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* CSS para la estructura de reportes */
        .chart-card { background-color: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
        /* CORRECCIÓN: Estructura de columna única */
        .chart-container { display: flex; flex-direction: column; gap: 20px; margin-top: 30px; }
        .reports-controls .filter-group { gap: 10px; }
        .reports-controls .control-input { width: 140px; }
        .reports-controls .control-button { padding: 10px 15px; }
        .reports-controls .actions-group { margin-left: auto; }
        
        /* Estilo de tabla de productos más vendidos */
        .best-sellers-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .best-sellers-table thead th { background-color: #f8f5f1; color: #4a3b30; font-weight: bold; text-align: left; padding: 12px 15px; }
        .best-sellers-table tbody td { padding: 10px 15px; border-bottom: 1px solid #e0d9d4; font-size: 0.9rem; color: #5d4a3c; }
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
        <a href="admin-reportes.php" class="nav-item active"><i class="fas fa-chart-line"></i> Reportes</a>
        <a href="admin-perfil.php" class="nav-item"><i class="fas fa-user-circle"></i> Mi Perfil</a>
    </nav>
    <div class="logout-area"><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
</aside>

<div class="admin-main-content">
    
    <header class="admin-header"></header>

    <section class="admin-content-area">
        <h1 class="content-header-title">Reportes y Analíticas</h1>

        <div class="table-controls-bar reports-controls">
            <form action="admin-reportes.php" method="GET" class="filter-group">
                <label for="date-start">Desde:</label>
                <input type="date" id="date-start" name="fecha_inicio" class="control-input" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                
                <label for="date-end">Hasta:</label>
                <input type="date" id="date-end" name="fecha_fin" class="control-input" value="<?php echo htmlspecialchars($fecha_fin); ?>">

                <button type="submit" class="control-button primary"><i class="fas fa-search"></i> Generar Reporte</button>
            </form>


        </div>
        
        <div class="kpi-grid detailed-metrics">
            <div class="kpi-card total-revenue">
                <div class="kpi-data">
                    <p class="kpi-value">$<?php echo $ingresos_formateados; ?></p>
                    <p class="kpi-label">Ingresos Totales (Período)</p>
                </div>
            </div>
            <div class="kpi-card total-orders">
                <div class="kpi-data">
                    <p class="kpi-value"><?php echo $pedidos_formateados; ?></p>
                    <p class="kpi-label">Pedidos Completados</p>
                </div>
            </div>
            <div class="kpi-card avg-order">
                <div class="kpi-data">
                    <p class="kpi-value">$<?php echo $ticket_formateado; ?></p>
                    <p class="kpi-label">Ticket Promedio</p>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-card">
                <h2>Productos más Vendidos</h2>
                
                <?php if (!empty($productos_vendidos)): ?>
                    <table class="best-sellers-table">
                        <thead>
                            <tr><th>Título</th><th>Autor</th><th>Cantidad Vendida</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos_vendidos as $producto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($producto['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['autor']); ?></td>
                                    <td><?php echo $producto['total_vendido']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="chart-placeholder">
                        [No se encontraron datos de ventas en el período seleccionado.]
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </section>

</div>
</body>
</html>