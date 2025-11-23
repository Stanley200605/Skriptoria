<?php
/**
 * procesar_pago.php
 * Procesa la compra completa: Incluye la llamada a la API de Stripe.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 
require_once '../includes/stripe_config.php'; 

$pagina_redireccion_exito = '../pages/perfil-pedidos.php'; 
$pagina_redireccion_error = '../pages/carrito.php';       
$costo_envio = 50.00;

// 1. Validar Petición y Obtener Datos de Stripe
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=Debes iniciar sesión para finalizar la compra.");
    exit;
}

// *** Lógica de Validación y Obtención de Mismo Nivel ***
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['stripeToken']) || !isset($_POST['total_pago_cents'])) {
    header("Location: " . $pagina_redireccion_error . "?error=Error interno: Faltan datos críticos para el pago. Intente nuevamente.");
    exit;
}

// **CORRECCIÓN CLAVE: Obtener $monto_cents aquí.**
$stripe_token = trim($_POST['stripeToken']);
$monto_cents = (int)$_POST['total_pago_cents']; 
$email_cliente = $_SESSION['user_email'] ?? 'cliente_skriptoria@ejemplo.com';

// 2. Obtener datos del usuario y del formulario
$id_usuario = $_SESSION['user_id'];
$metodo_pago = $_POST['payment-method'];
$estado_pedido = 'Pendiente'; 

// *** CORRECCIÓN CLAVE 2: Inicializar $id_pedido_nuevo ANTES del try ***
$id_pedido_nuevo = 0; 
$conn->autocommit(false);
$transaccion_exitosa = true;
$error_msg = '';

try {
    
    // 4. Obtener ítems del carrito (Lógica de Stock y Totales)
    $sql_carrito = "SELECT C.id_producto, C.cantidad, P.precio, P.stock FROM Carrito C JOIN Productos P ON C.id_producto = P.id_producto WHERE C.id_usuario = $id_usuario";
    $resultado_carrito = $conn->query($sql_carrito);
    $items_carrito = [];
    $subtotal_productos = 0;

    if (!$resultado_carrito || $resultado_carrito->num_rows == 0) {
        throw new Exception("Tu carrito está vacío, no se puede procesar el pago.");
    }

    while ($item = $resultado_carrito->fetch_assoc()) {
        if ($item['cantidad'] > $item['stock']) {
            throw new Exception("El producto con ID " . $item['id_producto'] . " no tiene suficiente stock.");
        }
        $subtotal_productos += $item['precio'] * $item['cantidad'];
        $items_carrito[] = $item;
    }
    
    $total_a_pagar = $subtotal_productos + $costo_envio; // Calcular el total en pesos para la BD

    // Validar que el monto enviado por JS coincida con el cálculo del servidor
    // $total_calculado_cents = round(($total_a_pagar) * 100);
    $total_calculado_cents = (int) round($total_a_pagar * 100);
    if ($monto_cents !== $total_calculado_cents) {
    // Si el error persiste, podemos registrar el valor real de ambas variables 
    // en el log para ver la discrepancia, pero la solución está en forzar la precisión.
    throw new Exception("Error de validación del monto. (Discrepancia: Servidor calculó: $total_calculado_cents, Recibido: $monto_cents)");
}
    

    /* *******************************************
     * ETAPA STRIPE: CREAR EL CARGO SIMULADO
     * *******************************************/

    $charge = \Stripe\Charge::create([
        'amount' => $monto_cents, // Usa la variable obtenida del POST
        'currency' => CURRENCY, 
        'description' => 'Compra en SKRIPTORIA - Usuario ID: ' . $id_usuario,
        'source' => $stripe_token,
        'receipt_email' => $email_cliente,
    ]);

    if ($charge->status !== 'succeeded') {
        throw new Exception("El pago fue rechazado por Stripe. Motivo: " . ($charge->failure_message ?? 'Error desconocido.'));
    }

    // SI STRIPE DEVUELVE ÉXITO, CONTINUAMOS CON LA BD (COMMIT)

    /* *******************************************
     * ETAPA BD 1: CREAR EL PEDIDO PRINCIPAL
     * *******************************************/

    $fecha_pedido = date('Y-m-d H:i:s');
    
    // Obtener ID de dirección predeterminada
    $sql_direccion = "SELECT id_direccion FROM Direcciones WHERE id_usuario = $id_usuario AND predeterminada = 1 LIMIT 1";
    $res_direccion = $conn->query($sql_direccion);
    
    if (!$res_direccion || $res_direccion->num_rows == 0) {
        throw new Exception("No tienes una dirección predeterminada configurada. Ve a 'Direcciones' para añadirla.");
    }
    
    $id_direccion = $res_direccion->fetch_assoc()['id_direccion'];

    $sql_pedido = "INSERT INTO Pedidos (
                    id_usuario, id_direccion_envio, fecha_pedido, total, metodo_pago, estado, costo_envio
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt_pedido = $conn->prepare($sql_pedido);

    // Cadena de tipos: iisdsdd (int, int, string, double, string, string, double)
    $stmt_pedido->bind_param("iisdssd", 
        $id_usuario, $id_direccion, $fecha_pedido, $total_a_pagar, $metodo_pago, $estado_pedido, $costo_envio
    );

    if (!$stmt_pedido->execute()) {
        $db_error = $stmt_pedido->error;
        $stmt_pedido->close();
        throw new Exception("Fallo al insertar pedido principal: " . $db_error);
    }
    $id_pedido_nuevo = $conn->insert_id;
    $stmt_pedido->close(); 

    /* *******************************************
     * ETAPA BD 2 Y 3: CREAR DETALLES, ACTUALIZAR STOCK Y VACIAR CARRITO
     * *******************************************/
    
    $sql_detalle = "INSERT INTO DetallePedido (id_pedido, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $conn->prepare($sql_detalle);
    
    $sql_stock_update = "UPDATE Productos SET stock = stock - ? WHERE id_producto = ?";
    $stmt_stock = $conn->prepare($sql_stock_update);

    foreach ($items_carrito as $item) {
        $stmt_detalle->bind_param("iiid", $id_pedido_nuevo, $item['id_producto'], $item['cantidad'], $item['precio']);
        if (!$stmt_detalle->execute()) { throw new Exception("Fallo al insertar detalle: " . $stmt_detalle->error); }
        $stmt_stock->bind_param("ii", $item['cantidad'], $item['id_producto']);
        if (!$stmt_stock->execute()) { throw new Exception("Fallo al actualizar stock: " . $stmt_stock->error); }
    }
    $stmt_detalle->close();
    $stmt_stock->close();

    $sql_vaciar = "DELETE FROM Carrito WHERE id_usuario = $id_usuario";
    if ($conn->query($sql_vaciar) === FALSE) { throw new Exception("Fallo al vaciar el carrito: " . $conn->error); }

    // SI TODO FUE EXITOSO (Stripe y BD)
    $conn->commit();
    $transaccion_exitosa = true;

} catch (\Stripe\Exception\CardException $e) {
    $error_msg = $e->getError()->message;
} catch (\Exception $e) {
    $error_msg = "Error: " . $e->getMessage();
}

// 5. FINALIZACIÓN Y REDIRECCIÓN

$conn->autocommit(true); 

// Comprobación Final: Solo si la bandera es true Y el ID es mayor a 0 (confirmando inserción)
if ($transaccion_exitosa && $id_pedido_nuevo > 0) {
    $conn->close();
    // ¡La variable $id_pedido_nuevo está disponible y correcta!
    $mensaje = "¡Tu pedido (#$id_pedido_nuevo) se ha realizado con éxito! El pago con Stripe fue APROBADO (Modo Test)."; 
    header("Location: " . $pagina_redireccion_exito . "?success=" . urlencode($mensaje));
} else {
    // Si algo falló, revertir los cambios de BD si la transacción llegó lejos
    if ($conn->in_transaction) $conn->rollback();
    $conn->close();
    // Si falló, pero no se capturó un error específico, forzar un mensaje genérico
    if (!isset($error_msg) || empty($error_msg)) {
        $error_msg = "Ocurrió un error inesperado al procesar la BD. Intente de nuevo.";
    }
    header("Location: " . $pagina_redireccion_error . "?error=" . urlencode($error_msg));
}

exit;
?>