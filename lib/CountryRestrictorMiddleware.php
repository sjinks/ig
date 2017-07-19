<?php

class CountryRestrictorMiddleware extends \Slim\Middleware
{
    public function call()
    {
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $cc = $_SERVER['HTTP_CF_IPCOUNTRY'];

            if ('RU' == $cc) {
                $this->app->render('russia.phtml', [], 403);
                return;
            }
            else if ('UA' !== $cc) {
                $this->app->render('403.phtml', [], 403);
                return;
            }
        }

        $this->next->call();
    }
}