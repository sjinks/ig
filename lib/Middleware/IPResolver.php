<?php

namespace WildWolf\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IPResolver
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) : ResponseInterface
    {
        $ip      = $this->getIP($request);
        $request = $request->withAttribute('REMOTE_ADDR', $ip);

        return $next($request, $response);
    }

    private function getIP(ServerRequestInterface $request) : string
    {
        $attrs  = $request->getAttributes();
        $server = $request->getServerParams();
        if (isset($attrs['REMOTE_ADDR'])) {
            return $attrs['REMOTE_ADDR'];
        }

        if (isset($server['REMOTE_ADDR'])) {
            return $server['REMOTE_ADDR'];
        }

        return (string)filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    }
}