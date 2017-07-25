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
            $this->processResult($guid, $response);
        }
        else {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
    }

    private function processResult(string $guid, ResultReady $response)
    {
        if (isset($_SESSION['user']) && !$_SESSION['user']->whitelisted && !$_SESSION['user']->paid) {
            unset($_SESSION['user']);
        }

        $stats = $this->app->fbr->getUploadStats($guid);
        if ($stats instanceof \WildWolf\FBR\Response\Stats) {
            $data = [
                'count' => $response->resultsAmount(),
                'stats' => $stats,
                'guid'  => $guid,
                'url'   => '/uploads/' . $this->app->uploader->getTargetName($guid . '.jpg'),
            ];

            $this->app->render('results.phtml', $data);
            return;
        }

        $this->failure(self::ERROR_GENERAL_FAILURE);
    }
}
