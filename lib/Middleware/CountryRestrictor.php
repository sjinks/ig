<?php

namespace WildWolf\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

class CountryRestrictor
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
        $container = $this->app->getContainer();
        $ip        = $request->getAttribute('REMOTE_ADDR', '');
        $cc        = $this->getCountryCode($request, $ip);
        $wl_ips    = (array)$container->get('settings')['whitelist.ips'];
        $wl_ccs    = (array)$container->get('settings')['whitelist.countries'];

        if (in_array($ip, $wl_ips) || empty($cc) || in_array($cc, $wl_ccs)) {
            return $next($request, $response);
        }

        /**
         * @var \Slim\Views\PhpRenderer $renderer
         */
        $renderer = $container->get('view');
        $template = ('RU' === $cc) ? 'russia.phtml' : '403.phtml';

        return $renderer->render($response, $template, []);
    }

    private function getCountryCode(ServerRequestInterface $request, string $ip) : string
    {
        $cc = $request->getAttribute('country_code', '');
        if (empty($cc) && function_exists('geoip_record_by_name')) {
            $rec = (array)geoip_record_by_name($ip);
            $cc  = $rec['country_code'] ?? '';
        }

        return $cc;
    }
}
