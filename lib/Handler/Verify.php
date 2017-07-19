<?php

namespace WildWolf\Handler;

class Verify extends BaseHandler
{
    protected function run()
    {
        $code = filter_input(INPUT_GET, 'code', FILTER_DEFAULT);
        $err  = self::ERROR_AUTH_FAILED;

        if ($code) {
            try {
                $d1 = $this->app->acckit->getAccessToken($code);
                $d2 = $this->app->acckit->validateAccessToken($d1->access_token);

                $response = $this->app->sepapi->smsLogin($d2->id, $d1->access_token, $d2->phone->number);
                if (is_numeric($response)) {
                    $this->app->acckit->logout($d1->access_token);
                    switch ($response) {
                        case 403:
                            $err = self::ERROR_BANNED;
                            break;

                        case 509:
                            $err = self::ERROR_NO_CREDITS;
                            break;
                    }
                }
                else {
                    $_SESSION['user'] = $response;
                    $err              = 0;
                }
            }
            catch (\Exception $e) {
                // Fall through
            }
        }

        if ($err) {
            $this->failure($err, true);
        }
        else {
            $this->app->redirect('/start');
        }
    }
}
