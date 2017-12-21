<?php

namespace WildWolf\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Session
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) : ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return $next($request, $response);
    }
}
