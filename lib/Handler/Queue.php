<?php

namespace WildWolf\Handler;

use WildWolf\FBR\Response\InProgress;
use WildWolf\FBR\Response\ResultReady;

class Queue extends BaseHandler
{
    protected function run()
    {
        $guid     = func_get_arg(0);
        $response = $this->app->fbr->checkUploadStatus($guid);

        if ($response instanceof InProgress) {
            $this->app->render('wait.phtml');
        }
        elseif ($response instanceof ResultReady) {
            $this->app->redirect('/result/' . $guid);
        }
        else {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
    }
}
