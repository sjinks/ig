<?php

namespace WildWolf\Logging;

class Processor
{
    public function __invoke(array $record)
    {
        if (!empty($_SERVER))  $record['extra']['_SERVER']  = $_SERVER;
        if (!empty($_GET))     $record['extra']['_GET']     = $_GET;
        if (!empty($_POST))    $record['extra']['_POST']    = $_POST;
        if (!empty($_SESSION)) $record['extra']['_SESSION'] = $_SESSION;

        return $record;
    }
}
