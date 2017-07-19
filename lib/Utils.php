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
}
