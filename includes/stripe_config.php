<?php
// stripe_config.php: Configuración de claves de Stripe (desde .env)

require_once __DIR__ . '/load_env.php';

// Read from .env: getenv() can be empty under Apache; $_ENV is set by Dotenv
$env = function ($key, $default = '') {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    return $_ENV[$key] ?? $default;
};

// Cargar la librería Stripe solo si existe (Composer o stripe-lib manual)
$autoload = __DIR__ . '/../vendor/autoload.php';
$stripe_lib = __DIR__ . '/../stripe-lib/init.php';
if (file_exists($autoload)) {
    require_once $autoload;
} elseif (file_exists($stripe_lib)) {
    require_once $stripe_lib;
}

$stripeSecretKey = $env('STRIPE_SECRET_KEY', '');
if (class_exists('Stripe\Stripe') && $stripeSecretKey !== '') {
    \Stripe\Stripe::setApiKey($stripeSecretKey);
}

if (!defined('STRIPE_PUBLISHABLE_KEY')) {
    define('STRIPE_PUBLISHABLE_KEY', $env('STRIPE_PUBLISHABLE_KEY', ''));
}
define('CURRENCY', $env('STRIPE_CURRENCY', 'mxn'));