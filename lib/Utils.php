<?php

namespace WildWolf;

abstract class Utils
{
    public static function safe_fopen(string $fname, string $mode, bool $use_include_path = false, $context = null)
    {
        $level = error_reporting();
        error_reporting($level & ~E_WARNING);
        if (null === $context) {
            $retval = fopen($fname, $mode, $use_include_path);
        }
        else {
            $retval = fopen($fname, $mode, $use_include_path, $context);
        }

        error_reporting($level);
        return $retval;
    }

    public static function splitFilename(string $filename) : array
    {
        $pos = strrpos($filename, '.');
        if (false === $pos) {
            $name = $filename;
            $ext  = '';
        }
        else {
            $name = substr($filename, 0, $pos);
            $ext  = substr($filename, $pos);
        }

        return [$name, $ext];
    }

    public static function maybePreprocessImage(string $name, float $maxmp)
    {
        try {
            $im = new \Imagick($name);
            $w  = $im->getimagewidth();
            $h  = $im->getimageheight();
            $sf = $im->getimageproperty('jpeg:sampling-factor');
            $q  = $im->getimagecompressionquality();
            $f  = strtolower($im->getimageformat());
            $il = $im->getinterlacescheme();

            $mp = $w * $h / 1000000.0;

            $flag =
                   ($mp > $maxmp && $maxmp > 0)     // More than $maxmp Mpix
                || ($f !== 'jpeg')                  // Not a JPEG
                || empty($sf)                       // Unknown sampling factor
                || (substr($sf, 0, 2) === '1x')     // Sampling factor is 4:4:x
                || ($il !== \Imagick::INTERLACE_NO) // FBR does not accept interlacing
            ;

            if ($flag) {
                $im->setimageformat('JPEG');
                if ($q) {
                    $im->setimagecompressionquality($q);
                }

                $im->setimageproperty('jpeg:sampling-factor', '4:2:0');
                $im->setinterlacescheme(\Imagick::INTERLACE_NO);

                if ($mp > $maxmp && $maxmp > 0) {
                    $factor = 1/sqrt($mp / $maxmp);
                    $res    = $im->resizeimage((int)($w * $factor), 0, \Imagick::FILTER_TRIANGLE, 1);
                    if (false === $res) {
                        return false;
                    }
                }

                return $im->writeimage($name);
            }

            return true;
        }
        catch (\ImagickException $e) {
            return false;
        }
    }
}
