<?php

namespace WildWolf;

class ImageReaderIMagick extends ImageReader implements ImageReaderInterface
{
    private $im;

    public function load()
    {
        try {
            $this->im = new \Imagick($this->file);
        }
        catch (\ImagickException $e) {
            throw new ImageUploaderException($e->getMessage(), ImageUploader::ERROR_FILE_NOT_SUPPORTED);
        }
    }

    public function width() : int
    {
        return $this->im->getImageWidth();
    }

    public function height() : int
    {
        return $this->im->getImageHeight();
    }

    public function getWriter() : ImageWriterInterface
    {
        return new ImageWriterIMagick($this->im);
    }
}
