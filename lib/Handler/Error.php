<?php

namespace WildWolf\Handler;

class Error extends BaseHandler
{
    protected function run(\Exception $e)
    {
        if ($e instanceof \ErrorException) {
            error_log($e->__toString());
        }

        $this->failure(self::ERROR_GENERAL_FAILURE);
    }
}
