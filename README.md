# 📚 SKRIPTORIA: Plataforma E-commerce para Librería Online

## Descripción del Proyecto

**SKRIPTORIA Online Bookstore** es un proyecto de e-commerce completamente funcional desarrollado para gestionar la venta y el inventario de libros en línea. Esta plataforma soporta el ciclo completo de venta, desde la visualización del catálogo hasta la gestión de pedidos por parte del administrador.

Este proyecto fue desarrollado como una demostración de un sistema transaccional de múltiples niveles (front-end, back-end y base de datos) enfocándose en la seguridad y la integridad de los datos.

## 🛠️ Tecnologías Utilizadas

| Componente | Tecnología | Propósito |
| :--- | :--- | :--- |
| **Frontend** | HTML5, CSS3, JavaScript | Estructura, Estilo y Lógica de Interfaz. |
| **Backend** | PHP (Nativo) | Lógica de negocio, manejo de sesiones y procesamiento de formularios. |
| **Base de Datos** | MySQL / MariaDB | Almacenamiento de productos, usuarios y datos transaccionales. |
| **API de Pago** | Stripe.js & Stripe PHP SDK | Simulación de la pasarela de pago para transacciones seguras (Modo Test). |
| **Control de Versiones**| Git / GitHub | Gestión del desarrollo y colaboración. |

## ✨ Características Principales (Funcionalidades)

### Módulo Cliente (Catálogo Público)
* **Catálogo Dinámico:** Visualización de productos con datos cargados directamente desde la base de datos (BD).
* **Filtros Avanzados:** Búsqueda y filtrado por Género, Idioma, Rango de Precios y Calificación.
* **Autenticación:** Login y Registro de usuarios.
* **Reseñas:** Sistema para dejar calificaciones y comentarios por producto (restringido a una reseña por usuario).
* **Carrito de Compras y Lista de Deseos:** Gestión de ítems antes de la compra.
* **Checkout Funcional:** Integración con Stripe para simular transacciones seguras.

### Módulo Administrador (Dashboard)
* **Control Total de Inventario (CRUD):** Dar de Alta, Baja y Modificación a productos, incluyendo subida de imágenes.
* **Gestión de Pedidos:** Visualización de pedidos recientes y gestión del estado (`Pendiente`, `Procesando`, `Enviado`, `Entregado`).
* **Analíticas:** KPIs en tiempo real (Ingreso Total, Pedidos Pendientes, Stock Bajo) y reportes por rango de fechas.
* **Gestión de Usuarios:** Listado de clientes con estadísticas de gasto total.

## ⚙️ Instalación y Configuración Local

1.  **Clonar el Repositorio:**
    ```bash
    git clone [https://github.com/Stanley200605/Skriptoria.git](https://github.com/Stanley200605/Skriptoria.git)
    cd Skriptoria
    ```
2.  **Configurar XAMPP/Servidor:** Colocar la carpeta `Skriptoria` dentro de `C:\xampp\htdocs\`.
3.  **Configurar la BD (MySQL):**
    * Abrir phpMyAdmin (`http://localhost/phpmyadmin/`).
    * Ejecutar el script SQL completo de creación de estructura y datos (incluyendo las 9 tablas y el usuario Admin).
4.  **Configurar Stripe (Opcional):**
    * Descargar la librería PHP de Stripe (`stripe-php`).
    * Ajustar el archivo `includes/stripe_config.php` con las claves de prueba (`pk_test_...` y `sk_test_...`).
