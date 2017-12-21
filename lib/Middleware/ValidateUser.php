<?php

namespace WildWolf\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ValidateUser
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) : ResponseInterface
    {
        if (empty($_SESSION['user'])) {
            return $response
                ->withStatus(302)
                ->withHeader('Locaiton', '/')
            ;
        }

        return $next($request, $response);
    }
}
