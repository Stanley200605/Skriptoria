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
 * Valores leídos desde .env (véase .env.example).
 */

require_once __DIR__ . '/load_env.php';

// Read from .env: getenv() can be empty under Apache; $_ENV is set by Dotenv
$env = function ($key, $default = '') {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    return $_ENV[$key] ?? $default;
};

// ----------------------------------------------------
// 1. Configuración de la Base de Datos
// ----------------------------------------------------

define('DB_SERVER',   $env('DB_SERVER',   'localhost'));
define('DB_USERNAME', $env('DB_USERNAME', 'root'));
define('DB_PASSWORD', $env('DB_PASSWORD', ''));
define('DB_NAME',     $env('DB_NAME',     'skriptoria_db'));

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