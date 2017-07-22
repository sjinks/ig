<?php

namespace WildWolf\Handler;

class CheckPhone extends JsonHandler
{
    protected function run()
    {
        $phone = filter_input(INPUT_POST, 'p', FILTER_DEFAULT);
        $body  = 0;

        try {
            $data = $this->app->sepapi->validatePhone($phone);

            if (is_object($data)) { // Only for whitelisted users
                if ($data->credits > 0) {
                    $this->app->acckit->validateAccessToken($data->token);
                    $body = 200;
                    $_SESSION['user'] = $data;
                }
            }
            elseif (is_scalar($data)) {
                $body = $data;
            }
        }
        catch (\Exception $e) {
            error_log($e);
        }

        $this->response($body);
    }
}
