<?php
/**
 * admin-productos.php
 * Vista principal de Gestión de Productos con formularios de Alta y Edición en modales.
 *
 * @autor: [Tu Nombre]
 * @fecha: 09/11/2025
 */

require_once '../includes/auth_check.php'; 
require_once '../includes/db_connection.php'; 

// 1. Verificar Rol de Administrador
verificar_rol(['Admin'], '../index.php'); 

// 2. Lógica de Carga de Productos desde la BD
$productos = [];
$total_productos = 0;
// Consulta SQL para obtener todos los productos
$sql_select = "SELECT id_producto, titulo, autor, precio, stock, genero, editorial, descripcion, idioma, num_paginas, fecha_publicacion, portada_url FROM Productos ORDER BY id_producto DESC";
$resultado = $conn->query($sql_select);

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = $fila;
    }
    $total_productos = count($productos);
}

// Lógica para mostrar mensajes de éxito/error después de acciones
$mensaje_status = '';
if (isset($_GET['success'])) {
    $mensaje_status = '<div class="alert success-alert">✅ ¡Operación exitosa! ' . htmlspecialchars($_GET['success']) . '</div>';
} elseif (isset($_GET['error'])) {
    $mensaje_status = '<div class="alert error-alert">❌ Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos | SKRIPTORIA ADMIN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Estilos básicos para alertas, añadir a admin.css */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success-alert { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-alert { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .modal-content-small { max-width: 450px; }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <div class="logo-area">SKRIPTORIA <span class="admin-label">ADMIN</span></div>
        <nav class="main-nav">
            <a href="../admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin-pedidos.php" class="nav-item"><i class="fas fa-box-open"></i> Pedidos</a>
            <a href="admin-productos.php" class="nav-item active"><i class="fas fa-book"></i> Productos</a>
            <a href="admin-clientes.php" class="nav-item"><i class="fas fa-users"></i> Clientes</a>
            <a href="admin-reportes.php" class="nav-item"><i class="fas fa-chart-line"></i> Reportes</a>
            <a href="admin-perfil.php" class="nav-item"><i class="fas fa-user-circle"></i> Mi Perfil</a>
        </nav>
        <div class="logout-area"><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
    </aside>

    <div class="admin-main-content">
        
        <header class="admin-header"></header>

        <section class="admin-content-area">
            <h1 class="content-header-title">Gestión de Productos</h1>
            
            <?php echo $mensaje_status; ?>

            <div class="table-controls-bar">
                <div class="filter-group">
                    <label for="stock-filter">Filtrar por Stock:</label>
                    <select id="stock-filter" class="control-select">
                        <option value="all">Todo el Inventario (<?php echo $total_productos; ?>)</option>
                    </select>
                    <label for="genre-filter">Género:</label>
                    <select id="genre-filter" class="control-select">
                        <option value="all">Todos los Géneros</option>
                    </select>
                </div>

                <div class="actions-group">
                    <button id="open-add-modal" class="control-button primary"><i class="fas fa-plus"></i> Añadir Nuevo</button>
                </div>
            </div>
            
            <div class="data-table-container">
                <table class="admin-data-table product-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Autor</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Género</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($total_productos > 0) {
                            foreach ($productos as $producto) {
                                // Lógica para el color y acción de stock (igual que antes)
                                $stock_class = 'high';
                                if ($producto['stock'] <= 5 && $producto['stock'] > 0) {
                                    $stock_class = 'low';
                                } elseif ($producto['stock'] == 0) {
                                    $stock_class = 'out';
                                }
                                
                                $accion_stock = ($producto['stock'] == 0) ? 
                                    '<button class="action-btn update primary btn-reabastecer" data-id="' . $producto['id_producto'] . '">Reabastecer</button>' : 
                                    '<button class="action-btn update btn-edit" data-id="' . $producto['id_producto'] . '" data-producto=\'' . json_encode($producto) . '\'><i class="fas fa-edit"></i></button>';
                        ?>
                        <tr>
                            <td>#<?php echo $producto['id_producto']; ?></td>
                            <td class="product-title-cell"><?php echo htmlspecialchars($producto['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($producto['autor']); ?></td>
                            <td>$<?php echo number_format($producto['precio'], 2); ?> MXN</td>
                            <td><span class="stock-tag <?php echo $stock_class; ?>"><?php echo $producto['stock']; ?></span></td>
                            <td><?php echo htmlspecialchars($producto['genero']); ?></td>
                            <td>
                                <?php echo $accion_stock; ?>
                                <button class="action-btn delete btn-delete" data-id="<?php echo $producto['id_producto']; ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="7" style="text-align: center; padding: 20px;">No se encontraron productos en el inventario.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class="pagination-admin">
                    <span>Mostrando 1 a <?php echo min(10, $total_productos); ?> de <?php echo $total_productos; ?> productos</span>
                    <div class="pagination-buttons">
                        <button class="control-button disabled">&laquo; Anterior</button>
                        <button class="control-button">Siguiente &raquo;</button>
                    </div>
                </div>
            </div>

        </section>

    </div>
    
    <div id="add-product-modal" class="modal"> 
        <div class="modal-content modal-content-large">
            <span class="close-btn add-product-close">&times;</span>
            <h2 class="modal-title">Registrar Nuevo Libro</h2>
            <form action="../actions/procesar_producto.php" method="POST" enctype="multipart/form-data" class="product-form">
                
                <div class="form-grid">
                    <div class="form-group"><label for="titulo">Título:</label><input type="text" id="titulo" name="titulo" required></div>
                    <div class="form-group"><label for="autor">Autor:</label><input type="text" id="autor" name="autor" required></div>
                    <div class="form-group"><label for="editorial">Editorial:</label><input type="text" id="editorial" name="editorial"></div>
                    <div class="form-group"><label for="precio">Precio (MXN):</label><input type="number" id="precio" name="precio" step="0.01" required></div>
                    <div class="form-group"><label for="stock">Stock:</label><input type="number" id="stock" name="stock" required></div>
                    <div class="form-group"><label for="idioma">Idioma:</label>
                        <select id="idioma" name="idioma" required><option value="Español">Español</option><option value="Inglés">Inglés</option><option value="Otro">Otro</option></select>
                    </div>
                    <div class="form-group"><label for="genero">Género:</label><input type="text" id="genero" name="genero" required placeholder="Ej: Fantasía, Clásico"></div>
                    <div class="form-group"><label for="num_paginas">Nro. Páginas:</label><input type="number" id="num_paginas" name="num_paginas"></div>
                    <div class="form-group"><label for="fecha_publicacion">Fecha Publicación:</label><input type="date" id="fecha_publicacion" name="fecha_publicacion"></div>
                </div>
                <div class="form-group-full"><label for="descripcion">Descripción:</label><textarea id="descripcion" name="descripcion" rows="4"></textarea></div>
                <div class="form-group-full"><label for="portada_file">Imagen de Portada:</label><input type="file" id="portada_file" name="portada_file" accept="image/*" required></div>

                <button type="submit" name="btn_guardar_producto" class="control-button primary submit-btn">
                    <i class="fas fa-save"></i> Guardar Producto
                </button>
            </form>
        </div>
    </div>

    <div id="edit-product-modal" class="modal"> 
        <div class="modal-content modal-content-large">
            <span class="close-btn edit-product-close">&times;</span>
            <h2 class="modal-title">Editar Libro #<span id="edit-product-id-display"></span></h2>
            
            <form id="edit-product-form" action="../actions/procesar_edicion.php" method="POST" enctype="multipart/form-data" class="product-form">
                
                <input type="hidden" name="id_producto" id="edit-id_producto">
                
                <div class="form-grid">
                    <div class="form-group"><label for="edit-titulo">Título:</label><input type="text" id="edit-titulo" name="titulo" required></div>
                    <div class="form-group"><label for="edit-autor">Autor:</label><input type="text" id="edit-autor" name="autor" required></div>
                    <div class="form-group"><label for="edit-editorial">Editorial:</label><input type="text" id="edit-editorial" name="editorial"></div>

                    <div class="form-group"><label for="edit-precio">Precio (MXN):</label><input type="number" id="edit-precio" name="precio" step="0.01" required></div>
                    <div class="form-group"><label for="edit-stock">Stock:</label><input type="number" id="edit-stock" name="stock" required></div>
                    <div class="form-group"><label for="edit-idioma">Idioma:</label>
                        <select id="edit-idioma" name="idioma" required>
                            <option value="Español">Español</option><option value="Inglés">Inglés</option><option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group"><label for="edit-genero">Género:</label><input type="text" id="edit-genero" name="genero" required></div>
                    <div class="form-group"><label for="edit-num_paginas">Nro. Páginas:</label><input type="number" id="edit-num_paginas" name="num_paginas"></div>
                    <div class="form-group"><label for="edit-fecha_publicacion">Fecha Publicación:</label><input type="date" id="edit-fecha_publicacion" name="fecha_publicacion"></div>
                </div>

                <div class="form-group-full"><label for="edit-descripcion">Descripción:</label><textarea id="edit-descripcion" name="descripcion" rows="4"></textarea></div>
                
                <div class="form-group-full">
                    <label>Portada Actual:</label>
                    <img id="current-cover" src="" alt="Portada Actual" style="max-width: 150px; margin-bottom: 10px;">
                </div>
                
                <div class="form-group-full">
                    <label for="edit-portada_file">Cambiar Imagen de Portada (Opcional):</label>
                    <input type="file" id="edit-portada_file" name="portada_file" accept="image/*">
                    <input type="hidden" name="portada_actual" id="portada_actual"> </div>

                <button type="submit" name="btn_editar_producto" class="control-button primary submit-btn" style="background-color: #a1885f;">
                    <i class="fas fa-sync-alt"></i> Actualizar Producto
                </button>
            </form>
        </div>
    </div>
    
    <div id="delete-confirm-modal" class="modal">
        <div class="modal-content modal-content-small">
            <span class="close-btn delete-confirm-close">&times;</span>
            <h2 class="modal-title" style="color: #e34c4c;"><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
            <p style="text-align: center; margin-bottom: 30px;">
                ¿Estás seguro de que deseas eliminar el producto **#<span id="delete-product-id-display"></span>**? <br>
                ¡Esta acción no se puede deshacer!
            </p>
            <form action="../actions/procesar_producto.php" method="POST" style="display: flex; justify-content: space-around;">
                <input type="hidden" name="id_producto_eliminar" id="id_producto_eliminar">
                <button type="button" class="control-button secondary delete-confirm-close">Cancelar</button>
                
                <button type="submit" name="btn_eliminar_producto" class="control-button primary" style="background-color: #e34c4c;">
                    <i class="fas fa-trash"></i> Sí, Eliminar
                </button>
            </form>
        </div>
    </div>


    <script>
        // Funciones de control de modales
        const addModal = document.getElementById("add-product-modal");
        const editModal = document.getElementById("edit-product-modal");
        const deleteModal = document.getElementById("delete-confirm-modal");
        
        function toggleModal(modal, display) {
            if (modal) modal.style.display = display;
        }

        // Abre el modal de AÑADIR
        document.getElementById("open-add-modal").onclick = function() {
            toggleModal(addModal, "block");
        };

        // Cierra los modales
        document.getElementsByClassName("add-product-close")[0].onclick = function() { toggleModal(addModal, "none"); };
        document.getElementsByClassName("edit-product-close")[0].onclick = function() { toggleModal(editModal, "none"); };
        document.getElementsByClassName("delete-confirm-close")[0].onclick = function() { toggleModal(deleteModal, "none"); };

        window.onclick = function(event) {
            if (event.target == addModal) { toggleModal(addModal, "none"); }
            if (event.target == editModal) { toggleModal(editModal, "none"); }
            if (event.target == deleteModal) { toggleModal(deleteModal, "none"); }
        }

        // --- Lógica de EDICIÓN (Cargar datos y abrir modal) ---
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const productoData = JSON.parse(this.getAttribute('data-producto'));
                
                // 1. Llenar los campos del modal de Edición
                document.getElementById('edit-product-id-display').textContent = productoData.id_producto;
                document.getElementById('edit-id_producto').value = productoData.id_producto;
                document.getElementById('edit-titulo').value = productoData.titulo;
                document.getElementById('edit-autor').value = productoData.autor;
                document.getElementById('edit-editorial').value = productoData.editorial;
                document.getElementById('edit-precio').value = productoData.precio;
                document.getElementById('edit-stock').value = productoData.stock;
                document.getElementById('edit-idioma').value = productoData.idioma;
                document.getElementById('edit-genero').value = productoData.genero;
                document.getElementById('edit-num_paginas').value = productoData.num_paginas || '';
                
                // Formato de fecha para input type="date"
                document.getElementById('edit-fecha_publicacion').value = productoData.fecha_publicacion ? productoData.fecha_publicacion.substring(0, 10) : '';
                
                document.getElementById('edit-descripcion').value = productoData.descripcion;
                
                // Imagen: mostrar actual y guardar ruta para UPDATE
                document.getElementById('current-cover').src = '../' + productoData.portada_url;
                document.getElementById('portada_actual').value = productoData.portada_url;
                
                // 2. Abrir el modal
                toggleModal(editModal, "block");
            });
        });

        // --- Lógica de ELIMINACIÓN (Abrir modal de confirmación) ---
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                const productoId = this.getAttribute('data-id');
                
                // 1. Llenar el modal de Confirmación
                document.getElementById('delete-product-id-display').textContent = productoId;
                document.getElementById('id_producto_eliminar').value = productoId;
                
                // 2. Abrir el modal
                toggleModal(deleteModal, "block");
            });
        });

    </script>
</body>
</html>