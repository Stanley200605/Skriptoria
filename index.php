<?php
/**
 * index.php
 * Página principal (Catálogo) de SKRIPTORIA Online Bookstore.
 * Implementa filtros dinámicos (Género, Idioma, Estrellas, Precios) y búsqueda.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

session_start();
require_once 'includes/db_connection.php'; 

// ----------------------------------------------------
// 1. OBTENER Y SANEAR DATOS DE FILTROS (GET)
// ----------------------------------------------------

$condiciones = ["activo = TRUE"];
$generos_seleccionados = isset($_GET['genero']) ? $_GET['genero'] : [];
$idiomas_seleccionados = isset($_GET['idioma']) ? $_GET['idioma'] : [];
$estrellas_minimas = isset($_GET['estrellas']) ? (int)$_GET['estrellas'] : 0; 
$precio_min_raw = isset($_GET['precio_min']) ? $_GET['precio_min'] : '';
$precio_max_raw = isset($_GET['precio_max']) ? $_GET['precio_max'] : '';

$precio_min = (float)str_replace(['$', ',', ' '], '', $precio_min_raw);
$precio_max = (float)str_replace(['$', ',', ' '], '', $precio_max_raw);
$search_query = isset($_GET['search_query']) ? $conn->real_escape_string($_GET['search_query']) : '';

// --- Búsqueda (Título o Autor) ---
if (!empty($search_query)) {
    $condiciones[] = "(titulo LIKE '%$search_query%' OR autor LIKE '%$search_query%')";
}
// --- Filtrado por Categorías y Precios (Cuerpo igual que antes) ---
if (!empty($generos_seleccionados)) {
    $generos_escapados = array_map(function($g) use ($conn) { return "'" . $conn->real_escape_string($g) . "'"; }, $generos_seleccionados);
    $condiciones[] = "genero IN (" . implode(",", $generos_escapados) . ")";
}
if (!empty($idiomas_seleccionados)) {
    $idiomas_escapados = array_map(function($i) use ($conn) { return "'" . $conn->real_escape_string($i) . "'"; }, $idiomas_seleccionados);
    $condiciones[] = "idioma IN (" . implode(",", $idiomas_escapados) . ")";
}
if ($estrellas_minimas > 0) {
    $condiciones[] = "promedio_calificacion >= " . $estrellas_minimas;
}
$precio_final_min = !empty($precio_min_raw) ? $precio_min : 0;
$precio_final_max = !empty($precio_max_raw) ? $precio_max : 10000;

if ($precio_final_min >= 0 && $precio_final_max > 0 && $precio_final_min <= $precio_final_max) {
     $condiciones[] = "precio BETWEEN $precio_final_min AND $precio_final_max";
}

// 2. EJECUTAR LA CONSULTA FINAL
$where_clause = "WHERE " . implode(" AND ", $condiciones);

$sql_select = "SELECT id_producto, titulo, autor, precio, portada_url, promedio_calificacion 
               FROM Productos 
               $where_clause
               ORDER BY titulo ASC";
               
$resultado = $conn->query($sql_select);
$productos = [];
$total_productos = 0;
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) { $productos[] = $fila; }
    $total_productos = count($productos);
}

// Carga de opciones para filtros dinámicos (Cuerpo igual que antes)
$generos_disponibles = $conn->query("SELECT DISTINCT genero FROM Productos WHERE activo = TRUE AND genero IS NOT NULL ORDER BY genero")->fetch_all(MYSQLI_ASSOC);
$idiomas_disponibles = $conn->query("SELECT DISTINCT idioma FROM Productos WHERE activo = TRUE AND idioma IS NOT NULL ORDER BY idioma")->fetch_all(MYSQLI_ASSOC);

// Función para estrellas (visualización)
function get_rating_stars($puntuacion) {
    $html = '';
    $puntuacion_redondeada = round($puntuacion);
    for ($i = 1; $i <= 5; $i++) {
        $class = ($i <= $puntuacion_redondeada) ? 'fas filled' : 'far';
        $html .= '<i class="' . $class . ' fa-star"></i>';
    }
    return $html;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKRIPTORIA - Online Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="top-nav-bar">
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="search_query" placeholder="Buscar..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer;">
                    <i class="fas fa-search" style="color: white;"></i>
                </button>
            </form>
            
            <div class="user-actions">
                <a href="index.php"><i class="fas fa-home" style="color: white; font-size: 25px;"></i></a>
                <a href="#" id="open-login"><i class="fas fa-user" style="color: white; font-size: 25px;"></i></a>
                <a href="pages/perfil-deseos.php"><i class="fas fa-heart" style="color: white; font-size: 25px;"></i></a>
                <a href="pages/carrito.php"><i class="fas fa-shopping-cart" style="color: white; font-size: 25px;"></i></a>
            </div>
        </div>

        <div class="hero-banner">
            <h1>SKRIPTORIA</h1> 
            <p>ONLINE BOOKSTORE</p>
        </div>
    </header>

    <main class="content-container">
        
        <div class="section-intro-banner"> 
            <h2 class="section-title">¡Conoce todo nuestro extenso catálogo!</h2>
            <p class="section-subtitle">Tenemos una amplia gama de libros de diferentes índoles e intereses</p>
        </div>
        
        <div class="main-layout">
            
            <aside class="sidebar-filters">
                <form action="index.php" method="GET">
                    
                    <?php if (!empty($search_query)): ?>
                        <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>
                    
                    <div class="filter-block">
                        <h3>Género</h3>
                        <?php foreach ($generos_disponibles as $g): ?>
                            <?php $genero_val = htmlspecialchars($g['genero']); ?>
                            <label>
                                <input type="checkbox" name="genero[]" value="<?php echo $genero_val; ?>" 
                                       <?php echo in_array($genero_val, $generos_seleccionados) ? 'checked' : ''; ?>> 
                                <?php echo $genero_val; ?>
                            </label>
                        <?php endforeach; ?>
                        <a href="#" class="more-link"> Más</a>
                    </div>
                    
                    <div class="filter-block">
                        <h3>Idioma</h3>
                        <?php foreach ($idiomas_disponibles as $i): ?>
                            <?php $idioma_val = htmlspecialchars($i['idioma']); ?>
                            <label>
                                <input type="checkbox" name="idioma[]" value="<?php echo $idioma_val; ?>" 
                                       <?php echo in_array($idioma_val, $idiomas_seleccionados) ? 'checked' : ''; ?>> 
                                <?php echo $idioma_val; ?>
                            </label>
                        <?php endforeach; ?>
                        <a href="#" class="more-link"> Más</a>
                    </div>
                    
                    <div class="filter-block">
                        <h3>Estrellas</h3>
                        <label>
                            <input type="radio" name="estrellas" value="0" 
                                   <?php echo ($estrellas_minimas == 0) ? 'checked' : ''; ?>> 
                            Ver Todo
                        </label>
                        <?php for ($i = 5; $i >= 2; $i--): ?>
                            <label>
                                <input type="radio" name="estrellas" value="<?php echo $i; ?>" 
                                       <?php echo ($estrellas_minimas == $i) ? 'checked' : ''; ?>> 
                                <?php echo get_rating_stars($i); ?> <?php echo $i; ?>
                            </label>
                        <?php endfor; ?>
                        <a href="#" class="more-link"> Más</a>
                    </div>
                    
                    <div class="filter-block price-range">
                        <h3>Precios</h3>
                        <div class="price-inputs">
                            <input type="text" name="precio_min" placeholder="$300" 
                                   value="<?php echo !empty($precio_min_raw) ? htmlspecialchars($precio_min_raw) : ''; ?>">
                            <span>a</span>
                            <input type="text" name="precio_max" placeholder="$10,000" 
                                   value="<?php echo !empty($precio_max_raw) ? htmlspecialchars($precio_max_raw) : ''; ?>">
                        </div>
                        <button type="submit" class="search-button">BUSCAR</button>
                    </div>
                </form>
            </aside>

            <section class="product-grid">
                <?php 
                if ($total_productos > 0) {
                    foreach ($productos as $libro) {
                        $imagen_src = htmlspecialchars($libro['portada_url']);
                        $detalle_url = 'pages/detalle-libro.php?id=' . $libro['id_producto'];
                ?>
                <div class="book-card">
                    <a href="<?php echo $detalle_url; ?>"> 
                        <img src="<?php echo $imagen_src; ?>" alt="Portada <?php echo htmlspecialchars($libro['titulo']); ?>">
                    </a>
                    <a href="actions/procesar_deseo.php?id=<?php echo $libro['id_producto']; ?>" class="add-to-favs" title="Agregar a Lista de Deseos">
                    <i class="far fa-heart"></i>
                    </a>
                    <h4><?php echo htmlspecialchars($libro['titulo']); ?></h4>
                    <p class="book-author"><?php echo htmlspecialchars($libro['autor']); ?></p>
                    <div class="rating-display">
                        <?php echo get_rating_stars($libro['promedio_calificacion']); ?>
                    </div>
                    <p class="book-price">$<?php echo number_format($libro['precio'], 2); ?> MXN</p>
                </div>
                <?php 
                    }
                } else {
                    $criterios = !empty($search_query) ? ' con la búsqueda "' . htmlspecialchars($search_query) . '"' : ' con los filtros seleccionados';
                    echo '<p style="grid-column: 1 / -1; text-align: center; padding: 50px;">No se encontraron libros que coincidan' . $criterios . '.</p>';
                }
                ?>
                
                <div class="load-more">
                    <a href="#"><i class="fas fa-chevron-down"></i> Más</a>
                </div>
            </section>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-section about">
                <h3>SKRIPTORIA</h3>
                <p>Tu librería online de confianza.<br>Ofrecemos una selección curada de literatura clásica y contemporánea para cada interés.</p>
                <div class="contact-info">
                    <span><i class="fas fa-map-marker-alt"></i> Ciudad, País</span>
                    <span><i class="fas fa-envelope"></i> info@skriptoria.com</span>
                </div>
            </div>
            <div class="footer-section links">
                <h3>Navegación Rápida</h3>
                <ul>
                    <li><a href="#">Catálogo Completo</a></li>
                    <li><a href="#">Nuevos Lanzamientos</a></li>
                    <li><a href="#">Autores</a></li>
                    <li><a href="#">Ofertas Especiales</a></li>
                    <li><a href="#">Blog Literario</a></li>
                </ul>
            </div>
            <div class="footer-section social">
                <h3>Síguenos</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2025 SKRIPTORIA Online Bookstore | Todos los derechos reservados.
        </div>
    </footer>


    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn login-close">&times;</span>
            <h2 class="modal-title">Iniciar Sesión</h2>
            <form class="login-form" action="actions/procesar_login.php" method="POST">
                <input type="email" name="email" placeholder="Correo Electrónico" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit" name="btn_login" class="login-button">Entrar</button>
                <p class="forgot-password"><a href="#" id="open-forgot-link">¿Olvidaste tu contraseña?</a></p>
                <hr class="modal-divider">
                <p class="register-link">¿No tienes cuenta? <a href="#" id="open-register-link">Regístrate</a></p>
            </form>
        </div>
    </div>
    
    <div id="register-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn register-close">&times;</span>
            <h2 class="modal-title">Crear Cuenta</h2>
            <form class="register-form" action="actions/procesar_registro.php" method="POST">
                <input type="text" name="nombre_completo" placeholder="Nombre Completo" required>
                <input type="email" name="email" placeholder="Correo Electrónico" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <input type="password" name="confirm_password" placeholder="Confirmar Contraseña" required>
                <button type="submit" name="btn_registro" class="login-button">Registrarme</button>
                <hr class="modal-divider">
                <p class="login-link-toggle">¿Ya tienes cuenta? <a href="#" id="open-login-from-register">Iniciar Sesión</a></p>
            </form>
        </div>
    </div>
    
    <div id="forgot-password-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn forgot-close">&times;</span>
            <h2 class="modal-title">Recuperar Contraseña</h2>
            <form class="forgot-form">
                <p>Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
                <input type="email" placeholder="Correo Electrónico" required>
                <button type="submit" class="login-button">Enviar Enlace</button>
                <hr class="modal-divider">
                <p class="login-link-toggle"><a href="#" id="open-login-from-forgot">Volver a Iniciar Sesión</a></p>
            </form>
        </div>
    </div>


    <script>
        // --- Referencias a los Modales ---
        var loginModal = document.getElementById("login-modal");
        var registerModal = document.getElementById("register-modal");
        var forgotModal = document.getElementById("forgot-password-modal");
        
        // Botones y Enlaces para abrir/cerrar
        var openLoginBtn = document.getElementById("open-login");
        var openRegisterLink = document.getElementById("open-register-link");
        var openLoginFromRegisterLink = document.getElementById("open-login-from-register");
        var openForgotLink = document.getElementById("open-forgot-link");
        var openLoginFromForgotLink = document.getElementById("open-login-from-forgot");
        
        // --- Función para Abrir/Cerrar Modales ---
        function closeModal(modal) {
            if (modal) modal.style.display = "none";
        }

        function openModal(modal) {
            if (modal) modal.style.display = "block";
        }

        // --- Manejadores de Eventos ---

        // 1. Abrir Modal de Login (desde ícono)
        if (openLoginBtn) {
            openLoginBtn.onclick = function() {
                // Alerta para fines de debug si no estás logueado
                // alert('Abriendo Login'); 
                openModal(loginModal);
            };
        }

        // 2. Intercambio: Login -> Registro
        if (openRegisterLink) {
            openRegisterLink.onclick = function(e) {
                e.preventDefault();
                closeModal(loginModal);
                openModal(registerModal);
            };
        }

        // 3. Intercambio: Registro -> Login
        if (openLoginFromRegisterLink) {
            openLoginFromRegisterLink.onclick = function(e) {
                e.preventDefault();
                closeModal(registerModal);
                openModal(loginModal);
            };
        }
        
        // 4. Intercambio: Login -> Olvidaste Contraseña
        if (openForgotLink) {
            openForgotLink.onclick = function(e) {
                e.preventDefault();
                closeModal(loginModal);
                openModal(forgotModal);
            };
        }

        // 5. Intercambio: Olvidaste Contraseña -> Login
        if (openLoginFromForgotLink) {
            openLoginFromForgotLink.onclick = function(e) {
                e.preventDefault();
                closeModal(forgotModal);
                openModal(loginModal);
            };
        }

        // 6. Cerrar Modales (usando el botón X o clic fuera)
        document.querySelectorAll('.close-btn').forEach(span => {
            span.onclick = function() {
                closeModal(loginModal);
                closeModal(registerModal);
                closeModal(forgotModal);
            }
        });

        // Cierre al hacer clic fuera
        window.onclick = function(event) {
            if (event.target == loginModal) {
                closeModal(loginModal);
            }
            if (event.target == registerModal) {
                closeModal(registerModal);
            }
            if (event.target == forgotModal) {
                closeModal(forgotModal);
            }
        }
    </script>
</body>
</html>