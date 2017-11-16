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
            $entry = $_FILES['photo'] ?? [];
            /**
             * @var ImageReader $resource
             */
            list($resource, $type) = $this->app->uploader->validateFile($entry);
            $writer = $resource->getWriter();
            $mp     = $resource->megapixels();

            if ($mp > 4) {
                $factor = 1/sqrt($mp/4);
                $writer->resize($factor);
            }

            $response = $this->app->fbr->uploadPhotoForSearch($writer->toString());
            if (!($response instanceof UploadAck)) {
                $this->failure(self::ERROR_GENERAL_FAILURE);
            }

            $guid = $response->serverRequestId();
            $file = $guid . '.jpg';
            $this->trackUpload($guid);

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

    private function trackUpload(string $guid)
    {
        /**
         * @var \WildWolf\User $user
         */
        $user = $_SESSION['user'];
        $id   = $user->id();
        $env  = $this->app->environment();
        $ip   = $env['REMOTE_ADDR'];

        $response = $this->app->sepapi->trackUpload($id, $guid, $ip, time());
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
                    // $_SESSION will be updated as well
                    $user->setCredits($response);
                    return;
            }
        }

        $this->failure($code);
    }
}
