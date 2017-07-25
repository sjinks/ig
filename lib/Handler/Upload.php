<?php

namespace WildWolf\Handler;

use WildWolf\FBR\Response\UploadAck;
use WildWolf\ImageReader;

class Upload extends BaseHandler
{
    protected function run()
    {
        $code = 0;

        try {
            /**
             * @var ImageReader $resource
             */
            list($resource, $type) = $this->app->uploader->validateFile('photo');
            $writer = $resource->getWriter();
            $mp     = $resource->megapixels();

            if ($mp > 4) {
                $factor = 1/sqrt($mp/4);
                $writer->resize($factor);
            }

            $this->trackUpload();
            $response = $this->app->fbr->uploadFile($writer->toString());
            if (!($response instanceof UploadAck)) {
                $this->failure(self::ERROR_GENERAL_FAILURE);
            }

            $guid = $response->serverRequestId();
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

        $this->app->redirect('/result/' . $guid);
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
