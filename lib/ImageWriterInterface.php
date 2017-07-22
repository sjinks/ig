<?php

namespace WildWolf;

interface ImageWriterInterafce
{
    public function setOutputFormat(string $format);
    public function save($f);
    public function resize(float $factor);
    public function toString() : string;
}

