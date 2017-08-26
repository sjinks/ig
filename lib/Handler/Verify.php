<?php

namespace WildWolf\Handler;

class Verify extends BaseHandler
{
    protected function run()
    {
        $code = $this->app->request()->get('code');
        $err  = self::ERROR_AUTH_FAILED;

        if ($code) {
            try {
                $err = $this->doSmsLogin($code);
            }
            catch (\Exception $e) {
                // Fall through
            }
        }

        if ($err) {
            $this->failure($err, true);
        }

        $this->app->redirect('/start');
    }

    private function doSmsLogin(string $code) : int
    {
        $d1 = $this->app->acckit->getAccessToken($code);
        $d2 = $this->app->acckit->validateAccessToken($d1->access_token);

        $response = $this->app->sepapi->smsLogin($d2->id, $d1->access_token, $d2->phone->number);
        if (is_numeric($response)) {
            $this->app->acckit->logout($d1->access_token);
            switch ($response) {
                case 403:
                    return self::ERROR_BANNED;

                case 509:
                    return self::ERROR_NO_CREDITS;

                default:
                    return self::ERROR_AUTH_FAILED;
            }
        }

        $_SESSION['user'] = new \WildWolf\User($response);
        return 0;
    }
}
