<?php

namespace WildWolf\Handler;

class Queue extends BaseHandler
{
    protected function run($guid)
    {
        $response = $this->app->fbr->checkUploadStatus($guid);

        if (!is_object($response)) {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
        elseif (\FBR\FBR::ANS_PROCESSING == $response->ans_type) {
            $this->app->render('wait.phtml');
        }
        elseif (\FBR\FBR::ANS_COMPLETED == $response->ans_type) {
            $this->app->redirect('/result/' . $guid);
        }
        else {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
    }
}
