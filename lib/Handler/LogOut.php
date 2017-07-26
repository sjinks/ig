<?php

namespace WildWolf\Handler;

class LogOut extends BaseHandler
{
    protected function run()
    {
        if (!empty($_SESSION['user'])) {
            /**
             * @var \WildWolf\User $user
             */
            $user = $_SESSION['user'];
            $user->logout($this->app->acckit);
            unset($_SESSION['user']);
        }

        $this->app->redirect('/');
    }
}
