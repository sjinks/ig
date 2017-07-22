<?php

namespace WildWolf;

abstract class ImageReader implements ImageReaderInterface
{
    protected $file;
    protected $type;

    public function __construct(string $fname, string $type)
    {
        $this->file = $fname;
        $this->type = $type;
    }

    public static function getReader(string $fname, string $type)
    {
        if (class_exists('IMagick')) {
            return new ImageReaderIMagick($fname, $type);
        }

        if (extension_loaded('gd')) {
            return new ImageReaderGD($fname, $type);
        }

        throw new ImageUploaderException('', ImageLoader::ERROR_FILE_NOT_SUPPORTED);
    }

    public function megapixels() : float
    {
        return $this->width() * $this->height() / 1000000.0;
    }
}
