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
        /**
         * @var \WildWolf\User $user
         */
        $user = $_SESSION['user'] ?? null;
        if ($user && !$user->isPrivileged()) {
            $user->logout($this->app->acckit);
            unset($_SESSION['user']);
        }

        $stats = $this->app->fbr->getUploadStats($guid);
        if ($stats instanceof \WildWolf\FBR\Response\Stats) {
            $iframe = filter_input(INPUT_GET, 'iframe', FILTER_SANITIZE_NUMBER_INT);
            $data   = [
                'count'  => $response->resultsAmount(),
                'stats'  => $stats,
                'guid'   => $guid,
                'url'    => '/uploads/' . $this->app->uploader->getTargetName($guid . '.jpg'),
                'iframe' => $iframe,
                'title'  => 'Результати розпізнавання',
            ];

            $this->app->render('results.phtml', $data);
            return;
        }

        $this->failure(self::ERROR_GENERAL_FAILURE);
    }
}
