<?php
/**
 * db_connection.php
 * Archivo de conexión a la base de datos MySQL para SKRIPTORIA.
 *
 * @autor: [Tu Nombre]
 * @fecha: [Fecha Actual]
 * @version: 1.0
 *
 * NOTA: Esta librería de conexión utiliza mysqli y se recomienda incluirla (require_once)
 * al inicio de cualquier script PHP que necesite interactuar con la BD.
 */

// ----------------------------------------------------
// 1. Configuración de la Base de Datos
// ----------------------------------------------------

// Define las constantes de conexión. Es una buena práctica usar constantes (DEFINE).
define('DB_SERVER', 'localhost'); // Servidor de la BD (comúnmente localhost en desarrollo)
define('DB_USERNAME', 'root');    // Usuario de la BD (cambiar en producción)
define('DB_PASSWORD', '');        // Contraseña de la BD (cambiar en producción)
define('DB_NAME', 'skriptoria_db'); // Nombre de la BD que creamos con SQL

// ----------------------------------------------------
// 2. Establecer la Conexión
// ----------------------------------------------------

// Crea una nueva conexión a MySQL
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// ----------------------------------------------------
// 3. Manejo de Errores de Conexión
// ----------------------------------------------------

// Verifica si la conexión falló
if ($conn->connect_error) {
    // Si falla, termina la ejecución del script y muestra el error.
    die("❌ Error de Conexión a MySQL: " . $conn->connect_error);
}

// ----------------------------------------------------
// 4. Configuración de Codificación (Opcional, pero recomendado)
// ----------------------------------------------------

// Establece la codificación a UTF-8 para evitar problemas con tildes, ñ y caracteres especiales.
$conn->set_charset("utf8mb4");

// Opcionalmente, puedes dejar un mensaje de éxito para verificar en pruebas (luego se debe eliminar).
// echo "✅ Conexión exitosa a la base de datos: " . DB_NAME;

// El objeto $conn ahora contiene la conexión activa a la BD.
?>