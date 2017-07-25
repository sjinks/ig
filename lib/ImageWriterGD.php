<?php

namespace WildWolf;

class ImageWriterGD extends ImageWriter implements ImageWriterInterface
{
    private function getSaveFunction() : string
    {
        static $lut = [
            'jpeg' => 'imagejpeg',
            'png'  => 'imagepng',
            'gif'  => 'imagegif',
        ];

        $fmt  = strtolower($this->fmt);
        $func = $lut[$fmt] ?? null;

        if (!$func) {
            throw new ImageUploaderException('', ImageUploader::ERROR_GENERAL_FAILURE);
        }

        return $func;
    }

    public function save($f)
    {
        $func = $this->getSaveFunction();
        if (!$func($this->im, $f)) {
            throw new ImageUploaderException('', ImageUploader::ERROR_GENERAL_FAILURE);
        }
    }

    public function resize(float $factor)
    {
        $w = imagesx($this->im);
        $h = imagesy($this->im);

        $new_w = floor($w * $factor);
        $new_h = floor($h * $factor);

        $res   = imagescale($this->im, $new_w, $new_h);
        if (false === $res) {
            throw new ImageUploaderException('', ImageUploader::ERROR_GENERAL_FAILURE);
        }

        $this->im = $res;
    }

    public function toString() : string
    {
        $func = $this->getSaveFunction();
        ob_start();
        $func($this->im);
        return ob_get_clean();
    }
}