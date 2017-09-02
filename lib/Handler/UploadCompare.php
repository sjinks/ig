<?php

namespace WildWolf\Handler;

use WildWolf\FBR\Response\StartCompareAck;
use WildWolf\FBR\Response\UploadCompareAck;

class UploadCompare extends BaseHandler
{
    public static function normalizeFileEntry(array $entry)
    {
        $res   = [];
        $count = count($entry['name']);
        $keys  = array_keys($entry);

        for ($i=0; $i<$count; ++$i) {
            foreach ($keys as $key) {
                $res[$i][$key] = $entry[$key][$i];
            }
        }

        return $res;
    }

    protected function run()
    {
        $code = 0;

        $entry = $_FILES['photo1'] ?? [];
        $refs  = empty($_FILES['photo2']) ? null : self::normalizeFileEntry($_FILES['photo2']);

        if (!$entry || !$refs) {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }

        try {
            $entries   = array_merge([$entry], $refs);
            $resources = [];

            foreach ($entries as $x) {
                list($resource) = $this->app->uploader->validateFile($x);
                $mp             = $resource->megapixels();

                if ($mp > 5 || $mp < 0.08) {
                    throw new \Exception('', self::ERROR_BAD_RESOLUTION);
                }

                $resources[] = $resource;
            }

            $response = $this->app->fbr->startCompare($resources[0]->getWriter()->toString(), count($resources) - 1);
            if (!($response instanceof StartCompareAck)) {
                throw new \Exception('', self::ERROR_GENERAL_FAILURE);
            }

            $guid = $response->serverRequestId();
            for ($i=1; $i<count($resources); ++$i) {
                $response = $this->app->fbr->uploadRefPhoto($guid, $resources[$i]->getWriter()->toString(), $i, count($resources) - 1, $i);
                if (!($response instanceof UploadCompareAck)) {
                    throw new \Exception('', self::ERROR_GENERAL_FAILURE);
                }
            }

            for ($i=0; $i<count($resources); ++$i) {
                $file = $guid . '-' . $i . '.jpg';
                $this->app->uploader->saveAsJpeg($resources[$i], $file);
            }
        }
        catch (\Exception $e) {
            $code = $e->getCode();
        }
        finally {
            unlink($entry['tmp_name']);
            foreach ($refs as $x) {
                unlink($x['tmp_name']);
            }
        }

        if ($code) {
            $this->failure($code);
        }

        $this->app->redirect('/uploadcmp/' . $guid);
    }
}
