<?php

namespace WildWolf\Handler;

class Index extends BaseHandler
{
    private function logOutOrRedirect()
    {
        if (!empty($_SESSION['user'])) {
            /**
             * @var \WildWolf\User $user
             */
            $user = $_SESSION['user'];
            if (!$user->isPrivileged()) {
                $user->logout($this->app->acckit);
                unset($_SESSION['user']);
            }
            else {
                $this->app->redirect($this->maybeAppendErrorCode('/start'));
            }
        }
    }

    protected function run()
    {
        $this->logOutOrRedirect();

        $error = $this->code ? self::getErrorByCode($this->code) : null;
        $this->app->render('index.phtml', ['error' => $error, 'app_id' => $this->app->config('fb.app_id')]);
    }
}
