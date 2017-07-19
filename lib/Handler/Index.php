<?php

namespace WildWolf\Handler;

class Index extends BaseHandler
{
    private function logOutOrRedirect()
    {
        if (!empty($_SESSION['user'])) {
            $user  = $_SESSION['user'];
            if (!$user->whitelisted && !$user->paid) {
                try {
                    $this->app->acckit->logout($user->token);
                }
                catch (\Exception $e) {
                    // Ignore exception
                }

                unset($_SESSION['user']);
            }
            else {
                $this->app->redirect(self::maybeAppendErrorCode('/start'));
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
