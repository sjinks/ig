<?php

use WildWolf\Handler\Index;

/**
 * @property \WildWolf\FBR\Client $fbr
 * @property \WildWolf\SepAPI $sepapi
 * @property \WildWolf\AccountKit $acckit
 * @property \WildWolf\ImageUploader $uploader
 * @property \WildWolf\Cache\Memcached $cache
 * @property \ReCaptcha\ReCaptcha $recaptcha
 */
final class Application extends \Slim\Slim
{
    public function init()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->setUpRouting();
        $this->setUpDI();

        $this->error(new \WildWolf\Handler\Error($this));
        $this->notFound(new \WildWolf\Handler\NotFound($this));
    }

    private function setUpRouting()
    {
        \Slim\Route::setDefaultConditions([
            'guid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
            'n'    => '[1-9][0-9]*'
        ]);

        $mw_validate_user = new \WildWolf\Handler\ValidateUser($this);

        $this->get('/',              new \WildWolf\Handler\Index($this));
        $this->post('/checkphone',   new \WildWolf\Handler\CheckPhone($this));
        $this->get('/verify',        new \WildWolf\Handler\Verify($this));
        $this->get('/logout',        new \WildWolf\Handler\LogOut($this));
        $this->get('/start',         $mw_validate_user, new \WildWolf\Handler\Start($this));
        $this->post('/upload',       $mw_validate_user, new \WildWolf\Handler\ValidateReCaptcha($this), new \WildWolf\Handler\Upload($this));
        $this->get('/result/:guid',  new \WildWolf\Handler\Result($this));
        $this->get('/stats/:guid',   new \WildWolf\Handler\Stats($this));
        $this->get('/face/:guid/:n', new \WildWolf\Handler\Face($this));
    }

    private function setUpDI()
    {
        $app = $this;

        $app->container->singleton('cache', function() {
            return new \WildWolf\Cache\Memcached([
                'prefix'  => 'separator.',
                'servers' => [['127.0.0.1', 11211, 1]],
                'options' => [
                    \Memcached::OPT_BINARY_PROTOCOL => true,
                ]
            ]);
        });

        $this->container->singleton('sepapi', function() use ($app) {
            return new \WildWolf\SepAPI($app->config('api.endpoint'), $app->config('api.token'));
        });

        $this->container->singleton('acckit', function() use ($app) {
            return new \WildWolf\AccountKit($app->config('fb.app_id'), $app->config('fb.ak.app_secret'));
        });

        $this->container->singleton('fbr', function() use ($app) {
            $fbr = new \WildWolf\FBR\Client($app->config('fbr.url'), $app->config('fbr.client_id'));
            $fbr->setCache(new \WildWolf\Psr6CacheAdapter($app->cache));
            return $fbr;
        });

        $this->container->singleton('uploader', function() {
            $uploader = new \WildWolf\ImageUploader();
            $uploader->setMaxUploadSize(7340032);
            $uploader->setDirectoryDepth(3);
            $uploader->setCheckUniqueness(false);
            $uploader->setAcceptedTypes(['image/jpeg', 'image/png']);
            $uploader->setUploadDir(realpath(__DIR__ . '/../public/uploads'));
            return $uploader;
        });

        $this->container->singleton('recaptcha', function() use ($app) {
            return new \ReCaptcha\ReCaptcha($app->config('recaptcha.secret'));
        });
    }
}
