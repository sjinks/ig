<?php

namespace WildWolf;

/**
 * @property \IMagick $im
 *
 */
class ImageWriterIMagick extends ImageWriter implements ImageWriterInterface
{
    public function save($f)
    {
        $this->im->setImageFormat(strtoupper($this->fmt));
        $this->im->SetImageProperty('jpeg:sampling-factor', '4:2:2');
        $res = $this->im->writeImageFile($f);
        if (!$res) {
            throw new ImageUploaderException('', ImageUploader::ERROR_GENERAL_FAILURE);
        }
    }

    public function resize(float $factor)
    {
        $w     = $this->im->getImageWidth();
        $new_w = floor($w * $factor);
        $res   = $this->im->resizeImage((int)$new_w, 0, \IMagick::FILTER_LANCZOS, 1);
        if (false === $res) {
            throw new ImageUploaderException('', ImageUploader::ERROR_GENERAL_FAILURE);
        }
    }

    public function toString() : string
    {
        $this->im->setImageFormat(strtoupper($this->fmt));
        $this->im->SetImageProperty('jpeg:sampling-factor', '4:2:2');
        return $this->im->getImageBlob();
    }
}
