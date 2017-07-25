<?php
require '../vendor/autoload.php';

if (empty($_ENV['SLIM_MODE'])) {
    $_ENV['SLIM_MODE'] = 'production'; // or 'development'
}

$app = new \WildWolf\Application();
$app->add(new \WildWolf\CountryRestrictorMiddleware());
$app->add(new \WildWolf\CloudflareIPRewriteMiddleware());

foreach (['development', 'production'] as $env) {
    $app->configureMode($env, function () use ($app, $env) { $app->config(require __DIR__ . '/../config/' . $env . '.php'); });
}

$app->init();
$app->run();
