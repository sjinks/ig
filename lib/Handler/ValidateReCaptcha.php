<?php

namespace WildWolf\Handler;

class ValidateReCaptcha extends BaseHandler
{
    protected function run()
    {
        if (!empty($_SESSION['user']->whitelisted)) {
            return;
        }

        $recaptcha = new \ReCaptcha\ReCaptcha($this->app->config('recaptcha.secret'));
        $response  = filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_DEFAULT);
        $ip        = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_DEFAULT);
        $result    = $recaptcha->verify($response, $ip);

        if (!$result->isSuccess()) {
            $this->failure(self::ERROR_RECAPTCHA);
        }
    }
}