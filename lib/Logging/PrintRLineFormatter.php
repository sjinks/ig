<?php

namespace WildWolf\Logging;

use Monolog\Formatter\LineFormatter;

class PrintRLineFormatter extends LineFormatter
{
    public function __construct()
    {
        $args = func_get_args();
        parent::__construct(...$args);
        $this->allowInlineLineBreaks(true);
        $this->includeStacktraces(true);
    }

    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string)$data;
        }

        return print_r($data, 1);
    }
}
