<?php
/**
 * obtener_detalle_pedido.php
 * Script AJAX que devuelve los detalles completos de un pedido en formato JSON.
 * ¡CORREGIDO! Incluye manejo de error SQL.
 *
 * @autor: [Tu Nombre]
 * @fecha: 16/11/2025
 */

session_start();
require_once dirname(__DIR__) . '/includes/db_connection.php';

header('Content-Type: application/json');

$id_pedido = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $id_pedido <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Acceso denegado o ID de pedido no válido.']);
    exit;
}

try {
    // 2. Obtener Detalles Generales del Pedido
    $sql_pedido = "SELECT 
                         P.*, 
                         D.calle_numero, D.colonia, D.ciudad, D.pais, 
                         U.nombre AS nombre_cliente, U.apellido AS apellido_cliente
                    FROM Pedidos P
                    JOIN Direcciones D ON P.id_direccion_envio = D.id_direccion
                    JOIN Usuarios U ON P.id_usuario = U.id_usuario
                    WHERE P.id_pedido = ?";
    
    // Verificamos si la preparación de la consulta falló (Error de Sintaxis)
    if (!$stmt_pedido = $conn->prepare($sql_pedido)) {
        // DEVUELVE EL ERROR ESPECÍFICO DE MYSQL
        throw new Exception("Error SQL Pedido: " . $conn->error);
    }
    
    $stmt_pedido->bind_param("i", $id_pedido);
    $stmt_pedido->execute();
    $resultado_pedido = $stmt_pedido->get_result();

    if ($resultado_pedido->num_rows == 0) {
        throw new Exception("Pedido no encontrado en la base de datos.");
    }
    
    $datos_pedido = $resultado_pedido->fetch_assoc();
    $stmt_pedido->close();


    // 3. Obtener Detalles de los Productos del Pedido
    $sql_detalles = "SELECT 
                        DP.cantidad, 
                        DP.precio_unitario, 
                        Pr.titulo, 
                        Pr.autor
                     FROM DetallePedido DP
                     JOIN Productos Pr ON DP.id_producto = Pr.id_producto
                     WHERE DP.id_pedido = ?";
                     
    if (!$stmt_detalles = $conn->prepare($sql_detalles)) {
        // DEVUELVE EL ERROR ESPECÍFICO DE MYSQL
        throw new Exception("Error SQL Detalles: " . $conn->error);
    }
                     
    $stmt_detalles->bind_param("i", $id_pedido);
    $stmt_detalles->execute();
    $resultado_detalles = $stmt_detalles->get_result();
    
    $detalles = [];
    while ($fila = $resultado_detalles->fetch_assoc()) {
        $detalles[] = $fila;
    }
    $stmt_detalles->close();
    
    // 4. Construir la Respuesta Final JSON (El cuerpo se mantiene igual)
    $response = [
        'error' => false,
        'pedido' => [
            'id_pedido' => $datos_pedido['id_pedido'],
            'fecha_pedido' => date('d/m/Y H:i', strtotime($datos_pedido['fecha_pedido'])),
            'estado' => $datos_pedido['estado'],
            'metodo_pago' => $datos_pedido['metodo_pago'],
            'total' => $datos_pedido['total'],
            'costo_envio' => $datos_pedido['costo_envio'],
            'cliente' => trim($datos_pedido['nombre_cliente'] . ' ' . $datos_pedido['apellido_cliente']),
        ],
        'direccion' => [
            'calle_numero' => $datos_pedido['calle_numero'],
            'colonia' => $datos_pedido['colonia'],
            'ciudad' => $datos_pedido['ciudad'],
            'pais' => $datos_pedido['pais'],
        ],
        'detalles' => $detalles
    ];

} catch (Exception $e) {
    // Si hay un error de lógica o BD, lo capturamos aquí
    http_response_code(500); 
    $response = ['error' => 'Error de servidor: ' . $e->getMessage()];
}

$conn->close();
echo json_encode($response);
exit;