<?php

namespace WildWolf\Handler;

class ValidateReCaptcha extends BaseHandler
{
    protected function run()
    {
        /**
         * @var \WildWolf\User $user
         */
        $user = $_SESSION['user'];

        if (!$user->isWhitelisted()) {
            $app       = $this->app;
            $env       = $app->environment();

            $recaptcha = $app->recaptcha;
            $response  = $app->request()->post('g-recaptcha-response', null);
            $ip        = $env['REMOTE_ADDR'] ?? null;
            $result    = $recaptcha->verify($response, $ip);

            if (!$result->isSuccess()) {
                $this->failure(self::ERROR_RECAPTCHA);
            }
        }
    }
}
