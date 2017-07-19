<?php

namespace WildWolf\Handler;

class ValidateUser extends BaseHandler
{
    protected function run()
    {
        if (empty($_SESSION['user'])) {
            $this->app->redirect(self::maybeAppendErrorCode('/'));
        }
    }
}
