<?php
/**
 * load_env.php
 * Loads .env from the project root into getenv() / $_ENV (once per request).
 * Include this before db_connection or stripe_config when using .env.
 */

if (defined('SKRIPTORIA_ENV_LOADED')) {
    return;
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

define('SKRIPTORIA_ENV_LOADED', true);
