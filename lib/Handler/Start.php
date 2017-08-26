<?php

namespace WildWolf\Handler;

class Start extends BaseHandler
{
    protected function run()
    {
        /**
         * @var \WildWolf\User $user
         */
        $user           = $_SESSION['user'];
        $error          = $this->code ? self::getErrorByCode($this->code) : null;
        $skip_recaptcha = $user->isWhitelisted();

        $this->app->render('upload.phtml', ['error' => $error, 'skip_recaptcha' => $skip_recaptcha, 'title' => 'Завантажити світлину']);
    }
}
