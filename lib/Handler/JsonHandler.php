<?php

namespace WildWolf\Handler;

abstract class JsonHandler extends BaseHandler
{
    protected function error()
    {
        $data = "[]";

        $this->sendCommonHeaders($data);
        $this->app->halt(500, $data);
    }

    protected function response($data)
    {
        $data = json_encode($data);

        $this->sendCommonHeaders($data);
        $this->app->halt(200, $data);
    }

    private function sendCommonHeaders($data)
    {
        $this->app->response->headers->set('Content-Type', 'application/json');
        $this->app->response->headers->set('Content-Length', strlen($data));
    }
}
