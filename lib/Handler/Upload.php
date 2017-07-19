<?php

namespace WildWolf\Handler;

class Upload extends BaseHandler
{
    protected function run()
    {
        $code = 0;

        try {
            list($resource, $type) = $this->app->uploader->validateFile('photo');

            $this->trackUpload();
            $response = $this->app->fbr->uploadFile($resource);
            if (!is_object($response) || $response->ans_type != \FBR\FBR::ANS_OK) {
                $this->failure(self::ERROR_GENERAL_FAILURE);
            }

            $guid = $response->data->reqID_serv;
            $file = $guid . '.jpg';

            $this->app->uploader->saveAsJpeg($resource, $file);
        }
        catch (\WildWolf\ImageUploaderException $e) {
            $code = $e->getCode();
        }
        finally {
            unlink($_FILES['photo']['tmp_name']);
        }

        if ($code) {
            $this->failure($code);
        }

        $this->app->redirect('/queue/' . $guid);
    }

    private function trackUpload()
    {
        $id = $_SESSION['user']->id;

        $response = $this->app->sepapi->trackUpload($id);
        $code     = self::ERROR_GENERAL_FAILURE;
        if (is_scalar($response)) {
            switch ($response) {
                case -1:
                    $code = self::ERROR_NO_CREDITS;
                    break;

                case -2:
                    $code = self::ERROR_BANNED;
                    break;

                default:
                    $_SESSION['user']->credits = $response;
                    return;
            }
        }

        $this->failure($code);
    }
}
