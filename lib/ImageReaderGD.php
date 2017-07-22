<?php

namespace WildWolf;

class ImageReaderGD extends ImageReader implements ImageReaderInterface
{
    private $im;

    public function load()
    {
        static $lut = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png'  => 'imagecreatefrompng',
            'image/gif'  => 'imagecreatefromgif',
        ];

        $im = isset($lut[$this->type]) ? ${$lut[$this->type]}($name) : null;
        if (!is_resource($im)) {
            throw new ImageUploaderException('', ImageUploader::ERROR_FILE_NOT_SUPPORTED);
        }

        $this->im = $im;
    }

    public function width() : int
    {
        return imagesx($this->im);
    }

    public function height() : int
    {
        return imagesy($this->im);
    }

    public function getWriter() : ImageWriterInterafce
    {
        return new ImageWriterGD($this->im);
    }
}
