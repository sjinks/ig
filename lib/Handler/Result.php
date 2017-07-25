<?php

namespace WildWolf\Handler;

use WildWolf\FBR\Response\InProgress;
use WildWolf\FBR\Response\ResultReady;

class Result extends BaseHandler
{
    protected function run()
    {
        $guid     = func_get_arg(0);
        $response = $this->app->fbr->checkUploadStatus($guid);

        if ($response instanceof InProgress) {
            $this->app->render('wait.phtml');
        }
        elseif ($response instanceof ResultReady) {
            if (isset($_SESSION['user']) && !$_SESSION['user']->whitelisted && !$_SESSION['user']->paid) {
                unset($_SESSION['user']);
            }

            $data = [
                'count' => $response->resultsAmount(),
                'guid'  => $guid,
                'url'   => '/uploads/' . $this->app->uploader->getTargetName($guid . '.jpg'),
            ];

            $this->app->render('results.phtml', $data);
        }
        else {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
    }
}
