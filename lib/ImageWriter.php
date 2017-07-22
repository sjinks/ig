<?php

namespace WildWolf;

class ImageWriter implements ImageWriterInterafce
{
    protected $im;
    protected $fmt;

    public function __construct($image)
    {
        $this->im = $image;
    }

    public function setOutputFormat(string $format)
    {
        $this->fmt = $format;
    }
}
