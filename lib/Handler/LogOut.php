<?php

namespace WildWolf\Handler;

class LogOut extends BaseHandler
{
    protected function run()
    {
        if (!empty($_SESSION['user'])) {
            $user = $_SESSION['user'];

            try {
                $this->app->acckit->logout($user->token);
            }
            catch (\Exception $e) {
                // Ignore exception
            }

            unset($_SESSION['user']);
        }

        $this->app->redirect('/');
    }
}
