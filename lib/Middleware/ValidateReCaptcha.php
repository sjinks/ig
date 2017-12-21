<?php

namespace WildWolf\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use WildWolf\BaseController;

class ValidateReCaptcha
{
    /**
     * @var App
     */
    private $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) : ResponseInterface
    {
        /**
         * @var \WildWolf\User $user
         */
        $user = $_SESSION['user'] ?? null;

        if (!$user || !$user->isWhitelisted()) {
            $post      = (array)$request->getParsedBody();
            $recaptcha = $this->app->getContainer()->get('recaptcha');
            $resp      = $post['g-recaptcha-response'] ?? null;
            $ip        = $request->getAttribute('REMOTE_ADDR', null);
            $result    = $recaptcha->verify($resp, $ip);

            if (!$result->isSuccess()) {
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/start?error=' . BaseController::ERROR_RECAPTCHA)
                ;
            }
        }

        return $next($request, $response);
    }
}
