<?php

namespace WildWolf;

class CountryRestrictorMiddleware extends \Slim\Middleware
{
    public function call()
    {
        $app    = $this->getApplication();
        $ip     = $this->getIp();
        $cc     = $this->getCountryCode();

        $wl_ips = (array)$app->config('whitelist.ips');
        $wl_ccs = (array)$app->config('whitelist.countries');

        if (in_array($ip, $wl_ips) || empty($cc) || in_array($cc, $wl_ccs)) {
            $this->next->call();
            return;
        }

        if ('RU' === $cc) {
            $this->app->render('russia.phtml', [], 403);
        }
        else {
            $this->app->render('403.phtml', [], 403);
        }
    }

    private function getCountryCode()
    {
        $app = $this->getApplication();
        $env = $app->environment();
        $cc  = $env['country_code'] ?? '';

        if (empty($cc) && function_exists('geoip_record_by_name')) {
            $rec = (array)geoip_record_by_name($app->request->getIp());
            $cc  = $rec['country_code'] ?? null;
        }

        return $cc;
    }

    private function getIp() : string
    {
        $app = $this->getApplication();
        $env = $app->environment();
        return $env['REMOTE_ADDR'] ?? '';
    }
}
