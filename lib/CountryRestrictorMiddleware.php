<?php

class CountryRestrictorMiddleware extends \Slim\Middleware
{
    public function call()
    {
        $cc = filter_input(INPUT_SERVER, 'HTTP_CF_IPCOUNTRY');
        if (!empty($cc)) {
            if ('RU' == $cc) {
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
}