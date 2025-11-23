<?php
/**
 * detalle-libro.php
 * Muestra la información detallada de un libro específico, sus características y comentarios.
 * Recibe el ID del producto por GET (ej: ?id=1).
 *
 * @autor: [Tu Nombre]
 * @fecha: 22/11/2025
 */

session_start();
require_once '../includes/db_connection.php'; 

// Identificar si el usuario está logueado
$user_logged_in = isset($_SESSION['user_id']);
$id_usuario = $user_logged_in ? $_SESSION['user_id'] : 0;
$libro_en_deseos = false; // Bandera para saber si el libro está en favoritos

// 1. Obtener y validar el ID del producto
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_producto = (int)$_GET['id'];
} else {
    header("Location: ../index.php");
    exit;
}

// 2. Consulta para obtener los detalles del producto (tabla Productos)
$sql_libro = "SELECT * FROM Productos WHERE id_producto = $id_producto AND activo = TRUE LIMIT 1";
$resultado_libro = $conn->query($sql_libro);

if (!$resultado_libro || $resultado_libro->num_rows == 0) {
    header("Location: ../index.php?error=libro_no_encontrado");
    exit;
}
$libro = $resultado_libro->fetch_assoc();

// 3. Verificar si el libro ya está en la lista de deseos del usuario
if ($user_logged_in) {
    $sql_check_deseo = "SELECT id_deseo FROM ListaDeseos 
                        WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
    $resultado_check = $conn->query($sql_check_deseo);
    if ($resultado_check && $resultado_check->num_rows > 0) {
        $libro_en_deseos = true;
    }
}

// 4. Consulta para obtener las Calificaciones y Comentarios (tabla Calificaciones)
$sql_comentarios = "SELECT C.comentario, C.puntuacion, C.fecha_calificacion, U.nombre
                    FROM Calificaciones C
                    JOIN Usuarios U ON C.id_usuario = U.id_usuario
                    WHERE C.id_producto = $id_producto
                    ORDER BY C.fecha_calificacion DESC"; // Eliminé el LIMIT 5 para mostrar todos
                                                        // o puedes ajustarlo a 10 si hay muchos
$resultado_comentarios = $conn->query($sql_comentarios);
$comentarios = [];
if ($resultado_comentarios) {
    while ($fila = $resultado_comentarios->fetch_assoc()) {
        $comentarios[] = $fila;
    }
}

// 5. Función para generar las estrellas de calificación
function get_rating_stars($puntuacion) {
    $html = '';
    $puntuacion_redondeada = round($puntuacion);
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $puntuacion_redondeada) {
            $html .= '<i class="fas fa-star filled"></i>'; // Estrella llena (fas)
        } else {
            $html .= '<i class="far fa-star"></i>'; // Estrella vacía (far)
        }
    }
    return $html;
}

