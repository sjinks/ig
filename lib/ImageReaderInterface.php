<?php

namespace WildWolf;

interface ImageReaderInterface
{
    public function width() : int;
    public function height() : int;
    public function load();
    public function getWriter() : ImageWriterInterafce;
}
