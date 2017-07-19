<?php
require '../vendor/autoload.php';
require '../lib/Application.php';
require '../lib/CountryRestrictorMiddleware.php';
require '../lib/CloudflareIPRewriteMiddleware.php';

if (empty($_ENV['SLIM_MODE'])) {
    $_ENV['SLIM_MODE'] = 'production';
//     $_ENV['SLIM_MODE'] = 'development';
}

$app = new \Application();
$app->add(new \CountryRestrictorMiddleware());
$app->add(new \CloudflareIPRewriteMiddleware());

foreach (['development', 'production'] as $env) {
    $app->configureMode($env, function () use ($app, $env) { $app->config(require __DIR__ . '/../config/' . $env . '.php'); });
}

$app->init();
$app->run();