// Lógica de mensajes
$mensaje_status = '';
if (isset($_GET['success'])) {
    $mensaje_status = '<div class="alert success-alert">✅ ¡Éxito! ' . htmlspecialchars($_GET['success']) . '</div>';
} elseif (isset($_GET['error'])) {
    $mensaje_status = '<div class="alert error-alert">❌ Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle: <?php echo htmlspecialchars($libro['titulo']); ?> | SKRIPTORIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/detalle.css"> 
    <style>
        .rating .fas.filled, #star-rating .fas { color: #a1885f; }
        .rating .far, #star-rating .far { color: #ccc; }
        .alert { padding: 10px; margin: 10px auto 20px auto; border-radius: 4px; font-weight: bold; width: 90%; max-width: 1000px; }
        .success-alert { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-alert { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .comment-card { display: flex; margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px; }
        .comment-card .user-icon { font-size: 24px; margin-right: 15px; color: #a1885f; }
        .comment-card .user-info p { margin-top: 5px; }
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

    <main class="detail-page-container">
        
        <?php echo $mensaje_status; ?>

        <div class="book-details-header">
            
            <img src="../<?php echo htmlspecialchars($libro['portada_url']); ?>" alt="Portada <?php echo htmlspecialchars($libro['titulo']); ?>">
            
            <div class="details-info">
                <h4><?php echo htmlspecialchars($libro['autor']); ?></h4>
                
                <div class="title-and-price-row">
                    <div class="details-text-group">
                        <h1><?php echo htmlspecialchars($libro['titulo']); ?></h1>
                        <p class="edition"><?php echo htmlspecialchars($libro['editorial']); ?></p>
                    </div>
                    
                    <div class="purchase-options-inline">
                        <div class="price-inline">$<?php echo number_format($libro['precio'], 2); ?> MXN</div>
                        
                        <form action="../actions/procesar_carrito.php" method="GET" style="display: contents;">
                            <input type="hidden" name="id" value="<?php echo $libro['id_producto']; ?>">
                            <input type="hidden" name="qty" value="1">
                            <input type="hidden" name="action" value="add">
                            
                            <button type="submit" class="buy-btn" <?php echo ($libro['stock'] == 0) ? 'disabled' : ''; ?>>
                                Comprar
                            </button>
                            <button type="submit" class="cart-btn" <?php echo ($libro['stock'] == 0) ? 'disabled' : ''; ?>>
                                Agregar al carrito
                            </button>
                        </form>
                    </div>
                </div>
                
                <a href="../actions/procesar_deseo.php?id=<?php echo $libro['id_producto']; ?>" class="fav-link-top">
                    <i class="<?php echo $libro_en_deseos ? 'fas' : 'far'; ?> fa-heart"></i>
                    <?php echo $libro_en_deseos ? 'Eliminar de favoritos' : 'Agregar a favoritos'; ?>
                </a>
                
                <div class="rating-and-fav-row">
                    <div class="rating">
                        <?php echo get_rating_stars($libro['promedio_calificacion']); ?>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <div class="sections-grid">
            
            <section class="section-description">
                <h2>Descripción</h2>
                <p><?php echo nl2br(htmlspecialchars($libro['descripcion'])); ?></p>
            </section>
            
            <section class="section-characteristics">
                <h2>Características</h2>
                <ul>
                    <li><strong>Idioma:</strong> <?php echo htmlspecialchars($libro['idioma']); ?></li>
                    <li><strong>Género:</strong> <?php echo htmlspecialchars($libro['genero']); ?></li>
                    <li><strong>Editorial:</strong> <?php echo htmlspecialchars($libro['editorial']); ?></li>
                    <li><strong>Fecha de publicación:</strong> <?php echo date('d/m/Y', strtotime($libro['fecha_publicacion'])); ?></li>
                    <li><strong>No. páginas:</strong> <?php echo htmlspecialchars($libro['num_paginas']); ?></li>
                    <li><strong>Stock:</strong> <span style="font-weight: bold; color: <?php echo ($libro['stock'] > 5) ? 'green' : (($libro['stock'] > 0) ? 'orange' : 'red'); ?>;"><?php echo $libro['stock']; ?> unidades</span></li>
                </ul>
            </section>
            
        </div>
        
        <section class="section-comments">
            <h2>Comentarios (<?php echo count($comentarios); ?>)</h2>
            
            <div class="comment-input-area">
                
                <?php if ($user_logged_in): ?>
                
                    <form action="../actions/procesar_comentario.php" method="POST" id="comment-form">
                        <input type="hidden" name="id_producto" value="<?php echo $libro['id_producto']; ?>">
                        
                        <div class="rating-input" style="margin-bottom: 10px;">
                            <label>Tu Calificación:</label>
                            <span id="star-rating">
                                <i class="far fa-star star" data-value="1"></i>
                                <i class="far fa-star star" data-value="2"></i>
                                <i class="far fa-star star" data-value="3"></i>
                                <i class="far fa-star star" data-value="4"></i>
                                <i class="far fa-star star" data-value="5"></i>
                            </span>
                            <input type="hidden" name="puntuacion" id="puntuacion-input" value="0" required>
                        </div>

                        <input type="text" name="comentario" placeholder="Escribe tu comentario (Opcional)" required>
                        <button type="submit" class="submit-comment-btn">Enviar</button>
                    </form>
                    
                <?php else: ?>
                    <p>Por favor, <a href="#" id="open-login-comment">inicia sesión</a> para dejar un comentario y calificación.</p>
                <?php endif; ?>
            </div>

            <?php if (count($comentarios) > 0): ?>
                <?php foreach ($comentarios as $comentario): ?>
                <div class="comment-card">
                    <div class="user-icon"><i class="fas fa-user-circle"></i></div>
                    <div class="user-info">
                        <strong>
                            <?php echo htmlspecialchars($comentario['nombre']); ?>
                            (<?php echo get_rating_stars($comentario['puntuacion']); ?>)
                        </strong>
                        <p><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></p>
                        <small>Publicado: <?php echo date('d/m/Y', strtotime($comentario['fecha_calificacion'])); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Sé el primero en dejar un comentario sobre este libro.</p>
            <?php endif; ?>
            </section>

    </main>

    <footer class="main-footer">
        </footer>
    
    <div id="login-modal" class="modal">
        </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('#star-rating .star');
            const puntuacionInput = document.getElementById('puntuacion-input');
            const loginLinkComment = document.getElementById('open-login-comment');

            // Lógica para el rating de estrellas
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    puntuacionInput.value = value;
                    highlightStars(value);
                });

                star.addEventListener('mouseover', function() {
                    highlightStars(parseInt(this.getAttribute('data-value')));
                });

                star.addEventListener('mouseout', function() {
                    highlightStars(parseInt(puntuacionInput.value));
                });
            });

            function highlightStars(value) {
                stars.forEach((star, index) => {
                    if (index < value) {
                        star.classList.remove('far');
                        star.classList.add('fas');
                    } else {
                        star.classList.remove('fas');
                        star.classList.add('far');
                    }
                });
            }

            // Asegura que el botón de login del comentario abra el modal principal
            if (loginLinkComment) {
                loginLinkComment.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Lógica para abrir el modal (debes tener la función openModal() disponible)
                    const loginModal = document.getElementById('login-modal');
                    if (loginModal) {
                        loginModal.style.display = 'block'; 
                    }
                });
            }
            
            // Inicializar el estado de las estrellas al cargar
            highlightStars(parseInt(puntuacionInput.value));
        });
    </script>
    
</body>
</html>