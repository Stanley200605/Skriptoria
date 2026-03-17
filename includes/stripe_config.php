<?php
// stripe_config.php: Configuración de claves de Stripe (desde .env)

require_once __DIR__ . '/load_env.php';

// Cargar la librería Stripe solo si existe (Composer o stripe-lib manual)
$autoload = __DIR__ . '/../vendor/autoload.php';
$stripe_lib = __DIR__ . '/../stripe-lib/init.php';
if (file_exists($autoload)) {
    require_once $autoload;
} elseif (file_exists($stripe_lib)) {
    require_once $stripe_lib;
}

$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: '';
if (class_exists('Stripe\Stripe') && $stripeSecretKey !== '') {
    \Stripe\Stripe::setApiKey($stripeSecretKey);
}

if (!defined('STRIPE_PUBLISHABLE_KEY')) {
    define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
}
define('CURRENCY', getenv('STRIPE_CURRENCY') ?: 'mxn');