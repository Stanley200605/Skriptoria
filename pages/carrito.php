<?php
/**
 * carrito.php
 * Muestra el contenido del Carrito de Compras, la tabla de productos dinámica 
 * y la sección de Pago (Solo Tarjeta con integración Stripe).
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 
require_once '../includes/stripe_config.php';

// 1. Verificar Autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=Debes iniciar sesión para ver tu carrito.");
    exit;
}

$id_usuario = $_SESSION['user_id'];
$costo_envio = 50.00; 

// 2. Consulta para obtener los ítems del carrito (Lógica igual que antes)
$sql_carrito = "SELECT 
                C.id_carrito, 
                C.id_producto, 
                C.cantidad,
                P.titulo, 
                P.autor, 
                P.precio, 
                P.portada_url,
                P.stock 
              FROM Carrito C
              JOIN Productos P ON C.id_producto = P.id_producto
              WHERE C.id_usuario = $id_usuario";
              
$resultado_carrito = $conn->query($sql_carrito);
$items_carrito = [];
$subtotal_productos = 0;

if ($resultado_carrito) {
    while ($item = $resultado_carrito->fetch_assoc()) {
        $item['subtotal'] = $item['precio'] * $item['cantidad'];
        $subtotal_productos += $item['subtotal'];
        $items_carrito[] = $item;
    }
}

$total_a_pagar = $subtotal_productos + $costo_envio;

$mensaje_status = '';
if (isset($_GET['success'])) {
    $mensaje_status = '<div class="alert success-alert">✅ ¡Éxito! ' . htmlspecialchars($_GET['success']) . '</div>';
} elseif (isset($_GET['error'])) {
    $mensaje_status = '<div class="alert error-alert">❌ Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}

// Clave Pública de Stripe (Modo Test) - REEMPLAZA ESTO
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/carrito.css"> 
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .alert { padding: 10px; margin: 10px auto 20px auto; border-radius: 4px; font-weight: bold; width: 90%; max-width: 1000px; }
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

    <main class="cart-container">
        <h1 class="page-title">Carrito de Compras</h1>
        
        <?php echo $mensaje_status; ?>

        <div class="cart-layout">
            
            <section class="cart-items-section">
                <form action="../actions/procesar_carrito_update.php" method="POST">
                    
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th class="product-col">Producto</th>
                                <th class="qty-col">Cantidad</th>
                                <th class="price-col">Precio</th>
                                <th class="subtotal-col">Subtotal</th>
                                <th class="remove-col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items_carrito)): ?>
                                <?php foreach ($items_carrito as $item): ?>
                                <tr class="cart-item">
                                    <td class="product-info-cell">
                                        <img src="../<?php echo htmlspecialchars($item['portada_url']); ?>" alt="<?php echo htmlspecialchars($item['titulo']); ?>">
                                        <div>
                                            <h5 class="book-title"><?php echo htmlspecialchars($item['titulo']); ?></h5>
                                            <p class="book-author"><?php echo htmlspecialchars($item['autor']); ?></p>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" name="qty_<?php echo $item['id_carrito']; ?>" 
                                               value="<?php echo $item['cantidad']; ?>" 
                                               min="1" max="<?php echo $item['stock']; ?>" 
                                               class="qty-input">
                                    </td>
                                    <td>$<?php echo number_format($item['precio'], 2); ?> MXN</td>
                                    <td>$<?php echo number_format($item['subtotal'], 2); ?> MXN</td>
                                    <td>
                                        <a href="../actions/procesar_carrito_remove.php?id=<?php echo $item['id_carrito']; ?>">
                                            <button type="button" class="remove-item-btn"><i class="fas fa-times"></i></button>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px;">
                                        Tu carrito está vacío. <a href="../index.php">¡Empieza a comprar!</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-actions-bottom">
                        <a href="../index.php" class="continue-shopping-btn"><i class="fas fa-chevron-left"></i> Continuar Comprando</a>
                        <button type="submit" name="update_cart" class="update-cart-btn"><i class="fas fa-sync-alt"></i> Actualizar Carrito</button>
                    </div>
                </form>
            </section>

            <aside class="checkout-summary-section">
                <form id="payment-form" action="../actions/procesar_pago.php" method="POST">
                
                    <div class="summary-card">
                        <h2 class="summary-title">Resumen del Pedido</h2>
                        <div class="summary-details">
                            <p>Subtotal de Productos: <span>$<?php echo number_format($subtotal_productos, 2); ?> MXN</span></p>
                            <p>Costo de Envío: <span>$<?php echo number_format($costo_envio, 2); ?> MXN</span></p>
                            <p class="total-line">Total a Pagar: <span>$<?php echo number_format($total_a_pagar, 2); ?> MXN</span></p>
                        </div>
                    </div>

                    <div class="payment-card">
                        <h2 class="payment-title">Método de Pago</h2>
                        
                        <div class="payment-method-selector">
                            <div class="method-option active" data-method="card">
                                <input type="radio" id="pay-card" name="payment-method" value="Tarjeta" checked style="display: none;">
                                <label for="pay-card">Tarjeta de Crédito / Débito</label>
                                <p class="method-description">Paga de forma segura con Visa, Mastercard, American Express.</p>
                            </div>
                        </div>

                        <div id="card-form-container"> 
                            <h3 class="card-details-header">Detalles de la Tarjeta</h3>
                            
                            <div class="form-group">
                                <label for="card-element">Información de Tarjeta</label>
                                <div id="card-element" style="border: 1px solid #e0d9d4; padding: 10px; border-radius: 4px; background-color: white;">
                                </div>
                                <div id="card-errors" role="alert" style="color: red; font-size: 0.9em; margin-top: 5px;"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="name-on-card">Nombre en la Tarjeta</label>
                                <input type="text" id="name-on-card" name="name_on_card" required>
                            </div>
                        </div> 
                        
                        <input type="hidden" name="total_pago_cents" value="<?php echo round($total_a_pagar * 100); ?>">
                        <input type="hidden" name="payment-method" value="Tarjeta"> 
                        <button type="submit" name="btn_procesar_pago" class="place-order-btn" id="submit-button">
                            <i class="fas fa-lock"></i> Pagar $<?php echo number_format($total_a_pagar, 2); ?> MXN
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. INICIALIZACIÓN DE STRIPE
            const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
            const elements = stripe.elements();

            const style = { /* ... (Estilos de Stripe) ... */ };

            // Crear una instancia del elemento de tarjeta
            const card = elements.create('card');
            card.mount('#card-element');

            // Manejo de errores de validación en tiempo real
            card.on('change', function(event) {
                const displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // 2. MANEJO DEL ENVÍO DEL FORMULARIO (TOKENIZACIÓN)
            const form = document.getElementById('payment-form');
            const submitButton = document.getElementById('submit-button');

            form.addEventListener('submit', function(ev) {
                ev.preventDefault(); 
                
                // Deshabilitar botón para evitar doble clic
                submitButton.disabled = true;
                submitButton.textContent = 'Procesando...';

                // Crear el Token de Stripe
                stripe.createToken(card, {
                    name: document.getElementById('name-on-card').value 
                }).then(function(result) {
                    if (result.error) {
                        // FALLO en la tokenización (ej. dato mal formateado)
                        const errorElement = document.getElementById('card-errors');
                        errorElement.textContent = result.error.message;
                        submitButton.disabled = false; 
                        submitButton.innerHTML = '<i class="fas fa-lock"></i> Pagar $<?php echo number_format($total_a_pagar, 2); ?> MXN';
                    } else {
                        // ÉXITO: El token se genera. Adjuntar el token y enviar.
                        
                        const hiddenInput = document.createElement('input');
                        hiddenInput.setAttribute('type', 'hidden');
                        hiddenInput.setAttribute('name', 'stripeToken');
                        hiddenInput.setAttribute('value', result.token.id);
                        form.appendChild(hiddenInput);

                        // Enviar el formulario a procesar_pago.php
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>