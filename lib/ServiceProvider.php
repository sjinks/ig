<?php

namespace WildWolf;

use Monolog\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReCaptcha\ReCaptcha;
use Slim\Views\PhpRenderer;
use WildWolf\Cache\Memcached;
use WildWolf\FBR\Client;
use WildWolf\Logging\PHPMailerHandler;
use WildWolf\Logging\PrintRLineFormatter;
use WildWolf\Logging\Processor;

class ServiceProvider
{
    public function register(\ArrayAccess $container)
    {
        $this->registerServices($container);
        $this->registerHandlers($container);
    }

    private function registerServices(\ArrayAccess $container)
    {
        $settings = $container['settings'];

        if (!empty($settings['memcached.servers'])) {
            $container['cache'] = function(ContainerInterface $c) {
                $settings = $c->get('settings');
                return new Memcached([
                    'prefix'  => $settings['memcached.prefix'],
                    'servers' => $settings['memcached.servers'],
                    'options' => $settings['memcached.options']
                ]);
            };
        }

        $container['logger'] = function(ContainerInterface $c) {
            $settings = $c->get('settings');

            $mailer = new PHPMailer();
            $mailer->From     = $settings['bugreport.from'];
            $mailer->FromName = '';
            $mailer->Sender   = $settings['bugreport.sender'] ?? $settings['bugreport.from'];
            $mailer->CharSet  = 'utf-8';
            $mailer->Subject  = $settings['bugreport.subject'];
            $mailer->addAddress($settings['bugreport.to']);

            if ($settings['bugreport.host']) {
                $mailer->isSMTP();
                $mailer->Host       = $settings['bugreport.host'];
                $mailer->Port       = $settings['bugreport.port'] ?? 25;
                $mailer->SMTPSecure = $settings['bugreport.secure'] ?? '';
                $mailer->Helo       = $settings['bugreport.helo'] ?? '';

                $mailer->SMTPAuth   = $settings['bugreport.smtpauth'];
                $mailer->Username   = $settings['bugreport.username'];
                $mailer->Password   = $settings['bugreport.password'];
            }

            $logger  = new Logger('logger');
            $handler = new PHPMailerHandler($mailer);
            $handler->setFormatter(new PrintRLineFormatter());
            $logger->pushHandler($handler);
            $logger->pushProcessor(new Processor());

            return $logger;
        };

        $container['sepapi'] = function(ContainerInterface $c) {
            $settings = $c->get('settings');
            return new SepAPI($settings['sepapi.endpoint'], $settings['sepapi.token']);
        };

        $container['acckit'] = function(ContainerInterface $c) {
            $settings = $c->get('settings');
            return new AccountKit($settings['fb.app_id'], $settings['fb.ak.app_secret']);
        };

        $container['fbr'] = function(ContainerInterface $c) {
            $settings = $c->get('settings');
            $fbr = new Client($settings['fbr.url'], $settings['fbr.client_id']);
            if ($c->has('cache')) {
                $fbr->setCache(new Psr6CacheAdapter($c->get('cache')));
            }

            return $fbr;
        };

        $container['uploader'] = function(/** @scrutinizer ignore-unused */ ContainerInterface $c) {
            $uploader = new ImageUploader();
            $uploader->setMaxUploadSize(7340032);
            $uploader->setDirectoryDepth(3);
            $uploader->setCheckUniqueness(false);
            $uploader->setAcceptedTypes(['image/jpeg', 'image/png']);
            $uploader->setUploadDir(realpath(__DIR__ . '/../public/uploads'));
            return $uploader;
        };

        $container['recaptcha'] = function(ContainerInterface $c) {
            $settings = $c->get('settings');
            return new ReCaptcha($settings['recaptcha.secret']);
        };

        $container['view'] = function(/** @scrutinizer ignore-unused */ ContainerInterface $c) {
            $renderer = new PhpRenderer(__DIR__ . "/../templates/");
            $renderer->addAttribute('script_nonce', base64_encode(openssl_random_pseudo_bytes(16)));
            return $renderer;
        };
    }

    private function registerHandlers(\ArrayAccess $container)
    {
        $container['notFoundHandler'] = function (ContainerInterface $container) {
            return function (ServerRequestInterface $request, ResponseInterface $response) use ($container) {
                return $container->get('view')->render($response, '404.phtml')->withStatus(404);
            };
        };

        $container['notAllowedHandler'] = function (/** @scrutinizer ignore-unused */ ContainerInterface $container) {
            return function (ServerRequestInterface $request, ResponseInterface $response) {
                return $response->withHeader('Location', '/')->withStatus(302);
            };
        };

        $error_handler = function (ContainerInterface $container) {
            return function (ServerRequestInterface $request, ResponseInterface $response, \Throwable $error) use ($container) {
                /**
                 * @var Logger $logger
                 */
                $logger = $container->get('logger');
                $logger->error(
                    sprintf(
                        'Exception %s: "%s" at %s line %s',
                        get_class($error),
                        $error->getMessage(),
                        $error->getFile(),
                        $error->getLine()
                    ),
                    array('exception' => $error)
                );

                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/start?error=' . BaseController::ERROR_GENERAL_FAILURE)
                ;
            };
        };

        $container['phpErrorHandler'] = $error_handler;
        $container['errorHandler']    = $error_handler;
    }
}
