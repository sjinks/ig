<?php

use Slim\App;
use WildWolf\CompareController;
use WildWolf\SearchController;
use WildWolf\ServiceProvider;
use WildWolf\UserController;
use WildWolf\Middleware\CloudflareIPRewrite;
use WildWolf\Middleware\CountryRestrictor;
use WildWolf\Middleware\IPResolver;
use WildWolf\Middleware\Session;
use WildWolf\Middleware\ValidateReCaptcha;
use WildWolf\Middleware\ValidateUser;

require '../vendor/autoload.php';

function exception_error_handler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) {
        return;
    }

    error_log("$message $file $line");
    throw new \ErrorException($message, 0, $severity, $file, $line);
}

set_error_handler("exception_error_handler");

$config    = require __DIR__ . '/../config/config.php';
$app       = new App(['settings' => $config]);
$container = $app->getContainer();
$provider  = new ServiceProvider();
$provider->register(/** @scrutinizer ignore-type */ $container);

$app
    ->add(new Session())
    ->add(new CountryRestrictor($app))
    ->add(new IPResolver())
    ->add(new CloudflareIPRewrite())
;

$validate_user      = new ValidateUser();
$validate_recaptcha = new ValidateReCaptcha($app);

$guid = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
$n    = '[1-9][0-9]*';

$app->get('/',                             UserController::class . ':index');
$app->post('/checkphone',                  UserController::class . ':checkPhone');
$app->get('/verify',                       UserController::class . ':verify');
$app->get('/logout',                       UserController::class . ':logout');

$app->get('/start',                        SearchController::class . ':start')->add($validate_user);
$app->post('/upload',                      SearchController::class . ':upload')->add($validate_recaptcha)->add($validate_user);
$app->get("/result/{guid:{$guid}}",        SearchController::class . ':result');
$app->get("/face/{guid:{$guid}}/{n:{$n}}", SearchController::class . ':face');

$app->post('/uploadcmp',                   CompareController::class . ':upload')->add($validate_recaptcha)->add($validate_user);
$app->get("/cresult/{guid:{$guid}}",       CompareController::class . ':result');

$app->run();
