<?php
/**
 * obtener_detalle_cliente.php
 * Script AJAX que devuelve los detalles completos (info personal, estadisticas, historial de pedidos)
 * de un usuario específico en formato JSON.
 *
 * @autor: [Tu Nombre]
 * @fecha: 16/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 

// Establecer el encabezado para indicar que la respuesta será JSON
header('Content-Type: application/json');

// 1. Validar Autenticación y ID del Cliente
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso denegado. Solo administradores.']);
    exit;
}

$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_cliente <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cliente no válido.']);
    exit;
}

try {
    
    // 2. Obtener Información Personal y Estadísticas
    $sql_info = "
        SELECT 
            U.id_usuario, U.nombre, U.apellido, U.email, U.telefono, U.fecha_registro, U.id_rol,
            COALESCE(SUM(P.total), 0.00) AS total_gastado,
            COUNT(P.id_pedido) AS total_pedidos,
            (
                SELECT id_pedido 
                FROM Pedidos 
                WHERE id_usuario = U.id_usuario 
                ORDER BY fecha_pedido DESC 
                LIMIT 1
            ) AS ultimo_pedido_id
        FROM Usuarios U
        LEFT JOIN Pedidos P ON U.id_usuario = P.id_usuario AND P.estado IN ('Entregado', 'Enviado', 'Procesando', 'Pendiente')
        WHERE U.id_usuario = ?
        GROUP BY U.id_usuario
        LIMIT 1
    ";
    
    if (!$stmt_info = $conn->prepare($sql_info)) {
        throw new Exception("Error SQL al preparar info: " . $conn->error);
    }
    
    $stmt_info->bind_param("i", $id_cliente);
    $stmt_info->execute();
    $resultado_info = $stmt_info->get_result();

    if ($resultado_info->num_rows == 0) {
        throw new Exception("Cliente no encontrado.");
    }
    
    $info = $resultado_info->fetch_assoc();
    $stmt_info->close();

    // 3. Obtener Historial de Pedidos del Cliente (Los últimos 10)
    $sql_pedidos = "
        SELECT id_pedido, fecha_pedido, total, estado 
        FROM Pedidos 
        WHERE id_usuario = ? 
        ORDER BY fecha_pedido DESC
        LIMIT 10
    ";
    
    if (!$stmt_pedidos = $conn->prepare($sql_pedidos)) {
        throw new Exception("Error SQL al preparar historial: " . $conn->error);
    }
    
    $stmt_pedidos->bind_param("i", $id_cliente);
    $stmt_pedidos->execute();
    $resultado_pedidos = $stmt_pedidos->get_result();
    
    $pedidos = [];
    while ($fila = $resultado_pedidos->fetch_assoc()) {
        $pedidos[] = [
            'id_pedido' => '#' . $fila['id_pedido'],
            'fecha_pedido' => date('d/m/Y', strtotime($fila['fecha_pedido'])),
            'total' => $fila['total'],
            'estado' => $fila['estado']
        ];
    }
    $stmt_pedidos->close();
    
    // 4. Construir la Respuesta Final JSON
    $response = [
        'error' => false,
        'info' => [
            'nombre_completo' => trim($info['nombre'] . ' ' . $info['apellido']),
            'email' => $info['email'],
            'telefono' => $info['telefono'],
            'fecha_registro' => date('d/m/Y', strtotime($info['fecha_registro'])),
            'id_rol' => $info['id_rol']
        ],
        'stats' => [
            'total_gastado' => $info['total_gastado'],
            'total_pedidos' => $info['total_pedidos'],
            'ultimo_pedido_id' => $info['ultimo_pedido_id'] ? '#' . $info['ultimo_pedido_id'] : null,
        ],
        'pedidos' => $pedidos
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response = ['error' => 'Error de servidor: ' . $e->getMessage()];
}

$conn->close();
echo json_encode($response);
exit;