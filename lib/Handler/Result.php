<?php

namespace WildWolf\Handler;

class Result extends BaseHandler
{
    protected function run($guid)
    {
        if (isset($_SESSION['user']) && !$_SESSION['user']->whitelisted && !$_SESSION['user']->paid) {
            unset($_SESSION['user']);
        }

        $response = $this->app->fbr->checkUploadStatus($guid);
        if (!is_object($response)) {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
        elseif (\FBR\FBR::ANS_PROCESSING == $response->ans_type) {
            $this->app->redirect('/queue/' . $guid);
        }
        elseif (\FBR\FBR::ANS_COMPLETED == $response->ans_type) {
            $data = [
                'count' => $response->data->results_amount,
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
