<?php

class CountryRestrictorMiddleware extends \Slim\Middleware
{
    public function call()
    {
        $cc = $this->getCountryCode();

        if (!empty($cc)) {
            if ('RU' === $cc) {
                $this->app->render('russia.phtml', [], 403);
                return;
            }

            if ('UA' !== $cc) {
                $this->app->render('403.phtml', [], 403);
                return;
            }
        }

        $this->next->call();
    }

    private function getCountryCode()
    {
        $app = $this->getApplication();
        $env = $app->environment();
        $cc  = $env['country_code'];

        if (empty($cc) && function_exists('geoip_record_by_name')) {
            $rec = (array)geoip_record_by_name($addr);
            $cc  = isset($rec['country_code']) ? $rec['country_code'] : null;
        }

        return $cc;
    }
}
