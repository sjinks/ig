<?php

namespace WildWolf;

/**
 * @property \IMagick $im
 *
 */
class ImageWriterIMagick extends ImageWriter implements ImageWriterInterafce
{
    public function save($f)
    {
        $this->im->setImageFormat(strtoupper($this->fmt));
        $res = $this->im->writeImageFile($f);
        if (!$res) {
            throw new ImageUploaderException('', ImageUploader::ERROR_GENERAL_FAILURE);
        }
    }

    public function resize(float $factor)
    {
        $w = $this->im->getImageWidth();
        $h = $this->im->getImageHeight();

        $new_w = floor($w * $factor);
        $new_h = floor($h * $factor);

        $res   = $this->im->resizeImage($new_w, 0, \IMagick::FILTER_LANCZOS, 1);
        if (false === $res) {
            throw new ImageUploaderException('', ImageUploader::ERROR_GENERAL_FAILURE);
        }
    }

    public function toString() : string
    {
        $this->im->setImageFormat(strtoupper($this->fmt));
        return $this->im->getImageBlob();
    }
}
