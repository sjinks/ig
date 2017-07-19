<?php

namespace WildWolf;

abstract class Utils
{
    public static function safe_fopen(string $fname, string $mode, bool $use_include_path = false, resource $context = null)
    {
        $level = error_reporting();
        error_reporting($level & ~E_WARNING);
        if ($context === null) {
            $retval = fopen($fname, $mode, $use_include_path);
        }
        else {
            $retval = fopen($fname, $mode, $use_include_path, $context);
        }

        error_reporting($level);
        return $retval;
    }
}
