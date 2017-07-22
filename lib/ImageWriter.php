<?php

namespace WildWolf;

abstract class ImageWriter implements ImageWriterInterface
{
    protected $im;
    protected $fmt = 'JPEG';

    public function __construct($image)
    {
        $this->im = $image;
    }

    public function setOutputFormat(string $format)
    {
        $this->fmt = $format;
    }
}
