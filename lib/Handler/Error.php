<?php

namespace WildWolf\Handler;

class Error extends BaseHandler
{
    protected function run()
    {
        $e = func_get_arg(0);

        error_log($e->__toString());
        $this->failure(self::ERROR_GENERAL_FAILURE);
    }
}
