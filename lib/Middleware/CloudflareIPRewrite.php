<?php

namespace WildWolf\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CloudflareIPRewrite
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) : ResponseInterface
    {
        $cf = new \CloudFlare\IpRewrite();
        if ($cf->isCloudFlare()) {
            $server  = $request->getServerParams();
            $request = $request
                ->withAttribute('cf.original_ip', $cf->getOriginalIP())
                ->withAttribute('REMOTE_ADDR', $cf->getRewrittenIP())
                ->withAttribute('country_code', $server['HTTP_CF_IPCOUNTRY'] ?? '')
            ;
        }

        return $next($request, $response);
    }
}
