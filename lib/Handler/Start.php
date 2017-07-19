<?php

namespace WildWolf\Handler;

class Start extends BaseHandler
{
    protected function run()
    {
        $error          = $this->code ? self::getErrorByCode($this->code) : null;
        $skip_recaptcha = !empty($_SESSION['user']->whitelisted);

        $this->app->render('upload.phtml', ['error' => $error, 'skip_recaptcha' => $skip_recaptcha]);
    }
}
